<?php
namespace topshelfcraft\impersonator\models;

use Craft;
use craft\base\Model;

/**
 * @author    Top Shelf Craft (Michael Rog)
 * @package   Impersonator
 * @since     1.0.0
 */
class Settings extends Model
{

	/**
	 * @var string
	 *
	 * @todo Allow customizing the criterion param name.
	 */
	public $accountParamName = 'impersonate';

    /**
     * @var string
	 *
	 * @todo Add token-based impersonation
     */
    public $tokenParamName = 'impersonatorToken';

	/**
	 * @var string
	 *
	 * @todo Add token-based impersonation
	 */
	public $validTokens = null;

	/**
	 * @var string
	 */
	public $impersonatorSessionDuration = null;

	/**
	 *
	 */
	public function init()
	{

		parent::init();

		if (empty($this->impersonatorSessionDuration))
		{
			$this->impersonatorSessionDuration = Craft::$app->getConfig()->getGeneral()->elevatedSessionDuration;
		}

	}

}
