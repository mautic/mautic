<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$fields    = $form->children;
$fieldKeys = array_keys($fields);
?>

<?php if (count(array_intersect($fieldKeys, ['saml_idp_metadata', 'saml_idp_own_certificate', 'saml_idp_own_private_key', 'saml_idp_own_password']))): ?>
<div class="panel panel-primary">
    <div class="panel-heading">
        <h3 class="panel-title"><?php echo $view['translator']->trans('mautic.user.config.header.saml'); ?></h3>
    </div>
    <div class="panel-body">
        <div class="alert alert-info"><?php echo $view['translator']->trans('mautic.user.config.form.saml.idp_entity_id', ['%entityId%' => $entityId]); ?></div>

        <div class="row">
            <div class="col-md-6">
                <?php echo $view['form']->row($fields['saml_idp_metadata'], ['fieldValue' => $formConfig['parameters']['saml_idp_metadata']]); ?>
            </div>
            <div class="col-md-6">
                <?php echo $view['form']->row($fields['saml_idp_default_role']); ?>
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
        <div class="alert alert-info"><?php echo $view['translator']->trans('mautic.user.config.form.saml.idp.own_certificate.description'); ?></div>
        <div class="row">
            <div class="col-md-6">
                <?php echo $view['form']->row($fields['saml_idp_own_certificate'], ['fieldValue' => $formConfig['parameters']['saml_idp_own_certificate']]); ?>
            </div>
            <div class="col-md-6">
                <?php echo $view['form']->row($fields['saml_idp_own_private_key'], ['fieldValue' => $formConfig['parameters']['saml_idp_own_private_key']]); ?>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <?php echo $view['form']->row($fields['saml_idp_own_password']); ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>