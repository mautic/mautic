<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$buttonCount = 0;
$groupType   = 'group';

// if any custom buttons are defined in the template $buttonCount=1 should display these in a dropdown,
// a larger number will display them in a group
// 0 will not display them
if (isset($preCustomButtons) or isset($customButtons) or isset($postCustomButtons)) {
    $buttonCount = 1;
    $groupType   = 'button-dropdown';
}


include 'action_button_helper.php';

echo '<div class="std-toolbar btn-group">';

foreach ($templateButtons as $action => $enabled) {

    $btnClass = 'btn btn-default';

    switch ($action) {
        case 'clone':
        case 'abtest':
            $icon = ($action == 'clone') ? 'copy' : 'sitemap';
            echo '<a class="'.$btnClass.'" href="'.$view['router']->path(
                    'mautic_'.$routeBase.'_action',
                    array_merge(array("objectAction" => $action), $query)
                ).'" data-toggle="ajax"'.$menuLink.">\n";
            echo '  <i class="fa fa-'.$icon.'"></i> <span class="hidden-xs hidden-sm">'.$view['translator']->trans(
                    'mautic.core.form.'.$action
                )."</span>\n";
            echo "</a>\n";
            break;
        case 'close':
            $icon = 'remove';
            echo '<a class="'.$btnClass.'" href="'.$view['router']->path('mautic_'.$routeBase.'_index')
                .'" data-toggle="'.$editMode.'"'.$editAttr.$menuLink.">\n";
            echo '  <i class="fa fa-'.$icon.'"></i> <span class="hidden-xs hidden-sm">'.$view['translator']->trans(
                    'mautic.core.form.'.$action
                )."</span>\n";
            echo "</a>\n";
            break;
        case 'new':
        case'edit':
            if ($action == 'new') {
                $icon = 'plus';
            } else {
                $icon              = 'pencil-square-o';
                $query['objectId'] = $item->getId();
            }
            echo '<a class="'.$btnClass.'" href="'.$view['router']->path(
                    'mautic_'.$routeBase.'_action',
                    array_merge(array("objectAction" => $action), $query)
                ).'" data-toggle="'.$editMode.'"'.$editAttr.$menuLink.">\n";
            echo '  <i class="fa fa-'.$icon.'"></i> <span class="hidden-xs hidden-sm">'.$view['translator']->trans(
                    'mautic.core.form.'.$action
                )."</span>\n";
            echo "</a>\n";
            break;
        case 'delete':
            echo $view->render(
                'MauticCoreBundle:Helper:confirm.html.php',
                array(
                    'message'       => $view["translator"]->trans(
                        "mautic.".$langVar.".form.confirmdelete",
                        array("%name%" => $item->$nameGetter()." (".$item->getId().")")
                    ),
                    'confirmAction' => $view['router']->path(
                        'mautic_'.$routeBase.'_action',
                        array_merge(array("objectAction" => "delete", "objectId" => $item->getId()), $query)
                    ),
                    'template'      => 'delete',
                    'btnTextClass'  => 'hidden-xs hidden-sm',
                    'btnClass'      => $btnClass
                )
            );
            break;
    }

}

if ($buttonCount > 0) {

    echo '<div class="dropdown-toolbar btn-group">';

    $dropdownOpenHtml = '<button type="button" class="btn btn-default btn-nospin  dropdown-toggle" data-toggle="dropdown" aria-expanded="false"><i class="fa fa-caret-down"></i></button>'
        ."\n";
    $dropdownOpenHtml .= '<ul class="dropdown-menu dropdown-menu-right" role="menu">'."\n";

    echo $view['buttons']->renderPreCustomButtons($buttonCount, $dropdownOpenHtml);
    echo $view['buttons']->renderPostCustomButtons($buttonCount, $dropdownOpenHtml);

    echo '</ul></div>';

}

echo '</div>';
echo $extraHtml;