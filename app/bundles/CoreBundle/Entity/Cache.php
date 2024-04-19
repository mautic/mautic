<?php

namespace Mautic\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

/**
 * @deprecated since Mautic 5.0, to be removed in 6.0 with no replacement.
 */
class Cache
{
    /**
     * @var mixed
     */
    private $id;

    /**
     * @var mixed
     */
    private $data;

    /**
     * @var int|null
     */
    private $lifetime;

    /**
     * @var int
     */
    private $time;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);
        $builder->setTable('cache_items');

        $builder->createField('id', 'binary')
                ->columnName('item_id')
                ->makePrimaryKey()
                ->build();

        $builder->addNamedField('data', 'blob', 'item_data');

        $builder->addField(
            'lifetime',
            'integer',
            [
                'columnName' => 'item_lifetime',
                'nullable'   => true,
                'options'    => [
                    'unsigned' => true,
                ],
            ]
        );

        $builder->addField(
            'time',
            'integer',
            [
                'columnName' => 'item_time',
                'options'    => [
                    'unsigned' => true,
                ],
            ]
        );
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return Cache
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param string $data
     *
     * @return Cache
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return int
     */
    public function getLifetime()
    {
        return $this->lifetime;
    }

    /**
     * @param int $lifetime
     *
     * @return Cache
     */
    public function setLifetime($lifetime)
    {
        $this->lifetime = $lifetime;

        return $this;
    }

    /**
     * @return int
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * @param int $time
     *
     * @return Cache
     */
    public function setTime($time)
    {
        $this->time = $time;

        return $this;
    }
}
