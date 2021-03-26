<?php

declare(strict_types=1);

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\EncryptionHelper;
use Mautic\CoreBundle\Security\Cryptography\Cipher\Symmetric\OpenSSLCipher;
use Mautic\CoreBundle\Security\Cryptography\Cipher\Symmetric\SymmetricCipherInterface;
use Mautic\CoreBundle\Security\Exception\Cryptography\Symmetric\InvalidDecryptionException;
use PHPUnit\Framework\MockObject\MockObject;

class EncryptionHelperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|CoreParametersHelper
     */
    private $coreParametersHelperMock;

    /**
     * @var MockObject|OpenSSLCipher
     */
    private $mainCipherMock;

    /**
     * @var MockObject|SymmetricCipherInterface
     */
    private $secondaryCipherMock;

    /**
     * @var string
     */
    private $key = 'totallySecretKeyHere';

    protected function setUp(): void
    {
        parent::setUp();
        $this->coreParametersHelperMock = $this->createMock(CoreParametersHelper::class);
        $this->mainCipherMock           = $this->createMock(OpenSSLCipher::class);
        $this->secondaryCipherMock      = $this->createMock(SymmetricCipherInterface::class);
    }

    public function testEncryptMainSupported(): void
    {
        $initVector       = 'totallyRandomInitializationVector';
        $secretMessage    = 'totallySecretMessage';
        $encryptedMessage = 'encryptionIsMagical';
        $expectedReturn   = base64_encode($encryptedMessage).'|'.base64_encode($initVector);

        $this->mainCipherMock->expects($this->once())
            ->method('isSupported')
            ->willReturn(true);

        $this->secondaryCipherMock->expects($this->once())
            ->method('isSupported')
            ->willReturn(true);

        $this->coreParametersHelperMock->expects($this->once())
            ->method('get')
            ->with('mautic.secret_key')
            ->willReturn($this->key);

        $this->mainCipherMock->expects($this->once())
            ->method('getRandomInitVector')
            ->willReturn($initVector);

        $this->mainCipherMock->expects($this->once())
            ->method('encrypt')
            ->with(serialize($secretMessage), $this->key, $initVector)
            ->willReturn($encryptedMessage);

        $encryptionHelper = $this->getEncryptionHelper();
        $actualReturn     = $encryptionHelper->encrypt($secretMessage);
        $this->assertSame($expectedReturn, $actualReturn);
    }

    public function testEncryptMainNotSupported(): void
    {
        $initVector       = 'totallyRandomInitializationVector';
        $secretMessage    = 'totallySecretMessage';
        $encryptedMessage = 'encryptionIsMagical';
        $expectedReturn   = base64_encode($encryptedMessage).'|'.base64_encode($initVector);

        $this->mainCipherMock->expects($this->once())
            ->method('isSupported')
            ->willReturn(false);

        $this->secondaryCipherMock->expects($this->once())
            ->method('isSupported')
            ->willReturn(true);

        $this->coreParametersHelperMock->expects($this->once())
            ->method('get')
            ->with('mautic.secret_key')
            ->willReturn($this->key);

        $this->secondaryCipherMock->expects($this->once())
            ->method('getRandomInitVector')
            ->willReturn($initVector);

        $this->secondaryCipherMock->expects($this->once())
            ->method('encrypt')
            ->with(serialize($secretMessage), $this->key, $initVector)
            ->willReturn($encryptedMessage);

        $encryptionHelper = $this->getEncryptionHelper();
        $actualReturn     = $encryptionHelper->encrypt($secretMessage);
        $this->assertSame($expectedReturn, $actualReturn);
    }

    public function testDecryptMain(): void
    {
        $toDecrypt      = 'ZW5jcnlwdGlvbklzTWFnaWNhbA==|dG90YWxseVJhbmRvbUluaXRpYWxpemF0aW9uVmVjdG9y';
        $expectedReturn = 'totallySecretMessage';

        $this->mainCipherMock->expects($this->once())
            ->method('isSupported')
            ->willReturn(true);

        $this->secondaryCipherMock->expects($this->once())
            ->method('isSupported')
            ->willReturn(true);

        $this->coreParametersHelperMock->expects($this->once())
            ->method('get')
            ->with('mautic.secret_key')
            ->willReturn($this->key);

        $this->mainCipherMock->expects($this->once())
            ->method('decrypt')
            ->with('encryptionIsMagical', $this->key, 'totallyRandomInitializationVector')
            ->willReturn('s:20:"totallySecretMessage";');

        $encryptionHelper = $this->getEncryptionHelper();
        $actualReturn     = $encryptionHelper->decrypt($toDecrypt);
        $this->assertSame($expectedReturn, $actualReturn);
    }

    public function testDecryptSecondary(): void
    {
        $toDecrypt      = 'ZW5jcnlwdGlvbklzTWFnaWNhbA==|dG90YWxseVJhbmRvbUluaXRpYWxpemF0aW9uVmVjdG9y';
        $expectedReturn = 'totallySecretMessage';

        $this->mainCipherMock->expects($this->once())
            ->method('isSupported')
            ->willReturn(true);

        $this->secondaryCipherMock->expects($this->once())
            ->method('isSupported')
            ->willReturn(true);

        $this->coreParametersHelperMock->expects($this->once())
            ->method('get')
            ->with('mautic.secret_key')
            ->willReturn($this->key);

        $this->mainCipherMock->expects($this->once())
            ->method('decrypt')
            ->with('encryptionIsMagical', $this->key, 'totallyRandomInitializationVector')
            ->willThrowException(new InvalidDecryptionException());

        $this->secondaryCipherMock->expects($this->once())
            ->method('decrypt')
            ->with('encryptionIsMagical', $this->key, 'totallyRandomInitializationVector')
            ->willReturn('s:20:"totallySecretMessage";');

        $encryptionHelper = $this->getEncryptionHelper();
        $actualReturn     = $encryptionHelper->decrypt($toDecrypt, false);
        $this->assertSame($expectedReturn, $actualReturn);
    }

    public function testDecryptFalse(): void
    {
        $toDecrypt = 'ZW5jcnlwdGlvbklzTWFnaWNhbA==|dG90YWxseVJhbmRvbUluaXRpYWxpemF0aW9uVmVjdG9y';

        $this->mainCipherMock->expects($this->once())
            ->method('isSupported')
            ->willReturn(true);

        $this->secondaryCipherMock->expects($this->once())
            ->method('isSupported')
            ->willReturn(true);

        $this->coreParametersHelperMock->expects($this->once())
            ->method('get')
            ->with('mautic.secret_key')
            ->willReturn($this->key);

        $this->mainCipherMock->expects($this->once())
            ->method('decrypt')
            ->with('encryptionIsMagical', $this->key, 'totallyRandomInitializationVector')
            ->willThrowException(new InvalidDecryptionException());

        $this->secondaryCipherMock->expects($this->once())
            ->method('decrypt')
            ->with('encryptionIsMagical', $this->key, 'totallyRandomInitializationVector')
            ->willThrowException(new InvalidDecryptionException());

        $encryptionHelper = $this->getEncryptionHelper();
        $actualReturn     = $encryptionHelper->decrypt($toDecrypt, false);
        $this->assertFalse($actualReturn);
    }

    public function testMainSupported(): void
    {
        $this->mainCipherMock->expects($this->once())
            ->method('isSupported')
            ->willReturn(true);

        $this->secondaryCipherMock->expects($this->once())
            ->method('isSupported')
            ->willReturn(false);

        $this->coreParametersHelperMock->expects($this->once())
            ->method('get')
            ->with('mautic.secret_key')
            ->willReturn($this->key);

        $this->getEncryptionHelper();
    }

    public function testSecondarySupported(): void
    {
        $this->mainCipherMock->expects($this->once())
            ->method('isSupported')
            ->willReturn(false);

        $this->secondaryCipherMock->expects($this->once())
            ->method('isSupported')
            ->willReturn(true);

        $this->coreParametersHelperMock->expects($this->once())
            ->method('get')
            ->with('mautic.secret_key')
            ->willReturn($this->key);

        $this->getEncryptionHelper();
    }

    public function testNoneSupported(): void
    {
        $this->mainCipherMock->expects($this->once())
            ->method('isSupported')
            ->willReturn(false);

        $this->secondaryCipherMock->expects($this->once())
            ->method('isSupported')
            ->willReturn(false);

        $this->expectException(\RuntimeException::class);
        $this->getEncryptionHelper();
    }

    private function getEncryptionHelper(): EncryptionHelper
    {
        return new EncryptionHelper(
            $this->coreParametersHelperMock,
            $this->mainCipherMock,
            $this->secondaryCipherMock
        );
    }
}
