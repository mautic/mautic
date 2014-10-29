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
<h4><?php echo $view['translator']->trans('mautic.email.variants'); ?>
    <?php if ($showSupport): ?>
        <button class="btn btn-primary pull-right" data-toggle="modal" data-target="#emailAbTestResults">
            <?php echo $view['translator']->trans('mautic.email.abtest.stats'); ?>
        </button>
    <?php endif; ?>
    <?php if (!empty($abTestResults['error'])): ?>
    <span class="text-danger"><?php echo $abTestResults['error']; ?></span>
    <?php endif; ?>
</h4>
<?php if ($startDate = $variants['parent']->getVariantStartDate()): ?>
<h5><?php echo $view['translator']->trans('mautic.email.variantstartdate', array(
        "%date%" => $view['date']->toFull($startDate)
    )); ?></h5>
<?php endif; ?>
<table class="table table-bordered table-stripped">
    <thead>
        <tr>
            <th class="col-email-abtest-subject"><?php echo $view['translator']->trans('mautic.email.thead.subject'); ?></th>
            <th class="col-email-abtest-sentcount"><?php echo $view['translator']->trans('mautic.email.thead.sentcount'); ?></th>
            <th class="col-email-abtest-readcount"><?php echo $view['translator']->trans('mautic.email.thead.readcount'); ?></th>
            <th class="col-email-abtest-actions"></th>
        </tr>
    </thead>
    <tbody>
        <?php if ($variants['parent']): ?>
        <tr>
            <td>
                <?php echo $view->render('MauticCoreBundle:Helper:publishstatus.html.php',array(
                    'item'       => $variants['parent'],
                    'model'      => 'email'
                )); ?>
                <a href="<?php echo $view['router']->generate('mautic_email_action', array(
                    'objectAction' => 'view', 'objectId' => $variants['parent']->getId())); ?>"
                   data-toggle="ajax">
                    <span><?php echo $variants['parent']->getSubject(); ?></span>
                    <?php if ($variants['parent']->getId() === $email->getId()): ?>
                    <span><strong> [<?php echo $view['translator']->trans('mautic.email.current'); ?>]</strong></span>
                    <?php endif; ?>
                    <span><strong> [<?php echo $view['translator']->trans('mautic.email.parent'); ?>]</strong></span>
                </a>
            </td>
            <td><?php echo $variants['parent']->getVariantSentCount(); ?></td>
            <td><?php echo $variants['parent']->getVariantReadCount(); ?></td>
            <td></td>
        </tr>
        <?php endif; ?>

        <?php if (count($variants['children'])): ?>
        <?php foreach ($variants['children'] as $c): ?>
        <tr>
            <td>
                <?php echo $view->render('MauticCoreBundle:Helper:publishstatus.html.php',array(
                    'item'       => $c,
                    'model'      => 'email'
                )); ?>
                <a href="<?php echo $view['router']->generate('mautic_email_action', array(
                    'objectAction' => 'view', 'objectId' => $c->getId())); ?>"
                   data-toggle="ajax">
                    <span><?php echo $c->getSubject(); ?></span>
                    <?php if ($c->getId() === $email->getId()): ?>
                        <span><strong> [<?php echo $view['translator']->trans('mautic.email.current'); ?>]</strong></span>
                    <?php endif; ?>
                </a>
            </td>
            <td><?php echo $c->getVariantSentCount(); ?></td>
            <td><?php echo $c->getVariantReadCount(); ?></td>
            <td class="text-center">
                <?php if (isset($abTestResults['winners']) && $startDate && $c->isPublished()): ?>
                    <?php $class = (in_array($c->getId(), $abTestResults['winners'])) ? 'success' : 'danger'; ?>
                    <a href="<?php echo $view['router']->generate('mautic_email_action', array(
                        'objectAction' => 'winner', 'objectId' => $c->getId())); ?>"
                       data-toggle="ajax" data-method="post" class="btn btn-<?php echo $class; ?>">
                        <?php echo $view['translator']->trans('mautic.email.abtest.makewinner'); ?>
                    </a>
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
    'id'     => 'emailAbTestResults',
    'header' => $view['translator']->trans('mautic.email.abtest.stats'),
    'body'   => $view->render($abTestResults['supportTemplate'], array(
        'variants'      => $variants,
        'abTestResults' => $abTestResults
    ))
));
endif;
endif;
?>