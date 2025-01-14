<?php

namespace Mautic\ReportBundle\Helper;

use Symfony\Component\Templating\Helper\Helper;

class ReportHelper extends Helper
{
    /**
     * @return string
     */
    public function getName()
    {
        return 'report';
    }

    /**
     * @param $type
     *
     * @return string
     */
    public function getReportBuilderFieldType($type)
    {
        switch ($type) {
            case 'number':
                $type = 'int';
                break;
            case 'lookup':
            case 'text':
            case 'url':
            case 'email':
            case 'tel':
            case 'region':
            case 'country':
            case 'locale':
                $type = 'string';
                break;
        }

        return $type;
    }

    /**
     * Returns standard form fields such as id, name, publish_up, etc.
     *
     * @param string[] $removeColumns
     *
     * @return array<string,array<string,string>>
     */
    public function getStandardColumns(string $prefix, array $removeColumns = [], string $idLink = ''): array
    {
        $aliasPrefix = str_replace('.', '_', $prefix);
        $columns     = [
            $prefix.'id' => [
                'label' => 'mautic.core.id',
                'type'  => 'int',
                'link'  => $idLink,
                'alias' => "{$aliasPrefix}id",
            ],
            $prefix.'name' => [
                'label' => 'mautic.core.name',
                'type'  => 'string',
                'alias' => "{$aliasPrefix}name",
            ],
            $prefix.'created_by_user' => [
                'label' => 'mautic.core.createdby',
                'type'  => 'string',
                'alias' => "{$aliasPrefix}created_by_user",
            ],
            $prefix.'date_added' => [
                'label' => 'mautic.report.field.date_added',
                'type'  => 'datetime',
                'alias' => "{$aliasPrefix}date_added",
            ],
            $prefix.'modified_by_user' => [
                'label' => 'mautic.report.field.modified_by_user',
                'type'  => 'string',
                'alias' => "{$aliasPrefix}modified_by_user",
            ],
            $prefix.'date_modified' => [
                'label' => 'mautic.report.field.date_modified',
                'type'  => 'datetime',
                'alias' => "{$aliasPrefix}date_modified",
            ],
            $prefix.'description' => [
                'label' => 'mautic.core.description',
                'type'  => 'string',
                'alias' => "{$aliasPrefix}description",
            ],
            $prefix.'publish_up' => [
                'label' => 'mautic.report.field.publish_up',
                'type'  => 'datetime',
                'alias' => "{$aliasPrefix}publish_up",
            ],
            $prefix.'publish_down' => [
                'label' => 'mautic.report.field.publish_down',
                'type'  => 'datetime',
                'alias' => "{$aliasPrefix}publish_down",
            ],
            $prefix.'is_published' => [
                'label' => 'mautic.report.field.is_published',
                'type'  => 'bool',
                'alias' => "{$aliasPrefix}is_published",
            ],
        ];

        if (empty($idLink)) {
            unset($columns[$prefix.'id']['link']);
        }

        if (!empty($removeColumns)) {
            foreach ($removeColumns as $c) {
                if (isset($columns[$prefix.$c])) {
                    unset($columns[$prefix.$c]);
                }
            }
        }

        return $columns;
    }
}
