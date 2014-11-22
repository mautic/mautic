<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<?php if (count($translations['children'])): ?>
<h4><?php echo $view['translator']->trans('mautic.page.translations'); ?></h4>

<table class="table table-bordered table-stripped">
    <?php if ($translations['parent']): ?>
    <tr>
        <td>
            <?php echo $view->render('MauticCoreBundle:Helper:publishstatus.html.php',array(
                'item'       => $translations['parent'],
                'model'      => 'page.page'
            )); ?>
            <a href="<?php echo $view['router']->generate('mautic_page_action', array(
                'objectAction' => 'view', 'objectId' => $translations['parent']->getId())); ?>"
               data-toggle="ajax">
                <span><?php echo $translations['parent']->getLanguage(); ?></span>
                <span> | </span>
                <span><?php echo $translations['parent']->getTitle() . " (" . $translations['parent']->getAlias() . ")"; ?></span>
                <?php if ($translations['parent']->getId() === $page->getId()): ?>
                <span><strong> [<?php echo $view['translator']->trans('mautic.page.current'); ?>]</strong></span>
                <?php endif; ?>
                <span><strong> [<?php echo $view['translator']->trans('mautic.page.parent'); ?>]</strong></span>
            </a>
        </td>
    </tr>
    <?php endif; ?>
    <?php if (count($translations['children'])): ?>
    <?php foreach ($translations['children'] as $c): ?>
    <tr>
        <td>
            <?php echo $view->render('MauticCoreBundle:Helper:publishstatus.html.php',array(
                'item'       => $c,
                'model'      => 'page.page'
            )); ?>
            <a href="<?php echo $view['router']->generate('mautic_page_action', array(
                'objectAction' => 'view', 'objectId' => $c->getId())); ?>"
               data-toggle="ajax">
                <span><?php echo $c->getLanguage(); ?></span>
                <span> | </span>
                <span><?php echo $c->getTitle() . " (" . $c->getAlias() . ")"; ?></span>
                <?php if ($c->getId() === $page->getId()): ?>
                    <span><strong> [<?php echo $view['translator']->trans('mautic.page.current'); ?>]</strong></span>
                <?php endif; ?>
            </a>
        </td>
    </tr>
    <?php endforeach; ?>
    <?php endif; ?>
</table>
<?php endif; ?>