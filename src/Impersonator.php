<?php
namespace topshelfcraft\impersonator;

use Craft;
use craft\base\Plugin;
use topshelfcraft\impersonator\services\Impersonator as ImpersonatorService;
use topshelfcraft\impersonator\twigextensions\ImpersonatorTwigExtension;
use topshelfcraft\impersonator\models\Settings;

/**
 * @author    Top Shelf Craft (Michael Rog)
 * @package   Impersonator
 * @since     1.0.0
 *
 * @property  ImpersonatorService $impersonator
 *
 * @method Settings getSettings()
 */
class Impersonator extends Plugin
{

    // Static Properties
    // =========================================================================

    /**
     * @var Impersonator
     */
    public static $plugin;


    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {

        parent::init();
        self::$plugin = $this;

        Craft::$app->view->registerTwigExtension(new ImpersonatorTwigExtension());

    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function createSettingsModel()
    {
        return new Settings();
    }

    /**
     * @inheritdoc
     */
    protected function settingsHtml(): string
    {
        return Craft::$app->view->renderTemplate(
            'impersonator/settings',
            [
                'settings' => $this->getSettings()
            ]
        );
    }

}
