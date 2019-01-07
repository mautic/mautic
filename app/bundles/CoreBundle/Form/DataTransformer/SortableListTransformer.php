<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Form\DataTransformer;

use Mautic\CoreBundle\Helper\AbstractFormFieldHelper;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * Class SortableListTransformer.
 */
class SortableListTransformer implements DataTransformerInterface
{
    /**
     * @var bool
     */
    private $removeEmpty = true;

    /**
     * @var bool
     */
    private $withLabels = true;

    /**
     * @var bool
     */
    private $useKeyValuePairs = false;

    /**
     * SortableListTransformer constructor.
     *
     * @param bool $removeEmpty
     * @param bool $withLabels
     * @param bool $atRootLevel
     */
    public function __construct($removeEmpty = true, $withLabels = true, $useKeyValuePairs = false)
    {
        $this->removeEmpty      = $removeEmpty;
        $this->withLabels       = $withLabels;
        $this->useKeyValuePairs = $useKeyValuePairs;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function transform($array)
    {
        if ($this->useKeyValuePairs) {
            return $this->transformKeyValuePair($array);
        }

        return $this->formatList($array);
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function reverseTransform($array)
    {
        if ($this->useKeyValuePairs) {
            return $this->reverseTransformKeyValuePair($array);
        }

        return $this->formatList($array);
    }

    /**
     * @param $array
     *
     * @return mixed
     */
    private function formatList($array)
    {
        if (null === $array || !isset($array['list'])) {
            return ['list' => []];
        }

        $array['list'] = AbstractFormFieldHelper::parseList($array['list'], $this->removeEmpty);

        if (!$this->withLabels) {
            $array['list'] = array_keys($array['list']);
        }

        $format        = ($this->withLabels) ? AbstractFormFieldHelper::FORMAT_ARRAY : AbstractFormFieldHelper::FORMAT_SIMPLE_ARRAY;
        $array['list'] = AbstractFormFieldHelper::formatList($format, $array['list']);

        return $array;
    }

    /**
     * @param $array
     *
     * @return array
     */
    private function transformKeyValuePair($array)
    {
        if (null === $array) {
            return ['list' => []];
        }

        $formattedArray = [];

        foreach ($array as $label => $value) {
            $formattedArray[] = [
                'label' => $label,
                'value' => $value,
            ];
        }

        return ['list' => $formattedArray];
    }

    /**
     * @param $array
     *
     * @return array
     */
    private function reverseTransformKeyValuePair($array)
    {
        if (null === $array || !isset($array['list'])) {
            return [];
        }

        $pairs = [];
        foreach ($array['list'] as $pair) {
            if (!isset($pair['label'])) {
                continue;
            }

            $pairs[$pair['label']] = $pair['value'];
        }

        return $pairs;
    }
}
