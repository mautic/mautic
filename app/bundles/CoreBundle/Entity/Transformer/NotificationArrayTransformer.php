<?php

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
            $propertyValue               = $propertyAccessor->getValue($value, $property->getName());
            $array[$property->getName()] = $propertyValue;
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

        foreach ($value as $property => $val) {
            if (!in_array($property, $vars)) {
                throw new \InvalidArgumentException('Object '.Notification::class.' does not have property '.$property);
            }
            $propertyAccessor->setValue($notification, "[{$property}]", $val);
        }

        return $notification;
    }
}
