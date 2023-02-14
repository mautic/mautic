<div class="panel panel-primary">
    <div class="panel-heading">
        <h3 class="panel-title"><?php echo $view['translator']->trans('mautic.open_id.config.header'); ?></h3>
    </div>
    <div class="panel-body">
        <div class="row">
            <div class="col-md-6">
                <?php echo $view['form']->row($form->children['open_id_is_enabled']); ?>
            </div>
            <div class="col-md-6">
                <?php echo $view['form']->row($form->children['open_id_is_required']); ?>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <?php echo $view['form']->row($form->children['open_id_is_user_registration_allowed']); ?>
            </div>
            <div class="col-md-6">
                <?php echo $view['form']->row($form->children['open_id_registered_user_role']); ?>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <?php echo $view['form']->row($form->children['open_id_client_url']); ?>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <?php echo $view['form']->row($form->children['open_id_client_id']); ?>
            </div>
            <div class="col-md-6">
                <?php echo $view['form']->row($form->children['open_id_client_secret']); ?>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <?php echo $view['form']->row($form->children['open_id_mapping_field']); ?>
                <div class="alert alert-danger" id="config_userconfig_open_id_mapping_field_tooltip" data-show-on='{"config_userconfig_open_id_is_enabled_1":"checked"}'><?php echo $view['translator']->trans('mautic.open_id.config.mapping_field.warning'); ?></div>
            </div>
        </div>
    </div>
</div>
