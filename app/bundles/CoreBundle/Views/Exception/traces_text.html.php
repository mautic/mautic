<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

/** @var $exception \Symfony\Component\HttpKernel\Exception\FlattenException */
?>
<div class="block">
    <h2>
        Stack Trace (Plain Text)&nbsp;
        <a href="#" onclick="toggle('traces-text'); switchIcons('icon-traces-text-open', 'icon-traces-text-close'); return false;">
            <img class="toggle" id="icon-traces-text-close" alt="-" src="data:image/gif;base64,R0lGODlhEgASAMQSANft94TG57Hb8GS44ez1+mC24IvK6ePx+Wa44dXs92+942e54o3L6W2844/M6dnu+P/+/l614P///wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAEAABIALAAAAAASABIAQAVCoCQBTBOd6Kk4gJhGBCTPxysJb44K0qD/ER/wlxjmisZkMqBEBW5NHrMZmVKvv9hMVsO+hE0EoNAstEYGxG9heIhCADs=" style="display: none" />
            <img class="toggle" id="icon-traces-text-open" alt="+" src="data:image/gif;base64,R0lGODlhEgASAMQTANft99/v+Ga44bHb8ITG52S44dXs9+z1+uPx+YvK6WC24G+944/M6W28443L6dnu+Ge54v/+/l614P///wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAEAABMALAAAAAASABIAQAVS4DQBTiOd6LkwgJgeUSzHSDoNaZ4PU6FLgYBA5/vFID/DbylRGiNIZu74I0h1hNsVxbNuUV4d9SsZM2EzWe1qThVzwWFOAFCQFa1RQq6DJB4iIQA7" style="display: inline" />
        </a>
    </h2>

    <div id="traces-text" class="trace" style="display: none;">
        <pre>
            <?php foreach ($exception->toArray() as $i => $e) : ?>
            [<?php echo $i + 1; ?>] <?php echo $e['class']; ?>: <?php echo $e['message']; ?>
                <?php echo $view->render('MauticCoreBundle:Exception:traces.txt.php', array(
                    'exception' => $e
                )); ?>
            <?php endforeach; ?>
        </pre>
    </div>
</div>
