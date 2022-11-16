<?php
namespace TopShelfCraft\Impersonator\view;

use TopShelfCraft\Impersonator\Impersonator;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class TwigExtension extends AbstractExtension implements GlobalsInterface
{

    public function getName()
    {
        return 'Impersonator';
    }

    public function getGlobals(): array
    {
        return [
            'impersonator' => Impersonator::getInstance(),
        ];
    }

}
