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

use Mautic\CoreBundle\Security\Cryptography\Cipher\Symmetric\SymmetricCipherInterface;
use Mautic\CoreBundle\Security\Exception\Cryptography\Symmetric\InvalidDecryptionException;

/**
 * Class EncryptionHelper.
 */
class EncryptionHelper
{
    /** @var SymmetricCipherInterface[] */
    private $availableCiphers;

    /** @var string */
    private $key;

    /**
     * EncryptionHelper constructor.
     *
     * @param CoreParametersHelper          $coreParametersHelper
     * @param SymmetricCipherInterface      $possibleCipher1
     * @param SymmetricCipherInterface|null $possibleCipher2
     */
    public function __construct(
        CoreParametersHelper $coreParametersHelper,
        SymmetricCipherInterface $possibleCipher1,
        SymmetricCipherInterface $possibleCipher2 = null
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
        if (count($this->availableCiphers) === 0) {
            throw new \RuntimeException('None of possible cryptography libraries is supported');
        }
        $this->key = $coreParametersHelper->getParameter('mautic.secret_key');
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
                return unserialize($availableCipher->decrypt($encryptedMessage, $this->key, $initVector));
            } catch (InvalidDecryptionException $ex) {
            }
            $mainTried = true;
        }

        return false;
    }
}
