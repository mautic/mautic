<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

//Lead Model
$container->setParameter('mautic.model.lead', 'Mautic\LeadBundle\Model\LeadModel');

//Lead field model
$container->setParameter('mautic.model.leadfield', 'Mautic\LeadBundle\Model\LeadFieldModel');

//Lead list model
$container->setParameter('mautic.model.leadlist', 'Mautic\LeadBundle\Model\LeadListModel');