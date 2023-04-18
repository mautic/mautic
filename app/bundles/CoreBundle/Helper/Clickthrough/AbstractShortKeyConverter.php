<?php

namespace Mautic\CoreBundle\Helper\Clickthrough;

abstract class AbstractShortKeyConverter
{
    protected array $shortKeys;

    public function pack(array $input): array
    {
        $packed = [];

        foreach ($input as $key => $value) {
            $newKey          = array_search($key, $this->shortKeys, true);
            $newKey          = false !== $newKey ? $newKey : $key;
            $packed[$newKey] = is_array($value) ? $this->pack($value) : $value;
        }

        return $packed;
    }

    public function unpack(array $input): array
    {
        $unpacked = [];

        foreach ($input as $key => $value) {
            $newKey            = $this->shortKeys[$key] ?? $key;
            $unpacked[$newKey] = is_array($value) ? $this->unpack($value) : $value;
        }

        return $unpacked;
    }
}
