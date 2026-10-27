<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Helper;

final class ConditionalFieldOrderTestData
{
    /**
     * @return string[]
     */
    public static function getExpectedChildLabels(): array
    {
        return ['Question A', 'Question B', 'Question C'];
    }

    /**
     * @param array{
     *     parentKey?: string,
     *     withConditions?: bool,
     *     withSelectProperties?: bool
     * } $options
     *
     * @return array<string, array<string, mixed>>
     */
    public static function createSessionFields(array $options = []): array
    {
        $parentKey            = $options['parentKey'] ?? 'parent';
        $withConditions       = $options['withConditions'] ?? false;
        $withSelectProperties = $options['withSelectProperties'] ?? false;

        $parent = [
            'id'         => $parentKey,
            'label'      => 'Yes or No',
            'alias'      => 'yes_no',
            'type'       => 'select',
            'showLabel'  => 1,
            'saveResult' => 1,
        ];

        if ($withSelectProperties) {
            $parent['properties'] = [
                'list' => [
                    'list' => [
                        ['label' => 'Yes', 'value' => 'yes'],
                        ['label' => 'No', 'value' => 'no'],
                    ],
                ],
            ];
        } else {
            $parent['order'] = 1;
        }

        $fields = [$parentKey => $parent];

        foreach (self::getChildFieldDefinitions() as $id => $definition) {
            $child = [
                'id'         => $id,
                'label'      => $definition['label'],
                'alias'      => $definition['alias'],
                'type'       => 'text',
                'showLabel'  => 1,
                'saveResult' => 1,
                'parent'     => $parentKey,
            ];

            if ($withConditions) {
                $child['conditions'] = [
                    'expr'   => 'in',
                    'any'    => 0,
                    'values' => ['yes'],
                ];
            }

            $fields[$id] = $child;
        }

        return $fields;
    }

    /**
     * @return array<string, array{label: string, alias: string}>
     */
    private static function getChildFieldDefinitions(): array
    {
        return [
            'child_a' => ['label' => 'Question A', 'alias' => 'question_a'],
            'child_b' => ['label' => 'Question B', 'alias' => 'question_b'],
            'child_c' => ['label' => 'Question C', 'alias' => 'question_c'],
        ];
    }
}
