<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Form\DataTransformer\FieldFilter;

use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\LeadBundle\Entity\LeadListRepository;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class FieldFilterDateTimeTransformer implements DataTransformerInterface
{
    use FieldFilterDateTransformerTrait;
    private TranslatorInterface $translator;

    /**
     * @var string[]
     */
    private array $relativeDateStrings;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator          = $translator;
        $this->relativeDateStrings = LeadListRepository::getRelativeDateTranslationKeys();
        foreach ($this->relativeDateStrings as &$string) {
            $string = $this->translator->trans($string);
        }
    }

    public function transform($value)
    {
        if ($this->skipTransformation($value)) {
            return $value;
        }

        $filterVal          = $this->getFilterValue($value);
        $isRelativeDateType = $this->isRelativeDateTypeMode($value);

        if ($this->isRelativeDateFormat($filterVal) || $isRelativeDateType) {
            // to support old date filter values
            if (!$isRelativeDateType && !isset($value['properties']['filter']['absoluteDate'])) {
                $value['properties']['filter'] = ['absoluteDate' => $filterVal];
            }

            return $value;
        }

        $dt = new DateTimeHelper($filterVal, 'Y-m-d H:i');

        if (isset($value['properties']['filter']['absoluteDate'])) {
            $value['properties']['filter']['absoluteDate'] = $dt->toLocalString();
        } else {
            $value['properties']['filter'] = ['absoluteDate' => $dt->toLocalString()];
        }

        return $value;
    }

    public function reverseTransform($value)
    {
        if ($this->skipTransformation($value)) {
            return $value;
        }

        $filterVal = $this->getFilterValue($value);

        if ($this->isRelativeDateFormat($filterVal) || $this->isRelativeDateTypeMode($value)) {
            return $value;
        }

        $dt = new DateTimeHelper($filterVal, 'Y-m-d H:i', 'local');

        if (is_string($value['properties']['filter'])) {
            $value['properties']['filter'] = ['absoluteDate' => $dt->toUtcString()];
        } else {
            $value['properties']['filter']['absoluteDate'] = $dt->toUtcString();
        }

        return $value;
    }

    /**
     * @param string|array<mixed> $value
     *
     * @return string|array<mixed>
     */
    private function getFilterValue($value)
    {
        $bcFilter    = $value['filter'] ?? '';
        $filterVal   = $value['properties']['filter'] ?? $bcFilter;

        if (!is_array($filterVal)) {
            return $filterVal;
        }

        if (!isset($filterVal['dateTypeMode']) && isset($filterVal['absoluteDate'])) {
            return $filterVal['absoluteDate'];
        }

        if (!isset($filterVal['dateTypeMode'])) {
            return $filterVal;
        }

        if ('absolute' === $filterVal['dateTypeMode']) {
            $filterVal = $filterVal['absoluteDate'];
        } else {
            $filterVal = $filterVal['relativeDateInterval'].' '.$filterVal['relativeDateIntervalUnit'];
        }

        return $filterVal;
    }

    /**
     * @param string $filterVal
     */
    public function isRelativeDateFormat($filterVal): bool
    {
        return empty($filterVal) || in_array($filterVal, $this->relativeDateStrings)
            || in_array($filterVal[0], ['+', '-']);
    }

    /**
     * @param string|array<mixed> $value
     */
    public function isRelativeDateTypeMode($value): bool
    {
        $filter = $value['properties']['filter'] ?? null;

        return is_array($filter) && 'relative' === $filter['dateTypeMode'];
    }
}
