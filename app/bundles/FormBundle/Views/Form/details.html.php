<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'form');
$view['slots']->set("headerTitle", $activeForm->getName());

/** @var \Mautic\FormBundle\Entity\Form $activeForm */
$actions = $activeForm->getActions();
$activeFormActions = array();
foreach ($activeForm->getActions() as $action) {
    $type                    = explode('.', $action->getType());
    $activeFormActions[$type[0]][] = $action;
}
?>

<?php $view['slots']->start("actions"); ?>
  <a class="btn btn-default" href="<?php echo $view['router']->generate('mautic_form_action', array(
        'objectAction' => 'results', 'objectId' => $activeForm->getId())); ?>"
       data-toggle="ajax"
       data-menu-link="mautic_form_index">
        <span>
            <i class="fa fa-fw fa-database"></i> <?php echo $view['translator']->trans('mautic.form.form.results'); ?>
        </span>
    </a>
    <a class="btn btn-default" href="<?php echo $view['router']->generate('mautic_form_action',
        array('objectAction' => 'preview', 'objectId' => $activeForm->getId())); ?>"
        target="_blank">
        <i class="fa fa-fw fa-camera"></i> <?php echo $view['translator']->trans('mautic.form.form.preview'); ?>
    </a>
<?php if ($security->hasEntityAccess($permissions['form:forms:editown'], $permissions['form:forms:editother'],
    $activeForm->getCreatedBy())): ?>
        <a class="btn btn-default" href="<?php echo $this->container->get('router')->generate(
            'mautic_form_action', array("objectAction" => "edit", "objectId" => $activeForm->getId())); ?>"
           data-toggle="ajax"
           data-menu-link="#mautic_form_index">
            <i class="fa fa-fw fa-pencil-square-o"></i><?php echo $view["translator"]->trans("mautic.core.form.edit"); ?>
        </a>
<?php endif; ?>
<?php if ($security->hasEntityAccess($permissions['form:forms:deleteown'], $permissions['form:forms:deleteother'],
    $activeForm->getCreatedBy())): ?>
    <a href="javascript:void(0);" class="btn btn-default"
       onclick="Mautic.showConfirmation(
           '<?php echo $view->escape($view["translator"]->trans("mautic.form.form.confirmdelete",
           array("%name%" => $activeForm->getName() . " (" . $activeForm->getId() . ")")), 'js'); ?>',
           '<?php echo $view->escape($view["translator"]->trans("mautic.core.form.delete"), 'js'); ?>',
           'executeAction',
           ['<?php echo $view['router']->generate('mautic_form_action',
           array("objectAction" => "delete", "objectId" => $activeForm->getId())); ?>',
           '#mautic_form_index'],
           '<?php echo $view->escape($view["translator"]->trans("mautic.core.form.cancel"), 'js'); ?>','',[]);">
        <span><i class="fa fa-fw fa-trash-o text-danger"></i> <?php echo $view['translator']->trans('mautic.core.form.delete'); ?></span>
    </a>
