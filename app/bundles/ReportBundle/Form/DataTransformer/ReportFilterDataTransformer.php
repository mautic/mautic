<?php

namespace Mautic\ReportBundle\Form\DataTransformer;

use Mautic\CoreBundle\Helper\DateTimeHelper;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;

class ReportFilterDataTransformer implements DataTransformerInterface
{
    /**
     * @var string
     */
    private $columns;

    /**
     * @param array $columns
     */
    public function __construct($columns)
    {
        $this->columns = $columns;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function transform($filters)
    {
        if (!is_array($filters)) {
            return [];
        }

        foreach ($filters as &$f) {
            if (!isset($this->columns[$f['column']])) {
                // Likely being called by form.pre_set_data after post
                return $filters;
            }
            $type = $this->columns[$f['column']]['type'];
            if (in_array($type, ['datetime', 'time', DateTimeType::class, DateType::class, TimeType::class])) {
                $dt         = new DateTimeHelper($f['value'], '', 'utc');
                $f['value'] = $dt->toLocalString();
            }
        }

        return $filters;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function reverseTransform($filters)
    {
        if (!is_array($filters)) {
            return [];
        }

        foreach ($filters as &$f) {
            if (!isset($this->columns[$f['column']])) {
                // Likely being called by form.pre_set_data after post
                return $filters;
            }
            $type = $this->columns[$f['column']]['type'];
            if (in_array($type, ['datetime', 'time'])) {
                $dt         = new DateTimeHelper($f['value'], '', 'local');
                $f['value'] = $dt->toUtcString();
            }
        }

        return $filters;
    }
}
