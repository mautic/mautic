<?php

namespace Mautic\CoreBundle\Helper;

use Mautic\CoreBundle\Exception\InvalidDecodedStringException;
use Mautic\CoreBundle\Helper\Clickthrough\ClickthroughKeyConverter;

class ClickthroughHelper
{
    use ClickthroughHelperBCTrait;

    public function __construct(private ClickthroughKeyConverter $shortKeyConverter)
    {
    }

    /**
     * @param array<mixed> $data
     */
    public function encode(array $data): string
    {
        $data       = $this->shortKeyConverter->pack($data);
        $serialized =  $this->isIgBinaryEnabled() ? igbinary_serialize($data) : serialize($data);

        return urlencode(base64_encode($serialized));
    }

    /**
     * @return array<mixed>
     */
    public function decode(?string $string, bool $urlDecode = true): array
    {
        $raw     = $urlDecode ? rawurldecode($string) : $string;
        $decoded = base64_decode($raw);

        if (empty($decoded)) {
            return [];
        }

        try {
            $data = @unserialize($decoded);

            if ((false !== $data || 'b:0;' === $string) && $unserialized = Serializer::decode($decoded)) {
                return $this->shortKeyConverter->unpack($unserialized);
            }
        } catch (\Exception) {
        }

        if ($this->isIgBinaryEnabled()) {
            try {
                if ($unserialized = igbinary_unserialize($decoded)) {
                    return $this->shortKeyConverter->unpack($unserialized);
                }
            } catch (\Exception) {
            }
        }

        throw new InvalidDecodedStringException($raw);
    }

    //  This method is public for test purposes only
    protected function isIgBinaryEnabled(): bool
    {
        return function_exists('igbinary_serialize');
    }
}
