<?php

namespace Mautic\CoreBundle\Security\Cryptography\Cipher\Symmetric;

use Mautic\CoreBundle\Security\Exception\Cryptography\Symmetric\InvalidDecryptionException;

/**
 * Class OpenSSLCryptography.
 */
class OpenSSLCipher implements SymmetricCipherInterface
{
    /** @var string */
    private $cipher = 'AES-256-CBC';

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

        return openssl_encrypt($data, $this->cipher, $key, $options = 0, $randomInitVector);
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
        if (strlen($originalInitVector) !== $this->getInitVectorSize()) {
            throw new InvalidDecryptionException();
        }
        $key       = pack('H*', $key);
        $decrypted = trim(openssl_decrypt($encryptedMessage, $this->cipher, $key, $options = 0, $originalInitVector));
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
        return openssl_random_pseudo_bytes($this->getInitVectorSize());
    }

    /**
     * @return bool
     */
    public function isSupported()
    {
        if (!extension_loaded('openssl')) {
            return false;
        }
        $testForRandom = $this->getRandomInitVector();

        return $testForRandom !== false;
    }

    /**
     * @return int
     */
    private function getInitVectorSize()
    {
        return openssl_cipher_iv_length($this->cipher);
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
