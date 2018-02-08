<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$formId = $form->getId();

?>
<div class="table-responsive table-responsive-force">
    <table class="table table-hover table-striped table-bordered formresult-list">
        <thead>
            <tr>
                <?php
                echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', [
                    'sessionVar' => 'formresult.'.$formId,
                    'text'       => 'mautic.form.result.thead.date',
                    'class'      => 'col-formresult-date',
                    'default'    => true,
                    'dataToggle' => 'date',
                ]);

                $fields     = $form->getFields();
                $fieldCount = ($canDelete) ? 4 : 3;
                foreach ($fields as $f):
                    if (in_array($f->getType(), $viewOnlyFields) || $f->getSaveResult() === false) {
                        continue;
                    }
                    echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', [
                        'sessionVar' => 'formresult.'.$formId,
                        'text'       => $f->getLabel(),
                        'class'      => 'col-formresult-field col-formresult-field'.$f->getId(),
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
                <td>
                    <?php echo $view['date']->toFull($item['dateSubmitted']); ?>
                </td>
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
        <?php endif; ?>
        </tbody>
    </table>
</div>
