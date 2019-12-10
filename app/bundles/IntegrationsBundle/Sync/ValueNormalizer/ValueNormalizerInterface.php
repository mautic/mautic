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

namespace MauticPlugin\IntegrationsBundle\Sync\ValueNormalizer;

use MauticPlugin\IntegrationsBundle\Sync\DAO\Value\NormalizedValueDAO;

interface ValueNormalizerInterface
{
    /**
     * @param $value
     * @param $type
     *
     * @return NormalizedValueDAO
     */
    public function normalizeForMautic(string $value, $type): NormalizedValueDAO;

    /**
     * @param NormalizedValueDAO $value
     *
     * @return mixed
     */
    public function normalizeForIntegration(NormalizedValueDAO $value);
}
