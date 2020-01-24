<?php
namespace topshelfcraft\impersonator\twigextensions;

use topshelfcraft\impersonator\Impersonator;
use Twig\Extension\GlobalsInterface;

/**
 * @author    Top Shelf Craft (Michael Rog)
 * @package   Impersonator
 * @since     1.0.0
 */
class ImpersonatorTwigExtension extends \Twig_Extension implements GlobalsInterface
{

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'Impersonator';
    }

    /**
     * @inheritdoc
     */
    public function getGlobals()
    {
        return [
            'impersonator' => Impersonator::$plugin->impersonator,
        ];
    }

}
