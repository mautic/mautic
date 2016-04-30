<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Event;

use Symfony\Component\Process\Exception\InvalidArgumentException;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Translation\Translator;

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
     * @var Translator
     */
    private $translator;

    /**
     * Container with registered tables and columns
     *
     * @var array
     */
    private $tableArray = array();

    /**
     * Supported graphs
     *
     * @var array
     */
    private $supportedGraphs = array(
        'table',
        'bar',
        'pie',
        'line'
    );

    /**
     * Container with registered graphs
     *
     * @var array
     */
    private $graphArray = array();

    public function __construct(Translator $translator, $context = '')
    {
        $this->context     = $context;
        $this->translator = $translator;
    }

    /**
     * Add a table with the specified columns to the lookup.
     *
     * The data should be an associative array with the following data:
     * 'display_name' => The translation key to display in the select list
     * 'columns'      => An array containing the table's columns
     *
     * @param string $context   Context for data
     * @param array  $data      Data array for the table
     *
     * @return void
     */
    public function addTable($context, array $data)
    {
        foreach ($data['columns'] as &$d) {
            $d['label'] = $this->translator->trans($d['label']);
        }

        uasort($data['columns'], function($a, $b) {
           return strnatcmp($a['label'], $b['label']);
        });

        $this->tableArray[$context] = $data;
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
    public function getStandardColumns($prefix, $removeColumns = array())
    {
        $columns = array(
            $prefix . 'id' => array(
                'label' => 'mautic.core.id',
                'type'  => 'int'
            ),
            $prefix . 'name' => array(
                'label' => 'mautic.core.name',
                'type'  => 'string'
            ),
            $prefix . 'created_by_user' => array(
                'label' => 'mautic.core.createdby',
                'type'  => 'string'
            ),
            $prefix . 'date_added' => array(
                'label' => 'mautic.report.field.date_added',
                'type'  => 'datetime'
            ),
            $prefix . 'modified_by_user' => array(
                'label' => 'mautic.report.field.modified_by_user',
                'type'  => 'string'
            ),
            $prefix . 'date_modified' => array(
                'label' => 'mautic.report.field.date_modified',
                'type'  => 'datetime'
            ),
            $prefix . 'description' => array(
                'label' => 'mautic.core.description',
                'type'  => 'string'
            ),
            $prefix . 'publish_up' => array(
                'label' => 'mautic.report.field.publish_up',
                'type'  => 'datetime'
            ),
            $prefix . 'publish_down' => array(
                'label' => 'mautic.report.field.publish_down',
                'type'  => 'datetime'
            ),
            $prefix . 'is_published' => array(
                'label' => 'mautic.report.field.is_published',
                'type'  => 'bool'
            )
        );

        if (!empty($removeColumns)) {
            foreach ($removeColumns as $c) {
                if(isset($columns[$prefix.$c])) {
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
        return array(
            $prefix . 'id' => array(
                'label' => 'mautic.report.field.lead.id',
                'type'  => 'int'
            ),
            $prefix . 'title' => array(
                'label' => 'mautic.report.field.lead.title',
                'type'  => 'string'
            ),
            $prefix . 'firstname' => array(
                'label' => 'mautic.report.field.lead.firstname',
                'type'  => 'string'
            ),
            $prefix . 'lastname' => array(
                'label' => 'mautic.report.field.lead.lastname',
                'type'  => 'string'
            ),
            $prefix . 'email' => array(
                'label' => 'mautic.report.field.lead.email',
                'type'  => 'string'
            ),
            $prefix . 'company' => array(
                'label' => 'mautic.report.field.lead.company',
                'type'  => 'string'
            ),
            $prefix . 'position' => array(
                'label' => 'mautic.report.field.lead.position',
                'type'  => 'string'
            ),
            $prefix . 'phone' => array(
                'label' => 'mautic.report.field.lead.phone',
                'type'  => 'string'
            ),
            $prefix . 'mobile' => array(
                'label' => 'mautic.report.field.lead.mobile',
                'type'  => 'string'
            ),
            $prefix . 'address1' => array(
                'label' => 'mautic.report.field.lead.address1',
                'type'  => 'string'
            ),
            $prefix . 'address2' => array(
                'label' => 'mautic.report.field.lead.address2',
                'type'  => 'string'
            ),
            $prefix . 'country' => array(
                'label' => 'mautic.report.field.lead.country',
                'type'  => 'string'
            ),
            $prefix . 'city' => array(
                'label' => 'mautic.report.field.lead.city',
                'type'  => 'string'
            ),
            $prefix . 'state' => array(
                'label' => 'mautic.report.field.lead.zipcode',
                'type'  => 'string'
            )
        );
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
        return array(
            $prefix . 'ip_address' => array(
                'label' => 'mautic.core.ipaddress',
                'type'  => 'string'
            )
        );
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
        return array(
            $prefix . 'id' => array(
                'label' => 'mautic.report.field.category_id',
                'type'  => 'int'
            ),
            $prefix . 'title' => array(
                'label' => 'mautic.report.field.category_name',
                'type'  => 'string'
            ),
        );
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
        } else if($this->context == $context) {
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
     */
    public function addGraph($context, $type, $graphId, $options = array())
    {
        if (in_array($type, $this->supportedGraphs)) {
            $this->graphArray[$context][$graphId] = array(
                'options' => $options,
                'type'    => $type
            );
        }
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
