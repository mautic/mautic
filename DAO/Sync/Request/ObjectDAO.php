<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */


namespace MauticPlugin\MauticIntegrationsBundle\DAO\Sync\Request;

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
     * @var \DateTimeInterface
     */
    private $fromDateTime;

    /**
     * @var string[]
     */
    private $fields = [];

    /**
     * ObjectDAO constructor.
     *
     * @param                    $object
     * @param \DateTimeInterface $fromDateTime
     */
    public function __construct($object, \DateTimeInterface $fromDateTime)
    {
        $this->object       = $object;
        $this->fromDateTime = $fromDateTime;
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
     * @return \DateTimeInterface
     */
    public function getFromDateTime(): \DateTimeInterface
    {
        return $this->fromDateTime;
    }
}
