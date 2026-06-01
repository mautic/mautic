<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Form\DataTransformer;

use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\LeadBundle\Entity\LeadListRepository;
use Mautic\LeadBundle\Segment\RelativeDate;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @implements DataTransformerInterface<mixed, array<mixed>|mixed>
 */
class FieldFilterTransformer implements DataTransformerInterface
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
        private TranslatorInterface $translator,
        private RelativeDate $relativeDate,
        private array $default = [],
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
        if (!is_array($rawFilters)) {
            return [];
        }

        foreach ($rawFilters as $key => $filter) {
            if (!empty($this->default)) {
                $rawFilters[$key] = array_merge($this->default, $rawFilters[$key]);
            }
            $filterType = $filter['type'];
            if ('datetime' === $filterType || 'date' === $filterType) {
                $bcFilter = $filter['filter'] ?? '';
                $filter   = $filter['properties']['filter'] ?? $bcFilter;
                if (empty($filter) || in_array($filter, $this->relativeDateStrings) || stristr($filter[0], '-') || stristr($filter[0], '+')) {
                    continue;
                }

                if (in_array(strtolower($filter), $this->defaultStrings)) {
                    $rawFilters[$key]['properties']['filter'] = $this->translator->trans(array_search(strtolower($filter), $this->defaultStrings));

                    continue;
                }

                if (!$this->isValidAbsoluteDate($filter, $filterType)) {
                    continue;
                }

                $dateFormat = 'datetime' === $filterType ? 'Y-m-d H:i' : 'Y-m-d';

                $dt = new DateTimeHelper($filter, $dateFormat);

                $rawFilters[$key]['properties']['filter'] = $dt->toLocalString();
            }
        }

        return $rawFilters;
    }

    /**
     * Form format to database format.
     */
    public function reverseTransform(mixed $rawFilters): mixed
    {
        if (!is_array($rawFilters)) {
            return [];
        }

        $rawFilters = array_values($rawFilters);

        foreach ($rawFilters as $k => $f) {
            if ('datetime' == $f['type'] || 'date' === $f['type']) {
                $bcFilter = $f['filter'] ?? '';
                $filter   = $f['properties']['filter'] ?? $bcFilter;
                $filter   = strtolower($filter);
                if (empty($filter) || stristr($filter[0], '-') || stristr($filter[0], '+')) {
                    continue;
                }

                if (in_array($filter, $this->relativeDateStrings)) {
                    $translationKey                         = array_search($filter, $this->relativeDate->getRelativeDateStrings());
                    $rawFilters[$k]['properties']['filter'] = $this->defaultStrings[$translationKey];

                    continue;
                }

                if (!$this->isValidAbsoluteDate($filter, $f['type'])) {
                    continue;
                }

                $dateFormat = 'datetime' === $f['type'] ? 'Y-m-d H:i' : 'Y-m-d';

                $dt = new DateTimeHelper($filter, $dateFormat, 'local');

                $rawFilters[$k]['properties']['filter'] = $dt->toUtcString();
            }
        }

        return $rawFilters;
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
