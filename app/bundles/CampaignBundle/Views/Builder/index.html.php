<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */



$view['slots']->append('modal', $this->render('MauticCoreBundle:Helper:modal.html.php', array(
    'id'     => 'campaignEventModal',
    'header' => $view['translator']->trans('mautic.campaign.form.modalheader'),
)));
?>
