<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

if (count($variants['children'])):
$showSupport = (isset($abTestResults['supportTemplate']) && isset($abTestResults['support']));
?>
<h4><?php echo $view['translator']->trans('mautic.page.variants'); ?>
    <?php if ($showSupport): ?>
        <button class="btn btn-primary" data-toggle="modal" data-target="#pageAbTestResults">
            <?php echo $view['translator']->trans('mautic.page.abtest.stats'); ?>
        </button>
    <?php endif; ?>
    <?php if (!empty($abTestResults['error'])): ?>
    <span class="text-danger"><?php echo $abTestResults['error']; ?></span>
    <?php endif; ?>
</h4>
<?php if ($startDate = $variants['parent']->getVariantStartDate()): ?>
<h5><?php echo $view['translator']->trans('mautic.page.variantstartdate', array(
        "%date%" => $view['date']->toFull($startDate)
    )); ?></h5>
<?php endif;?>
<table class="table table-bordered table-stripped">
    <tbody>
        <?php if ($variants['parent']): ?>
        <tr>
            <td>
                <?php echo $view->render('MauticCoreBundle:Helper:publishstatus.html.php',array(
                    'item'       => $variants['parent'],
                    'model'      => 'page.page'
                )); ?>
                <a href="<?php echo $view['router']->generate('mautic_page_action', array(
                    'objectAction' => 'view', 'objectId' => $variants['parent']->getId())); ?>"
                   data-toggle="ajax">
                    <span><?php echo $variants['parent']->getTitle() . " (" . $variants['parent']->getAlias() . ")"; ?></span>
                    <?php if ($variants['parent']->getId() === $page->getId()): ?>
                    <span><strong> [<?php echo $view['translator']->trans('mautic.page.current'); ?>]</strong></span>
                    <?php endif; ?>
                    <span><strong> [<?php echo $view['translator']->trans('mautic.page.parent'); ?>]</strong></span>
                </a>
            </td>
            <td></td>
        </tr>
        <?php endif; ?>

        <?php if (count($variants['children'])): ?>
        <?php foreach ($variants['children'] as $c): ?>
        <tr>
            <td>
                <?php echo $view->render('MauticCoreBundle:Helper:publishstatus.html.php',array(
                    'item'       => $c,
                    'model'      => 'page.page'
                )); ?>
                <a href="<?php echo $view['router']->generate('mautic_page_action', array(
                    'objectAction' => 'view', 'objectId' => $c->getId())); ?>"
                   data-toggle="ajax">
                    <span><?php echo $c->getTitle() . " (" . $c->getAlias() . ")"; ?></span>
                </a>
            </td>
            <td class="text-center">
                <?php if (isset($abTestResults['winners']) && $startDate && $c->isPublished()): ?>
                    <?php $class = (in_array($c->getId(), $abTestResults['winners'])) ? 'success' : 'danger'; ?>
                    <a href="<?php echo $view['router']->generate('mautic_page_action', array(
                        'objectAction' => 'winner', 'objectId' => $c->getId())); ?>"
                       data-toggle="ajax" data-method="post" class="btn btn-<?php echo $class; ?>">
                        <?php echo $view['translator']->trans('mautic.page.abtest.makewinner'); ?>
                    </a>
                    <?php if ($c->getId() === $page->getId()): ?>
                    <span><strong> [<?php echo $view['translator']->trans('mautic.page.current'); ?>]</strong></span>
                    <?php endif; ?>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table>
<?php
if ($showSupport):
echo $view->render('MauticCoreBundle:Helper:modal.html.php', array(
    'id'     => 'pageAbTestResults',
    'header' => $view['translator']->trans('mautic.page.abtest.stats'),
    'body'   => $view->render($abTestResults['supportTemplate'], array(
        'variants'      => $variants,
        'abTestResults' => $abTestResults
    ))
));
endif;
endif;
?>