<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'asset');
$view['slots']->set("headerTitle", $view['translator']->trans('mautic.asset.asset.header.index'));
$view['slots']->set('searchUri', $view['router']->generate('mautic_asset_index', array('asset' => $asset)));
$view['slots']->set('searchString', $app->getSession()->get('mautic.asset.filter'));
$view['slots']->set('searchHelp', $view['translator']->trans('mautic.asset.asset.help.searchcommands'));
?>
<h1>This is asset page</h1>
<?php if ($permissions['asset:assets:create']): ?>
    <?php $view['slots']->start("actions"); ?>
    <li>
        <a href="<?php echo $this->container->get('router')->generate(
            'mautic_asset_action', array("objectAction" => "new")); ?>"
           data-toggle="ajax"
           data-menu-link="#mautic_asset_index">
            <?php echo $view["translator"]->trans("mautic.asset.asset.menu.new"); ?>
        </a>
    </li>
    <?php $view['slots']->stop(); ?>
<?php endif; ?>

<?php $view['slots']->output('_content'); ?>