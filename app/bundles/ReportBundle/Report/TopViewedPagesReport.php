<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Report;

use Doctrine\ORM\QueryBuilder;
use Mautic\ReportBundle\Builder\BaseReportBuilder;

/**
 * Class TopViewedPagesReport
 */
class TopViewedPagesReport extends BaseReportBuilder
{
    /**
     * Configures builder
     *
     * This method configures the ReportBuilder. It has to return
     * a configured Doctrine QueryBuilder.
     *
     * @param \Doctrine\ORM\QueryBuilder $queryBuilder Doctrine ORM query builder
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function configureBuilder(QueryBuilder $queryBuilder)
    {
        $queryBuilder
            ->select('p.title, p.hits')
            ->from('MauticPageBundle:Page', 'p', 'p.id')
            ->orderBy('p.hits', 'DESC');
        return $queryBuilder;
    }

    /**
     * Configures parameters
     *
     * This method configures parameters, which will be passed to
     * the QueryBuilder and the Form too, so the users (admins) can
     * change them.
     *
     * @return array
     */
    protected function configureParameters()
    {
        return array();

        // Kept for reference, this array adds form fields to the edit view
        $parameters = array(
            'from' => array(
                'value'   => new \DateTime('yesterday'), // default value
                'type'    => 'date', // form type
                'options' => array('label' => 'From'), // form options
            ),
            'to'   => array(
                'value'   => new \DateTime('now'),
                'type'    => 'date',
                'options' => array('label' => 'To'),
            ),
        );

        return $parameters;
    }

    /**
     * Configures modifiers
     *
     * If an element in the select statement returns an object without
     * a __toString() method implemented, it needs a modifier to be set.
     *
     * @return array
     */
    protected function configureModifiers()
    {
        $modifiers = array();

        return $modifiers;
    }
}
