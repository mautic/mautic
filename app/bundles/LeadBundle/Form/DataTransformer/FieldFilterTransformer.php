<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Form\DataTransformer;

use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\LeadBundle\Entity\LeadListRepository;
use Mautic\LeadBundle\Segment\OperatorOptions;
use Mautic\LeadBundle\Segment\RelativeDate;
use Psr\Container\ContainerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * @implements DataTransformerInterface<mixed, array<mixed>|mixed>
 */
final class FieldFilterTransformer implements DataTransformerInterface
class FieldFilterTransformer implements DataTransformerInterface, ServiceSubscriberInterface
{
    /**
     * @var string[]
     */
    private array $relativeDateStrings;

    /**
     * @var string[]
     */
    private array $defaultStrings;

    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly RelativeDate $relativeDate,
        private readonly array $default = [],
        private ContainerInterface $serviceLocator
    ) {
        $this->relativeDateStrings = LeadListRepository::getRelativeDateTranslationKeys();
        foreach ($this->relativeDateStrings as &$string) {
            $this->defaultStrings[$string] = $translator->trans($string, [], null, 'en_US');
            $string                        = $translator->trans($string);
        }
    }

    /**
     * From DB format to form format.
     */
    public function transform(mixed $rawFilters): mixed
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
     * Form format to database format.
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

    private function isValidAbsoluteDate(string $value, string $type): bool
    {
        $formats = 'datetime' === $type
            ? ['Y-m-d H:i', 'Y-m-d H:i:s']
            : ['Y-m-d'];

        foreach ($formats as $format) {
            $dt = \DateTimeImmutable::createFromFormat($format, $value);

            if (false !== $dt && $dt->format($format) === $value) {
                return true;
            }
        }

        return false;
    }
}
