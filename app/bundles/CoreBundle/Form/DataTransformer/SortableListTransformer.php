<?php

namespace Mautic\CoreBundle\Form\DataTransformer;

use Mautic\CoreBundle\Helper\AbstractFormFieldHelper;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * @implements DataTransformerInterface<array<mixed>, array<mixed>>
 */
class SortableListTransformer implements DataTransformerInterface
{
    /**
     * @param bool $withLabels
     * @param bool $useKeyValuePairs
     */
    public function __construct(
        private $withLabels = true,
        private $useKeyValuePairs = false,
    ) {
    }

    /**
     * @return array<mixed>
     */
    public function transform($array)
    {
        if ($this->useKeyValuePairs) {
            return $this->transformKeyValuePair($array);
        }

        return $this->formatList($array);
    }

    /**
     * @return array<mixed>
     */
    public function reverseTransform($array)
    {
        if ($this->useKeyValuePairs) {
            return $this->reverseTransformKeyValuePair($array);
        }

        return $this->formatList($array);
    }

    /**
     * @param array<mixed>|null $array
     *
     * @return array<mixed>
     */
    private function formatList(?array $array): array
    {
        if (null === $array || !isset($array['list'])) {
            return ['list' => []];
        }

        // Reindex the array before processing
        $array['list'] = array_values($array['list']);

        $array['list'] = AbstractFormFieldHelper::parseList($array['list']);

        if (!$this->withLabels) {
            $array['list'] = array_keys($array['list']);
        }

        $format        = ($this->withLabels) ? AbstractFormFieldHelper::FORMAT_ARRAY : AbstractFormFieldHelper::FORMAT_SIMPLE_ARRAY;
        $array['list'] = AbstractFormFieldHelper::formatList($format, $array['list']);

        return $array;
    }

    /**
     * @return array<mixed>
     */
    private function transformKeyValuePair($array): array
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
     * @param array<mixed> $array
     *
     * @return array<mixed>
     */
    private function reverseTransformKeyValuePair(?array $array): array
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
