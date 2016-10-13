<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\Serializer\Driver;

use JMS\Serializer\Metadata\ClassMetadata;

/**
 * Class AnnotationDriver.
 */
class AnnotationDriver extends \JMS\Serializer\Metadata\Driver\AnnotationDriver
{
    public function loadMetadataForClass(\ReflectionClass $class)
    {
        // Overriding annotation driver to not generate annotation cache files
        $classMetadata = new ClassMetadata($name = $class->name);

        return $classMetadata;
    }
}
