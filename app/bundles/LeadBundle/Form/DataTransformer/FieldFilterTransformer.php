<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Form\DataTransformer;

use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\LeadBundle\Entity\LeadListRepository;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Translation\TranslatorInterface;

class FieldFilterTransformer implements DataTransformerInterface
{
    /**
     * @var string[]
     */
    private $relativeDateStrings;

    /**
     * @var array
     */
    private $default;

    public function __construct(TranslatorInterface $translator, array $default = [])
    {
        $this->relativeDateStrings = LeadListRepository::getRelativeDateTranslationKeys();
        foreach ($this->relativeDateStrings as &$string) {
            $string = $translator->trans($string);
        }
        $this->default = $default;
    }

    /**
     * From DB format to form format.
     *
     * @param mixed $rawFilters
     *
     * @return array|mixed
     */
    public function transform($rawFilters)
    {
        if (!is_array($rawFilters)) {
            return [];
        }

        foreach ($rawFilters as $k => $f) {
            if (!empty($this->default)) {
                $rawFilters[$k] = array_merge($this->default, $rawFilters[$k]);
            }
            if ('datetime' === $f['type']) {
                $bcFilter = $f['filter'] ?? '';
                $filter   = $f['properties']['filter'] ?? $bcFilter;
                if (empty($filter) || in_array($filter, $this->relativeDateStrings) || stristr($filter[0], '-') || stristr($filter[0], '+')) {
                    continue;
                }

                $dt = new DateTimeHelper($filter, 'Y-m-d H:i');

                $rawFilters[$k]['properties']['filter'] = $dt->toLocalString();
            }
        }

        return $rawFilters;
    }

    /**
     * Form format to database format.
     *
     * @param mixed $rawFilters
     *
     * @return array|mixed
     */
    public function reverseTransform($rawFilters)
    {
        if (!is_array($rawFilters)) {
            return [];
        }

        $rawFilters = array_values($rawFilters);

        foreach ($rawFilters as $k => $f) {
            if ('datetime' == $f['type']) {
                $bcFilter = $f['filter'] ?? '';
                $filter   = $f['properties']['filter'] ?? $bcFilter;
                if (empty($filter) || in_array($filter, $this->relativeDateStrings) || stristr($filter[0], '-') || stristr($filter[0], '+')) {
                    continue;
                }

                $dt = new DateTimeHelper($filter, 'Y-m-d H:i', 'local');

                $rawFilters[$k]['properties']['filter'] = $dt->toUtcString();
            }
        }

        return $rawFilters;
    }
}
