<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:slim.html.php');
$view['blocks']->set('pageTitle', $pageTitle);
$view['blocks']->set('mauticContent', 'formresult');
$view['blocks']->set("headerTitle", $view['translator']->trans('mautic.form.result.header.index', array(
    '%name%' => $form->getName()
)));
?>

<div class="table-responsive body-white padding-sm formresults">
    <?php
    /**
     * @package     Mautic
     * @copyright   2014 Mautic, NP. All rights reserved.
     * @author      Mautic
     * @link        http://mautic.com
     * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
     */
    $formId = $form->getId();
    ?>
    <table class="table table-hover table-striped table-bordered formresult-list">
        <thead>
        <tr>
            <th class="col-formresult-id"></th>
            <th class="col-formresult-date"><?php echo $view['translator']->trans('mautic.form.result.thead.date'); ?></th>
            <th class="col-formresult-ip"><?php echo $view['translator']->trans('mautic.form.result.thead.ip'); ?></th>
            <?php
            $fields = $form->getFields();
            foreach ($fields as $f):
            if (in_array($f->getType(), array('button', 'freetext'))) continue;
            ?>
            <th class="col-formresult-field col-formresult-field<?php echo $f->getId(); ?>"><?php echo $f->getLabel(); ?></th>
            <?php endforeach; ?>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($results as $item):?>
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
</div>