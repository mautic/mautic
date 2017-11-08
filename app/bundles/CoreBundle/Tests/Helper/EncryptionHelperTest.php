<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests\Helper;

use Mautic\CoreBundle\Helper\EncryptionHelper;
use Mautic\CoreBundle\Security\Cryptography\Cipher\Symmetric\McryptCipher;
use Mautic\CoreBundle\Security\Cryptography\Cipher\Symmetric\OpenSSLCipher;
use Mautic\CoreBundle\Security\Exception\Cryptography\Symmetric\InvalidDecryptionException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EncryptionHelperTest.
 */
class EncryptionHelperTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $containerMock;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $mainCipherMock;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $secondaryCipherMock;

    /** @var string */
    private $key = 'totallySecretKeyHere';

    protected function setUp()
    {
        $this->containerMock       = $this->createMock(ContainerInterface::class);
        $this->mainCipherMock      = $this->createMock(OpenSSLCipher::class);
        $this->secondaryCipherMock = $this->createMock(McryptCipher::class);

        $this->containerMock->method('getParameter')
            ->with('mautic.secret_key')
            ->willReturn($this->key);
    }

    public function testEncryptMainSupported()
    {
        $initVector       = 'totallyRandomInitializationVector';
        $secretMessage    = 'totallySecretMessage';
        $encryptedMessage = 'encryptionIsMagical';
        $expectedReturn   = base64_encode($encryptedMessage).'|'.base64_encode($initVector);

        $this->expectCiphersIsSupported(true);

        $this->mainCipherMock->expects($this->at(1))
            ->method('getRandomInitVector')
            ->willReturn($initVector);
        $this->mainCipherMock->expects($this->at(2))
            ->method('encrypt')
            ->with(serialize($secretMessage), $this->key, $initVector)
            ->willReturn($encryptedMessage);
        $encryptionHelper = $this->getEncryptionHelper();
        $actualReturn     = $encryptionHelper->encrypt($secretMessage);
        $this->assertSame($expectedReturn, $actualReturn);
    }

    public function testEncryptMainNotSupported()
    {
        $initVector       = 'totallyRandomInitializationVector';
        $secretMessage    = 'totallySecretMessage';
        $encryptedMessage = 'encryptionIsMagical';
        $expectedReturn   = base64_encode($encryptedMessage).'|'.base64_encode($initVector);

        $this->expectCiphersIsSupported(false, true);

        $this->secondaryCipherMock->expects($this->at(1))
            ->method('getRandomInitVector')
            ->willReturn($initVector);
        $this->secondaryCipherMock->expects($this->at(2))
            ->method('encrypt')
            ->with(serialize($secretMessage), $this->key, $initVector)
            ->willReturn($encryptedMessage);
        $encryptionHelper = $this->getEncryptionHelper();
        $actualReturn     = $encryptionHelper->encrypt($secretMessage);
        $this->assertSame($expectedReturn, $actualReturn);
    }

    public function testDecryptMain()
    {
        $toDecrypt      = 'ZW5jcnlwdGlvbklzTWFnaWNhbA==|dG90YWxseVJhbmRvbUluaXRpYWxpemF0aW9uVmVjdG9y';
        $expectedReturn = 'totallySecretMessage';

        $this->expectCiphersIsSupported();
        $this->mainCipherMock->expects($this->at(1))
            ->method('decrypt')
            ->with('encryptionIsMagical', $this->key, 'totallyRandomInitializationVector')
            ->willReturn('s:20:"totallySecretMessage";');
        $encryptionHelper = $this->getEncryptionHelper();
        $actualReturn     = $encryptionHelper->decrypt($toDecrypt);
        $this->assertSame($expectedReturn, $actualReturn);
    }

    public function testDecryptSecondary()
    {
        $toDecrypt      = 'ZW5jcnlwdGlvbklzTWFnaWNhbA==|dG90YWxseVJhbmRvbUluaXRpYWxpemF0aW9uVmVjdG9y';
        $expectedReturn = 'totallySecretMessage';

        $this->expectCiphersIsSupported();
        $this->mainCipherMock->expects($this->at(1))
            ->method('decrypt')
            ->with('encryptionIsMagical', $this->key, 'totallyRandomInitializationVector')
            ->willThrowException(new InvalidDecryptionException());
        $this->secondaryCipherMock->expects($this->at(1))
            ->method('decrypt')
            ->with('encryptionIsMagical', $this->key, 'totallyRandomInitializationVector')
            ->willReturn('s:20:"totallySecretMessage";');
        $encryptionHelper = $this->getEncryptionHelper();
        $actualReturn     = $encryptionHelper->decrypt($toDecrypt, false);
        $this->assertSame($expectedReturn, $actualReturn);
    }

    public function testDecryptFalse()
    {
        $toDecrypt = 'ZW5jcnlwdGlvbklzTWFnaWNhbA==|dG90YWxseVJhbmRvbUluaXRpYWxpemF0aW9uVmVjdG9y';

        $this->expectCiphersIsSupported();
        $this->mainCipherMock->expects($this->at(1))
            ->method('decrypt')
            ->with('encryptionIsMagical', $this->key, 'totallyRandomInitializationVector')
            ->willThrowException(new InvalidDecryptionException());
        $this->secondaryCipherMock->expects($this->at(1))
            ->method('decrypt')
            ->with('encryptionIsMagical', $this->key, 'totallyRandomInitializationVector')
            ->willThrowException(new InvalidDecryptionException());
        $encryptionHelper = $this->getEncryptionHelper();
        $actualReturn     = $encryptionHelper->decrypt($toDecrypt, false);
        $this->assertFalse($actualReturn);
    }

    public function testMainSupported()
    {
        $this->expectCiphersIsSupported(true, false);
        $this->getEncryptionHelper();
    }

    public function testSecondarySupported()
    {
        $this->expectCiphersIsSupported(false, true);
        $this->getEncryptionHelper();
    }

    public function testNoneSupported()
    {
        $this->expectCiphersIsSupported(false, false);
        $this->expectException(\RuntimeException::class);
        $this->getEncryptionHelper();
    }

    /**
     * @param bool $mainSupported
     * @param bool $secondarySupported
     */
    private function expectCiphersIsSupported($mainSupported = true, $secondarySupported = true)
    {
        $this->mainCipherMock->expects($this->at(0))
            ->method('isSupported')
            ->willReturn($mainSupported);
        $this->secondaryCipherMock->expects($this->at(0))
            ->method('isSupported')
            ->willReturn($secondarySupported);
    }

    /**
     * @return EncryptionHelper
     */
    private function getEncryptionHelper()
    {
        return new EncryptionHelper(
            $this->containerMock,
            $this->mainCipherMock,
            $this->secondaryCipherMock
        );
    }
}
