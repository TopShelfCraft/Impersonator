<?php
namespace TopShelfCraft\Impersonator;

use Craft;
use craft\config\BaseConfig;
use craft\helpers\ConfigHelper;

class Settings extends BaseConfig
{

	/**
	 * @var string The parameter used to specify the account to impersonate.
	 */
	public string $accountParamName = 'impersonate';

	/**
	 * @var mixed The amount of time an impersonation session will last.
	 *
	 * Set to `0` to disable impersonation session support.
	 *
	 * See [[ConfigHelper::durationInSeconds()]] for a list of supported value types.
	 *
	 * @defaultAlt The value of GeneralConfig::$elevatedSessionDuration
	 */
	public mixed $impersonatorSessionDuration;


	/**
	 * The form parameter used to specify the account to impersonate.
	 *
	 * ```php
	 * ->accountParamName('impersonate')
	 * ```
	 */
	public function accountParamName(string $value): self
	{
		$this->accountParamName = $value;
		return $this;
	}

	/**
	 * The amount of time an impersonation session will last.
	 *
	 * Set to `0` to disable impersonation session support.
	 *
	 * See [[ConfigHelper::durationInSeconds()]] for a list of supported value types.
	 *
	 * ```php
	 * ->impersonatorSessionDuration(0)
	 * ```
	 *
	 * @defaultAlt The value of GeneralConfig::$elevatedSessionDuration
	 */
	public function impersonatorSessionDuration(mixed $value): self
	{
		$this->impersonatorSessionDuration = ConfigHelper::durationInSeconds($value);
		return $this;
	}

	/**
	 * Initiates the model and applies default $impersonatorSessionDuration if needed.
	 */
	public function init(): void
	{

		parent::init();

		$this->impersonatorSessionDuration(
			$this->impersonatorSessionDuration ?? Craft::$app->getConfig()->getGeneral()->elevatedSessionDuration
		);

	}

}
