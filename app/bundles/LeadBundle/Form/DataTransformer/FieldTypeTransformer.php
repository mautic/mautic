<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */


namespace Mautic\LeadBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class FieldTypeTransformer implements DataTransformerInterface
{

    public function transform($rawFilters)
    {

        if (!is_array($rawFilters)) {
            return array();
        }

        $keys    = array( "glue", "field", "type", "operator", "filter", "display");
        $filters = array();

        foreach ($keys as $k) {
            ${$k} = array();
        }
        foreach ($rawFilters as $rawFilter) {
            foreach ($rawFilter as $k => $v) {
                ${$k}[] = $v;
            }
        }
        foreach ($keys as $k) {
            $filters[$k] = ${$k};
        }

        return $filters;
    }

    public function reverseTransform($rawFilters)
    {

        if (!is_array($rawFilters)) {
            return array();
        }

        $keys    = array( "glue", "field", "type", "operator", "filter", "display");
        $filters = array();

        foreach ($keys as $key) {
            foreach ($rawFilters[$key] as $k => $v) {
                $filters[$k][$key] = $v;
            }
        }
        $filters = array_values($filters);
        return $filters;
    }
}