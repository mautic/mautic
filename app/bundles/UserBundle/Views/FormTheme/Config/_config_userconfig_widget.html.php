<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$fields              = $form->children;
$fieldKeys           = array_keys($fields);
$generateDownloadRow = function ($field) use ($formConfig, $fields, $view) {
    $hasErrors     = count($fields[$field]->vars['errors']);
    $feedbackClass = (!empty($hasErrors)) ? ' has-error' : '';
    $hide          = (!empty($formConfig['parameters'][$field])) ? '' : ' hide';
    $filename      = \Mautic\CoreBundle\Helper\InputHelper::alphanum($view['translator']->trans($fields[$field]->vars['label']), true, '_');
    $downloadUrl   = $view['router']->path('mautic_config_action',
        [
            'objectAction' => 'download',
            'objectId'     => $field,
            'filename'     => $filename,
        ]
    );
    echo <<<HTML
            
    <div class="row">
        <div class="form-group col-xs-12 {$feedbackClass}">
            {$view['form']->label($fields[$field], $fields[$field]->vars['label'])}
            <span class="small pull-right{$hide}">
                <a href="{$downloadUrl}">{$view['translator']->trans('mautic.core.download')}</a>
            </span>
            {$view['form']->widget($fields[$field])}
            {$view['form']->errors($fields[$field])}
        </div>
    </div>

HTML;
}
?>

<?php if (count(array_intersect($fieldKeys, ['saml_idp_metadata', 'saml_idp_certificate', 'saml_idp_private_key', 'saml_idp_key_password']))): ?>
<div class="panel panel-primary">
    <div class="panel-heading">
        <h3 class="panel-title"><?php echo $view['translator']->trans('mautic.user.config.header.saml'); ?></h3>
    </div>
    <div class="panel-body">
        <div class="row">
            <div class="col-md-6">
                <?php echo $generateDownloadRow('saml_idp_metadata'); ?>
            </div>
            <div class="col-md-6">
                <?php echo $generateDownloadRow('saml_idp_certificate'); ?>
            </div>
        </div>
        <hr />
        <div class="alert alert-info"><?php echo $view['translator']->trans('mautic.user.config.form.saml.idp_attributes'); ?></div>
        <div class="row">
            <div class="col-md-6">
                <?php echo $view['form']->row($fields['saml_idp_email_attribute']); ?>
            </div>
            <div class="col-md-6">
                <?php echo $view['form']->row($fields['saml_idp_username_attribute']); ?>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <?php echo $view['form']->row($fields['saml_idp_firstname_attribute']); ?>
            </div>
            <div class="col-md-6">
                <?php echo $view['form']->row($fields['saml_idp_lastname_attribute']); ?>
            </div>
        </div>
        <hr />
        <div class="row">
            <div class="col-md-6">
                <?php echo $view['form']->row($fields['saml_idp_default_role']); ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>