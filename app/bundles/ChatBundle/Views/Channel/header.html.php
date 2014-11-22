<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<div>
    <span><?php echo $channel->getName(); ?></span>
    <div class="pull-right chat-channel-buttons">
        <button class="btn btn-primary btn-xs" type="button" data-toggle="dropdown">
            <i class="fa fa-gear"></i>
        </button>
        <ul class="dropdown-menu text-left" role="menu">
            <li>
                <a href="<?php echo $view['router']->generate('mautic_chatchannel_action', array(
                    'objectAction' => 'edit',
                    'objectId'     => $channel->getId()
                )); ?>" data-toggle="ajax" data-ignore-formexit="true"><?php echo $view['translator']->trans('mautic.core.form.edit'); ?></a>
            </li>
            <li>
                <a href="<?php echo $view['router']->generate('mautic_chatchannel_action', array(
                    'objectAction' => 'archive',
                    'objectId'     => $channel->getId()
                )); ?>" data-toggle="ajax" data-ignore-formexit="true"><?php echo $view['translator']->trans('mautic.core.form.archive'); ?></a>
            </li>
        </ul>
    </div>
</div>
