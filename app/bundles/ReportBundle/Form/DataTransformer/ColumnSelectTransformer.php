<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

/**
 * Class ColumnSelectTransformer
 */
class ColumnSelectTransformer implements DataTransformerInterface
{

    /**
     * @var array
     */
    private $columnList;

    /**
     * @param array $columnList
     */
    public function __construct($columnList)
    {
        $this->columnList = $columnList;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($rawFilters)
    {
        if (!is_array($rawFilters)) {
            return array();
        }

        $temp = array_keys($this->columnList);
        $filters = array_combine($temp, $rawFilters);

        return $filters;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($rawFilters)
    {
        if (!is_array($rawFilters)) {
            return array();
        }

        $submittedValues = array();

        foreach ($rawFilters as $key => $value) {
            if (!array_key_exists($key, $this->columnList)) {
                $submittedValues[$key] = $value;
            }
        }

        return $submittedValues;
    }
}
