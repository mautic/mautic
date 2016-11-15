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
    private $removeEmpty = true;

    /**
     * SortableListTransformer constructor.
     *
     * @param bool $removeEmpty
     */
    public function __construct($removeEmpty = true)
    {
        $this->removeEmpty = $removeEmpty;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function transform($array)
    {
        if ($array === null || !isset($array['list'])) {
            return ['list' => []];
        }

        $array['list'] = AbstractFormFieldHelper::parseList($array['list'], $this->removeEmpty);

        $array['list'] = AbstractFormFieldHelper::formatList(AbstractFormFieldHelper::FORMAT_ARRAY, $array['list']);

        return $array;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function reverseTransform($array)
    {
        if ($array === null || !isset($array['list'])) {
            return ['list' => []];
        }

        $array['list'] = AbstractFormFieldHelper::parseList($array['list'], $this->removeEmpty);

        return $array;
    }
}
