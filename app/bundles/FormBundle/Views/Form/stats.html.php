<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

//@todo generate stats for results
/** @var \Mautic\FormBundle\Entity\Form $form */
$actions = $form->getActions();
$formActions = array();
foreach ($form->getActions() as $action) {
    $type                    = explode('.', $action->getType());
    $formActions[$type[0]][] = $action;
}
?>
<!-- left section -->
<div class="col-md-9 bg-white height-auto">
    <div class="bg-auto">
        <!-- form detail header -->
        <div class="pr-md pl-md pt-lg pb-lg">
            <div class="box-layout">
                <div class="col-xs-10">
                    <p class="text-muted"><?php echo $form->getDescription(); ?></p>
                </div>
                <div class="col-xs-2 text-right">
                    <h4 class="fw-sb"><span class="label label-success"><?php echo strtoupper($form->getPublishStatus()); ?></span></h4>
                </div>
            </div>
        </div>
        <!--/ form detail header -->

        <!-- form detail collapseable -->
        <div class="collapse" id="form-details">
            <div class="pr-md pl-md pb-md">
                <div class="panel shd-none mb-0">
                    <table class="table table-bordered table-striped mb-0">
                        <tbody>
                            <tr>
                                <td width="20%"><span class="fw-b"><?php echo $view['translator']->trans('mautic.form.details.created_on'); ?></span></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td width="20%"><span class="fw-b"><?php echo $view['translator']->trans('mautic.form.details.created_by'); ?></span></td>
                                <td><?php echo $form->getCreatedBy(); ?></td>
                            </tr>
                            <tr>
                                <td width="20%"><span class="fw-b"><?php echo $view['translator']->trans('mautic.form.details.category'); ?></span></td>
                                <td><?php echo $form->getCategory(); ?></td>
                            </tr>
                            <tr>
                                <td width="20%"><span class="fw-b"><?php echo $view['translator']->trans('mautic.form.details.publish_up'); ?></span></td>
                                <td><?php echo ($form->getPublishUp() === null) ? '' : $view['date']->toDate($form->getPublishUp()); ?></td>
                            </tr>
                            <tr>
                                <td width="20%"><span class="fw-b"><?php echo $view['translator']->trans('mautic.form.details.publish_down'); ?></span></td>
                                <td><?php echo ($form->getPublishDown() === null) ? '' : $view['date']->toDate($form->getPublishDown()); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!--/ form detail collapseable -->
    </div>

    <div class="bg-auto bg-dark-xs">
        <!-- form detail collapseable toggler -->
        <div class="hr-expand nm">
            <span data-toggle="tooltip" title="<?php echo $view['translator']->trans('mautic.form.details.detail'); ?>">
                <a href="javascript:void(0)" class="arrow" data-toggle="collapse" data-target="#form-details"><span class="caret"></span></a>
            </span>
        </div>
        <!--/ form detail collapseable toggler -->

        <!--
        some stats: need more input on what type of form data to show.
        delete if it is not require
        -->
        <div class="pa-md">
            <div class="row">
                <div class="col-md-4">
                    <div class="panel ovf-h bg-auto bg-light-xs">
                        <div class="panel-body box-layout">
                            <div class="col-xs-8 va-m">
                                <h5 class="text-white dark-md fw-sb mb-xs">Form Views</h5>
                                <h2 class="fw-b">112</h2>
                            </div>
                            <div class="col-xs-4 va-t text-right">
                                <h3 class="text-white dark-sm"><span class="fa fa-eye"></span></h3>
                            </div>
                        </div>
                        <div class="plugin-sparkline text-right pr-md pl-md"
                        sparkHeight="34"
                        sparkWidth="180"
                        sparkType="bar"
                        sparkBarWidth="8"
                        sparkBarSpacing="3"
                        sparkZeroAxis="false"
                        sparkBarColor="#00B49C">
                            129,137,186,167,200,115,118,162,112,106,104,106
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="panel ovf-h bg-auto bg-light-xs">
                        <div class="panel-body box-layout">
                            <div class="col-xs-8 va-m">
                                <h5 class="text-white dark-md fw-sb mb-xs">Form Conversions</h5>
                                <h2 class="fw-b">162</h2>
                            </div>
                            <div class="col-xs-4 va-t text-right">
                                <h3 class="text-white dark-sm"><span class="fa fa-arrows-h"></span></h3>
                            </div>
                        </div>
                        <div class="plugin-sparkline text-right pr-md pl-md"
                        sparkHeight="34"
                        sparkWidth="180"
                        sparkType="bar"
                        sparkBarWidth="8"
                        sparkBarSpacing="3"
                        sparkZeroAxis="false"
                        sparkBarColor="#F86B4F">
                            156,162,185,102,144,156,150,114,198,117,120,138
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="panel ovf-h bg-auto bg-light-xs">
                        <div class="panel-body box-layout">
                            <div class="col-xs-8 va-m">
                                <h5 class="text-white dark-md fw-sb mb-xs">Total Leads</h5>
                                <h2 class="fw-b">192</h2>
                            </div>
                            <div class="col-xs-4 va-t text-right">
                                <h3 class="text-white dark-sm"><span class="fa fa-user"></span></h3>
                            </div>
                        </div>
                        <div class="plugin-sparkline text-right pr-md pl-md"
                        sparkHeight="34"
                        sparkWidth="180"
                        sparkType="bar"
                        sparkBarWidth="8"
                        sparkBarSpacing="3"
                        sparkZeroAxis="false"
                        sparkBarColor="#FDB933">
                            115,195,185,110,182,192,168,185,138,176,119,109
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--/ some stats -->

        <!-- tabs controls -->
        <ul class="nav nav-tabs pr-md pl-md">
            <li class="active"><a href="#actions-container" role="tab" data-toggle="tab"><?php echo $view['translator']->trans('mautic.form.details.actions'); ?></a></li>
            <li class=""><a href="#fields-container" role="tab" data-toggle="tab"><?php echo $view['translator']->trans('mautic.form.details.fields'); ?></a></li>
        </ul>
        <!--/ tabs controls -->
    </div>

    <!-- start: tab-content -->
    <div class="tab-content pa-md">
        <!-- #actions-container -->
        <div class="tab-pane active fade in bdr-w-0" id="actions-container">
            <!-- header -->
            <div class="mb-lg">
                <!-- form -->
                <form action="" class="panel mb-0">
                    <div class="form-control-icon pa-xs">
                        <input type="text" class="form-control bdr-w-0" placeholder="<?php echo $view['translator']->trans('mautic.form.details.filter_actions_placeholder'); ?>">
                        <span class="the-icon fa fa-search text-muted mt-xs"></span><!-- must below `form-control` -->
                    </div>
                </form>
                <!--/ form -->
            </div>
            <!--/ header -->

            <?php foreach ($formActions as $group => $groupActions) : ?>
                <h5 class="fw-sb mb-xs"><?php echo ucfirst($group); ?></h5>
                <ul class="list-group">
                    <?php /** @var \Mautic\FormBundle\Entity\Action $action */ ?>
                    <?php foreach ($groupActions as $action) : ?>
                        <li class="list-group-item bg-auto bg-light-xs">
                            <div class="box-layout">
                                <div class="col-md-1 va-m">
                                    <?php switch ($group) {
                                        // TODO - Better way of doing this
                                        case 'lead':
                                            $icon = 'fa-user';
                                            break;
                                        case 'asset':
                                            $icon = 'fa-cloud-download';
                                            break;
                                        default:
                                            $icon = '';
                                    } ?>
                                    <h3><span class="fa <?php echo $icon; ?> text-white dark-xs"></span></h3>
                                </div>
                                <div class="col-md-7 va-m">
                                    <h5 class="fw-sb text-primary mb-xs"><?php echo $action->getName(); ?></h5>
                                    <h6 class="text-white dark-sm"><?php echo $action->getDescription(); ?></h6>
                                </div>
                                <div class="col-md-4 va-m text-right">
                                    <em class="text-white dark-sm"><?php echo $action->getType(); ?></em>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endforeach; ?>
        </div>
        <!--/ #actions-container -->

        <!-- #fields-container -->
        <div class="tab-pane fade bdr-w-0" id="fields-container">
            <!-- header -->
            <div class="mb-lg">
                <!-- form -->
                <form action="" class="panel mb-0">
                    <div class="form-control-icon pa-xs">
                        <input type="text" class="form-control bdr-w-0" placeholder="<?php echo $view['translator']->trans('mautic.form.details.filter_fields_placeholder'); ?>">
                        <span class="the-icon fa fa-search text-muted mt-xs"></span><!-- must below `form-control` -->
                    </div>
                </form>
                <!--/ form -->
            </div>
            <!--/ header -->

            <h5 class="fw-sb mb-xs">Form Field</h5>
            <ul class="list-group mb-xs">
                <?php /** @var \Mautic\FormBundle\Entity\Field $field */
                foreach ($form->getFields() as $field) : ?>
                    <li class="list-group-item bg-auto bg-light-xs">
                        <div class="box-layout">
                            <div class="col-md-1 va-m">
                                <?php $requiredTitle = $field->getIsRequired() ? 'mautic.form.details.required' : 'mautic.form.details.not_required'; ?>
                                <h3><span class="fa fa-<?php echo $field->getIsRequired() ? 'check' : 'times'; ?> text-white dark-xs" data-toggle="tooltip" data-placement="left" title="<?php echo $view['translator']->trans($requiredTitle); ?>"></span></h3>
                            </div>
                            <div class="col-md-7 va-m">
                                <h5 class="fw-sb text-primary mb-xs"><?php echo $field->getLabel(); ?></h5>
                                <h6 class="text-white dark-md"><?php echo $view['translator']->trans('mautic.form.details.field_type', array('%type%' => $field->getType())); ?></h6>
                            </div>
                            <div class="col-md-4 va-m text-right">
                                <em class="text-white dark-sm"><?php echo $view['translator']->trans('mautic.form.details.field_order', array('%order%' => $field->getOrder())); ?></em>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <!--/ #fields-container -->
    </div>
    <!--/ end: tab-content -->
</div>
<!--/ left section -->

<?php
    echo $view->render('MauticCoreBundle:Helper:modal.html.php', array(
        'id'     => 'form-preview',
        'header' => $view['translator']->trans('mautic.form.form.header.preview'),
        'body'   => $view->render('MauticFormBundle:Form:preview.html.php', array('form' => $form)),
        'size'   => 'lg'
    ));
?>
