<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if ($trace['function']) : ?>
at <strong><abbr title="<?php echo $trace['class']; ?>"><?php echo $trace['short_class']; ?></abbr>
        <?php echo $trace['type'] . ' ' . $trace['function']; ?>
    </strong>
    <?php echo $view['exception']->formatArgs($trace['args']); ?>
<?php endif; ?>

<?php if (isset($trace['file']) && $trace['file'] && isset($trace['line']) && $trace['line']) : ?>
    <?php echo $trace['function'] ? '<br />' : ''; ?> in <?php echo $view['exception']->formatFile($trace['file'], $trace['line']); ?>&nbsp;
    <a href="#" onclick="toggle('trace-<?php echo $prefix . '-' . $i; ?>'); switchIcons('icon-<?php echo $prefix . '-' . $i; ?>-open', 'icon-<?php echo $prefix . '-' . $i; ?>-close'); return false;">
        <img class="toggle" id="icon-<?php echo $prefix . '-' . $i; ?>-close" alt="-" src="data:image/gif;base64,R0lGODlhEgASAMQSANft94TG57Hb8GS44ez1+mC24IvK6ePx+Wa44dXs92+942e54o3L6W2844/M6dnu+P/+/l614P///wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAEAABIALAAAAAASABIAQAVCoCQBTBOd6Kk4gJhGBCTPxysJb44K0qD/ER/wlxjmisZkMqBEBW5NHrMZmVKvv9hMVsO+hE0EoNAstEYGxG9heIhCADs=" style="display: <?php echo $i == 0 ? 'inline' : 'none'; ?>" />
        <img class="toggle" id="icon-<?php echo $prefix . '-' . $i; ?>-open" alt="+" src="data:image/gif;base64,R0lGODlhEgASAMQTANft99/v+Ga44bHb8ITG52S44dXs9+z1+uPx+YvK6WC24G+944/M6W28443L6dnu+Ge54v/+/l614P///wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAEAABMALAAAAAASABIAQAVS4DQBTiOd6LkwgJgeUSzHSDoNaZ4PU6FLgYBA5/vFID/DbylRGiNIZu74I0h1hNsVxbNuUV4d9SsZM2EzWe1qThVzwWFOAFCQFa1RQq6DJB4iIQA7" style="display: <?php echo $i == 0 ? 'none' : 'inline'; ?>" />
    </a>
    <div id="trace-<?php echo $prefix . '-' . $i; ?>" style="display: <?php echo $i == 0 ? 'block' : 'none'; ?>" class="trace">
        <?php echo $view['exception']->fileExcerpt($trace['file'], $trace['line']); ?>
    </div>
<?php endif; ?>
