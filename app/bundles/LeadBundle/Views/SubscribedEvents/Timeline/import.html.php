<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$import = $event['extra'];

?>
<dl class="dl-horizontal">
<?php if (!empty($import['user_id'])) : ?>
    <dt>
        <?php echo $view['translator']->trans('mautic.core.createdby'); ?>
    </dt>
    <dd>
        <a href="<?php echo $view['router']->path('mautic_user_action', ['objectAction' => 'view', 'objectId' => $import['user_id']]); ?>" data-toggle="ajax">
            <?php echo $import['user_name']; ?>
        </a>
    </dd>
<?php endif; ?>
<?php if (!empty($import['properties']['file'])) : ?>
    <dt>
        <?php echo $view['translator']->trans('mautic.lead.import.source.file'); ?>
    </dt>
    <dd>
        <?php echo $import['properties']['file']; ?>
    </dd>
<?php endif; ?>
<?php if (!empty($import['properties']['line'])) : ?>
    <dt>
        <?php echo $view['translator']->trans('mautic.lead.import.csv.line.number'); ?>
    </dt>
    <dd>
        <?php echo $import['properties']['line']; ?>
    </dd>
<?php endif; ?>
</dl>
