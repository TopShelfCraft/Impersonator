<?php
namespace TopShelfCraft\Impersonator\controllers;

use Craft;
use craft\controllers\UsersController;
use craft\elements\User;
use craft\helpers\ConfigHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Session;
use craft\web\Controller;
use TopShelfCraft\Impersonator\Impersonator;
use yii\web\BadRequestHttpException;
use yii\web\Cookie;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

class ImpersonatorController extends Controller
{

	public $enableCsrfValidation = false;

    protected int|bool|array $allowAnonymous = [
		'unimpersonate',
	];

	/**
	 * Starts an impersonation session, using the username, email, or ID of the user to be impersonated.
	 *
	 * @throws BadRequestHttpException if no user can be found using the given criterion.
	 * @throws ForbiddenHttpException if the user doesn't have permission to perform the impersonation.
	 */
	public function actionImpersonate(): ?Response
	{

		$this->requirePostRequest();

		$userSession = Craft::$app->getUser();
		$settings = Impersonator::getInstance()->getSettings();

		/*
		 * Identify the user to be impersonated
		 */

		$criterion = $this->request->getRequiredBodyParam($settings->accountParamName);

		$impersonatee = Craft::$app->users->getUserByUsernameOrEmail($criterion)
			?? Craft::$app->users->getUserById($criterion);

		if (!$impersonatee)
		{
			// TODO: Are we worried about revealing that the user doesn't exist, as a disclosure vector?
			throw new BadRequestHttpException("Invalid user: $criterion");
		}

		/*
		 * Make sure they're allowed to impersonate this user
		 */
		$this->_enforceImpersonatePermission($impersonatee);

		/*
		 * Save the original (impersonator) user ID to the session now so User::findIdentity()
		 * knows not to worry if the impersonated User isn't active yet,
		 * and so we can verify the impersonator's identity before restoring it later.
		 */
		Session::set(User::IMPERSONATE_KEY, $userSession->getId());

		/*
		 * Attempt to copy the current impersonator's Identity cookie,
		 * so we restore it later to back them out of impersonation mode.
		 */

		$identityCookie = $this->request->getCookies()->get(Craft::$app->user->identityCookie['name']);
		$impersonatorCookieName = Craft::$app->user->identityCookie['name'].'_impersonator';

		if ($identityCookie)
		{
			$this->_addIdentityCookie([
				'name' => $impersonatorCookieName,
				'value' => $identityCookie->value,
				'expire' => $identityCookie->expire,
			]);
		}

		/*
		 * Proceed with the impersonation...
		 */

		$duration = ConfigHelper::durationInSeconds($settings->impersonatorSessionDuration);
		if (!$userSession->loginByUserId($impersonatee->id, $duration))
		{
			Session::remove(User::IMPERSONATE_KEY);
			Craft::$app->getResponse()->getCookies()->remove($impersonatorCookieName);
			$this->setFailFlash(Craft::t('app', 'There was a problem impersonating this user.'));
			Craft::error($userSession->getIdentity()->username . ' tried to impersonate userId: ' . $impersonatee->id . ' but something went wrong.', __METHOD__);
			return null;
		}

		return $this->_handleSuccessfulLogin();

	}

	/**
	 * Ends the current impersonation session, and attempts to restore the impersonator's original identity.
	 */
	public function actionUnimpersonate(): Response
	{

		$session = Craft::$app->getSession();
		$userSession = Craft::$app->getUser();

		$impersonatorId = Impersonator::getInstance()->getImpersonatorId();
		$impersonatorCookie = $this->request->getCookies()->get($userSession->identityCookie['name'].'_impersonator');

		// Log-out the impersonated User and clean up any active impersonation session.
		$userSession->logout(false);
		$session->remove(User::IMPERSONATE_KEY);

		// If we have an Impersonator Identity present in the cookie collection...
		if ($impersonatorCookie)
		{

			// Make sure we have an Impersonator ID, which means we were coming from a known impersonation session.
			if ($impersonatorId)
			{
				// Try to restore the original Identity.
				$this->_addIdentityCookie([
					'value' => $impersonatorCookie->value,
					'expire' => $impersonatorCookie->expire,
				]);
			}

			// And either way, clear out the Impersonator Identity cookie.
			Craft::$app->getResponse()->getCookies()->remove($impersonatorCookie);

		}

		return $this->_handleSuccessfulLogin();

	}

	private function _addIdentityCookie(array $config): void
	{
		$config = array_merge(Craft::$app->getUser()->identityCookie, $config);
		Craft::$app->getResponse()->getCookies()->add(new Cookie($config));
	}

	/**
	 * Ensures that the current user has permission to impersonate the given user.
	 *
	 * @see UsersController::_enforceImpersonatePermission()
	 *
	 * @param User $user
	 * @throws ForbiddenHttpException
	 */
	private function _enforceImpersonatePermission(User $user): void
	{
		if (!Craft::$app->getUsers()->canImpersonate(static::currentUser(), $user))
		{
			throw new ForbiddenHttpException('You do not have sufficient permissions to impersonate this user');
		}
	}

	/**
	 * Redirects the user after a successful request.
	 *
	 * @see UsersController::_handleSuccessfulLogin()
	 */
	private function _handleSuccessfulLogin(): Response
	{

		// Get the return URL
		$userSession = Craft::$app->getUser();
		$returnUrl = $userSession->getReturnUrl();

		// Clear it out
		$userSession->removeReturnUrl();

		// If this was an Ajax request, just return success:true
		if ($this->request->getAcceptsJson())
		{
			$return = [
				'returnUrl' => $returnUrl,
			];
			if (Craft::$app->getConfig()->getGeneral()->enableCsrfProtection)
			{
				$return['csrfTokenValue'] = $this->request->getCsrfToken();
			}
			return $this->asSuccess(data: $return);
		}

		return $this->redirectToPostedUrl($userSession->getIdentity(), $returnUrl);

	}

}
