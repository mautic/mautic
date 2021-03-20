<?php

declare(strict_types=1);

/*
 * @copyright   2021 Mautic. All rights reserved
 * @author      Mautic Contributors.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\IntegrationsBundle\Integration\Interfaces;

interface BuilderInterface extends IntegrationInterface
{
    public function isSupported(string $featureName): bool;
}
