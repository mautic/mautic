<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$baseUrl = $view['router']->generate('mautic_chatchannel_list');
?>
<div class="table-responsive chat-channel-list">
    <table class="table table-hover table-striped table-bordered">
        <thead>
        <tr>
            <?php
            echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                'sessionVar' => 'chat.channel',
                'orderBy'    => 'c.name',
                'text'       => 'mautic.chat.channel.thead.name',
                'default'    => true,
                'target'     => '.chat-channel-list',
                'baseUrl'    => $view['router']->generate('mautic_chatchannel_list'),
                'filterBy'   => 'c.name'
            ));

            ?>
            <th class="col-chat-count"></th>
            <th class="col-chat-settings">
                <div class="dropdown">
                    <a class="dropdown-toggle btn btn-default btn-nospin" data-toggle="dropdown" href="#">
                        <?php $class = (!empty($filters)) ? ' text-danger' : ''; ?>
                        <i class="fa fa-filter<?php echo $class; ?>"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-form dropdown-menu-right" role="menu">
                        <li>
                            <label class="checkbox">
                                <?php $checked = (in_array('newmessages', $filters)) ? ' checked' : ''; ?>
                                <input type="checkbox"<?php echo $checked; ?> onchange="Mautic.filterByChatAttribute('channels', 'newmessages', mQuery(this).prop('checked'), '<?php echo $baseUrl; ?>');">
                                <?php echo $view['translator']->trans('mautic.chat.filter.newmessages'); ?>
                            </label>
                        </li>
                        <li>
                            <label class="checkbox">
                                <?php $checked = (in_array('invisible', $filters)) ? ' checked' : ''; ?>
                                <input type="checkbox"<?php echo $checked; ?> onchange="Mautic.filterByChatAttribute('channels', 'invisible', mQuery(this).prop('checked'), '<?php echo $baseUrl; ?>');">
                                <?php echo $view['translator']->trans('mautic.chat.filter.invisible'); ?>
                            </label>
                        </li>
                        <li>
                            <label class="checkbox">
                                <?php $checked = (in_array('silent', $filters)) ? ' checked' : ''; ?>
                                <input type="checkbox"<?php echo $checked; ?> onchange="Mautic.filterByChatAttribute('channels', 'silent', mQuery(this).prop('checked'), '<?php echo $baseUrl; ?>');">
                                <?php echo $view['translator']->trans('mautic.chat.filter.silent'); ?>
                            </label>
                        </li>
                        <li>
                            <label class="checkbox">
                                <?php $checked = (in_array('mute', $filters)) ? ' checked' : ''; ?>
                                <input type="checkbox"<?php echo $checked; ?> onchange="Mautic.filterByChatAttribute('channels', 'mute', mQuery(this).prop('checked'), '<?php echo $baseUrl; ?>');">
                                <?php echo $view['translator']->trans('mautic.chat.filter.mute'); ?>
                            </label>
                        </li>
                        <li>
                            <label class="checkbox">
                                <?php $checked = (in_array('archived', $filters)) ? ' checked' : ''; ?>
                                <input type="checkbox"<?php echo $checked; ?> onchange="Mautic.filterByChatAttribute('channels', 'archived', mQuery(this).prop('checked'), '<?php echo $baseUrl; ?>');">
                                <?php echo $view['translator']->trans('mautic.chat.filter.archived'); ?>
                            </label>
                        </li>
                        <li>
                            <label class="checkbox">
                                <?php $checked = (in_array('subscribed', $filters)) ? ' checked' : ''; ?>
                                <input type="checkbox"<?php echo $checked; ?> onchange="Mautic.filterByChatAttribute('channels', 'subscribed', mQuery(this).prop('checked'), '<?php echo $baseUrl; ?>');">
                                <?php echo $view['translator']->trans('mautic.chat.filter.subscribed'); ?>
                            </label>
                        </li>
                    </ul>
                </div>
            </th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $item):?>
            <?php
            $createdby = $item->getCreatedBy();
            $isMine = ($createdby && $createdby->getId() == $me->getId());
            ?>
            <tr>
                <td>
                    <?php if ($isMine):?>
                        <a href="<?php echo $view['router']->generate('mautic_chatchannel_action', array('objectAction' => 'edit', 'objectId' => $item->getId())); ?>" data-toggle="ajaxmodal" data-target="#MauticSharedModal" data-header="<?php echo $view['translator']->trans('mautic.chat.channel.header.edit', array('%name%' => $item->getName())); ?>"><?php echo $item->getName(); ?></a>
                    <?php else: ?>
                    <?php echo $item->getName(); ?>
                    <?php endif; ?>
                    <?php
                    $description = $item->getDescription();
                    if (!empty($description)):
                    ?>
                    <div class="small"><?php echo $description; ?></div>
                    <?php endif; ?>
                </td>
                <td>
                    <?php $count = (isset($unread[$item->getId()])) ? $unread[$item->getId()] : 0; ?>
                    <?php $class = ($count > 0) ? 'primary' : 'default'; ?>
                    <span class="label label-<?php echo $class; ?> label-as-badge"><?php echo $count; ?></span>
                </td>
                <td>
                    <div class="dropdown">
                        <a class="dropdown-toggle btn btn-default btn-xs btn-nospin" data-toggle="dropdown" href="#">
                            <i class="fa fa-cog"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-form dropdown-menu-right" role="menu">
                            <li>
                                <label class="checkbox">
                                    <?php $checked = (isset($stats[$item->getId()])) ? ' checked' : ''; ?>
                                    <input id="channels_subscribed<?php echo $item->getId(); ?>" type="checkbox"<?php echo $checked; ?> onchange="Mautic.toggleChatSetting('channels', 'subscribed', <?php echo $item->getId(); ?>, mQuery(this).prop('checked'));">
                                    <?php echo $view['translator']->trans('mautic.chat.setting.subscribed'); ?>
                                </label>
                            </li>
                            <li>
                                <label class="checkbox">
                                    <?php $checked = (in_array($item->getId(), $settings['visible'])) ? ' checked' : ''; ?>
                                    <input id="channels_visible<?php echo $item->getId(); ?>" type="checkbox"<?php echo $checked; ?> onchange="Mautic.toggleChatSetting('channels', 'visible', <?php echo $item->getId(); ?>, mQuery(this).prop('checked'));">
                                    <?php echo $view['translator']->trans('mautic.chat.setting.visible'); ?>
                                </label>
                            </li>
                            <li>
                                <label class="checkbox">
                                    <?php $checked = (!in_array($item->getId(), $settings['silent'])) ? ' checked' : ''; ?>
                                    <input id="channels_notifications<?php echo $item->getId(); ?>" type="checkbox"<?php echo $checked; ?> onchange="Mautic.toggleChatSetting('channels', 'notifications', <?php echo $item->getId(); ?>, mQuery(this).prop('checked'));">
                                    <?php echo $view['translator']->trans('mautic.chat.setting.notifications'); ?>
                                </label>
                            </li>
                            <li>
                                <label class="checkbox">
                                    <?php $checked = (!in_array($item->getId(), $settings['mute'])) ? ' checked' : ''; ?>
                                    <input id="channels_sound<?php echo $item->getId(); ?>" type="checkbox"<?php echo $checked; ?> onchange="Mautic.toggleChatSetting('channels', 'sound', <?php echo $item->getId(); ?>, mQuery(this).prop('checked'));">
                                    <?php echo $view['translator']->trans('mautic.chat.setting.sound'); ?>
                                </label>
                            </li>
                            <?php if ($isMine): ?>
                            <li>
                                <label class="checkbox">
                                    <?php $checked = (!$item->isPublished()) ? ' checked' : ''; ?>
                                    <input id="channels_archived<?php echo $item->getId(); ?>" type="checkbox"<?php echo $checked; ?> onchange="Mautic.toggleChatSetting('channels', 'archived', <?php echo $item->getId(); ?>, mQuery(this).prop('checked'));">
                                    <?php echo $view['translator']->trans('mautic.chat.setting.archived'); ?>
                                </label>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <div class="panel-footer">
        <?php echo $view->render('MauticCoreBundle:Helper:pagination.html.php', array(
            'totalItems' => count($items),
            'page'       => $page,
            'limit'      => $limit,
            'baseUrl'    =>  $view['router']->generate('mautic_chatchannel_list'),
            'sessionVar' => 'chat.channel',
            'target'     => '.chat-channel-list'
        )); ?>
    </div>
</div>
