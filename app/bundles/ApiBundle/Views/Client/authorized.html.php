<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<table class="table table-striped table-hover">
    <thead>
        <tr>
            <th style="width: 50px;"></th>
            <th><?php echo $view['translator']->trans('mautic.api.client.thead.name'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($clients as $k): ?>
        <tr>
            <td>
                <button class="btn btn-danger btn-xs"
                        onclick="Mautic.showConfirmation('<?php echo $view->escape($view["translator"]->trans("mautic.api.client.form.confirmrevoke", array("%name%" => $k->getName())), 'js'); ?>','<?php echo $view->escape($view["translator"]->trans("mautic.api.client.form.revoke"), 'js'); ?>','executeAction',['<?php echo $view['router']->generate('mautic_client_action',array("objectAction" => "revoke", "objectId" => $k->getId())); ?>'],'<?php echo $view->escape($view["translator"]->trans("mautic.core.form.cancel"), 'js'); ?>','',[]);">
                    <i class="fa fa-trash-o padding-sm-right"></i><span><?php echo $view["translator"]->trans("mautic.api.client.form.revoke"); ?></span>
                </button>
            </td>
            <td><?php echo $k->getName(); ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
