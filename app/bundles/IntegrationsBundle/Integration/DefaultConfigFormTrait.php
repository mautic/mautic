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

namespace MauticPlugin\IntegrationsBundle\Integration;

trait DefaultConfigFormTrait
{
    /**
     * Use the default.
     *
     * @return null|string
     */
    public function getConfigFormName(): ?string
    {
        return null;
    }

    /**
     * Use the default.
     *
     * @return null|string
     */
    public function getConfigFormContentTemplate(): ?string
    {
        return null;
    }

    /**
     * Use the default.
     *
     * @return null|string
     */
    public function getSyncConfigFormName(): ?string
    {
        return null;
    }
}
