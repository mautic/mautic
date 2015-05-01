<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if ($tmpl == 'index'):
    $view->extend('MauticFormBundle:Result:index.html.php');
endif;

$formId = $form->getId();
?>
<div class="table-responsive table-responsive-force">
    <table class="table table-hover table-striped table-bordered formresult-list">
        <thead>
            <tr>
                <th class="col-formresult-id"></th>
                <?php
                echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                    'sessionVar' => 'formresult.'.$formId,
                    'orderBy'    => 's.date_submitted',
                    'text'       => 'mautic.form.result.thead.date',
                    'class'      => 'col-formresult-date',
                    'default'    => true,
                    'filterBy'   => 's.date_submitted',
                    'dataToggle' => 'date'
                ));

                echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                    'sessionVar' => 'formresult.'.$formId,
                    'orderBy'    => 'i.ip_address',
                    'text'       => 'mautic.core.ipaddress',
                    'class'      => 'col-formresult-ip',
                    'filterBy'   => 'i.ip_address'
                ));

                $fields = $form->getFields();
                $fieldCount = 3;
                foreach ($fields as $f):
                    if (in_array($f->getType(), array('button', 'freetext')))
                        continue;
                    echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                        'sessionVar' => 'formresult.'.$formId,
                        'orderBy'    => 'r.' . $f->getAlias(),
                        'text'       => $f->getLabel(),
                        'class'      => 'col-formresult-field col-formresult-field'.$f->getId(),
                        'filterBy'   => 'r.' . $f->getAlias(),
                    ));
                    $fieldCount++;
                endforeach;
                ?>
            </tr>
        </thead>
        <tbody>
        <?php if (count($items)): ?>
        <?php foreach ($items as $item):?>
            <tr>
                <td><?php echo $item['id']; ?></td>
                <td>
                    <?php if (!empty($item['lead']['id'])): ?>
                    <a href="<?php echo $view['router']->generate('mautic_lead_action', array('objectAction' => 'view', 'objectId' => $item['lead']['id'])); ?>" data-toggle="ajax">
                        <?php echo $view['date']->toFull($item['dateSubmitted']); ?>
                    </a>
                    <?php else: ?>
                    <?php echo $view['date']->toFull($item['dateSubmitted']); ?>
                    <?php endif; ?>
                </td>
                <td><?php echo $item['ipAddress']['ipAddress']; ?></td>
                <?php foreach($item['results'] as $r):?>
                    <td><?php echo $r['value']; ?></td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="<?php echo $fieldCount; ?>">
                    <?php echo $view->render('MauticCoreBundle:Helper:noresults.html.php'); ?>
                </td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<div class="panel-footer">
    <?php echo $view->render('MauticCoreBundle:Helper:pagination.html.php', array(
        "totalItems" => $totalCount,
        "page"       => $page,
        "limit"      => $limit,
        "baseUrl"    =>  $view['router']->generate('mautic_form_results', array('objectId' => $form->getId())),
        'sessionVar' => 'formresult.'.$formId
    )); ?>
</div>