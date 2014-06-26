<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$status = $form->getPublishStatus();
switch ($status) {
    case 'published':
        $icon = " fa-check-circle-o text-success";
        $text = $view['translator']->trans('mautic.core.form.published');
        break;
    case 'unpublished':
        $icon = " fa-times-circle-o text-danger";
        $text = $view['translator']->trans('mautic.core.form.unpublished');
        break;
    case 'expired':
        $icon = " fa-clock-o text-danger";
        $text = $view['translator']->trans('mautic.core.form.expired', array(
            '%date%' => $item->getPublishDown()->format($dateFormat)
        ));
        break;
    case 'pending':
        $icon = " fa-clock-o text-warning";
        $text = $view['translator']->trans('mautic.core.form.pending', array(
            '%date%' => $item->getPublishUp()->format($dateFormat)
        ));
        break;
}
?>

<div class="global-search-result">
<?php if (!empty($showMore)): ?>
    <a class="pull-right margin-md-sides" href="<?php echo $this->container->get('router')->generate(
        'mautic_form_index', array('search' => $searchString)); ?>"
       data-toggle="ajax">
        <span><?php echo $view['translator']->trans('mautic.core.search.more', array("%count%" => $remaining)); ?></span>
    </a>
<?php else: ?>
    <i class="fa fa-fw <?php echo $icon; ?>"
       data-toggle="tooltip"
       data-container="body"
       data-placement="right"
       data-status="<?php echo $status; ?>"
       data-original-title="<?php echo $text ?>"></i>
    <a href="<?php echo $this->container->get('router')->generate(
        'mautic_form_action', array('objectAction' => 'view', 'objectId' => $form->getId())); ?>"
    data-toggle="ajax">
        <span class="gs-form-name"><?php echo $form->getName(); ?></span>
        <span class="gs-form-desc"><?php echo $form->getDescription(); ?></span>
    </a>
<?php endif; ?>
</div>