<?php endif; ?>
<?php $view['slots']->stop(); ?>

  <!-- start: box layout -->
  <div class="box-layout">
      <!-- left section -->
      <div class="col-md-9 bg-white height-auto">
          <div class="bg-auto">
              <!-- form detail header -->
              <div class="pr-md pl-md pt-lg pb-lg">
                  <div class="box-layout">
                      <div class="col-xs-10">
                          <p class="text-muted"><?php echo $activeForm->getDescription(); ?></p>
                      </div>
                      <div class="col-xs-2 text-right">
                          <?php switch ($activeForm->getPublishStatus()) {
                              case 'published':
                                  $labelColor = "success";
                                  break;
                              case 'unpublished':
                              case 'expired'    :
                                  $labelColor = "danger";
                                  break;
                              case 'pending':
                                  $labelColor = "warning";
                                  break;
                          } ?>
                          <?php $labelText = strtoupper($view['translator']->trans('mautic.core.form.' . $activeForm->getPublishStatus())); ?>
                          <h4 class="fw-sb"><span class="label label-<?php echo $labelColor; ?>"><?php echo $labelText; ?></span></h4>
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
                              <?php echo $view->render('MauticCoreBundle:Helper:details.html.php', array('entity' => $activeForm)); ?>
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
                    <a href="javascript:void(0)" class="arrow text-muted collapsed" data-toggle="collapse" data-target="#form-details"><span class="caret"></span> <?php echo $view['translator']->trans('mautic.core.details'); ?></a>
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
                  <?php foreach ($activeFormActions as $group => $groupActions) : ?>
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
                      foreach ($activeForm->getFields() as $field) : ?>
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

      <!-- right section -->
      <div class="col-md-3 bg-white bdr-l height-auto">
          <!-- form HTML -->
          <div class="pa-md">
              <div class="panel bg-info bg-light-lg bdr-w-0 mb-0">
                  <div class="panel-body">
                      <h5 class="fw-sb mb-sm"><?php echo $view['translator']->trans('mautic.form.form.header.copy'); ?></h5>
                      <p class="mb-sm"><?php echo $view['translator']->trans('mautic.form.form.help.landingpages'); ?></p>

                      <a href="#" class="btn btn-info" data-toggle="modal" data-target="#modal-automatic-copy"><?php echo $view['translator']->trans('mautic.form.form.header.automaticcopy'); ?></a>
                      <a href="#" class="btn btn-info" data-toggle="modal" data-target="#modal-manual-copy"><?php echo $view['translator']->trans('mautic.form.form.header.manualcopy'); ?></a>
                  </div>
              </div>
          </div>
          <!--/ form HTML -->

          <hr class="hr-w-2" style="width:50%">

          <!--
          we can leverage data from audit_log table
          and build activity feed from it
          -->
          <div class="panel bg-transparent shd-none bdr-rds-0 bdr-w-0 mb-0">

              <!-- recent activity -->
              <?php echo $view->render('MauticCoreBundle:Helper:recentactivity.html.php', array('logs' => $logs)); ?>

          </div>
      </div>
      <!--/ right section -->

      <!-- #modal-automatic-copy -->
      <div class="modal fade" id="modal-automatic-copy">
          <div class="modal-dialog">
              <div class="modal-content">
                  <div class="modal-header">
                      <h5 class="modal-title fw-sb"><?php echo $view['translator']->trans('mautic.form.form.header.automaticcopy'); ?></h5>
                  </div>
                  <div class="modal-body">
                      <p><?php echo $view['translator']->trans('mautic.form.form.help.automaticcopy'); ?></p>
                      <textarea class="form-html form-control" readonly onclick="this.setSelectionRange(0, this.value.length);">&lt;script type="text/javascript" src="<?php echo $view['router']->generate('mautic_form_generateform', array('id' => $activeForm->getId()), true); ?>"&gt;&lt;/script&gt;</textarea>
                  </div>
                  <div class="modal-footer">
                      <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                  </div>
              </div>
          </div>
      </div>
      <!--/ #modal-automatic-copy -->

      <!-- #modal-manual-copy -->
      <div class="modal fade" id="modal-manual-copy">
          <div class="modal-dialog">
              <div class="modal-content">
                  <div class="modal-header">
                      <h5 class="modal-title fw-sb"><?php echo $view['translator']->trans('mautic.form.form.header.manualcopy'); ?></h5>
                  </div>
                  <div class="panel-body">
                      <p><?php echo $view['translator']->trans('mautic.form.form.help.manualcopy'); ?></p>
                      <textarea class="form-html form-control" readonly onclick="this.setSelectionRange(0, this.value.length);"><?php echo htmlentities($activeForm->getCachedHtml()); ?></textarea>
                  </div>
                  <div class="panel-footer text-right">
                      <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                  </div>
              </div>
          </div>
      </div>
      <!--/ #modal-manual-copy -->
  </div>
  <!--/ end: box layout -->

  <input type="hidden" name="formId" id="formId" value="<?php echo $activeForm->getId(); ?>" />
