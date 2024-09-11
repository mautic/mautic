<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Form\DataTransformer\FieldFilter;

use Mautic\CoreBundle\Form\Type\AbsoluteRelativeDateFilterType;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\LeadBundle\Entity\LeadListRepository;
use Mautic\LeadBundle\Segment\Decorator\ParseDateFilterValueTrait;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class FieldFilterDateTimeTransformer implements DataTransformerInterface
{
    use FieldFilterDateTransformerTrait;
    use ParseDateFilterValueTrait;

    /**
     * @var string[]
     */
    private array $relativeDateStrings;

    public function __construct(TranslatorInterface $translator)
    {
        $this->relativeDateStrings = LeadListRepository::getRelativeDateTranslationKeys();

        foreach ($this->relativeDateStrings as &$string) {
            $string = $translator->trans($string);
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
            if (!$isRelativeDateType && $this->isAbsoluteRelativeDateFilterAllowed($value)
                && !isset($value['properties']['filter']['absoluteDate'])) {
                $value['properties']['filter'] = ['absoluteDate' => $filterVal];
            }

            return $value;
        }

        $dt = new DateTimeHelper($filterVal, 'Y-m-d H:i');

        if ($this->isAbsoluteRelativeDateFilterAllowed($value)) {
            if (isset($value['properties']['filter']['absoluteDate'])) {
                $value['properties']['filter']['absoluteDate'] = $dt->toLocalString();
            } else {
                $value['properties']['filter'] = ['absoluteDate' => $dt->toLocalString()];
            }
        } else {
            $value['properties']['filter'] = $dt->toLocalString();
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

        if ($this->isAbsoluteRelativeDateFilterAllowed($value)) {
            if (isset($value['properties']) && is_string($value['properties']['filter'])) {
                $value['properties']['filter'] = ['absoluteDate' => $dt->toUtcString()];
            } else {
                $value['properties']['filter']['absoluteDate'] = $dt->toUtcString();
            }
        } else {
            $value['properties']['filter'] = $dt->toUtcString();
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

        return $this->parseDateFilterValue($filterVal, $value['operator']);
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
        $filter       = $value['properties']['filter'] ?? null;
        $dateTypeMode = $filter['dateTypeMode'] ?? '';

        return is_array($filter)
            && (AbsoluteRelativeDateFilterType::RELATIVE_DATE_TYPE === $dateTypeMode
                || (isset($filter['interval']) && isset($filter['unit'])));
    }
}
