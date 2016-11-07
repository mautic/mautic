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

use Mautic\CoreBundle\Factory\MauticFactory;

class EncryptionHelper
{
    private $key;

    /**
     * @param MauticFactory $factory
     *
     * @throws \RuntimeException if the mcrypt extension is not enabled
     */
    public function __construct(MauticFactory $factory)
    {
        // Toss an Exception back if mcrypt is not found
        if (!extension_loaded('mcrypt')) {
            throw new \RuntimeException($factory->getTranslator()->trans('mautic.core.error.no.mcrypt'));
        }

        $this->key = $factory->getParameter('secret_key');
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
     * @param $encrypt
     *
     * @return string
     */
    public function encrypt($encrypt)
    {
        $encrypt   = serialize($encrypt);
        $iv        = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC), MCRYPT_DEV_URANDOM);
        $key       = pack('H*', $this->key);
        $mac       = hash_hmac('sha256', $encrypt, substr(bin2hex($key), -32));
        $passcrypt = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $encrypt.$mac, MCRYPT_MODE_CBC, $iv);
        $encoded   = base64_encode($passcrypt).'|'.base64_encode($iv);

        return $encoded;
    }

    /**
     * Decrypt string.
     *
     * @param $decrypt
     *
     * @return bool|mixed|string
     */
    public function decrypt($decrypt)
    {
        $decrypt = explode('|', $decrypt.'|');
        $decoded = base64_decode($decrypt[0]);
        $iv      = base64_decode($decrypt[1]);
        if (strlen($iv) !== mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC)) {
            return false;
        }

        $key       = pack('H*', $this->key);
        $decrypted = trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, $decoded, MCRYPT_MODE_CBC, $iv));
        $mac       = substr($decrypted, -64);
        $decrypted = substr($decrypted, 0, -64);
        $calcmac   = hash_hmac('sha256', $decrypted, substr(bin2hex($key), -32));
        if ($calcmac !== $mac) {
            return false;
        }
        $decrypted = unserialize($decrypted);

        return $decrypted;
    }
}
