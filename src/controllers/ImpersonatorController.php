<?php
namespace topshelfcraft\impersonator\controllers;

use Craft;
use craft\elements\User;
use craft\web\Controller;
use topshelfcraft\impersonator\Impersonator;
use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;
use yii\web\Cookie;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

/**
 * @author    Top Shelf Craft (Michael Rog)
 * @package   Impersonator
 * @since     1.0.0
 */
class ImpersonatorController extends Controller
{

    // Protected Properties
    // =========================================================================

    /**
     * @var    bool|array Controller actions that can be used anonymously
	 *
     * @access protected
     */
    protected $allowAnonymous = ['unimpersonate'];

    public $enableCsrfValidation = false;


    // Public Methods
    // =========================================================================

	/**
	 * @return Response|null
	 *
	 * @throws BadRequestHttpException
	 * @throws ForbiddenHttpException
	 * @throws InvalidConfigException
	 */
	public function actionImpersonate()
	{

		$this->requirePostRequest();
		$this->requireLogin();

		$request = Craft::$app->getRequest();
		$session = Craft::$app->getSession();
		$users = Craft::$app->getUsers();
		$userSession = Craft::$app->getUser();

		// Try to identify the User to be impersonated

		$criterion = $request->getBodyParam(Impersonator::$plugin->getSettings()->accountParamName);

		$impersonatee = $users->getUserByUsernameOrEmail($criterion);
		if (!$impersonatee)
		{
			$impersonatee = $users->getUserById($criterion);
		}
		if (!$impersonatee)
		{
			throw new BadRequestHttpException("Cannot find a User by the given criteria.");
		}

		// Make sure you're allowed to impersonate this user

		$canImpersonate = $users->canImpersonate($userSession->getIdentity(), $impersonatee);
		if (!$canImpersonate) {
			throw new ForbiddenHttpException(Craft::t('app', '\'You do not have sufficient permissions to impersonate this user'));
		}

		/*
		 * Save the original (impersonator) user ID to the session now so User::findIdentity()
		 * knows not to worry if the impersonated User isn't active yet,
		 * and so we can verify the impersonator's identity before restoring it later.
		 */
		$session->set(User::IMPERSONATE_KEY, $userSession->getId());

		// Attempt to copy the impersonator's Identity, so we can back them out of impersonation mode later.

		$identityCookie = $request->getCookies()->get(Craft::$app->user->identityCookie['name']);
		$impersonatorCookieName = Craft::$app->user->identityCookie['name'].'_impersonator';

		if ($identityCookie)
		{
			$impersonatorCookie = Craft::createObject(array_merge($userSession->identityCookie, [
				'name' => $impersonatorCookieName,
				'class' => Cookie::class,
				'value' => $identityCookie->value,
				'expire' => $identityCookie->expire,
			]));
			Craft::$app->getResponse()->getCookies()->add($impersonatorCookie);
		}

		// Proceed with the impersonation

		if (!$userSession->loginByUserId($impersonatee->id)) {
			$session->remove(User::IMPERSONATE_KEY);
			Craft::$app->getResponse()->getCookies()->remove($impersonatorCookieName);
			$session->setError(Craft::t('app', 'There was a problem impersonating this user.'));
			Craft::error($userSession->getIdentity()->username . ' tried to impersonate userId: ' . $impersonatee->id . ' but something went wrong.', __METHOD__);
			return null;
		}

		$session->setNotice(Craft::t('app', 'Logged in.'));

		return $this->_handleSuccess(Craft::t('app', 'Logged in.'));

	}

	/**
	 * @return Response
	 *
	 * @throws BadRequestHttpException
	 * @throws InvalidConfigException
	 */
	public function actionUnimpersonate()
	{

		$request = Craft::$app->getRequest();
		$session = Craft::$app->getSession();
		$userSession = Craft::$app->getUser();

		$impersonatorId = Impersonator::$plugin->impersonator->getImpersonatorId();
		$impersonatorCookie = $request->getCookies()->get($userSession->identityCookie['name'].'_impersonator');

		// Log-out the impersonated User and clean up any active impersonation session.
		$userSession->logout(false);
		$session->remove(User::IMPERSONATE_KEY);

		// If we have an Impersonator Identity present in the cookie collection...
		if ($impersonatorCookie)
		{

			// Make sure we have an Impersonator ID, which means we were coming from a known impersonation session.
			if ((bool)$impersonatorId)
			{

				// Try to restore the original Identity.

				$identityCookie = Craft::createObject(array_merge($userSession->identityCookie, [
					'class' => Cookie::class,
					'value' => $impersonatorCookie->value,
					'expire' => $impersonatorCookie->expire,
				]));

				Craft::$app->getResponse()->getCookies()->add($identityCookie);

			}

			// And either way, clear out the Impersonator Identity cookie.

			Craft::$app->getResponse()->getCookies()->remove($impersonatorCookie);

		}

		return $this->_handleSuccess();

	}


	// Private Methods
	// =========================================================================

	/**
	 * Redirects the user after a successful request.
	 *
	 * @param string $notice Whether a flash notice should be set, if this isn't an Ajax request.
	 *
	 * @return Response
	 *
	 * @throws BadRequestHttpException
	 */
	private function _handleSuccess(string $notice = null): Response
	{

		$request = Craft::$app->getRequest();
		$userSession = Craft::$app->getUser();

		// Get, and then clear out, the Return URL

		$returnUrl = $userSession->getReturnUrl();
		$userSession->removeReturnUrl();

		// If this was an Ajax request, just return a success blob.
		if ($request->getAcceptsJson())
		{
			$return = [
				'success' => true,
				'returnUrl' => $returnUrl
			];
			if (Craft::$app->getConfig()->getGeneral()->enableCsrfProtection) {
				$return['csrfTokenValue'] = $request->getCsrfToken();
			}
			return $this->asJson($return);
		}

		if (!empty($notice))
		{
			Craft::$app->getSession()->setNotice($notice);
		}

		return $this->redirectToPostedUrl($userSession->getIdentity(), $returnUrl);

	}

}
