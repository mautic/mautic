<?php

namespace Mautic\UserBundle\Tests\Security\SAML\Store;

use LightSaml\Credential\X509Credential;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\UserBundle\Security\SAML\Store\CredentialsStore;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CredentialsStoreTest extends TestCase
{
    private string $cacheDir;

    /**
     * @var CoreParametersHelper|MockObject
     */
    private MockObject $coreParametersHelper;

    protected function setUp(): void
    {
        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $this->cacheDir             = dirname((new \ReflectionClass(\Composer\Autoload\ClassLoader::class))->getFileName(), 3);
    }

    public function testEmptyArrayReturnedIfEntityIdsDoNotMatch(): void
    {
        $store = new CredentialsStore($this->coreParametersHelper, 'foobar');

        $this->assertEquals([], $store->getByEntityId('barfoo'));
    }

    public function testDefaultCredentialsAreUsedIfSamlIsDisabled(): void
    {
        $this->coreParametersHelper->method('get')
          ->withConsecutive(['saml_idp_metadata'], ['cache_path'])
          ->willReturnOnConsecutiveCalls('', $this->cacheDir);

        $store = new CredentialsStore($this->coreParametersHelper, 'foobar');

        $credentials = $store->getByEntityId('foobar');
        $this->assertCount(1, $credentials);

        $this->assertInstanceOf(X509Credential::class, $credentials[0]);
    }

    public function testDefaultCredentialsAreUsedIfCustomCertificateIsNotProvided(): void
    {
        $this->coreParametersHelper->method('get')
            ->withConsecutive(['saml_idp_metadata'], ['saml_idp_own_certificate'], ['cache_path'])
            ->willReturnOnConsecutiveCalls('1', '', $this->cacheDir);

        $store = new CredentialsStore($this->coreParametersHelper, 'foobar');

        $credentials = $store->getByEntityId('foobar');
        $this->assertCount(1, $credentials);

        $this->assertInstanceOf(X509Credential::class, $credentials[0]);
    }

    public function testOwnCredentialsAreUsedIfProvided(): void
    {
        $this->coreParametersHelper->method('get')
            ->withConsecutive(
                ['saml_idp_metadata'],
                ['saml_idp_own_certificate'],
                ['saml_idp_own_certificate'],
                ['saml_idp_own_private_key'],
                ['saml_idp_own_password']
            )
            ->willReturnOnConsecutiveCalls(
                '1',
                'LS0tLS1CRUdJTiBDRVJUSUZJQ0FURS0tLS0tCk1JSUNOakNDQVorZ0F3SUJBZ0lCQURBTkJna3Foa2lHOXcwQkFRMEZBREE0TVFzd0NRWURWUVFHRXdKMWN6RUwKTUFrR0ExVUVDQXdDVkZneERUQUxCZ05WQkFvTUJGUmxjM1F4RFRBTEJnTlZCQU1NQkZSbGMzUXdIaGNOTVRreApNakk1TVRjME56RTBXaGNOTWpBeE1qSTRNVGMwTnpFMFdqQTRNUXN3Q1FZRFZRUUdFd0oxY3pFTE1Ba0dBMVVFCkNBd0NWRmd4RFRBTEJnTlZCQW9NQkZSbGMzUXhEVEFMQmdOVkJBTU1CRlJsYzNRd2daOHdEUVlKS29aSWh2Y04KQVFFQkJRQURnWTBBTUlHSkFvR0JBTDQ4eCtJY29BQVVjOVEvL2QxRkhxZFQ1WjNWejRCSVIzNFJqNUUvQkpkegpmODN0dGx0NnBKNFdCbEFYcFlHWW5PSDh4YXpjdGJEUzd2QVVhbmtQMUxBV2haUnBDeFVkdHg2VlV3MXZlNS8xCnRjV1VBcnBZdFVIMXJHdEdoaDlncFJMVkxEMktxaWQzengyMjlXeHJmaHV0NjVBbEJKRzlSeVV6T2E4cWlVS2IKQWdNQkFBR2pVREJPTUIwR0ExVWREZ1FXQkJUZWtkN0RvWUI4dFc0K2N3TGYzR0FKNTl5VFVEQWZCZ05WSFNNRQpHREFXZ0JUZWtkN0RvWUI4dFc0K2N3TGYzR0FKNTl5VFVEQU1CZ05WSFJNRUJUQURBUUgvTUEwR0NTcUdTSWIzCkRRRUJEUVVBQTRHQkFGd05Uc3lHNVZ5dG5EdWF5ZjBmbi9zOGtPcG1mcG1FcDBTRDFBajdvRGhNTytHdG5SWGEKUGZsWVozWlFJWCt4Wkl2K1FSOTNZNUZDM1h2V1JWbk9abWtybzh3YmZoZkFOa2ZGWnFiNFg3SlFqY2YrOVNOTwoxenpyVVVKK1BSVGpBSnR3REdrRVB6Q2d3UDk5QVIrUm5UQ1RaUS9OM2xoQXl3Zm1qRTNQNUpoNwotLS0tLUVORCBDRVJUSUZJQ0FURS0tLS0t',
                'LS0tLS1CRUdJTiBDRVJUSUZJQ0FURS0tLS0tCk1JSUNOakNDQVorZ0F3SUJBZ0lCQURBTkJna3Foa2lHOXcwQkFRMEZBREE0TVFzd0NRWURWUVFHRXdKMWN6RUwKTUFrR0ExVUVDQXdDVkZneERUQUxCZ05WQkFvTUJGUmxjM1F4RFRBTEJnTlZCQU1NQkZSbGMzUXdIaGNOTVRreApNakk1TVRjME56RTBXaGNOTWpBeE1qSTRNVGMwTnpFMFdqQTRNUXN3Q1FZRFZRUUdFd0oxY3pFTE1Ba0dBMVVFCkNBd0NWRmd4RFRBTEJnTlZCQW9NQkZSbGMzUXhEVEFMQmdOVkJBTU1CRlJsYzNRd2daOHdEUVlKS29aSWh2Y04KQVFFQkJRQURnWTBBTUlHSkFvR0JBTDQ4eCtJY29BQVVjOVEvL2QxRkhxZFQ1WjNWejRCSVIzNFJqNUUvQkpkegpmODN0dGx0NnBKNFdCbEFYcFlHWW5PSDh4YXpjdGJEUzd2QVVhbmtQMUxBV2haUnBDeFVkdHg2VlV3MXZlNS8xCnRjV1VBcnBZdFVIMXJHdEdoaDlncFJMVkxEMktxaWQzengyMjlXeHJmaHV0NjVBbEJKRzlSeVV6T2E4cWlVS2IKQWdNQkFBR2pVREJPTUIwR0ExVWREZ1FXQkJUZWtkN0RvWUI4dFc0K2N3TGYzR0FKNTl5VFVEQWZCZ05WSFNNRQpHREFXZ0JUZWtkN0RvWUI4dFc0K2N3TGYzR0FKNTl5VFVEQU1CZ05WSFJNRUJUQURBUUgvTUEwR0NTcUdTSWIzCkRRRUJEUVVBQTRHQkFGd05Uc3lHNVZ5dG5EdWF5ZjBmbi9zOGtPcG1mcG1FcDBTRDFBajdvRGhNTytHdG5SWGEKUGZsWVozWlFJWCt4Wkl2K1FSOTNZNUZDM1h2V1JWbk9abWtybzh3YmZoZkFOa2ZGWnFiNFg3SlFqY2YrOVNOTwoxenpyVVVKK1BSVGpBSnR3REdrRVB6Q2d3UDk5QVIrUm5UQ1RaUS9OM2xoQXl3Zm1qRTNQNUpoNwotLS0tLUVORCBDRVJUSUZJQ0FURS0tLS0t',
                'LS0tLS1CRUdJTiBFTkNSWVBURUQgUFJJVkFURSBLRVktLS0tLQpNSUlDeGpCQUJna3Foa2lHOXcwQkJRMHdNekFiQmdrcWhraUc5dzBCQlF3d0RnUUkrdGdUM1FGaGpFZ0NBZ2dBCk1CUUdDQ3FHU0liM0RRTUhCQWcxSEdyU0hiN3pWd1NDQW9CRzg5enFBeEF4K3ZQdmhRVlc2ZGZKRkJUU3BBR3EKUlJsZml5Z3IwaXdMQ3hwbm9UNVpZLzBPZDI4dXZCL0hrQWIwY0NnNnNZdk5WdkRERGl0c3BDYjN2SVUvZ1BtTgpoOFZFdGd4RGxYRUUxWXdpbkRuMEJmTzV0Um9DNFNmT3JKUnNyUmRYNHFjN3hrODBLazc0Y2J6TEp5NlZFU1E1CnUvWlk4WXcyRTlleElINHJsWitkQmZzNEpla1FTL2ZMYmlYR2R0U2RWRjZSTzBlOUloUnNiM2RVaFNxcUphZmwKNitWa3B0aGVYR0Z3VHlTZjhjNXlMbU1VQy96Mk5DR3hPNUc5MXVVeHNoRVF2ZFEyTk0wa3Z0OUFHM1Rjd1o2ZwpvYnFqSFdmVkVtVDlqOVJhcWJrbis5ZGVzYWxLUk9OYmUxbEkwSUwxdmNJQlhXZHVRVU5RSzFkVDlnaGJ3R1QrCnZlZ3c4NnhsTXRMTlBydkZ4YzNHNlpWdGlkL1Qxd0RYWkVLS0NxcDlVZWkzZmgwU0xLeTJ3aXRqdDV5dmFiYmMKUWhYT1MyaFZBOXpTUTBJR2N5Y1d6eGVhZitOYjgyNnh2dkFWOVRmMUdocmVUNnZRV0M4QnhEQ1ZyOXczMWg2eQpMbkM1UjJkeHpvbTFkL2tpQnFsY0doMXU5ZCsyT3lDRnBmWXZ5bVdsS2NYWVlPOEUyTnUyb0s0SWx6QjBZZEpwCjgveTdQTEd6YWlwZjU3ZThzckdNR3ZMTUt3UVRMQ3ZhRHUvZ3hsNGQrYXd3VFo5VXNHMnFNT0taU2tJVTRJTXUKdVAwQ0RTYlJ4YkhCcTlnWTRaaUVDdUF4bW1vYWRaWG1OT3U4aUdRV2E5cm1vQ3FXNVhtQWd3dmNuZnhEV2F3SQpaVEpVZ0hkWjZUZmUyalBGSmpTWlZvVS9lbjBXNUJRWGd5MXUzQkRYNjhDOG5BZlo0eG1leUVMY011YjloVFliCkhURG1ZSUJvelpOSWNZSEI2T0dablVSdUdlb2ZNVkpxTWtOZm5FdVN1b0NKc1hHSWhMem5ES3A4RzAwRjllUjIKMUwrQjBaVVp2L084MnFFR3pDL0lYNytDRm1TRFN0VjlSNDAwY0R2aSs4QnNkTU1CK1dWNlNNbksKLS0tLS1FTkQgRU5DUllQVEVEIFBSSVZBVEUgS0VZLS0tLS0=',
                'abc123'
            );

        $store = new CredentialsStore($this->coreParametersHelper, 'foobar');

        $credentials = $store->getByEntityId('foobar');
        $this->assertCount(1, $credentials);

        $cert = $credentials[0];
        $this->assertInstanceOf(X509Credential::class, $cert);

        $issuer = $cert->getCertificate()->getIssuer();
        $this->assertEquals('TX', $issuer['ST']);
    }
}
