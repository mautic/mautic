<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['blocks']->set('mauticContent', 'leadsocial');
$view['blocks']->set("headerTitle", $view['translator']->trans('mautic.social.header.index'));
?>
<div class="scrollable">
    <?php echo $view['form']->start($form); ?>
    <?php echo $view['form']->row($form->children['services']->children['sm-panel-wrapper-start']); ?>
    <?php foreach ($services as $service => $object): ?>
    <?php echo $view['form']->row($form->children['services']->children[$service . '-panel-start']); ?>
        <?php $serviceForm = $form->children['services']->children[$service]; ?>
        <?php foreach ($serviceForm->children as $child):?>
            <?php if (!empty($child->children) && $child->vars['name'] != 'isPublished'): ?>
                <?php echo $view['form']->label($child); ?>
                <?php foreach ($child->children as $child2): ?>
                <?php echo $view['form']->row($child2); ?>
                <?php endforeach; ?>
            <?php else: ?>
                <?php echo $view['form']->row($child); ?>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php echo $view['form']->row($form->children['services']->children[$service . '-panel-end']); ?>
    <?php endforeach; ?>
    <?php echo $view['form']->end($form); ?>
    <div class="footer-margin"></div>
</div>