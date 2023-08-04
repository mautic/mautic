<?php

namespace Mautic\CoreBundle\Entity\Transformer;

use Mautic\CoreBundle\Entity\Notification;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * @template T of Notification
 *
 * @template-implements DataTransformerInterface<T, array<string, mixed>>
 */
class NotificationArrayTransformer implements DataTransformerInterface
{
    /**
     * @param T $value
     *
     * @return array<string, mixed>
     */
    public function transform($value)
    {
        /** @phpstan-ignore-next-line  */
        assert($value instanceof Notification);

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
