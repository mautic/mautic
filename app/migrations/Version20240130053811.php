<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;
use Mautic\CoreBundle\Security\Cryptography\Cipher\Symmetric\OpenSSLCipher;

final class Version20240130053811 extends AbstractMauticMigration
{
    public function up(Schema $schema): void
    {
        $secreteKey = $this->container->getParameter('mautic.secret_key');

        /** @var OpenSSLCipher $openSSLCipher */
        $openSSLCipher = $this->container->get('mautic.cipher.openssl');

        // Load the \Mautic\PluginBundle\Entity\Integration entity
        $integrations = $this->connection->fetchAllAssociative(sprintf('select id, name, api_keys from %splugin_integration_settings WHERE api_keys <> "a:0:{}"', $this->prefix));

        error_reporting(E_ALL & ~E_WARNING);

        foreach ($integrations as $integration) {
            $apiKeys = unserialize($integration['api_keys']);
            if (empty($apiKeys)) {
                continue;
            }

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

            $this->addSql(sprintf("UPDATE %splugin_integration_settings SET api_keys = '%s' WHERE id = %s", $this->prefix, serialize($apiKeys), $integration['id']));
            $this->write(sprintf('API Keys are updated for "%s" plugin.', $integration['name']));
        }
        $this->write('Please so check the sanity of Api keys for all configured plugins!!!');
    }
}
