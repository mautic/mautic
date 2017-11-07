<?php

namespace Mautic\CoreBundle\Security\Cryptography\Cipher\Symmetric;

use Mautic\CoreBundle\Security\Exception\Cryptography\Symmetric\InvalidDecryptionException;

/**
 * Interface ISymmetricCryptography
 * @package Mautic\CoreBundle\Security\Cryptography\Cipher\Symmetric
 */
interface ISymmetricCipher
{
    /**
     * @param string $secretMessage
     * @param string $key
     * @param string $randomInitVector
     *
     * @return string
     */
    public function encrypt($secretMessage, $key, $randomInitVector);

    /**
     * @param string $encryptedMessage
     * @param string $key
     * @param string $originalInitVector
     *
     * @return string
     * @throws InvalidDecryptionException
     */
    public function decrypt($encryptedMessage, $key, $originalInitVector);

    /**
     * @return string
     */
    public function getRandomInitVector();
}
