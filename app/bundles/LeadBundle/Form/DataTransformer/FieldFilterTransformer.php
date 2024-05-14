<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Form\DataTransformer;

use Psr\Container\ContainerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * @implements DataTransformerInterface<mixed, array<mixed>|mixed>
 */
class FieldFilterTransformer implements DataTransformerInterface, ServiceSubscriberInterface
{
    public function __construct(
        ContainerInterface $serviceLocator
    ) {
    }

    public static function getSubscribedServices(): array
    {
        return [
            'date'     => FieldFilter\FieldFilterDateTransformer::class,
            'datetime' => FieldFilter\FieldFilterDateTimeTransformer::class,
        ];
    }

    public function transform($value)
    {
        return $this->doTransform($value, fn (DataTransformerInterface $transformer, array $filter) => $transformer->transform($filter));
    }

    public function reverseTransform($value)
    {
        return $this->doTransform($value, fn (DataTransformerInterface $transformer, array $filter) => $transformer->reverseTransform($filter));
    }

    /**
     * @param mixed[]                                              $value
     * @param callable(DataTransformerInterface, mixed[]): mixed[] $transform
     *
     * @return mixed[]
     */
    private function doTransform($value, callable $transform)
    {
        if (!is_array($value)) {
            return [];
        }

        $value = array_values($value);

        foreach ($value as $key => $filter) {
            $type = $filter['type'] ?? null;

            if (!$type) {
                continue;
            }

            if ($this->serviceLocator->has($type)) {
                $transformer = $this->serviceLocator->get($type);
                \assert($transformer instanceof DataTransformerInterface);

                $value[$key] = $transform($transformer, $filter);
            }
        }

        return $value;
    }
}
