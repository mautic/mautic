<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\Mapper;

final class Category
{
    /**
     * @param string $category
     * @param string $type
     * @param bool   $isPermanent
     */
    public function __construct(
        private $category,
        private $type,
        private $isPermanent,
    ) {
    }

    /**
     * @return string
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return bool
     */
    public function isPermanent()
    {
        return $this->isPermanent;
    }
}
