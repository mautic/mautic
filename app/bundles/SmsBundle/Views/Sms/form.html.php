<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:FormTheme:form_simple.html.php');
$view->addGlobal('translationBase', 'mautic.sms');
$view->addGlobal('mauticContent', 'sms');
/** @var \Mautic\SmsBundle\Entity\Sms $sms */
$type       = $sms->getSmsType();
$isExisting = $sms->getId();
?>

<?php $view['slots']->start('primaryFormContent'); ?>
<div class="row">
    <div class="col-md-6">
        <?php echo $view['form']->row($form['name']); ?>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="characters-count">
            <label class="control-label" for="" data-toggle="tooltip" data-container="body" data-placement="top" title="" data-original-title="<?php echo $view['translator']->trans('mautic.sms.form.nbcharacter.infobox'); ?>">
                <?php echo $view['translator']->trans('mautic.sms.form.nbcharacter.counter'); ?>
                <span class="label label-default" id="sms_nb_char">0</span>
            </label>
        </div>
        <?php echo $view['form']->row($form['message']); ?>
    </div>
</div>
<?php $view['slots']->stop(); ?>

<?php $view['slots']->start('rightFormContent'); ?>
<?php echo $view['form']->row($form['category']); ?>
<?php echo $view['form']->row($form['language']); ?>
<?php echo $view['form']->row($form['isPublished']); ?>

<div id="leadList"<?php echo ('template' == $type) ? ' class="hide"' : ''; ?>>
    <?php echo $view['form']->row($form['lists']); ?>
    <?php echo $view['form']->row($form['publishUp']); ?>
    <?php echo $view['form']->row($form['publishDown']); ?>
</div>

<div class="hide">
    <?php echo $view['form']->rest($form); ?>
</div>

<?php
if ((empty($updateSelect) && !$isExisting && !$view['form']->containsErrors($form)) || empty($type)):
    echo $view->render('MauticCoreBundle:Helper:form_selecttype.html.php',
        [
            'item'       => $sms,
            'mauticLang' => [
                'newListSms'     => 'mautic.sms.type.list.header',
                'newTemplateSms' => 'mautic.sms.type.template.header',
            ],
            'typePrefix'         => 'sms',
            'cancelUrl'          => 'mautic_sms_index',
            'header'             => 'mautic.sms.type.header',
            'typeOneHeader'      => 'mautic.sms.type.template.header',
            'typeOneIconClass'   => 'fa-cube',
            'typeOneDescription' => 'mautic.sms.type.template.description',
            'typeOneOnClick'     => "Mautic.selectSmsType('template');",
            'typeTwoHeader'      => 'mautic.sms.type.list.header',
            'typeTwoIconClass'   => 'fa-pie-chart',
            'typeTwoDescription' => 'mautic.sms.type.list.description',
            'typeTwoOnClick'     => "Mautic.selectSmsType('list');",
        ]);
endif;
?>
<?php $view['slots']->stop(); ?>

