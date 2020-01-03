<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\IntegrationsBundle\Facade;

use Mautic\CoreBundle\Helper\EncryptionHelper;

class EncryptionService
{
    /**
     * @var EncryptionHelper
     */
    private $encryptionHelper;

    /**
     * EncryptionService constructor.
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
