<?php

/*
 * @copyright   2019 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Entity\Transformer;

use InvalidArgumentException;
use Mautic\CoreBundle\Entity\Notification;
use ReflectionClass;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

class NotificationArrayTransformer implements DataTransformerInterface
{
    public function transform($value)
    {
        /** Notification $value */
        if (!$value instanceof Notification) {
            throw new InvalidArgumentException('Transformer expects '.Notification::class);
        }

        $notification = new Notification();
        $reflection   = new ReflectionClass($notification);
        $vars         = $reflection->getProperties();

        $propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()
            ->enableExceptionOnInvalidIndex()
            ->getPropertyAccessor();

        $array = [];

        foreach ($vars as $property) {
            $propertyValue               = $propertyAccessor->getValue($value, $property->getName());
            $array[$property->getName()] = $propertyValue;
        }

        return $array;
    }

    public function reverseTransform($value)
    {
        if (!is_array($value)) {
            throw new InvalidArgumentException('Method expects array as argument');
        }

        $vars         = get_class_vars(Notification::class);
        $notification = new Notification();

        $propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()
            ->enableExceptionOnInvalidIndex()
            ->getPropertyAccessor();

        foreach ($value as $property => $val) {
            if (!in_array($property, $vars)) {
                throw new InvalidArgumentException('Object '.Notification::class.' does not have property '.$property);
            }
            $propertyAccessor->setValue($notification, "[{$property}]", $val);
        }

        return $notification;
    }
}
