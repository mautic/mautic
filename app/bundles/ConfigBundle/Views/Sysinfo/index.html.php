<?php

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'sysinfo');
$view['slots']->set('headerTitle', $view['translator']->trans('mautic.sysinfo.header.index'));
?>

<!-- start: box layout -->
<div class="box-layout">
    <!-- step container -->
    <div class="col-md-3 bg-white height-auto">
        <div class="pr-lg pl-lg pt-md pb-md">
            <!-- Nav tabs -->
            <ul class="list-group list-group-tabs" role="tablist">
                <li role="presentation" class="list-group-item in active">
                    <a href="#phpinfo" aria-controls="phpinfo" role="tab" data-toggle="tab">
                        <?php echo $view['translator']->trans('mautic.sysinfo.tab.phpinfo'); ?>
                    </a>
                </li>
                <li role="presentation" class="list-group-item">
                    <a href="#recommendations" aria-controls="phpinfo" role="tab" data-toggle="tab">
                        <?php echo $view['translator']->trans('mautic.sysinfo.tab.recommendations'); ?>
                    </a>
                </li>
                <li role="presentation" class="list-group-item">
                    <a href="#folders" aria-controls="folders" role="tab" data-toggle="tab">
                        <?php echo $view['translator']->trans('mautic.sysinfo.tab.folders'); ?>
                    </a>
                </li>
                <li role="presentation" class="list-group-item">
                    <a href="#log" aria-controls="log" role="tab" data-toggle="tab">
                        <?php echo $view['translator']->trans('mautic.sysinfo.tab.log'); ?>
                    </a>
                </li>
                <li role="presentation" class="list-group-item">
                    <a href="#dbinfo" aria-controls="dbinfo" role="tab" data-toggle="tab">
                        <?php echo $view['translator']->trans('mautic.sysinfo.tab.dbinfo'); ?>
                    </a>
                </li>
            </ul>

        </div>
    </div>

    <!-- container -->
    <div class="col-md-9 bg-auto height-auto bdr-l">

        <!-- Tab panes -->
        <div class="tab-content">
            <div role="tabpanel" class="tab-pane fade in active bdr-w-0" id="phpinfo">
                <div class="pt-md pr-md pl-md pb-md">
                    <?php echo $phpInfo; ?>
                </div>
            </div>
            <div role="tabpanel" class="tab-pane fade bdr-w-0" id="recommendations">
                <div class="pt-md pr-md pl-md pb-md">
                    <?php if (empty($recommendations) && empty($requirement)) : ?>
                        <div class="alert alert-info">
                            <?php echo $view['translator']->trans('mautic.sysinfo.no.recommendations'); ?>
                        </div>
                    <?php endif; ?>
                    <?php foreach ($requirements as $requirement): ?>
                        <div class="alert alert-danger">
                            <?php echo $requirement; ?>
                        </div>
                    <?php endforeach; ?>
                    <?php foreach ($recommendations as $recommendation): ?>
                        <div class="alert alert-warning">
                            <?php echo $recommendation; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div role="tabpanel" class="tab-pane fade bdr-w-0" id="folders">
                <div class="pt-md pr-md pl-md pb-md">
                    <h2 class="pb-md"><?php echo $view['translator']->trans('mautic.sysinfo.folders.title'); ?></h2>
                    <table class="table">
                        <thead>
                            <tr>
                                <th><?php echo $view['translator']->trans('mautic.sysinfo.folder.path'); ?></th>
                                <th><?php echo $view['translator']->trans('mautic.sysinfo.is.writable'); ?></th>
                            </tr>
                        </thead>
                        <?php foreach ($folders as $folder => $isWritable) : ?>
                            <tr class="<?php echo ($isWritable) ? 'success' : 'danger'; ?>">
                                <td><?php echo $folder; ?></td>
                                <td><?php echo ($isWritable) ? $view['translator']->trans('mautic.sysinfo.writable') : $view['translator']->trans('mautic.sysinfo.unwritable'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>
            <div role="tabpanel" class="tab-pane fade bdr-w-0" id="log">
                <div class="pt-md pr-md pl-md pb-md">
                    <h2 class="pb-md"><?php echo $view['translator']->trans('mautic.sysinfo.log.title'); ?></h2>
                    <?php if ($log) : ?>
                        <pre><?php echo $log; ?></pre>
                    <?php else : ?>
                        <div class="alert alert-info" role="alert">
                            <?php echo $view['translator']->trans('mautic.sysinfo.log.missing'); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div role="tabpanel" class="tab-pane fade bdr-w-0" id="dbinfo">
                <div class="pt-md pr-md pl-md pb-md">
                    <h2 class="pb-md"><?php echo $view['translator']->trans('mautic.sysinfo.dbinfo.title'); ?></h2>
                    <table class="table">
                        <thead>
                            <tr>
                                <th><?php echo $view['translator']->trans('mautic.sysinfo.dbinfo.property'); ?></th>
                                <th><?php echo $view['translator']->trans('mautic.sysinfo.dbinfo.value'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><?php echo $view['translator']->trans('mautic.sysinfo.dbinfo.version'); ?></td>
                                <td id="dbinfo-version"><?php echo $dbInfo['version']; ?></td>
                            </tr>
                            <tr>
                                <td><?php echo $view['translator']->trans('mautic.sysinfo.dbinfo.driver'); ?></td>
                                <td id="dbinfo-driver"><?php echo $dbInfo['driver']; ?></td>
                            </tr>
                            <tr>
                                <td><?php echo $view['translator']->trans('mautic.sysinfo.dbinfo.platform'); ?></td>
                                <td id="dbinfo-platform"><?php echo $dbInfo['platform']; ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>
