<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class Cache
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $data;

    /**
     * @var int
     */
    private $lifetime;

    /**
     * @var int
     */
    private $time;

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
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
