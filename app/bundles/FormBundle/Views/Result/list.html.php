<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if ($tmpl == 'index'):
    $view->extend('MauticFormBundle:Result:index.html.php');
endif;

$formId = $form->getId();
?>
<div class="table-responsive scrollable body-white padding-sm page-list">
    <table class="table table-hover table-striped table-bordered formresult-list">
        <thead>
            <tr>
                <th class="col-formresult-id"></th>
                <?php
                echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                    'sessionVar' => 'formresult.'.$formId,
                    'orderBy'    => 's.dateSubmitted',
                    'text'       => 'mautic.form.result.thead.date',
                    'class'      => 'col-formresult-date',
                    'default'    => true,
                    'filterBy'   => 's.dateSubmitted',
                    'dataToggle' => 'date'
                ));

                echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                    'sessionVar' => 'formresult.'.$formId,
                    'orderBy'    => 'i.ipAddress',
                    'text'       => 'mautic.form.result.thead.ip',
                    'class'      => 'col-formresult-ip',
                    'filterBy'   => 'i.ipAddress'
                ));

                $fields = $form->getFields();

                foreach ($fields as $f):
                    if (in_array($f->getType(), array('button', 'freetext')))
                        continue;
                    echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                        'sessionVar' => 'formresult.'.$formId,
                        'text'       => $f->getLabel(),
                        'class'      => 'col-formresult-field col-formresult-field'.$f->getId(),
                        'filterBy'   => 'field.' . $f->getId()
                    ));
                endforeach;
                ?>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $item):?>
            <tr>
                <td><?php echo $item['id']; ?></td>
                <td><?php echo $item['dateSubmitted']->format($dateFormat); ?></td>
                <td><?php echo $item['ipAddress']['ipAddress']; ?></td>
                <?php foreach($item['results'] as $r):?>
                    <td><?php echo $r['value']; ?></td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php echo $view->render('MauticCoreBundle:Helper:pagination.html.php', array(
        "totalItems" => count($items),
        "page"       => $page,
        "limit"      => $limit,
        "baseUrl"    =>  $view['router']->generate('mautic_form_results', array('objectId' => $form->getId())),
        'sessionVar' => 'formresult.'.$formId,
        'target'     => '.formresults'
    )); ?>
    <div class="footer-margin"></div>
</div>
