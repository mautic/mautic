<?php
/**
 * @package     Mautic
 * @copyright   2015 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<div class="dropdown pull-right">
    <button id="time-scopes" class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown">
        <span class="button-label"><?php echo $view['translator']->trans('mautic.core.timeframe.daily'); ?></span>
        <span class="caret"></span>
    </button>
    <ul class="dropdown-menu" role="menu" aria-labelledby="time-scopes">
        <li role="presentation">
            <a href="#" onclick="Mautic.<?php echo $callback; ?>(this, 24, 'H');return false;" role="menuitem" tabindex="-1">
                <?php echo $view['translator']->trans('mautic.core.timeframe.hourly'); ?>
            </a>
        </li>
        <li role="presentation">
            <a href="#" class="bg-primary" onclick="Mautic.<?php echo $callback; ?>(this, 30, 'D');return false;" role="menuitem" tabindex="-1">
                <?php echo $view['translator']->trans('mautic.core.timeframe.daily'); ?>
            </a>
        </li>
        <li role="presentation">
            <a href="#" onclick="Mautic.<?php echo $callback; ?>(this, 20, 'W');return false;" role="menuitem" tabindex="-1">
                <?php echo $view['translator']->trans('mautic.core.timeframe.weekly'); ?>
            </a>
        </li>
        <li role="presentation">
            <a href="#" onclick="Mautic.<?php echo $callback; ?>(this, 24, 'M');return false;" role="menuitem" tabindex="-1">
                <?php echo $view['translator']->trans('mautic.core.timeframe.monthly'); ?>
            </a>
        </li>
        <li role="presentation">
            <a href="#" onclick="Mautic.<?php echo $callback; ?>(this, 10, 'Y');return false;" role="menuitem" tabindex="-1">
                <?php echo $view['translator']->trans('mautic.core.timeframe.yearly'); ?>
            </a>
        </li>
    </ul>
</div>