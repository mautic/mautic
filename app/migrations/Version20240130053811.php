<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;
use Mautic\CoreBundle\Security\Cryptography\Cipher\Symmetric\OpenSSLCipher;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Entity\IntegrationRepository;

final class Version20240130053811 extends AbstractMauticMigration
{
    public function up(Schema $schema): void
    {
        $secreteKey = $this->container->getParameter('mautic.secret_key');

        /** @var OpenSSLCipher $openSSLCipher */
        $openSSLCipher = $this->container->get('mautic.cipher.openssl');

        // Load the \Mautic\PluginBundle\Entity\Integration entity
        /** @var IntegrationRepository $integrationRepo */
        $integrationRepo = $this->entityManager->getRepository(Integration::class);
        $integrations    = $integrationRepo->getIntegrations();

        error_reporting(E_ALL & ~E_WARNING);

        /** @var Integration $integration */
        foreach ($integrations as $integration) {
            if (empty($integration->getApiKeys())) {
                continue;
            }

            $apiKeys = $integration->getApiKeys();
            foreach ($apiKeys as $name => $apiKey) {
                $encryptData      = explode('|', $apiKey);
                $encryptedMessage = base64_decode($encryptData[0]);
                $initVector       = base64_decode($encryptData[1]);

                // decrypt with old secret
                $decrypted     = trim(openssl_decrypt($encryptedMessage, 'AES-256-CBC', pack('H*', $secreteKey), 0, $initVector));
                $sha256Length  = 64;
                $secretMessage = substr($decrypted, 0, -$sha256Length);

                // encrypt
                $encrypted = $openSSLCipher->encrypt($secretMessage, $secreteKey, $initVector);
                $newApiKey = base64_encode($encrypted).'|'.base64_encode($initVector);

                $apiKeys[$name] = $newApiKey;
            }
            $integration->setApiKeys($apiKeys);

            $integrationRepo->saveEntity($integration);
            $this->write(sprintf('Updates api keys for "%s" plugin', $integration->getName()));
        }
        $this->write('Please so check the sanity of Api keys for all configured plugins!!!');
    }
}
