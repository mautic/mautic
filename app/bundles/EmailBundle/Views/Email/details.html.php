<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

//@todo - add email stats/analytics
$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'email');
$view['slots']->set("headerTitle", $email->getSubject());
$isVariant = $email->isVariant(true);
$view['slots']->start('actions');
if ($security->hasEntityAccess($permissions['email:emails:editown'], $permissions['email:emails:editother'],
    $email->getCreatedBy())): ?>
    <li>
        <a href="<?php echo $this->container->get('router')->generate(
            'mautic_email_action', array("objectAction" => "edit", "objectId" => $email->getId())); ?>"
           data-toggle="ajax"
           data-menu-link="#mautic_email_index">
            <i class="fa fa-fw fa-pencil-square-o"></i><?php echo $view["translator"]->trans("mautic.core.form.edit"); ?>
        </a>
    </li>
<?php endif; ?>
<?php if ($security->hasEntityAccess($permissions['email:emails:deleteown'], $permissions['email:emails:deleteother'],
    $email->getCreatedBy())): ?>
    <li>
        <a href="javascript:void(0);"
           onclick="Mautic.showConfirmation(
               '<?php echo $view->escape($view["translator"]->trans("mautic.email.confirmdelete",
               array("%name%" => $email->getSubject() . " (" . $email->getId() . ")")), 'js'); ?>',
               '<?php echo $view->escape($view["translator"]->trans("mautic.core.form.delete"), 'js'); ?>',
               'executeAction',
               ['<?php echo $view['router']->generate('mautic_email_action',
               array("objectAction" => "delete", "objectId" => $email->getId())); ?>',
               '#mautic_email_index'],
               '<?php echo $view->escape($view["translator"]->trans("mautic.core.form.cancel"), 'js'); ?>','',[]);">
            <span><i class="fa fa-fw fa-trash-o"></i><?php echo $view['translator']->trans('mautic.core.form.delete'); ?></span>
        </a>
    </li>
<?php endif; ?>
<?php if (!$isVariant && $permissions['email:emails:create']): ?>
    <li>
        <a href="<?php echo $view['router']->generate('mautic_email_action',
           array("objectAction" => "abtest", "objectId" => $email->getId())); ?>"
        data-toggle="ajax"
        data-menu-link="mautic_email_index">
        <span><i class="fa fa-sitemap"></i><?php echo $view['translator']->trans('mautic.email.form.abtest'); ?></span>
        </a>
    </li>
<?php endif; ?>
<?php if (!$isVariant): ?>
<li>
    <a href="javascript:void(0);"
       onclick="Mautic.showConfirmation(
           '<?php echo $view->escape($view["translator"]->trans("mautic.email.form.confirmsend",
           array("%name%" => $email->getSubject() . " (" . $email->getId() . ")")), 'js'); ?>',
           '<?php echo $view->escape($view["translator"]->trans("mautic.email.send"), 'js'); ?>',
           'executeAction',
           ['<?php echo $view['router']->generate('mautic_email_action',
           array("objectAction" => "send", "objectId" => $email->getId())); ?>',
           '#mautic_email_index'],
           '<?php echo $view->escape($view["translator"]->trans("mautic.core.form.cancel"), 'js'); ?>','',[]);">
        <span><i class="fa fa-fw fa-send"></i><?php echo $view['translator']->trans('mautic.email.send'); ?></span>
    </a>
</li>
<?php endif; ?>
<?php $view['slots']->stop(); ?>

<div class="scrollable">
    <div class="bundle-main-header">
        <span class="bundle-main-item-primary">
            <?php
            if ($category = $email->getCategory()):
                $catSearch = $view['translator']->trans('mautic.core.searchcommand.category') . ":" . $category->getAlias();
                $catName = $category->getTitle();
            else:
                $catSearch = $view['translator']->trans('mautic.core.searchcommand.is') . ":" .
                    $view['translator']->trans('mautic.core.searchcommand.isuncategorized');
                $catName = $view['translator']->trans('mautic.core.form.uncategorized');
            endif;
            ?>
            <a href="<?php echo $view['router']->generate('mautic_email_index', array('search' => $catSearch))?>"
               data-toggle="ajax">
                <?php echo $catName; ?>
            </a>
            <span> | </span>
            <span>
                <?php
                $author     = $email->getCreatedBy();
                $authorId   = ($author) ? $author->getId() : 0;
                $authorName = ($author) ? $author->getName() : "";
                ?>
                <a href="<?php echo $view['router']->generate('mautic_user_action', array(
                    'objectAction' => 'contact',
                    'objectId'     => $authorId,
                    'entity'       => 'page.page',
                    'id'           => $email->getId(),
                    'returnUrl'    => $view['router']->generate('mautic_email_action', array(
                        'objectAction' => 'view',
                        'objectId'     => $email->getId()
                    ))
                )); ?>">
                    <?php echo $authorName; ?>
                </a>
            </span>
            <span> | </span>
            <span>
            <?php $langSearch = $view['translator']->trans('mautic.core.searchcommand.lang').":".$email->getLanguage(); ?>
                <a href="<?php echo $view['router']->generate('mautic_email_index', array('search' => $langSearch)); ?>"
                   data-toggle="ajax">
                    <?php echo $email->getLanguage(); ?>
                </a>
            </span>
        </span>
    </div>

    <h3><?php echo $view['translator']->trans('mautic.email.recipient.lists'); ?> (<?php
        echo $stats['combined']['sent'] . '/' . $stats['combined']['total']; ?>)</h3>
    <ul class="fa-ul">
        <?php
        $lists = $email->getLists();
        foreach ($lists as $l):
        ?>
        <li><i class="fa-li fa fa-send"></i><?php echo $l->getName(); ?> (<?php echo (isset($stats[$l->getId()]) ?
                $stats[$l->getId()]['sent'] . '/' . $stats[$l->getId()]['total'] : '0/0/0'); ?>)</li>
        <?php endforeach; ?>
    </ul>

    <h3>@todo - Email stats/analytics/AB test results will go here</h3>

    <?php if (!empty($variants['parent']) || !empty($variants['children'])): ?>
    <?php echo $view->render('MauticEmailBundle:AbTest:details.html.php', array(
        'email'         => $email,
        'abTestResults' => $abTestResults,
        'variants'      => $variants
    )); ?>
    <?php endif; ?>
    '
</div>