<?php

namespace MauticPlugin\MauticIntegrationsBundle\Integration\Interfaces;

use MauticPlugin\MauticIntegrationsBundle\Facade\EncryptionService;
use MauticPlugin\MauticIntegrationsBundle\Integration\BasicIntegration;

interface EncryptionInterface {
    /**
     * @return EncryptionService
     */
    public function getEncryption(): EncryptionService;

    /**
     * @param EncryptionService $encryption
     *
     * @return BasicIntegration
     */
    public function setEncryption(EncryptionService $encryption): BasicIntegration;
}