<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\Serializer\Exclusion;

use JMS\Serializer\Context;
use JMS\Serializer\Exclusion\ExclusionStrategyInterface;
use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\Metadata\PropertyMetadata;

/**
 * Class FieldExclusionStrategy.
 *
 * Exclude specific fields at a specific level
 */
class FieldExclusionStrategy implements ExclusionStrategyInterface
{
    /**
     * @var array
     */
    private $fields = [];

    /**
     * @var int
     */
    private $level;

    /**
     * FieldExclusionStrategy constructor.
     *
     * @param array $fields
     * @param int   $level
     */
    public function __construct(array $fields, $level = 3)
    {
        $this->fields = $fields;
        $this->level  = (int) $level;
    }

    /**
     * {@inheritdoc}
     */
    public function shouldSkipClass(ClassMetadata $metadata, Context $navigatorContext)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function shouldSkipProperty(PropertyMetadata $property, Context $navigatorContext)
    {
        $name = $property->serializedName ?: $property->name;

        if (!in_array($name, $this->fields)) {
            return false;
        }

        // children of children or parents of chidlren will be more than 3 levels deep
        if ($navigatorContext->getDepth() <= $this->level) {
            return false;
        }

        return true;
    }
}
