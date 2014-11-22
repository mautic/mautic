<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\EventListener;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;

/**
 * Class DoctrineEventsSubscriber
 */
class DoctrineEventsSubscriber implements \Doctrine\Common\EventSubscriber
{

    /**
     * @var string
     */
    protected $prefix = '';

    /**
     * @param string $prefix
     */
    public function __construct($prefix)
    {
        $this->prefix  = (string) $prefix;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        return array('loadClassMetadata');
    }

    /**
     * @param LoadClassMetadataEventArgs $args
     *
     * @return void
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $args)
    {
        $classMetadata = $args->getClassMetadata();

        // Do not re-apply the prefix in an inheritance hierarchy.
        if ($classMetadata->isInheritanceTypeSingleTable() && !$classMetadata->isRootEntity()) {
            return;
        }

        if (FALSE !== strpos($classMetadata->namespace, 'Mautic')) {
            $classMetadata->setPrimaryTable(array('name' => $this->prefix . $classMetadata->getTableName()));

            foreach ($classMetadata->getAssociationMappings() as $fieldName => $mapping) {
                if ($mapping['type'] == \Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_MANY
                    && isset($classMetadata->associationMappings[$fieldName]['joinTable']['name'])) {
                    $mappedTableName = $classMetadata->associationMappings[$fieldName]['joinTable']['name'];
                    $classMetadata->associationMappings[$fieldName]['joinTable']['name'] = $this->prefix . $mappedTableName;
                }
            }

            $reader = new AnnotationReader();
            $class = $classMetadata->getReflectionClass();

            $annotation = $reader->getClassAnnotation($class, 'Mautic\CoreBundle\Doctrine\Annotation\LoadClassMetadataCallback');

            if (null !== $annotation) {
                if (method_exists($class->getName(), $annotation->functionName['value'])) {
                    $func = $class->getName() . '::' . $annotation->functionName['value'];
                    call_user_func($func, $args);
                }
            }
        }
    }
}
