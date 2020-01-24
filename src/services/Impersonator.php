<?php
namespace topshelfcraft\impersonator\services;

use Craft;
use craft\base\Component;
use craft\elements\User;

/**
 * @author    Top Shelf Craft (Michael Rog)
 * @package   Impersonator
 * @since     1.0.0
 */
class Impersonator extends Component
{

	// Public Methods
    // =========================================================================

	/**
	 * @return int|null
	 */
	public function getImpersonatorId()
	{
		return Craft::$app->getSession()->get(User::IMPERSONATE_KEY);
	}

	/**
	 * @return User|null
	 */
	public function getImpersonatorIdentity()
	{
		$impersonator = $this->getImpersonatorId();
		return $impersonator ? Craft::$app->getUsers()->getUserById($impersonator) : null;
	}

	/**
	 * Returns a reason, if one exists, why a given User would be unable to log in.
	 *
	 * @see User::_getAuthError
	 *
	 * @todo If the User::_getAuthError becomes public, we'd prefer to use that, obvs.
	 *
	 * @return null|string
	 */
	public function getAuthError(User $user)
	{

		switch ($user->getStatus()) {
			case User::STATUS_ARCHIVED:
				return User::AUTH_INVALID_CREDENTIALS;
			case User::STATUS_PENDING:
				return User::AUTH_PENDING_VERIFICATION;
			case User::STATUS_SUSPENDED:
				return User::AUTH_ACCOUNT_SUSPENDED;
			case User::STATUS_ACTIVE:
				if ($user->locked) {
					// Let them know how much time they have to wait (if any) before their account is unlocked.
					if (Craft::$app->getConfig()->getGeneral()->cooldownDuration) {
						return User::AUTH_ACCOUNT_COOLDOWN;
					}
					return User::AUTH_ACCOUNT_LOCKED;
				}
				// Is a password reset required?
				if ($user->passwordResetRequired) {
					return User::AUTH_PASSWORD_RESET_REQUIRED;
				}
				$request = Craft::$app->getRequest();
				if (!$request->getIsConsoleRequest()) {
					if ($request->getIsCpRequest()) {
						if (!$user->can('accessCp')) {
							return User::AUTH_NO_CP_ACCESS;
						}
						if (
							Craft::$app->getIsLive() === false &&
							$user->can('accessCpWhenSystemIsOff') === false
						) {
							return User::AUTH_NO_CP_OFFLINE_ACCESS;
						}
					} else if (
						Craft::$app->getIsLive() === false &&
						$user->can('accessSiteWhenSystemIsOff') === false
					) {
						return User::AUTH_NO_SITE_OFFLINE_ACCESS;
					}
				}
		}

		return null;

	}

}
