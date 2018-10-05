<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Security\Cryptography\Cipher\Symmetric;

use Mautic\CoreBundle\Security\Exception\Cryptography\Symmetric\InvalidDecryptionException;

/**
 * Class McryptCryptography.
 *
 * @deprecated Use \Mautic\CoreBundle\Security\Cryptography\Cipher\Symmetric\OpenSSLCipher instead
 */
class McryptCipher implements SymmetricCipherInterface
{
    /**
     * @var string
     */
    private $cipher;

    /**
     * @var string
     */
    private $mode;

    public function __construct()
    {
        // Do not use Mcrypt constants if the extension is not installed
        if (!$this->isSupported()) {
            return;
        }

        $this->cipher = MCRYPT_RIJNDAEL_256;
        $this->mode   = MCRYPT_MODE_CBC;
    }

    /**
     * @param string $secretMessage
     * @param string $key
     * @param string $randomInitVector
     *
     * @return string
     */
    public function encrypt($secretMessage, $key, $randomInitVector)
    {
        $this->checkSupport();

        $key  = pack('H*', $key);
        $data = $secretMessage.$this->getHash($secretMessage, $this->getHashKey($key));

        return mcrypt_encrypt($this->cipher, $key, $data, $randomInitVector);
    }

    /**
     * @param string $encryptedMessage
     * @param string $key
     * @param string $originalInitVector
     *
     * @return string
     *
     * @throws InvalidDecryptionException
     */
    public function decrypt($encryptedMessage, $key, $originalInitVector)
    {
        $this->checkSupport();

        if (strlen($originalInitVector) !== $this->getInitVectorSize()) {
            throw new InvalidDecryptionException();
        }
        $key           = pack('H*', $key);
        $decrypted     = trim(mcrypt_decrypt($this->cipher, $key, $encryptedMessage, $this->mode, $originalInitVector));
        $sha256Length  = 64;
        $secretMessage = substr($decrypted, 0, -$sha256Length);
        $originalHash  = substr($decrypted, -$sha256Length);

        $newHash = $this->getHash($secretMessage, $this->getHashKey($key));
        if (!hash_equals($originalHash, $newHash)) {
            throw new InvalidDecryptionException();
        }

        return $secretMessage;
    }

    /**
     * @return string
     */
    public function getRandomInitVector()
    {
        $this->checkSupport();

        return mcrypt_create_iv($this->getInitVectorSize(), MCRYPT_DEV_URANDOM);
    }

    /**
     * @return bool
     */
    public function isSupported()
    {
        return extension_loaded('mcrypt');
    }

    /**
     * @return int
     */
    private function getInitVectorSize()
    {
        return mcrypt_get_iv_size($this->cipher, $this->mode);
    }

    /**
     * @param string $data
     * @param string $key
     *
     * @return string
     */
    private function getHash($data, $key)
    {
        return hash_hmac('sha256', $data, $key);
    }

    /**
     * @param $binaryKey
     *
     * @return string
     */
    private function getHashKey($binaryKey)
    {
        $hexKey = bin2hex($binaryKey);
        // Get second half of hexKey version (stable but different than original key)
        return substr($hexKey, -32);
    }

    /**
     * Throws an exception if Mcrypt is not supported.
     *
     * @throws \BadMethodCallException
     */
    private function checkSupport()
    {
        if (!$this->isSupported()) {
            throw new \BadMethodCallException('Mcrypt PHP extension is not installed. Use OpenSSL.');
        }
    }
}
