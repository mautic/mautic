<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if ($tmpl == 'index'):
    $view->extend('MauticFormBundle:Result:index.html.php');
endif;

$formId = $form->getId();

?>
<div class="table-responsive table-responsive-force">
    <table class="table table-hover table-striped table-bordered formresult-list" id="formResultTable">
        <thead>
            <tr>
                <?php
                if ($canDelete):
                echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', [
                    'checkall'        => 'true',
                    'target'          => '#formResultTable',
                    'routeBase'       => 'form_results',
                    'query'           => ['formId' => $formId],
                    'templateButtons' => [
                        'delete' => $canDelete,
                    ],
                ]);
                endif;

                echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', [
                    'sessionVar' => 'formresult.'.$formId,
                    'orderBy'    => 's.id',
                    'text'       => 'mautic.core.id',
                    'class'      => 'col-formresult-id',
                    'filterBy'   => 's.id',
                ]);

                echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', [
                    'sessionVar' => 'formresult.'.$formId,
                    'orderBy'    => 's.date_submitted',
                    'text'       => 'mautic.form.result.thead.date',
                    'class'      => 'col-formresult-date',
                    'default'    => true,
                    'filterBy'   => 's.date_submitted',
                    'dataToggle' => 'date',
                ]);

                echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', [
                    'sessionVar' => 'formresult.'.$formId,
                    'orderBy'    => 'i.ip_address',
                    'text'       => 'mautic.core.ipaddress',
                    'class'      => 'col-formresult-ip',
                    'filterBy'   => 'i.ip_address',
                ]);

                $fields     = $form->getFields();
                $fieldCount = ($canDelete) ? 4 : 3;
                foreach ($fields as $f):
                    if (in_array($f->getType(), $viewOnlyFields) || $f->getSaveResult() === false) {
                        continue;
                    }
                    echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', [
                        'sessionVar' => 'formresult.'.$formId,
                        'orderBy'    => 'r.'.$f->getAlias(),
                        'text'       => $f->getLabel(),
                        'class'      => 'col-formresult-field col-formresult-field'.$f->getId(),
                        'filterBy'   => 'r.'.$f->getAlias(),
                    ]);
                    ++$fieldCount;
                endforeach;
                ?>
            </tr>
        </thead>
        <tbody>
        <?php if (count($items)): ?>
        <?php foreach ($items as $item): ?>
            <?php $item['name'] = $view['translator']->trans('mautic.form.form.results.name', ['%id%' => $item['id']]); ?>
            <tr>
                <?php if ($canDelete): ?>
                <td>
                    <?php
                    echo $view->render('MauticCoreBundle:Helper:list_actions.html.php', [
                        'item'            => $item,
                        'templateButtons' => [
                            'delete' => $canDelete,
                        ],
                        'route'   => 'mautic_form_results_action',
                        'langVar' => 'form.results',
                        'query'   => [
                            'formId'       => $formId,
                            'objectAction' => 'delete',
                        ],
                    ]);
                    ?>
                </td>
                <?php endif; ?>

                <td><?php echo $item['id']; ?></td>
                <td>
                    <?php if (!empty($item['lead']['id'])): ?>
                    <a href="<?php echo $view['router']->path('mautic_contact_action', ['objectAction' => 'view', 'objectId' => $item['lead']['id']]); ?>" data-toggle="ajax">
                        <?php echo $view['date']->toFull($item['dateSubmitted']); ?>
                    </a>
                    <?php else: ?>
                    <?php echo $view['date']->toFull($item['dateSubmitted']); ?>
                    <?php endif; ?>
                </td>
                <td><?php echo $item['ipAddress']; ?></td>
                <?php foreach ($item['results'] as $key => $r): ?>
                    <?php $isTextarea = $r['type'] === 'textarea'; ?>
                    <td <?php echo $isTextarea ? 'class="long-text"' : ''; ?>>
                        <?php if ($isTextarea) : ?>
                            <?php echo nl2br($r['value']); ?>
                        <?php elseif ($r['type'] === 'file') : ?>
                            <a href="<?php echo $view['router']->path('mautic_form_file_download', ['submissionId' => $item['id'], 'field' => $key]); ?>">
                                <?php echo $r['value']; ?>
                            </a>
                        <?php else : ?>
                            <?php echo $r['value']; ?>
                        <?php endif; ?>
                    </td>
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
    <?php echo $view->render('MauticCoreBundle:Helper:pagination.html.php', [
        'totalItems' => $totalCount,
        'page'       => $page,
        'limit'      => $limit,
        'baseUrl'    => $view['router']->path('mautic_form_results', ['objectId' => $form->getId()]),
        'sessionVar' => 'formresult.'.$formId,
    ]); ?>
</div>
