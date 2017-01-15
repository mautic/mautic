<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if ($tmpl == 'index') {
    $view->extend('MauticInstallBundle:Install:content.html.php');
}

?>
<div class="panel-heading">
    <h2 class="panel-title">
        <?php echo $view['translator']->trans('mautic.install.heading.check.environment'); ?>
    </h2>
</div>
<div class="panel-body">
    <?php if (count($majors)) : ?>
    <div class="panel-group" id="minors">
        <div class="panel panel-danger">
            <div class="panel-heading">
                <h3 class="panel-title" style="font-size: 1em;">
                    <?php echo $view['translator']->trans('mautic.install.heading.major.problems'); ?>
                </h3>
            </div>
            <div class="panel-body alert-warning">
                <p><?php echo $view['translator']->trans('mautic.install.sentence.major.problems', ['%majors%' => count($majors)]); ?></p>
            </div>
            <ul class="list-group">
                <?php foreach ($majors as $message) : ?>
                    <?php switch ($message) :
                        case 'mautic.install.cache.unwritable': ?>
                            <li class="list-group-item"><?php echo $view['translator']->trans('mautic.install.directory.unwritable', ['%path%' => $appRoot.'/cache']); ?></li>
                            <?php break;
                        case 'mautic.install.config.unwritable': ?>
                            <li class="list-group-item"><?php echo $view['translator']->trans($message, ['%path%' => $configFile]); ?></li>
                            <?php break;
                        case 'mautic.install.logs.unwritable': ?>
                            <li class="list-group-item"><?php echo $view['translator']->trans('mautic.install.directory.unwritable', ['%path%' => $appRoot.'/logs']); ?></li>
                            <?php break;
                        case 'mautic.install.apc.version': ?>
                            <?php $minAPCverison = version_compare(PHP_VERSION, '5.4.0', '>=') ? '3.1.13' : '3.0.17'; ?>
                            <li class="list-group-item"><?php echo $view['translator']->trans($message, ['%minapc%' => $minAPCverison, '%currentapc%' => phpversion('apc')]); ?></li>
                            <?php break;
                        default: ?>
                            <li class="list-group-item"><?php echo $view['translator']->trans($message); ?></li>
                            <?php break;
                    endswitch; ?>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!count($majors)) : ?>
        <div class="alert alert-success">
            <h4><i class="fa fa-check"></i> <?php echo $view['translator']->trans('mautic.install.heading.ready'); ?></h4>
            <p><?php echo $view['translator']->trans('mautic.install.sentence.ready'); ?></p>
        </div>
    <?php endif; ?>


    <?php if (count($minors)) : ?>
    <div class="panel-group" id="minors">
        <div class="panel panel-warning">
            <div class="panel-heading">
                <h3 class="panel-title" style="font-size: 1em;">
                    <a data-toggle="collapse" style="display: block; " href="#minorDetails"><i class="fa fa-chevron-down"></i> <?php echo $view['translator']->trans('mautic.install.heading.minor.problems'); ?></a>
                </h3>
            </div>
            <div id="minorDetails" class="panel-collapse collapse">
                <div class="panel-body alert-warning">
                    <p><?php echo $view['translator']->trans('mautic.install.sentence.minor.problems'); ?></p>
                </div>
                 <ul class="list-group">
                    <?php foreach ($minors as $message) : ?>
                        <?php switch ($message) :
                            case 'mautic.install.pcre.version': ?>
                                <li class="list-group-item"><?php echo $view['translator']->trans($message, ['%pcreversion%' => (float) PCRE_VERSION]); ?></li>
                                <?php break;
                            case 'mautic.install.php.version.not.supported': ?>
                                <li class="list-group-item"><?php echo $view['translator']->trans($message, ['%phpversion%' => PHP_VERSION]); ?></li>
                                <?php break;
                            case 'mautic.install.php.version.has.only.security.support': ?>
                                <li class="list-group-item"><?php echo $view['translator']->trans($message, ['%phpversion%' => PHP_VERSION]); ?></li>
                                <?php break;
                            default: ?>
                                <li class="list-group-item"><?php echo $view['translator']->trans($message); ?></li>
                                <?php break; ?>
                        <?php endswitch; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!(count($majors))): ?>
    <?php echo $view['form']->start($form); ?>

        <div class="row mt-20">
            <div class="col-sm-12">
                <?php echo $view['form']->row($form['buttons']); ?>
            </div>
        </div>

    <?php echo $view['form']->end($form); ?>
    <?php endif; ?>
</div>
