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
$view['slots']->set("headerTitle", $header);
?>

<div class="row bundle-content-container">
    <div class="col-xs-12 col-sm-8 bundle-main bundle-main-left auto-height">
        <div class="rounded-corners body-white bundle-main-inner-wrapper scrollable padding-md">
            <!-- tabs controls -->
                <ul class="nav nav-tabs pr-md pl-md">
                    <li class="active"><a href="#details-container" role="tab" data-toggle="tab">Details</a></li>
                    <li class=""><a href="#fields-container" role="tab" data-toggle="tab">Fields</a></li>
                    <li class=""><a href="#actions-container" role="tab" data-toggle="tab">Actions</a></li>
                </ul>
            <!--/ tabs controls -->


            <?php echo $view['form']->start($form); ?>

                <div class="tab-content pa-md bg-white">
                    <!-- #history-container -->
                    <div class="tab-pane fade in active bdr-w-0" id="details-container">

                        <?php
                        echo $view['form']->row($form['name']);
                        echo $view['form']->row($form['description']);
                        echo $view['form']->row($form['category_lookup']);
                        echo $view['form']->row($form['category']);
                        echo $view['form']->row($form['isPublished']);
                        echo $view['form']->row($form['publishUp']);
                        echo $view['form']->row($form['publishDown']);
                        echo $view['form']->row($form['postAction']);
                        echo $view['form']->row($form['postActionProperty']);
                    ?>
                    </div>
                    <div class="tab-pane fade bdr-w-0" id="fields-container">
                        <?php echo $view->render('MauticFormBundle:Builder:style.html.php'); ?>
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
                            <div class="alert alert-info">
                                <p id='form-field-placeholder'><?php echo $view['translator']->trans('mautic.form.form.addfield'); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="tab-pane fade bdr-w-0" id="actions-container">
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
                                <div class="alert alert-info">
                                    <p id='form-action-placeholder'><?php echo $view['translator']->trans('mautic.form.form.addaction'); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php echo $view['form']->end($form); ?>
            </div>
        </div>
        <div class="col-xs-12 col-sm-4 bundle-side bundle-side-right auto-height">
            <div class="rounded-corners body-white bundle-side-inner-wrapper scrollable padding-md">
                <?php $view['slots']->output('_content'); ?>
            </div>
        </div>

    <?php
    $view['slots']->append('modal', $this->render('MauticCoreBundle:Helper:modal.html.php', array(
        'id'     => 'formComponentModal',
        'header' => $view['translator']->trans('mautic.form.form.modalheader'),
    )));
    ?>
</div>