<?php

declare(strict_types=1);

namespace Mautic\MessengerBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class MauticMessengerBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        include __DIR__.'/Config/parameters.php';
    }
}
