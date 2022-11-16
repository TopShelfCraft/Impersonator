<?php
namespace TopShelfCraft\Impersonator;

use Craft;
use craft\base\Plugin;
use craft\elements\User;
use TopShelfCraft\Impersonator\view\TwigExtension;

/**
 * @author Michael Rog <michael@michaelrog.com>
 *
 * @method Settings getSettings()
 */
class Impersonator extends Plugin
{

	public string $schemaVersion = '0.0.0.0';

    public function init(): void
    {
		Craft::$app->view->registerTwigExtension(new TwigExtension());
    }

	public function getImpersonatorId(): ?int
	{
		$impersonatorId = Craft::$app->getSession()->get(User::IMPERSONATE_KEY);
		return $impersonatorId ? (int) $impersonatorId : null;
	}

	public function getImpersonatorIdentity(): ?User
	{
		$impersonatorId = $this->getImpersonatorId();
		return $impersonatorId ? Craft::$app->getUsers()->getUserById($impersonatorId) : null;
	}

	/**
	 * Returns a reason, if one exists, why a given User would be unable to log in.
	 *
	 * @internal
	 * @see User::_getAuthError
	 * @todo If the User::_getAuthError becomes public, use the canonical method instead of duplicating.
	 */
	public function getUserAuthError(User $user): ?string
	{

		switch ($user->getStatus()) {
			case User::STATUS_INACTIVE:
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
					} elseif (
						Craft::$app->getIsLive() === false &&
						$user->can('accessSiteWhenSystemIsOff') === false
					) {
						return User::AUTH_NO_SITE_OFFLINE_ACCESS;
					}
				}
		}

		return null;

	}

    protected function createSettingsModel(): Settings
    {
        return new Settings();
    }

}
