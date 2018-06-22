<?php
/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @author      Jan Kozak <galvani78@gmail.com>
 */

namespace MauticPlugin\MauticIntegrationsBundle\Facade;

use Mautic\CoreBundle\Helper\EncryptionHelper;

class EncryptionService
{
    /**
     * @var EncryptionHelper
     */
    private $encryptionHelper;

    /**
     * EncryptionService constructor.
     *
     * @param EncryptionHelper $encryptionHelper
     */
    public function __construct(EncryptionHelper $encryptionHelper)
    {
        $this->encryptionHelper = $encryptionHelper;
    }

    /**
     * @param mixed $keys
     *
     * @return array|string
     */
    public function encrypt($keys)
    {
        if (!is_array($keys)) {
            return $this->encryptionHelper->encrypt($keys);
        }

        foreach ($keys as $name => $key) {
            $keys[$name] = $this->encryptionHelper->encrypt($key);
        }

        return $keys;
    }

    /**
     * @param      $keys
     * @param bool $onlyPrimaryCipher
     *
     * @return array|string
     */
    public function decrypt($keys, $onlyPrimaryCipher = false)
    {
        if (!is_array($keys)) {
            return $this->encryptionHelper->decrypt($keys, $onlyPrimaryCipher);
        }

        foreach ($keys as $name => $key) {
            $keys[$name] = $this->encryptionHelper->decrypt($key, $onlyPrimaryCipher);
        }

        return $keys;
    }
}
