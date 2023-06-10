<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Facade;

use Mautic\CoreBundle\Helper\EncryptionHelper;

class EncryptionService
{
    /**
     * EncryptionService constructor.
     */
    public function __construct(private EncryptionHelper $encryptionHelper)
    {
    }

    public function encrypt(mixed $keys): array|string
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
     * @param bool $onlyPrimaryCipher
     */
    public function decrypt($keys, $onlyPrimaryCipher = false): array|string
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
