<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 *
 * This file was originally distributed as part of VelvelReportBundle (C) 2012 Velvel IT Solutions
 * and distributed under the GNU Lesser General Public License version 3.
 */

namespace Mautic\ReportBundle\Form;

interface FormBuilderInterface
{
    /**
     * Gets the query instance with default parameters
     *
     * @param array $parameters Parameters
     * @param array $options    Options
     *
     * @return \Symfony\Component\Form\Form
     */
    function getForm(array $parameters, array $options);
}
