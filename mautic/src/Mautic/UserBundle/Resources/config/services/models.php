<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

//User model
$container->setParameter('mautic.model.user', 'Mautic\UserBundle\Model\UserModel');

//Role model
$container->setParameter('mautic.model.role', 'Mautic\UserBundle\Model\RoleModel');