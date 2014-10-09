<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'user');
$view['slots']->set("headerTitle", $view['translator']->trans(
    'mautic.user.user.header.contact',
    array('%name%' => $user->getName()))
);
?>

<?php echo $view['form']->form($form); ?>