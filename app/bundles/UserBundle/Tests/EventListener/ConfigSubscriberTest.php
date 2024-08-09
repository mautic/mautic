<?php

namespace Mautic\UserBundle\Tests\EventListener;

use Mautic\ConfigBundle\Event\ConfigEvent;
use Mautic\UserBundle\EventListener\ConfigSubscriber;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ConfigSubscriberTest extends TestCase
{
    /**
     * @var ConfigEvent|MockObject
     */
    private MockObject $configEvent;

    protected function setUp(): void
    {
        $this->configEvent = $this->createMock(ConfigEvent::class);
    }

    public function testOwnPasswordIsNotWipedOutOnConfigSaveIfEmpty(): void
    {
        $subscriber = new ConfigSubscriber();
        $this->configEvent->expects($this->once())
            ->method('unsetIfEmpty')
            ->with('saml_idp_own_password');

        $this->configEvent->expects($this->once())
            ->method('getConfig')
            ->with('userconfig')
            ->willReturn([]);

        $subscriber->onConfigSave($this->configEvent);
    }

    public function testMetadataFileIsDetectedAsXml(): void
    {
        $subscriber = new ConfigSubscriber();
        $this->configEvent->expects($this->once())
            ->method('unsetIfEmpty')
            ->with('saml_idp_own_password');

        $file = $this->createMock(UploadedFile::class);
        $this->configEvent->expects($this->once())
            ->method('getFileContent')
            ->willReturn('<xml></xml>');

        $this->configEvent->expects($this->once())
            ->method('getConfig')
            ->with('userconfig')
            ->willReturn(
                [
                    'saml_idp_metadata' => $file,
                ]
            );

        $this->configEvent->expects($this->never())
            ->method('setError');

        $subscriber->onConfigSave($this->configEvent);
    }

    public function testMetadataFileFailsValidationIfNotXml(): void
    {
        $subscriber = new ConfigSubscriber();

        $file = $this->createMock(UploadedFile::class);
        $this->configEvent->expects($this->once())
            ->method('getFileContent')
            ->willReturn('foobar');

        $this->configEvent->expects($this->once())
            ->method('getConfig')
            ->with('userconfig')
            ->willReturn(
                [
                    'saml_idp_metadata' => $file,
                ]
            );

        $this->configEvent->expects($this->once())
            ->method('setError')
            ->with('mautic.user.saml.metadata.invalid', [], 'userconfig', 'saml_idp_metadata');

        $subscriber->onConfigSave($this->configEvent);
    }

    public function testCertificatePassesValidationIfValid(): void
    {
        $subscriber = new ConfigSubscriber();

        $file = $this->createMock(UploadedFile::class);
        $this->configEvent->expects($this->once())
            ->method('getFileContent')
            ->willReturn('-----BEGIN CERTIFICATE-----');

        $this->configEvent->expects($this->once())
            ->method('getConfig')
            ->with('userconfig')
            ->willReturn(
                [
                    'saml_idp_own_certificate' => $file,
                ]
            );

        $this->configEvent->expects($this->never())
            ->method('setError');

        $subscriber->onConfigSave($this->configEvent);
    }

    public function testCertificateFailsValidationIfNotValid(): void
    {
        $subscriber = new ConfigSubscriber();

        $file = $this->createMock(UploadedFile::class);
        $this->configEvent->expects($this->once())
            ->method('getFileContent')
            ->willReturn('foobar');

        $this->configEvent->expects($this->once())
            ->method('getConfig')
            ->with('userconfig')
            ->willReturn(
                [
                    'saml_idp_own_certificate' => $file,
                ]
            );

        $this->configEvent->expects($this->once())
            ->method('setError')
            ->with('mautic.user.saml.certificate.invalid', [], 'userconfig', 'saml_idp_own_certificate');

        $subscriber->onConfigSave($this->configEvent);
    }

    public function testPrivateKeyPassesValidationIfValid(): void
    {
        $subscriber = new ConfigSubscriber();

        $file = $this->createMock(UploadedFile::class);
        $this->configEvent->expects($this->once())
            ->method('getFileContent')
            ->willReturn('-----BEGIN RSA PRIVATE KEY-----');

        $this->configEvent->expects($this->once())
            ->method('getConfig')
            ->with('userconfig')
            ->willReturn(
                [
                    'saml_idp_own_private_key' => $file,
                ]
            );

        $this->configEvent->expects($this->never())
            ->method('setError');

        $subscriber->onConfigSave($this->configEvent);
    }

    public function testPrivateKeyFailsValidationIfNotValid(): void
    {
        $subscriber = new ConfigSubscriber();

        $file = $this->createMock(UploadedFile::class);
        $this->configEvent->expects($this->once())
            ->method('getFileContent')
            ->willReturn('foobar');

        $this->configEvent->expects($this->once())
            ->method('getConfig')
            ->with('userconfig')
            ->willReturn(
                [
                    'saml_idp_own_private_key' => $file,
                ]
            );

        $this->configEvent->expects($this->once())
            ->method('setError')
            ->with('mautic.user.saml.private_key.invalid', [], 'userconfig', 'saml_idp_own_private_key');

        $subscriber->onConfigSave($this->configEvent);
    }

    public function testEncryptedPrivateKeyPassesValidationIfValid(): void
    {
        $subscriber = new ConfigSubscriber();

        $file = $this->createMock(UploadedFile::class);
        $key  = <<<KEY_WRAP
-----BEGIN ENCRYPTED PRIVATE KEY-----
MIICxjBABgkqhkiG9w0BBQ0wMzAbBgkqhkiG9w0BBQwwDgQI+tgT3QFhjEgCAggA
MBQGCCqGSIb3DQMHBAg1HGrSHb7zVwSCAoBG89zqAxAx+vPvhQVW6dfJFBTSpAGq
RRlfiygr0iwLCxpnoT5ZY/0Od28uvB/HkAb0cCg6sYvNVvDDDitspCb3vIU/gPmN
h8VEtgxDlXEE1YwinDn0BfO5tRoC4SfOrJRsrRdX4qc7xk80Kk74cbzLJy6VESQ5
u/ZY8Yw2E9exIH4rlZ+dBfs4JekQS/fLbiXGdtSdVF6RO0e9IhRsb3dUhSqqJafl
6+VkptheXGFwTySf8c5yLmMUC/z2NCGxO5G91uUxshEQvdQ2NM0kvt9AG3TcwZ6g
obqjHWfVEmT9j9Raqbkn+9desalKRONbe1lI0IL1vcIBXWduQUNQK1dT9ghbwGT+
vegw86xlMtLNPrvFxc3G6ZVtid/T1wDXZEKKCqp9Uei3fh0SLKy2witjt5yvabbc
QhXOS2hVA9zSQ0IGcycWzxeaf+Nb826xvvAV9Tf1GhreT6vQWC8BxDCVr9w31h6y
LnC5R2dxzom1d/kiBqlcGh1u9d+2OyCFpfYvymWlKcXYYO8E2Nu2oK4IlzB0YdJp
8/y7PLGzaipf57e8srGMGvLMKwQTLCvaDu/gxl4d+awwTZ9UsG2qMOKZSkIU4IMu
uP0CDSbRxbHBq9gY4ZiECuAxmmoadZXmNOu8iGQWa9rmoCqW5XmAgwvcnfxDWawI
ZTJUgHdZ6Tfe2jPFJjSZVoU/en0W5BQXgy1u3BDX68C8nAfZ4xmeyELcMub9hTYb
HTDmYIBozZNIcYHB6OGZnURuGeofMVJqMkNfnEuSuoCJsXGIhLznDKp8G00F9eR2
1L+B0ZUZv/O82qEGzC/IX7+CFmSDStV9R400cDvi+8BsdMMB+WV6SMnK
-----END ENCRYPTED PRIVATE KEY-----
KEY_WRAP;

        $this->configEvent->expects($this->once())
            ->method('getFileContent')
            ->willReturn($key);

        $this->configEvent->expects($this->once())
            ->method('getConfig')
            ->with('userconfig')
            ->willReturn(
                [
                    'saml_idp_own_private_key' => $file,
                    'saml_idp_own_password'    => 'abc123',
                ]
            );

        $this->configEvent->expects($this->never())
            ->method('setError');

        $subscriber->onConfigSave($this->configEvent);
    }

    public function testPrivateKeyFailsValidationIfPasswordNotValid(): void
    {
        $subscriber = new ConfigSubscriber();

        $file = $this->createMock(UploadedFile::class);
        $key  = <<<KEY_WRAP
-----BEGIN ENCRYPTED PRIVATE KEY-----
MIICxjBABgkqhkiG9w0BBQ0wMzAbBgkqhkiG9w0BBQwwDgQI+tgT3QFhjEgCAggA
MBQGCCqGSIb3DQMHBAg1HGrSHb7zVwSCAoBG89zqAxAx+vPvhQVW6dfJFBTSpAGq
RRlfiygr0iwLCxpnoT5ZY/0Od28uvB/HkAb0cCg6sYvNVvDDDitspCb3vIU/gPmN
h8VEtgxDlXEE1YwinDn0BfO5tRoC4SfOrJRsrRdX4qc7xk80Kk74cbzLJy6VESQ5
u/ZY8Yw2E9exIH4rlZ+dBfs4JekQS/fLbiXGdtSdVF6RO0e9IhRsb3dUhSqqJafl
6+VkptheXGFwTySf8c5yLmMUC/z2NCGxO5G91uUxshEQvdQ2NM0kvt9AG3TcwZ6g
obqjHWfVEmT9j9Raqbkn+9desalKRONbe1lI0IL1vcIBXWduQUNQK1dT9ghbwGT+
vegw86xlMtLNPrvFxc3G6ZVtid/T1wDXZEKKCqp9Uei3fh0SLKy2witjt5yvabbc
QhXOS2hVA9zSQ0IGcycWzxeaf+Nb826xvvAV9Tf1GhreT6vQWC8BxDCVr9w31h6y
LnC5R2dxzom1d/kiBqlcGh1u9d+2OyCFpfYvymWlKcXYYO8E2Nu2oK4IlzB0YdJp
8/y7PLGzaipf57e8srGMGvLMKwQTLCvaDu/gxl4d+awwTZ9UsG2qMOKZSkIU4IMu
uP0CDSbRxbHBq9gY4ZiECuAxmmoadZXmNOu8iGQWa9rmoCqW5XmAgwvcnfxDWawI
ZTJUgHdZ6Tfe2jPFJjSZVoU/en0W5BQXgy1u3BDX68C8nAfZ4xmeyELcMub9hTYb
HTDmYIBozZNIcYHB6OGZnURuGeofMVJqMkNfnEuSuoCJsXGIhLznDKp8G00F9eR2
1L+B0ZUZv/O82qEGzC/IX7+CFmSDStV9R400cDvi+8BsdMMB+WV6SMnK
-----END ENCRYPTED PRIVATE KEY-----
KEY_WRAP;

        $this->configEvent->expects($this->once())
            ->method('getFileContent')
            ->willReturn($key);

        $this->configEvent->expects($this->once())
            ->method('getConfig')
            ->with('userconfig')
            ->willReturn(
                [
                    'saml_idp_own_private_key' => $file,
                    'saml_idp_own_password'    => '123abc',
                ]
            );

        $this->configEvent->expects($this->once())
            ->method('setError')
            ->with('mautic.user.saml.private_key.password_invalid', [], 'userconfig', 'saml_idp_own_password');

        $subscriber->onConfigSave($this->configEvent);
    }

    public function testPrivateKeyFailsValidationIfPasswordMissing(): void
    {
        $subscriber = new ConfigSubscriber();

        $file = $this->createMock(UploadedFile::class);
        $key  = <<<KEY_WRAP
-----BEGIN ENCRYPTED PRIVATE KEY-----
MIICxjBABgkqhkiG9w0BBQ0wMzAbBgkqhkiG9w0BBQwwDgQI+tgT3QFhjEgCAggA
MBQGCCqGSIb3DQMHBAg1HGrSHb7zVwSCAoBG89zqAxAx+vPvhQVW6dfJFBTSpAGq
RRlfiygr0iwLCxpnoT5ZY/0Od28uvB/HkAb0cCg6sYvNVvDDDitspCb3vIU/gPmN
h8VEtgxDlXEE1YwinDn0BfO5tRoC4SfOrJRsrRdX4qc7xk80Kk74cbzLJy6VESQ5
u/ZY8Yw2E9exIH4rlZ+dBfs4JekQS/fLbiXGdtSdVF6RO0e9IhRsb3dUhSqqJafl
6+VkptheXGFwTySf8c5yLmMUC/z2NCGxO5G91uUxshEQvdQ2NM0kvt9AG3TcwZ6g
obqjHWfVEmT9j9Raqbkn+9desalKRONbe1lI0IL1vcIBXWduQUNQK1dT9ghbwGT+
vegw86xlMtLNPrvFxc3G6ZVtid/T1wDXZEKKCqp9Uei3fh0SLKy2witjt5yvabbc
QhXOS2hVA9zSQ0IGcycWzxeaf+Nb826xvvAV9Tf1GhreT6vQWC8BxDCVr9w31h6y
LnC5R2dxzom1d/kiBqlcGh1u9d+2OyCFpfYvymWlKcXYYO8E2Nu2oK4IlzB0YdJp
8/y7PLGzaipf57e8srGMGvLMKwQTLCvaDu/gxl4d+awwTZ9UsG2qMOKZSkIU4IMu
uP0CDSbRxbHBq9gY4ZiECuAxmmoadZXmNOu8iGQWa9rmoCqW5XmAgwvcnfxDWawI
ZTJUgHdZ6Tfe2jPFJjSZVoU/en0W5BQXgy1u3BDX68C8nAfZ4xmeyELcMub9hTYb
HTDmYIBozZNIcYHB6OGZnURuGeofMVJqMkNfnEuSuoCJsXGIhLznDKp8G00F9eR2
1L+B0ZUZv/O82qEGzC/IX7+CFmSDStV9R400cDvi+8BsdMMB+WV6SMnK
-----END ENCRYPTED PRIVATE KEY-----
KEY_WRAP;

        $this->configEvent->expects($this->once())
            ->method('getFileContent')
            ->willReturn($key);

        $this->configEvent->expects($this->once())
            ->method('getConfig')
            ->with('userconfig')
            ->willReturn(
                [
                    'saml_idp_own_private_key' => $file,
                    'saml_idp_own_password'    => '',
                ]
            );

        $this->configEvent->expects($this->once())
            ->method('setError')
            ->with('mautic.user.saml.private_key.password_needed', [], 'userconfig', 'saml_idp_own_password');

        $subscriber->onConfigSave($this->configEvent);
    }
}
