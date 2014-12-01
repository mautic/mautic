<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

?>
<div class="block">
    <?php if ($count > 0) : ?>
        <h5>
            <span><small>[<?php echo $count - $position + 1 . '/' . $count + 1; ?>]</small></span>
            <?php echo $view['exception']->abbrClass($exception['class']); ?>: <?php echo $view['exception']->formatFileFromText($exception['message']); ?>&nbsp;
            <a href="#" onclick="toggle('traces-<?php echo $position; ?>', 'traces'); switchIcons('icon-traces-<?php echo $position; ?>-open', 'icon-traces-<?php echo $position; ?>-close'); return false;">
                <img class="toggle" id="icon-traces-<?php echo $position; ?>-close" alt="-" src="data:image/gif;base64,R0lGODlhEgASAMQSANft94TG57Hb8GS44ez1+mC24IvK6ePx+Wa44dXs92+942e54o3L6W2844/M6dnu+P/+/l614P///wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAEAABIALAAAAAASABIAQAVCoCQBTBOd6Kk4gJhGBCTPxysJb44K0qD/ER/wlxjmisZkMqBEBW5NHrMZmVKvv9hMVsO+hE0EoNAstEYGxG9heIhCADs=" style="display: <?php echo ($count == 0) ? 'inline' : 'none'; ?>" />
                <img class="toggle" id="icon-traces-<?php echo $position; ?>-open" alt="+" src="data:image/gif;base64,R0lGODlhEgASAMQTANft99/v+Ga44bHb8ITG52S44dXs9+z1+uPx+YvK6WC24G+944/M6W28443L6dnu+Ge54v/+/l614P///wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAEAABMALAAAAAASABIAQAVS4DQBTiOd6LkwgJgeUSzHSDoNaZ4PU6FLgYBA5/vFID/DbylRGiNIZu74I0h1hNsVxbNuUV4d9SsZM2EzWe1qThVzwWFOAFCQFa1RQq6DJB4iIQA7" style="display: <?php echo ($count == 0) ? 'none' : 'inline'; ?>" />
            </a>
        </h5>
    <?php else : ?>
        <h5>Stack Trace</h5>
    <?php endif; ?>

    <a id="traces-link-<?php echo $position; ?>"></a>
    <ol class="traces list-exception" id="traces-<?php echo $position; ?>" style="display: <?php echo ($count == 0) ? 'block' : 'none'; ?>">
        <?php foreach ($exception['trace'] as $i => $trace) : ?>
            <li>
                <?php echo $view->render('MauticCoreBundle:Exception:trace.html.php', array(
                    'i'      => $i,
                    'prefix' => $position,
                    'trace'  => $trace
                )); ?>
            </li>
        <?php endforeach; ?>
    </ol>
</div>
