<?php

namespace Mautic\CoreBundle\Security\Cryptography\Cipher\Symmetric;

use Mautic\CoreBundle\Security\Exception\Cryptography\Symmetric\InvalidDecryptionException;

/**
 * Class McryptCryptography
 * @package Mautic\CoreBundle\Security\Cryptography\Cipher\Symmetric
 * @deprecated
 */
class McryptCipher implements ISymmetricCipher
{
    /** @var string */
    private $cipher = MCRYPT_RIJNDAEL_256;

    /** @var string */
    private $mode = MCRYPT_MODE_CBC;

    /**
     * @param string $secretMessage
     * @param string $key
     * @param string $randomInitVector
     *
     * @return string
     */
    public function encrypt($secretMessage, $key, $randomInitVector)
    {
        $key  = pack('H*', $key);
        $data = $secretMessage.$this->getHash($secretMessage, substr(bin2hex($key), -32));

        return mcrypt_encrypt($this->cipher, $key, $data, $randomInitVector);
    }

    /**
     * @param string $encryptedMessage
     * @param string $key
     * @param string $originalInitVector
     *
     * @return string
     * @throws InvalidDecryptionException
     */
    public function decrypt($encryptedMessage, $key, $originalInitVector)
    {
        if (strlen($originalInitVector) !== $this->getInitVectorSize()) {
            throw new InvalidDecryptionException();
        }
        $key       = pack('H*', $key);
        $decrypted = trim(mcrypt_decrypt($this->cipher, $key, $encryptedMessage, $this->mode, $originalInitVector));
        // 64 is length of SHA256
        $secretMessage = substr($decrypted, 0, -64);
        $originalHash  = substr($decrypted, -64);
        $newHash       = $this->getHash($secretMessage, substr(bin2hex($key), -32));
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
}

