<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\EncryptionHelper;
use Mautic\CoreBundle\Helper\Filesystem;
use Mautic\EmailBundle\Mailer\Message\MauticMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Crypto\SMimeSigner;
use Symfony\Component\Mime\Message;

/**
 * Signs message with S/MIME certificate.
 */
class SMimeHelper
{
    /**
     * Caching the certificate paths to avoid reading/decrypting them on every message.
     *
     * @var array<string,array{certPath: string, keyPath: string}>
     */
    private array $certCache = [];

    /**
     * Temporary decrypted key files that need to be cleaned up.
     *
     * @var string[]
     */
    private array $tempFiles = [];

    public function __construct(private CoreParametersHelper $coreParametersHelper, private Filesystem $filesystem, private EncryptionHelper $encryptionHelper, private LoggerInterface $logger)
    {
    }

    public function __destruct()
    {
        // Clean up temporary decrypted key files
        foreach ($this->tempFiles as $tempFile) {
            if (file_exists($tempFile)) {
                @unlink($tempFile);
            }
        }
    }

    public function sMimeSigningEnabled(): bool
    {
        return (bool) $this->coreParametersHelper->get('smime_signing_enabled', false);
    }

    public function getSMimeCertificatePath(): string
    {
        return rtrim((string) $this->coreParametersHelper->get('smime_certificates_path'), '/');
    }

    /**
     * Signs the message with S/MIME if enabled and certificates are available.
     * Returns the signed message, or the original message if signing is not applicable.
     */
    public function signContent(MauticMessage $message): Message
    {
        if (!$this->sMimeSigningEnabled()) {
            return $message;
        }

        /** @var Address[] $fromArray */
        $fromArray = $message->getFrom();

        if (!is_array($fromArray) || 1 !== count($fromArray)) {
            return $message;
        }

        $fromEmail = $fromArray[0]->getAddress();

        if (isset($this->certCache[$fromEmail])) {
            $certPaths = $this->certCache[$fromEmail];
        } else {
            try {
                $certPaths = $this->getCertificatePaths($fromEmail);
            } catch (IOException $e) {
                // Log the exception for debugging
                $this->logger->error('SMimeHelper: IOException when loading certificates for '.$fromEmail, ['exception' => $e]);

                return $message;
            }

            $this->certCache[$fromEmail] = $certPaths;
        }

        // Create Symfony's SMimeSigner with the certificate and private key file paths
        $signer = new SMimeSigner($certPaths['certPath'], $certPaths['keyPath']);

        // Sign and return the signed message
        try {
            return $signer->sign($message);
        } catch (\RuntimeException $e) {
            // Catch OpenSSL signing errors (e.g., invalid certificate, corrupted key)
            $this->logger->error('SMimeHelper: Failed to sign message', ['exception' => $e]);

            return $message;
        }
    }

    /**
     * @return array{certPath: string, keyPath: string}
     *
     * @throws IOException if one of the certificates is not found
     */
    private function getCertificatePaths(string $fromEmail): array
    {
        $certPath                = $this->getSMimeCertificatePath();
        $publicCertPath          = "{$certPath}/{$fromEmail}.crt";
        $privateKeyPath          = "{$certPath}/{$fromEmail}.pem";
        $privateKeyEncryptedPath = "{$certPath}/{$fromEmail}.pem.enc";

        // Check if public certificate exists
        if (!file_exists($publicCertPath)) {
            throw new IOException("Public certificate not found: {$publicCertPath}");
        }

        // Try encrypted key first
        if (file_exists($privateKeyEncryptedPath)) {
            try {
                $encryptedContent = $this->filesystem->readFile($privateKeyEncryptedPath);
                $decryptedContent = $this->encryptionHelper->decrypt($encryptedContent);

                // Create a temporary file with a unique hash
                $tempKeyPath = sys_get_temp_dir().'/mautic_smime_'.md5($fromEmail.uniqid('', true)).'.pem';
                file_put_contents($tempKeyPath, $decryptedContent);
                chmod($tempKeyPath, 0600); // Secure the temporary file

                // Track this file for cleanup
                $this->tempFiles[] = $tempKeyPath;

                return [
                    'certPath' => $publicCertPath,
                    'keyPath'  => $tempKeyPath,
                ];
            } catch (IOException) {
                // Fall through to try unencrypted key
            }
        }

        // Try unencrypted key
        if (file_exists($privateKeyPath)) {
            return [
                'certPath' => $publicCertPath,
                'keyPath'  => $privateKeyPath,
            ];
        }

        throw new IOException("Private key not found for {$fromEmail}");
    }
}
