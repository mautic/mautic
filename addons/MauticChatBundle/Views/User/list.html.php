<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$baseUrl = $view['router']->generate('mautic_chat_list');
?>
<div class="table-responsive chat-user-list">
    <table class="table table-hover table-striped table-bordered">
        <thead>
        <tr>
            <th class="col-user-avatar visible-md visible-lg"></th>
            <?php
            echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                'sessionVar' => 'chat.user',
                'orderBy'    => 'u.lastName, u.firstName, u.username',
                'text'       => 'mautic.chat.user.thead.name',
                'class'      => 'col-user-name',
                'default'    => true,
                'target'     => '.chat-user-list',
                'baseUrl'    => $view['router']->generate('mautic_chat_list'),
                'filterBy'   => 'u.lastName, u.firstName'
            ));

            echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                'sessionVar' => 'chat.user',
                'orderBy'    => 'u.username',
                'text'       => 'mautic.chat.user.thead.username',
                'class'      => 'col-user-username visible-md visible-lg',
                'target'     => '.chat-user-list',
                'baseUrl'    => $view['router']->generate('mautic_chat_list'),
                'filterBy'   => 'u.username'
            ));
            ?>
            <th class="col-chat-count"></th>
            <th class="col-chat-settings">
                <div class="dropdown">
                    <a class="dropdown-toggle btn btn-default btn-xs btn-nospin" data-toggle="dropdown" href="#">
                        <?php $class = (!empty($filters)) ? ' text-danger' : ''; ?>
                        <i class="fa fa-filter<?php echo $class; ?>"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-form dropdown-menu-right" role="menu">
                        <li>
                            <label class="checkbox">
                                <?php $checked = (in_array('newmessages', $filters)) ? ' checked' : ''; ?>
                                <input type="checkbox"<?php echo $checked; ?> onchange="Mautic.filterByChatAttribute('users', 'newmessages', mQuery(this).prop('checked'), '<?php echo $baseUrl; ?>');">
                                <?php echo $view['translator']->trans('mautic.chat.filter.newmessages'); ?>
                            </label>
                        </li>
                        <li>
                            <label class="checkbox">
                                <?php $checked = (in_array('invisible', $filters)) ? ' checked' : ''; ?>
                                <input type="checkbox"<?php echo $checked; ?> onchange="Mautic.filterByChatAttribute('users', 'invisible', mQuery(this).prop('checked'), '<?php echo $baseUrl; ?>');">
                                <?php echo $view['translator']->trans('mautic.chat.filter.invisible'); ?>
                            </label>
                        </li>
                        <li>
                            <label class="checkbox">
                                <?php $checked = (in_array('silent', $filters)) ? ' checked' : ''; ?>
                                <input type="checkbox"<?php echo $checked; ?> onchange="Mautic.filterByChatAttribute('users', 'silent', mQuery(this).prop('checked'), '<?php echo $baseUrl; ?>');">
                                <?php echo $view['translator']->trans('mautic.chat.filter.silent'); ?>
                            </label>
                        </li>
                        <li>
                            <label class="checkbox">
                                <?php $checked = (in_array('mute', $filters)) ? ' checked' : ''; ?>
                                <input type="checkbox"<?php echo $checked; ?> onchange="Mautic.filterByChatAttribute('users', 'mute', mQuery(this).prop('checked'), '<?php echo $baseUrl; ?>');">
                                <?php echo $view['translator']->trans('mautic.chat.filter.mute'); ?>
                            </label>
                        </li>
                    </ul>
                </div>
            </th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $item):?>
            <tr>
                <td class="visible-md visible-lg">
                    <img class="img img-responsive img-thumbnail mr-sm" src="<?php echo $view['gravatar']->getImage($item->getEmail(), '50'); ?>" />
                </td>
                <td>
                    <?php echo $item->getName(true); ?>
                </td>
                <td class="visible-md visible-lg"><?php echo $item->getUsername(); ?></td>
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
                                    <?php $checked = (in_array($item->getId(), $settings['visible'])) ? ' checked' : ''; ?>
                                    <input id="users_visible<?php echo $item->getId(); ?>" type="checkbox"<?php echo $checked; ?> onchange="Mautic.toggleChatSetting('users', 'visible', <?php echo $item->getId(); ?>, mQuery(this).prop('checked'));">
                                    <?php echo $view['translator']->trans('mautic.chat.setting.visible'); ?>
                                </label>
                            </li>
                            <li>
                                <label class="checkbox">
                                    <?php $checked = (!in_array($item->getId(), $settings['silent'])) ? ' checked' : ''; ?>
                                    <input id="users_notifications<?php echo $item->getId(); ?>" type="checkbox"<?php echo $checked; ?> onchange="Mautic.toggleChatSetting('users', 'notifications', <?php echo $item->getId(); ?>, mQuery(this).prop('checked'));">
                                    <?php echo $view['translator']->trans('mautic.chat.setting.notifications'); ?>
                                </label>
                            </li>
                            <li>
                                <label class="checkbox">
                                    <?php $checked = (!in_array($item->getId(), $settings['mute'])) ? ' checked' : ''; ?>
                                    <input id="users_sound<?php echo $item->getId(); ?>" type="checkbox"<?php echo $checked; ?> onchange="Mautic.toggleChatSetting('users', 'sound', <?php echo $item->getId(); ?>, mQuery(this).prop('checked'));">
                                    <?php echo $view['translator']->trans('mautic.chat.setting.sound'); ?>
                                </label>
                            </li>
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
            'baseUrl'    =>  $view['router']->generate('mautic_chat_list'),
            'sessionVar' => 'chat.user',
            'target'     => '.chat-user-list'
        )); ?>
    </div>
</div>
