<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 *
 * http://www.warpconduit.net/2013/04/14/highly-secure-data-encryption-decryption-made-easy-with-php-mcrypt-rijndael-256-and-cbc/
 */

namespace Mautic\CoreBundle\Helper;

use Mautic\CoreBundle\Security\Cryptography\Cipher\Symmetric\McryptCipher;
use Mautic\CoreBundle\Security\Cryptography\Cipher\Symmetric\OpenSSLCipher;
use Mautic\CoreBundle\Security\Exception\Cryptography\Symmetric\InvalidDecryptionException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EncryptionHelper.
 */
class EncryptionHelper
{
    /** @var McryptCipher */
    private $mcryptCipher;

    /** @var OpenSSLCipher */
    private $openSSLCipher;

    /** @var string */
    private $key;

    /**
     * @param ContainerInterface $container
     * @param McryptCipher       $mcryptCipher
     * @param OpenSSLCipher      $openSSLCipher
     */
    public function __construct(
        ContainerInterface $container,
        McryptCipher $mcryptCipher,
        OpenSSLCipher $openSSLCipher
    ) {
        $this->mcryptCipher  = $mcryptCipher;
        $this->openSSLCipher = $openSSLCipher;
        $this->key           = $container->getParameter('mautic.secret_key');
    }

    /**
     * Returns a 64 character string.
     *
     * @return string
     */
    public static function generateKey()
    {
        return hash('sha256', uniqid(mt_rand(), true));
    }

    /**
     * Encrypt string.
     *
     * @param mixed $data
     *
     * @return string
     */
    public function encrypt($data)
    {
        $initVector = $this->openSSLCipher->getRandomInitVector();
        $encrypted  = $this->openSSLCipher->encrypt(serialize($data), $this->key, $initVector);

        return base64_encode($encrypted).'|'.base64_encode($initVector);
    }

    /**
     * Decrypt string.
     * Returns false in case of failed decryption.
     *
     * @param string $data
     * @param bool   $mainDecryptOnly
     *
     * @return mixed|false
     */
    public function decrypt($data, $mainDecryptOnly = false)
    {
        $encryptData      = explode('|', $data);
        $encryptedMessage = base64_decode($encryptData[0]);
        $initVector       = base64_decode($encryptData[1]);
        // Try OpenSSL
        try {
            return unserialize($this->openSSLCipher->decrypt($encryptedMessage, $this->key, $initVector));
        } catch (InvalidDecryptionException $ex) {
            if ($mainDecryptOnly) {
                return false;
            }
        }
        // Try Mcrypt
        try {
            return unserialize($this->mcryptCipher->decrypt($encryptedMessage, $this->key, $initVector));
        } catch (InvalidDecryptionException $ex) {
            return false;
        }
    }
}
