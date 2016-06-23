<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class ReportBuilderEvent
 */
class ReportBuilderEvent extends Event
{
    /**
     * @var string
     */
    private $context = '';

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * Container with registered tables and columns
     *
     * @var array
     */
    private $tableArray = [];

    /**
     * Supported graphs
     *
     * @var array
     */
    private $supportedGraphs = [
        'table',
        'bar',
        'pie',
        'line'
    ];

    /**
     * Container with registered graphs
     *
     * @var array
     */
    private $graphArray = [];

    /**
     * ReportBuilderEvent constructor.
     *
     * @param TranslatorInterface $translator
     * @param string              $context
     */
    public function __construct(TranslatorInterface $translator, $context = '')
    {
        $this->context    = $context;
        $this->translator = $translator;
    }

    /**
     * Add a table with the specified columns to the lookup.
     *
     * The data should be an associative array with the following data:
     * 'display_name' => The translation key to display in the select list
     * 'columns'      => An array containing the table's columns
     *
     * @param string $context Context for data
     * @param array  $data    Data array for the table
     *
     * @return ReportBuilderEvent
     */
    public function addTable($context, array $data, $group = null)
    {
        $data['group'] = (null == $group) ? $context : $group;

        foreach ($data['columns'] as $column => &$d) {
            $d['label'] = $this->translator->trans($d['label']);
            if (!isset($d['alias'])) {
                $d['alias'] = substr(
                    $column,
                    ($pos = strpos($column, '.')) !== false ? $pos + 1 : 0
                );
            }
        }

        uasort(
            $data['columns'],
            function ($a, $b) {
                return strnatcmp($a['label'], $b['label']);
            }
        );

        if (isset($data['filters'])) {
            foreach ($data['filters'] as $column => &$d) {
                $d['label'] = $this->translator->trans($d['label']);
                if (!isset($d['alias'])) {
                    $d['alias'] = substr(
                        $column,
                        ($pos = strpos($column, '.')) !== false ? $pos + 1 : 0
                    );
                }
            }

            uasort(
                $data['filters'],
                function ($a, $b) {
                    return strnatcmp($a['label'], $b['label']);
                }
            );
        }

        $this->tableArray[$context] = $data;

        if ($this->context == $context) {
            $this->stopPropagation();
        }
        return $this;
    }

    /**
     * Fetch the tables in the lookup array
     *
     * @return array
     */
    public function getTables()
    {
        return $this->tableArray;
    }

    /**
     * Returns standard form fields such as id, name, publish_up, etc
     *
     * @param        $prefix
     *
     * @return array
     */
    public function getStandardColumns($prefix, $removeColumns = [], $idLink = null)
    {
        $columns = [
            $prefix.'id'               => [
                'label' => 'mautic.core.id',
                'type'  => 'int',
                'link'  => $idLink
            ],
            $prefix.'name'             => [
                'label' => 'mautic.core.name',
                'type'  => 'string'
            ],
            $prefix.'created_by_user'  => [
                'label' => 'mautic.core.createdby',
                'type'  => 'string'
            ],
            $prefix.'date_added'       => [
                'label' => 'mautic.report.field.date_added',
                'type'  => 'datetime'
            ],
            $prefix.'modified_by_user' => [
                'label' => 'mautic.report.field.modified_by_user',
                'type'  => 'string'
            ],
            $prefix.'date_modified'    => [
                'label' => 'mautic.report.field.date_modified',
                'type'  => 'datetime'
            ],
            $prefix.'description'      => [
                'label' => 'mautic.core.description',
                'type'  => 'string'
            ],
            $prefix.'publish_up'       => [
                'label' => 'mautic.report.field.publish_up',
                'type'  => 'datetime'
            ],
            $prefix.'publish_down'     => [
                'label' => 'mautic.report.field.publish_down',
                'type'  => 'datetime'
            ],
            $prefix.'is_published'     => [
                'label' => 'mautic.report.field.is_published',
                'type'  => 'bool'
            ]
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

    /**
     * Returns lead columns
     *
     * @param        $prefix
     *
     * @return array
     */
    public function getLeadColumns($prefix = 'l.')
    {
        return [
            $prefix.'id'        => [
                'label' => 'mautic.report.field.lead.id',
                'type'  => 'int'
            ],
            $prefix.'title'     => [
                'label' => 'mautic.report.field.lead.title',
                'type'  => 'string'
            ],
            $prefix.'firstname' => [
                'label' => 'mautic.report.field.lead.firstname',
                'type'  => 'string'
            ],
            $prefix.'lastname'  => [
                'label' => 'mautic.report.field.lead.lastname',
                'type'  => 'string'
            ],
            $prefix.'email'     => [
                'label' => 'mautic.report.field.lead.email',
                'type'  => 'string'
            ],
            $prefix.'company'   => [
                'label' => 'mautic.report.field.lead.company',
                'type'  => 'string'
            ],
            $prefix.'position'  => [
                'label' => 'mautic.report.field.lead.position',
                'type'  => 'string'
            ],
            $prefix.'phone'     => [
                'label' => 'mautic.report.field.lead.phone',
                'type'  => 'string'
            ],
            $prefix.'mobile'    => [
                'label' => 'mautic.report.field.lead.mobile',
                'type'  => 'string'
            ],
            $prefix.'address1'  => [
                'label' => 'mautic.report.field.lead.address1',
                'type'  => 'string'
            ],
            $prefix.'address2'  => [
                'label' => 'mautic.report.field.lead.address2',
                'type'  => 'string'
            ],
            $prefix.'country'   => [
                'label' => 'mautic.report.field.lead.country',
                'type'  => 'string'
            ],
            $prefix.'city'      => [
                'label' => 'mautic.report.field.lead.city',
                'type'  => 'string'
            ],
            $prefix.'state'     => [
                'label' => 'mautic.report.field.lead.zipcode',
                'type'  => 'string'
            ]
        ];
    }

    /**
     * Get IP Address column
     *
     * @param string $prefix
     *
     * @return array
     */
    public function getIpColumn($prefix = 'i.')
    {
        return [
            $prefix.'ip_address' => [
                'label' => 'mautic.core.ipaddress',
                'type'  => 'string'
            ]
        ];
    }

    /**
     * Add category columns
     *
     * @param string $prefix
     *
     * @return array
     */
    public function getCategoryColumns($prefix = 'c.')
    {
        return [
            $prefix.'id'    => [
                'label' => 'mautic.report.field.category_id',
                'type'  => 'int'
            ],
            $prefix.'title' => [
                'label' => 'mautic.report.field.category_name',
                'type'  => 'string'
            ],
        ];
    }

    /**
     * Get the context
     *
     * @return string
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @param $context
     *
     * @return bool
     */
    public function checkContext($context)
    {
        if (empty($this->context)) {
            return true;
        }

        if (is_array($context)) {
            return in_array($this->context, $context);
        } else if ($this->context == $context) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param       $context
     * @param       $type
     * @param       $graphId
     * @param array $options
     *
     * @return $this
     */
    public function addGraph($context, $type, $graphId, $options = [])
    {
        if (in_array($type, $this->supportedGraphs)) {
            $this->graphArray[$context][$graphId] = [
                'options' => $options,
                'type'    => $type
            ];
        }

        return $this;
    }

    /**
     * Get graphs
     *
     * @return array
     */
    public function getGraphs()
    {
        return $this->graphArray;
    }
}
