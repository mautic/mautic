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
     * SortableListTransformer constructor.
     *
     * @param bool $removeEmpty
     */
    public function __construct($removeEmpty = true, $withLabels = true)
    {
        $this->removeEmpty = $removeEmpty;
        $this->withLabels  = $withLabels;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function transform($array)
    {
        return $this->formatList($array);
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function reverseTransform($array)
    {
        return $this->formatList($array);
    }

    /**
     * @param $array
     *
     * @return mixed
     */
    private function formatList($array)
    {
        if ($array === null || !isset($array['list'])) {
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
}
