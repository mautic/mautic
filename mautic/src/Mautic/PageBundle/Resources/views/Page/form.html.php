<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

if ($tmpl == 'index') {
    $view->extend('MauticPageBundle:Page:index.html.php');
}
?>

<div class="bundle-main-header">
    <div class="bundle-main-item-primary">
        <?php
        $header = ($activePage->getId()) ?
            $view['translator']->trans('mautic.page.page.header.edit',
                array('%name%' => $activePage->getTitle())) :
            $view['translator']->trans('mautic.page.page.header.new');
        echo $header;
        ?>
    </div>
</div>

<?php echo $view['form']->form($form); ?>

<div class="hide page-builder">
    <div class="page-builder-toolbar">
        <button class="btn btn-warning" onclick="Mautic.closePageEditor();"><?php echo $view['translator']->trans('mautic.page.page.builder.close'); ?></button>
    </div>
    <input type="hidden" id="pageBuilderUrl" value="<?php echo $view['router']->generate('mautic_page_action', array('objectAction' => 'builder', 'objectId' => $activePage->getSessionId())); ?>" />
    <iframe src=""
            style="margin: 15px 0 0 0; padding: 0; border: none; width: 100%; height: 100%;"></iframe>
</div>