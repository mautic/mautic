<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$view->extend('MauticCoreBundle:Default:content.html.php');

$view['slots']->set('mauticContent', 'leadImport');
$view['slots']->set('headerTitle', $view['translator']->trans('mautic.lead.import.leads', ['%object%' => $view['request']->getParameter('object')]));

?>
<?php if (isset($form['file'])): ?>
<?php echo $view->render('MauticLeadBundle:Import:upload_form.html.php', ['form' => $form]); ?>
<?php else: ?>
<?php echo $view->render('MauticLeadBundle:Import:mapping_form.html.php', ['form' => $form]); ?>
<?php endif; ?>
