<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 * @created     8.4.19
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Entity\Transformer;

use Mautic\CoreBundle\Entity\Notification;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

class NotificationArrayTransformer implements DataTransformerInterface
{
    /** {@inheritdoc} */
    public function transform($value)
    {
        /** Notification $value */
        if (!$value instanceof Notification) {
            throw new \InvalidArgumentException('Transformer expects '.Notification::class);
        }

        $notification = new Notification();
        $reflection   = new \ReflectionClass($notification);
        $vars         = $reflection->getProperties();

        $propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()
            ->enableExceptionOnInvalidIndex()
            ->getPropertyAccessor();

        $array = [];

        foreach ($vars as $property) {
            $value                       = $propertyAccessor->getValue($value, $property->getName());
            $array[$property->getName()] = $value;
        }

        return $array;
    }

    /** {@inheritdoc} */
    public function reverseTransform($value)
    {
        if (!is_array($value)) {
            throw new \InvalidArgumentException('Method expects array as argument');
        }

        $vars         = get_class_vars(Notification::class);
        $notification = new Notification();

        $propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()
            ->enableExceptionOnInvalidIndex()
            ->getPropertyAccessor();

        foreach ($value as $property=>$value) {
            if (!in_array($property, $vars)) {
                throw new \InvalidArgumentException('Object '.Notification::class.' does not have property '.$property);
            }
            $propertyAccessor->setValue($notification, "[{$property}]", $value);
        }

        return $notification;
    }
}
