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

use Mautic\CoreBundle\Security\Cryptography\Cipher\Symmetric\ISymmetricCipher;
use Mautic\CoreBundle\Security\Exception\Cryptography\Symmetric\InvalidDecryptionException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EncryptionHelper.
 */
class EncryptionHelper
{
    /** @var ISymmetricCipher[] */
    private $possibleCiphers;

    /** @var string */
    private $key;

    /**
     * EncryptionHelper constructor.
     *
     * @param ContainerInterface    $container
     * @param ISymmetricCipher      $possibleCipher1
     * @param ISymmetricCipher|null $possibleCipher2
     */
    public function __construct(
        ContainerInterface $container,
        ISymmetricCipher $possibleCipher1,
        ISymmetricCipher $possibleCipher2 = null
    ) {
        $args            = func_get_args();
        $totalArgs       = func_num_args();
        $nonCipherArgs   = 1;
        $possibleCiphers = [];
        for ($i = $nonCipherArgs; $i < $totalArgs; $i++) {
            $possibleCipher = $args[$i];
            if (!($possibleCipher instanceof ISymmetricCipher)) {
                throw new \LogicException(get_class($possibleCipher).' has to implement '.ISymmetricCipher::class);
            }
            $possibleCiphers[] = $possibleCipher;
        }
        $this->possibleCiphers = $possibleCiphers;
        $this->key             = $container->getParameter('mautic.secret_key');
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
     * @throws \LogicException If there isn't supported cipher available
     */
    public function encrypt($data)
    {
        $encryptionCipher = null;
        foreach ($this->possibleCiphers as $possibleCipher) {
            if ($possibleCipher->isSupported()) {
                $encryptionCipher = $possibleCipher;
                break;
            }
        }
        if ($encryptionCipher === null) {
            throw new \LogicException('None of possible ciphers is supported');
        }
        $initVector = $encryptionCipher->getRandomInitVector();
        $encrypted  = $encryptionCipher->encrypt(serialize($data), $this->key, $initVector);

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
        foreach ($this->possibleCiphers as $possibleCipher) {
            if (!$possibleCipher->isSupported()) {
                continue;
            }
            if ($mainDecryptOnly && $mainTried) {
                return false;
            }
            try {
                return unserialize($possibleCipher->decrypt($encryptedMessage, $this->key, $initVector));
            } catch (InvalidDecryptionException $ex) {
            }
            $mainTried = true;
        }

        return false;
    }
}
