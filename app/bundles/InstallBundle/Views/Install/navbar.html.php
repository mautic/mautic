<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$start = 1;
?>
<div id="stepNavigation" class="hidden-xs">
    <ul class="horizontal-step">
        <?php while ($start < $count): ?>
        <li<?php echo ($start == $step) ? ' class="active"' : ''; ?>>
            <?php $url = ($start == $step || in_array($start, $completedSteps) || in_array($start - 1, $completedSteps)) ? $view['router']->path('mautic_installer_step', ['index' => $start]) : '#'; ?>
            <a href="<?php echo $url; ?>" class="steps<?php echo ($url == '#') ? ' disabled' : ''; ?>">
                <span class="steps-figure"><?php echo $view['translator']->trans('mautic.install.step.'.$start); ?></span>
            </a>
        </li>
        <?php ++$start; ?>
        <?php endwhile; ?>
    </ul>
    <div class="clearfix"></div>
</div>