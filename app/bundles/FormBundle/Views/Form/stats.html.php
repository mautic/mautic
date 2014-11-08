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
                                <td width="20%"><span class="fw-b"><?php echo $view['translator']->trans('mautic.form.details.created'); ?></span></td>
                                <td><?php echo $view['date']->toFull($form->getDateAdded()); ?></td>
                            </tr>
                            <tr>
                                <td width="20%"><span class="fw-b"><?php echo $view['translator']->trans('mautic.form.details.created_by'); ?></span></td>
                                <td><?php echo ($form->getCreatedBy() === null) ? '' : $form->getCreatedBy()->getName(); ?></td>
                            </tr>
                            <tr>
                                <td width="20%"><span class="fw-b"><?php echo $view['translator']->trans('mautic.form.details.category'); ?></span></td>
                                <td><?php echo ($form->getCategory() === null) ? '' : $form->getCategory()->getTitle(); ?></td>
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

        <!-- stats -->
        <div class="pa-md">
            <div class="row">
                <div class="col-sm-12">
                    <div class="panel">
                        <div class="panel-body box-layout">
                            <div class="col-xs-6 va-m">
                                <h5 class="text-white dark-md fw-sb mb-xs">
                                    <span class="fa fa-download"></span>
                                    <?php echo $view['translator']->trans('mautic.form.graph.line.submissions'); ?>
                                </h5>
                            </div>
                            <div class="col-xs-6 va-m">
                                <div class="dropdown pull-right">
                                    <button id="time-scopes" class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown">
                                        <span class="button-label"><?php echo $view['translator']->trans('mautic.asset.asset.downloads.daily'); ?></span>
                                        <span class="caret"></span>
                                    </button>
                                    <ul class="dropdown-menu" role="menu" aria-labelledby="time-scopes">
                                        <li role="presentation">
                                            <a href="#" onclick="Mautic.updateSubmissionChart(this, 24, 'H');return false;" role="menuitem" tabindex="-1">
                                                <?php echo $view['translator']->trans('mautic.asset.asset.downloads.hourly'); ?>
                                            </a>
                                        </li>
                                        <li role="presentation">
                                            <a href="#" class="bg-primary" onclick="Mautic.updateSubmissionChart(this, 30, 'D');return false;" role="menuitem" tabindex="-1">
                                                <?php echo $view['translator']->trans('mautic.asset.asset.downloads.daily'); ?>
                                            </a>
                                        </li>
                                        <li role="presentation">
                                            <a href="#" onclick="Mautic.updateSubmissionChart(this, 20, 'W');return false;" role="menuitem" tabindex="-1">
                                                <?php echo $view['translator']->trans('mautic.asset.asset.downloads.weekly'); ?>
                                            </a>
                                        </li>
                                        <li role="presentation">
                                            <a href="#" onclick="Mautic.updateSubmissionChart(this, 24, 'M');return false;" role="menuitem" tabindex="-1">
                                                <?php echo $view['translator']->trans('mautic.asset.asset.downloads.monthly'); ?>
                                            </a>
                                        </li>
                                        <li role="presentation">
                                            <a href="#" onclick="Mautic.updateSubmissionChart(this, 10, 'Y');return false;" role="menuitem" tabindex="-1">
                                                <?php echo $view['translator']->trans('mautic.asset.asset.downloads.yearly'); ?>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="pt-0 pl-15 pb-10 pr-15">
                            <div>
                                <canvas id="submission-chart" height="80"></canvas>
                            </div>
                        </div>
                        <div id="submission-chart-data" class="hide"><?php echo json_encode($stats['submissionsInTime']); ?></div>
                    </div>
                </div>
            </div>
        </div>
        <!--/ stats -->

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
