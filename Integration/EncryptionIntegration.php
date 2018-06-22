<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticIntegrationsBundle\Integration;

use MauticPlugin\MauticIntegrationsBundle\Facade\EncryptionService;

trait EncryptionIntegration
{
    /** @var EncryptionService */
    private $encryption;

    /**
     * @return EncryptionService
     */
    public function getEncryption(): EncryptionService
    {
        return $this->encryption;
    }

    /**
     * @param EncryptionService $encryption
     *
     * @return BasicIntegration
     */
    public function setEncryption(EncryptionService $encryption): BasicIntegration
    {
        $this->encryption = $encryption;

        return $this;
    }
}
