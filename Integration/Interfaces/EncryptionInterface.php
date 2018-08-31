<?php

namespace MauticPlugin\IntegrationsBundle\Integration\Interfaces;

use MauticPlugin\IntegrationsBundle\Facade\EncryptionService;
use MauticPlugin\IntegrationsBundle\Integration\BasicIntegration;

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