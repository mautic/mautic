<?php

namespace Mautic\UserBundle\Security\SAML\Store;

use LightSaml\Credential\KeyHelper;
use LightSaml\Credential\X509Certificate;
use LightSaml\Credential\X509Credential;
use LightSaml\Store\Credential\CredentialStoreInterface;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use RobRichards\XMLSecLibs\XMLSecurityKey;

class CredentialsStore implements CredentialStoreInterface
{
    private ?\LightSaml\Credential\X509Credential $credentials = null;

    public function __construct(
        private CoreParametersHelper $coreParametersHelper,
        private string $entityId
    ) {
    }

    public function getByEntityId($entityId): array
    {
        // EntityIds do not match
        if ($entityId !== $this->entityId) {
            return [];
        }

        if (!$this->credentials) {
            $this->delegateAndCreateCredentials();
        }

        return [$this->credentials];
    }

    private function delegateAndCreateCredentials(): void
    {
        // Credentials are required or SP will cause a never ending login loop as it throws an exception
        $samlEnabled = (bool) $this->coreParametersHelper->get('saml_idp_metadata');

        if (!$samlEnabled || !$certificateContent = $this->coreParametersHelper->get('saml_idp_own_certificate')) {
            $this->credentials = $this->createDefaultCredentials();

            return;
        }

        $this->credentials = $this->createOwnCredentials();
    }

    private function createOwnCredentials(): X509Credential
    {
        $certificateContent = base64_decode($this->coreParametersHelper->get('saml_idp_own_certificate'));
        $privateKeyContent  = base64_decode($this->coreParametersHelper->get('saml_idp_own_private_key'));
        $keyPassword        = (string) $this->coreParametersHelper->get('saml_idp_own_password');

        return $this->createCredentials($certificateContent, $privateKeyContent, $keyPassword);
    }

    private function createDefaultCredentials(): X509Credential
    {
        $cache_dir   = $this->coreParametersHelper->get('cache_path');
        $keyPassword = '';

        if (!file_exists($cache_dir.'/saml_default.key') || !file_exists($cache_dir.'/saml_default.crt')) {
            $dn = ['commonName' => 'Mautic dummy cert'];

            // Generate a new private (and public) key pair
            $privkey = openssl_pkey_new([
              'private_key_bits' => 2048,
              'private_key_type' => OPENSSL_KEYTYPE_RSA,
            ]);

            // Generate a certificate signing request
            $csr = openssl_csr_new($dn, $privkey, ['digest_alg' => 'sha256']);

            // Generate a self-signed cert, valid for 365 days
            $x509 = openssl_csr_sign($csr, null, $privkey, $days=365, ['digest_alg' => 'sha256']);

            openssl_x509_export_to_file($x509, $cache_dir.'/saml_default.crt');
            openssl_pkey_export_to_file($privkey, $cache_dir.'/saml_default.key', $keyPassword);
        }

        $cert       = file_get_contents($cache_dir.'/saml_default.crt');
        $privateKey = file_get_contents($cache_dir.'/saml_default.key');

        return $this->createCredentials($cert, $privateKey, $keyPassword);
    }

    private function createCertificate(string $certificateContent): X509Certificate
    {
        $certificate = new X509Certificate();
        $certificate->loadPem($certificateContent);

        return $certificate;
    }

    private function createPrivateKey(string $privateKeyContent, string $keyPassword, X509Certificate $certificate): XMLSecurityKey
    {
        return KeyHelper::createPrivateKey($privateKeyContent, $keyPassword, false, $certificate->getSignatureAlgorithm());
    }

    private function createCredentials(string $certificateContent, string $privateKeyContent, string $keyPassword): X509Credential
    {
        $certificate = $this->createCertificate($certificateContent);
        $privateKey  = $this->createPrivateKey($privateKeyContent, $keyPassword, $certificate);

        $credentials = new X509Credential($certificate, $privateKey);
        $credentials->setEntityId($this->entityId);

        return $credentials;
    }
}
