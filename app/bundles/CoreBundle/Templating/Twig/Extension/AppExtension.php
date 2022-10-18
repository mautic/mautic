<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Templating\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigTest;

class AppExtension extends AbstractExtension
{
    public function getTests(){
        return [
            new TwigTest('instanceof', [$this, 'isinstanceof'])
        ];
    }


    public function isInstanceof($var, $instance) {
        return  $var instanceof $instance;
    }
}
