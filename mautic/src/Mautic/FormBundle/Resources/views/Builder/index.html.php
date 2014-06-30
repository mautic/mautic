<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'form');

$header = ($activeForm->getId()) ?
    $view['translator']->trans('mautic.form.form.header.edit',
        array('%name%' => $view['translator']->trans($activeForm->getName()))) :
    $view['translator']->trans('mautic.form.form.header.new');
$view["slots"]->set("headerTitle", $header);
?>

<div class="row bundle-content-container">
    <div class="col-xs-12 col-sm-8 bundle-main bundle-main-left auto-height">
        <div class="rounded-corners body-white bundle-main-inner-wrapper scrollable padding-md">
            <?php echo $view['form']->start($form); ?>
            <?php
            echo $view['form']->row($form['forms-panel-wrapper-start']);
            echo $view['form']->row($form['details-panel-start']);
            echo $view['form']->row($form['name']);
            echo $view['form']->row($form['description']);
            echo $view['form']->row($form['isPublished']);
            echo $view['form']->row($form['publishUp']);
            echo $view['form']->row($form['publishDown']);
            echo $view['form']->row($form['postAction']);
            echo $view['form']->row($form['postActionProperty']);
            echo $view['form']->row($form['details-panel-end']);
            echo $view['form']->row($form['fields-panel-start']);

            echo $view->render('MauticFormBundle:Builder:style.html.php');
            ?>
            <div id="mauticforms_fields">
                <?php
                foreach ($formFields as $field):
                    if (!empty($field['isCustom'])):
                        $params   = $field['customParameters'];
                        $template = $params['template'];
                    else:
                        $template = 'MauticFormBundle:Field:' . $field['type'] . '.html.php';
                    endif;
                    echo $view->render($template, array(
                        'field'  => $field,
                        'inForm' => true,
                        'id'     => $field['id'],
                        'deleted' => in_array($field['id'], $deletedFields)
                    ));
                endforeach;
                ?>
                <?php if (!count($formFields)): ?>
                <h3 id='form-field-placeholder'><?php echo $view['translator']->trans('mautic.form.form.addfield'); ?></h3>
                <?php endif; ?>
            </div>
            <?php
            echo $view['form']->row($form['fields-panel-end']);
            echo $view['form']->row($form['actions-panel-start']);
            ?>
            <div id="mauticforms_actions">
                <?php
                foreach ($formActions as $action):
                    $template = (isset($action['settings']['template'])) ? $action['settings']['template'] :
                        'MauticFormBundle:Action:generic.html.php';
                    echo $view->render($template, array(
                        'action'  => $action,
                        'inForm'  => true,
                        'id'      => $action['id'],
                        'deleted' => in_array($action['id'], $deletedActions)
                    ));
                endforeach;
                ?>
                <?php if (!count($formActions)): ?>
                    <h3 id='form-action-placeholder'><?php echo $view['translator']->trans('mautic.form.form.addaction'); ?></h3>
                <?php endif; ?>
            </div>
            <?php
            echo $view['form']->row($form['actions-panel-end']);
            echo $view['form']->row($form['forms-panel-wrapper-end']);
            echo $view['form']->end($form);
            ?>
            <div class="footer-margin"></div>
        </div>
    </div>

    <div class="col-xs-12 col-sm-4 bundle-side bundle-side-right auto-height">
        <div class="rounded-corners body-white bundle-side-inner-wrapper scrollable padding-md">
            <?php $view['slots']->output('_content'); ?>
        </div>
    </div>
</div>