<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\Serializer\Exclusion;

use JMS\Serializer\Exclusion\ExclusionStrategyInterface;
use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\Metadata\PropertyMetadata;
use JMS\Serializer\Context;

/**
 * Class PublishDetailsExclusionStrategy
 *
 * Only include FormEntity properties for the top level entity and not the associated entities
 */
class PublishDetailsExclusionStrategy implements ExclusionStrategyInterface
{

    /**
     * @var array
     */
    private $fields = array();

    public function __construct()
    {
        $this->fields = array(
            'isPublished',
            'dateAdded',
            'createdBy',
            'dateModified',
            'modifiedBy',
            'checkedOut',
            'checkedOutBy'
        );
    }

    /**
     * {@inheritDoc}
     */
    public function shouldSkipClass(ClassMetadata $metadata, Context $navigatorContext)
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function shouldSkipProperty(PropertyMetadata $property, Context $navigatorContext)
    {
        $name = $property->serializedName ?: $property->name;

        if (!in_array($name, $this->fields)) {
            return false;
        }

        if ($navigatorContext->getDepth() == 1) {
            return false;
        }

        return true;
    }
}
