<?php

namespace Mautic\CoreBundle\Helper;

use Mautic\CoreBundle\Exception\InvalidDecodedStringException;
use Mautic\CoreBundle\Helper\Clickthrough\ClickthroughKeyConverter;

class ClickthroughHelper
{
    use ClickthroughHelperBCTrait;

    private ClickthroughKeyConverter $shortKeyConverter;

    public function __construct(ClickthroughKeyConverter $shortKeyConverter)
    {
        $this->shortKeyConverter = $shortKeyConverter;
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
    public function decode(string $string, bool $urlDecode = true): array
    {
        $raw     = $urlDecode ? urldecode($string) : $string;
        $decoded = base64_decode($raw);

        if (empty($decoded)) {
            return [];
        }

        if ($this->isSerialized($decoded)) {
            return $this->shortKeyConverter->unpack(Serializer::decode($decoded));
        }

        if ($this->isIgBinaryEnabled()) {
            try {
                return $this->shortKeyConverter->unpack(igbinary_unserialize($decoded));
            } catch (\Exception $e) {
            }
        }

        throw new InvalidDecodedStringException($raw);
    }

    /**
     * @param string $string
     */
    public function isSerialized($string): bool
    {
        try {
            $data = @unserialize($string);

            return false !== $data || 'b:0;' === $string;
        } catch (\Exception $exception) {
        }

        return false;
    }

    protected function isIgBinaryEnabled(): bool
    {
        return function_exists('igbinary_serialize');
    }
}
