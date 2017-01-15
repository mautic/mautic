<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Form\DataTransformer;

use Mautic\CoreBundle\Helper\DateTimeHelper;
use Symfony\Component\Form\DataTransformerInterface;

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
            if (in_array($type, ['datetime', 'date', 'time'])) {
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
            if (in_array($type, ['datetime', 'date', 'time'])) {
                $dt         = new DateTimeHelper($f['value'], '', 'local');
                $f['value'] = $dt->toUtcString();
            }
        }

        return $filters;
    }
}
