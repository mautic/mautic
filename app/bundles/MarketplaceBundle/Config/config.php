<?php

declare(strict_types=1);

use Mautic\MarketplaceBundle\Service\Config;

return [
    // NOTE: when adding new parameters here, please add them to the developer documentation as well:
    'parameters' => [
        Config::MARKETPLACE_ENABLED                => true,
        Config::MARKETPLACE_WEBSITE_URL            => 'https://marketplace.mautic.org',
        Config::MARKETPLACE_REGISTRY_URL           => 'https://marketplace.mautic.org',
    ],
];
