<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('headerTitle', $view['translator']->trans('mautic.user.auth.expired.header'));
?>

<div class="row">
    <div class="col-sm-12 col-md-8 col-lg-6">
        <?php $view['slots']->output('_content'); ?>
    </div>
</div>