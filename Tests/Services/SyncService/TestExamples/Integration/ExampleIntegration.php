<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

declare(strict_types=1);

namespace MauticPlugin\MagentoBundle\Integration;

use MauticPlugin\MauticIntegrationsBundle\Integration\BasicIntegration;
use MauticPlugin\MauticIntegrationsBundle\Integration\Interfaces\BasicInterface;

final class ExampleIntegration extends BasicIntegration implements BasicInterface
{

    const NAME = 'Example';

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return MagentoIntegration::NAME;
    }

    /**
     * @return bool
     */
    public function isConfigured(): bool
    {
        return true;
    }

    /**
     * @return bool
     */
    public function isAuthorized(): bool
    {
        return true;
    }

    /**
     * Get if data priority is enabled in the integration or not default is false.
     *
     * @return bool
     */
    public function getDataPriority(): bool
    {
        return true;
    }
}
