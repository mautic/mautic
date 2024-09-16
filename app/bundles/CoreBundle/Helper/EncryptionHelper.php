<?php

namespace Mautic\CoreBundle\Helper;

use Mautic\CoreBundle\Security\Cryptography\Cipher\Symmetric\SymmetricCipherInterface;
use Mautic\CoreBundle\Security\Exception\Cryptography\Symmetric\InvalidDecryptionException;

class EncryptionHelper
{
    /** @var SymmetricCipherInterface[] */
    private $availableCiphers;

    /** @var string */
    private $key;

    public function __construct(
        CoreParametersHelper $coreParametersHelper
    ) {
        $nonCipherArgs = 1;
        for ($i = $nonCipherArgs; $i < func_num_args(); ++$i) {
            $possibleCipher = func_get_arg($i);
            if (!($possibleCipher instanceof SymmetricCipherInterface)) {
                throw new \InvalidArgumentException(get_class($possibleCipher).' has to implement '.SymmetricCipherInterface::class);
            }
            if (!$possibleCipher->isSupported()) {
                continue;
            }
            $this->availableCiphers[] = $possibleCipher;
        }

        if (!$this->availableCiphers || 0 === count($this->availableCiphers)) {
            throw new \RuntimeException('None of possible cryptography libraries is supported');
        }

        $this->key = $coreParametersHelper->get('mautic.secret_key');
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
        $encryptionCipher = reset($this->availableCiphers);
        $initVector       = $encryptionCipher->getRandomInitVector();
        $encrypted        = $encryptionCipher->encrypt(serialize($data), $this->key, $initVector);

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
        $mainTried        = false;
        foreach ($this->availableCiphers as $availableCipher) {
            if ($mainDecryptOnly && $mainTried) {
                return false;
            }
            try {
                return Serializer::decode($availableCipher->decrypt($encryptedMessage, $this->key, $initVector));
            } catch (InvalidDecryptionException $ex) {
            }
            $mainTried = true;
        }

        return false;
    }
}
