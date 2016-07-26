<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

if (!isset($nameGetter)) {
    $nameGetter = 'getName';
}

if (count($translations['children']) || ($translations['parent'] && $translations['parent']->getId() !== $activeEntity->getId())): ?>
<!-- start: related translations list -->

<ul class="list-group">
    <?php if ($translations['parent']) : ?>
    <li class="list-group-item bg-auto bg-light-xs">
        <div class="box-layout">
            <div class="col-md-1 va-m">
                <h3>
                    <?php echo $view->render(
                        'MauticCoreBundle:Helper:publishstatus_icon.html.php',
                        [
                            'item'  => $translations['parent'],
                            'model' => $model,
                            'size'  => '',
                        ]
                    ); ?>
                </h3>
            </div>
            <div class="col-md-7 va-m">
                <h5 class="fw-sb text-primary">
                    <a href="<?php echo $view['router']->path($actionRoute, ['objectAction' => 'view', 'objectId' => $translations['parent']->getId()]); ?>" data-toggle="ajax">
                        <span><?php echo $translations['parent']->$nameGetter(); ?></span>
                        <?php if ($translations['parent']->getId() == $activeEntity->getId()) : ?>
                        <span>[<?php echo $view['translator']->trans('mautic.core.current'); ?>]</span>
                        <?php endif; ?>
                        <span>[<?php echo $view['translator']->trans('mautic.core.parent'); ?>]</span>
                    </a>
                </h5>
                <?php if (method_exists($translations['parent'], 'getAlias')): ?>
                    <span class="text-white dark-sm"><?php echo $translations['parent']->getAlias(); ?></span>
                <?php endif; ?>
            </div>
            <div class="col-md-4 va-m text-right">
                <em class="text-white dark-sm"><?php echo $translations['parent']->getLanguage(); ?></em>
            </div>
        </div>
    </li>
    <?php endif; ?>
    <?php if (count($translations['children'])) : ?>
    <?php /** @var \Mautic\PageBundle\Entity\Page $translation */ ?>
    <?php foreach ($translations['children'] as $translation) : ?>
    <li class="list-group-item bg-auto bg-light-xs">
        <div class="box-layout">
            <div class="col-md-1 va-m">
                <h3>
                    <?php echo $view->render(
                        'MauticCoreBundle:Helper:publishstatus_icon.html.php',
                        [
                            'item'  => $translation,
                            'model' => $model,
                            'size'  => '',
                            'query' => 'size=',
                        ]
                    ); ?>
                </h3>
            </div>
            <div class="col-md-7 va-m">
                <h5 class="fw-sb text-primary">
                    <a href="<?php echo $view['router']->path($actionRoute, ['objectAction' => 'view', 'objectId' => $translation->getId()]); ?>" data-toggle="ajax">
                        <span><?php echo $translation->$nameGetter(); ?></span>
                        <?php if ($translation->getId() == $activeEntity->getId()) : ?>
                        <span>[<?php echo $view['translator']->trans('mautic.core.current'); ?>]</span>
                        <?php endif; ?>
                    </a>
                </h5>
                <?php if (method_exists($translation, 'getAlias')): ?>
                <span class="text-white dark-sm"><?php echo $translation->getAlias(); ?></span>
                <?php endif; ?>
            </div>
            <div class="col-md-4 va-m text-right">
                <em class="text-white dark-sm"><?php echo $translation->getLanguage(); ?></em>
            </div>
        </div>
    </li>
    <?php endforeach; ?>
    <?php endif; ?>
</ul>
<!--/ end: related translations list -->
<?php endif; ?>