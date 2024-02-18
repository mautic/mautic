<?php

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
     *
     * @return $this
     */
    public function setItemId($value)
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
     * @param mixed  $value
     *
     * @return $this
     */
    public function setOption($key, $value)
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
