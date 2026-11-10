<?php

declare(strict_types=1);

namespace Mautic\StatsBundle\Event\Options;

class FetchOptions
{
    private array $options = [];

    /**
     * @var int|null
     */
    private $itemId;

    /**
     * @param int $value
     */
    public function setItemId($value): static
    {
        $this->itemId = $value;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getItemId()
    {
        return $this->itemId;
    }

    /**
     * @param string $key
     */
    public function setOption($key, mixed $value): static
    {
        $this->options[$key] = $value;

        return $this;
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function getOption($key, $default = null)
    {
        return $this->options[$key] ?? $default;
    }
}
