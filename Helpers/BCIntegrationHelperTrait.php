<?php
/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @author      Jan Kozak <galvani78@gmail.com>
 */

namespace MauticPlugin\IntegrationsBundle\Helpers;

use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\PluginEvents;
use MauticPlugin\IntegrationsBundle\Integration\BasicIntegration;

/**
 * Class BCPluginHelper provides interfacing between requirements for old AsbtractIntegration and new integrations.
 */
trait BCIntegrationHelperTrait
{
    private $keys;

    /**
     * @param Integration $integration
     */
    public function setIntegrationSettings(Integration $integration)
    {
        $this->integration = $integration;
    }

    /**
     * @return Integration
     */
    public function getIntegrationSettings(): Integration
    {
        return $this->integration;
    }

    /**
     * Encrypts API keys.
     *
     * @param array $keys
     *
     * @return array
     */
    public function encryptApiKeys(array $keys)
    {
        return $this->encryption->encrypt($keys);
    }

    /**
     * Encrypts and saves keys to the entity.
     *
     * @param array       $keys
     * @param Integration $integration
     */
    public function encryptAndSetApiKeys(array $keys, Integration $integration)
    {
        $encrypted = $this->getEncryption()->encrypt($keys);
        $integration->setApiKeys($encrypted);
        $this->setKeys($keys);
    }

    /**
     * Returns decrypted API keys.
     *
     * @todo refactor to use EncryptionService
     *
     * @param bool $entity
     *
     * @return array
     */
    public function getDecryptedApiKeys($entity)
    {
        $keys = $entity->getApiKeys();

        $serialized = serialize($keys);
        if (empty($decryptedKeys[$serialized])) {
            $decrypted = $this->decryptApiKeys($keys, true);
            if (count($keys) !== 0 && count($decrypted) === 0) {
                $decrypted = $this->decryptApiKeys($keys);
                $this->encryptAndSetApiKeys($decrypted, $entity);
                $this->em->flush($entity);
            }
            $decryptedKeys[$serialized] = $this->dispatchIntegrationKeyEvent(
                PluginEvents::PLUGIN_ON_INTEGRATION_KEYS_DECRYPT,
                $decrypted
            );
        }

        return $decryptedKeys[$serialized];
    }

    public function decryptApiKeys(array $keys, $onlyPrimaryCipher = false)
    {
        return $this->getEncryption()->decrypt($keys, $onlyPrimaryCipher);
    }

    /**
     * @return mixed
     */
    public function getKeys()
    {
        return $this->keys;
    }

    /**
     * @param $keys
     *
     * @return $this
     */
    public function setKeys($keys)
    {
        $this->keys = $keys;

        return $this;
    }
}
