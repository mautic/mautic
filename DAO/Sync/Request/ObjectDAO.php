<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */


namespace MauticPlugin\IntegrationsBundle\DAO\Sync\Request;

/**
 * Class ObjectDAO
 */
class ObjectDAO
{
    /**
     * @var string
     */
    private $object;

    /**
     * @var \DateTimeInterface|null
     */
    private $fromDateTime;

    /**
     * @var \DateTimeInterface|null
     */
    private $toDateTime;

    /**
     * @var string[]
     */
    private $fields = [];

    /**
     * ObjectDAO constructor.
     *
     * @param string             $object
     * @param \DateTimeInterface $fromDateTime
     * @param \DateTimeInterface $toDateTime
     */
    public function __construct(string $object, \DateTimeInterface $fromDateTime = null, \DateTimeInterface $toDateTime = null)
    {
        $this->object       = $object;
        $this->fromDateTime = $fromDateTime;
        $this->toDateTime   = $toDateTime;
    }

    /**
     * @return string
     */
    public function getObject(): string
    {
        return $this->object;
    }

    /**
     * @param string $field
     *
     * @return self
     */
    public function addField(string $field)
    {
        $this->fields[] = $field;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getFromDateTime(): ?\DateTimeInterface
    {
        return $this->fromDateTime;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getToDateTime(): ?\DateTimeInterface
    {
        return $this->toDateTime;
    }
}
