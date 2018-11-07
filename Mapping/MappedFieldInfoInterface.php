<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Mapping;

interface MappedFieldInfoInterface
{
    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @return string
     */
    public function getLabel(): string;

    /**
     * @return bool
     */
    public function showAsRequired(): bool;

    /**
     * @return bool
     */
    public function isBidirectionalSyncEnabled(): bool;

    /**
     * @return bool
     */
    public function isToIntegrationSyncEnabled(): bool;

    /**
     * @return bool
     */
    public function isToMauticSyncEnabled(): bool;
}
