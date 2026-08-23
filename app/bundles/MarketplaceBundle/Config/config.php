<?php

declare(strict_types=1);

use Mautic\MarketplaceBundle\Service\Config;

return [
    // NOTE: when adding new parameters here, please add them to the developer documentation as well:
    'parameters' => [
        Config::MARKETPLACE_ENABLED                     => true,
        Config::MARKETPLACE_ALLOWLIST_URL               => 'https://raw.githubusercontent.com/mautic/marketplace-allowlist/main/allowlist.json',
        Config::MARKETPLACE_ALLOWLIST_CACHE_TTL_SECONDS => 3600,
    ],
];
