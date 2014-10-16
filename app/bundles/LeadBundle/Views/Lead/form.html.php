<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$view->extend('MauticCoreBundle:Default:content.html.php');
$header = ($lead->getId()) ?
    $view['translator']->trans('mautic.lead.lead.header.edit',
        array('%name%' => $view['translator']->trans($lead->getPrimaryIdentifier()))) :
    $view['translator']->trans('mautic.lead.lead.header.new');

$view['slots']->set('mauticContent', 'lead');

$groups = array_keys($fields);
?>

<!-- reset container-fluid padding -->
<div class="mna-md">
    <!-- start: box layout -->
    <div class="box-layout">
        <!-- step container -->
        <div class="col-md-3 bg-white height-auto">
            <div class="pr-lg pl-lg pt-md pb-md">
                <h4 class="mb-sm fw-sb"><?php echo $header; ?></h4>

                <ul class="step">
                    <?php $step = 1; ?>
                    <?php foreach ($groups as $g): ?>
                        <?php if (!empty($fields[$g])): ?>
                            <li class="<?php if ($step === 1) echo "active"; ?>">
                                <a href="#<?php echo $g; ?>" class="steps" data-toggle="tab">
                                    <span class="steps-figure"><?php echo $step; ?></span>
                                    <span class="steps-text fw-sb"><?php echo $view['translator']->trans('mautic.lead.field.group.' . $g); ?></span>
                                </a>
                            </li>
                            <?php $step++; ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <!--/ step container -->

        <!-- container -->
        <div class="col-md-9 bg-auto height-auto bdr-l">
            <!-- Tab panes act as a form -->
            <?php echo $view['form']->start($form, array('attr' => array('class' => 'tab-content'))); ?>
                <!-- pane -->
                <?php $first = ' in active'; ?>
                <?php foreach ($fields as $group => $groupFields): ?>
                <?php if (!empty($groupFields)): ?>
                    <div class="tab-pane fade<?php echo $first; ?> bdr-rds-0 bdr-w-0" id="<?php echo $group; ?>">
                        <div class="pa-md bg-auto bg-light-xs bdr-b">
                            <h4 class="fw-sb"><?php echo $view['translator']->trans('mautic.lead.field.group.' . $group); ?></h4>
                        </div>
                        <div class="pa-md">
                            <?php if ($group == 'core'): ?>
                            <?php echo $view['form']->row($form['owner_lookup']); ?>
                            <?php echo $view['form']->row($form['owner']); ?>
                            <hr class="mnr-md mnl-md">
                            <?php endif; ?>
                            <?php foreach ($groupFields as $alias => $field): ?>
                            <?php echo $view['form']->row($form[$alias]); ?>
                            <hr class="mnr-md mnl-md">
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php $first = ''; ?>
                <?php endif; ?>
                <?php endforeach; ?>
                <!--/ #pane -->
            <?php echo $view['form']->end($form); ?>
            <!--/ Tab panes act as a form -->
        </div>
        <!--/ end: container -->
    </div>
    <!--/ end: box layout -->
</div>
<!--/ reset container-fluid padding -->