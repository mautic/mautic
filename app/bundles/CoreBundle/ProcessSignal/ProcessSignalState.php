<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\ProcessSignal;

use Mautic\CoreBundle\ProcessSignal\Exception\InvalidStateException;

class ProcessSignalState implements \Stringable
{
    private const START_TAG = '<<<StartOfState>>>';
    private const END_TAG   = '<<<EndOfState>>>';

    /**
     * @param mixed[] $data
     */
    public function __construct(private array $data)
    {
    }

    /**
     * @throws InvalidStateException
     */
    public static function fromString(string $string): self
    {
        preg_match('/'.self::START_TAG.'(.+?)'.self::END_TAG.'/', $string, $matches);

        if (empty($matches[1])) {
            throw new InvalidStateException($string);
        }

        try {
            $data = json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new InvalidStateException($string, $e);
        }

        return new self($data);
    }

    public function __toString(): string
    {
        return sprintf(self::START_TAG.'%s'.self::END_TAG, json_encode($this->data));
    }

    /**
     * @return mixed[]
     */
    public function getData(): array
    {
        return $this->data;
    }
}
