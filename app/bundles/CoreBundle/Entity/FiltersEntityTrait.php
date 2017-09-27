<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Entity;

use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

trait FiltersEntityTrait
{
    /**
     * @var array
     */
    public static $defaultFilters = [
        [
            'glue'     => null,
            'field'    => null,
            'object'   => null,
            'type'     => null,
            'operator' => null,
            'display'  => null,
            'filter'   => null,
        ],
    ];

    /**
     * @var array
     */
    private $filters = [];

    /**
     * @param ClassMetadataBuilder $builder
     */
    protected static function addFiltersMetadata(ClassMetadataBuilder $builder)
    {
        $builder->createField('filters', 'array')
            ->columnName('filters')
            ->nullable()
            ->build();
    }

    /**
     * @return array
     */
    public function getFilters()
    {
        return empty($this->filters) ? $this->getDefaultFilters() : $this->filters;
    }

    /**
     * @param $filters
     *
     * @return $this
     */
    public function setFilters($filters)
    {
        if (empty($filters)) {
            $filters = $this->getDefaultFilters();
        }

        $this->filters = $filters;

        return $this;
    }

    /**
     * @return array
     */
    public function getDefaultFilters()
    {
        return self::$defaultFilters;
    }
}
