<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */


namespace Mautic\LeadBundle\Form\DataTransformer;

use Mautic\CoreBundle\Helper\DateTimeHelper;
use Symfony\Component\Form\DataTransformerInterface;

class FieldDateTimeTransformer implements DataTransformerInterface
{

    public function transform($rawFilters)
    {

        if (!is_array($rawFilters)) {
            return array();
        }

        foreach ($rawFilters as $k => $f) {
            if ($f['type']  == 'datetime') {
                $dt = new DateTimeHelper($f['filter'], 'Y-m-d H:i');
                $rawFilters[$k]['filter'] = $dt->toLocalString();
            }
        }
        return $rawFilters;
    }

    public function reverseTransform($rawFilters)
    {

        if (!is_array($rawFilters)) {
            return array();
        }

        foreach ($rawFilters as $k => $f) {
            if ($f['type']  == 'datetime') {
                $dt = new DateTimeHelper($f['filter'], 'Y-m-d H:i', 'local');
                $rawFilters[$k]['filter'] = $dt->toUtcString();
            }
        }

        return $rawFilters;
    }
}