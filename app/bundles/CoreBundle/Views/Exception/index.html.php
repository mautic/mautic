<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

if ($tmpl == 'index') {
    $view->extend('MauticCoreBundle:Default:error.html.php');
}

$view['slots']->set('pageHeader', $status_code . ' ' . $status_text);

/** @var $exception \Symfony\Component\HttpKernel\Exception\FlattenException */
/** @var $logger    \Symfony\Component\HttpKernel\Log\DebugLoggerInterface */
?>
<?php $view['slots']->output('_content'); ?>

<?php $previous_count = count($exception->getAllPrevious()); ?>
<?php if ($previous_count) : ?>
    <div class="linked"><span><strong><?php echo $previous_count; ?></strong> linked Exception<?php echo $previous_count > 1 ? 's' : ''; ?>:</span>
        <ul>
            <?php /** @var $previous \Symfony\Component\HttpKernel\Exception\FlattenException */?>
            <?php foreach ($exception->getAllPrevious() as $i => $previous) : ?>
                <li>
                    <?php echo $view['exception']->abbrClass($previous->getClass()); ?> <a href="#traces-link-<?php echo $i + 1; ?>" onclick="toggle('traces-<?php echo $i + 1; ?>', 'traces'); switchIcons('icon-traces-<?php echo $i + 1; ?>-open', 'icon-traces-<?php echo $i + 1; ?>-close');">&#187;</a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php foreach ($exception->toArray() as $position => $e) : ?>
    <?php echo $view->render('MauticCoreBundle:Exception:traces.html.php', array(
        'exception' => $e,
        'position'  => $position,
        'count'     => $previous_count
    )); ?>
<?php endforeach; ?>

<?php if ($logger) : ?>
    <div class="block">
        <div class="logs clear-fix">
            <h2>
                Logs&nbsp;
                <a href="#" onclick="toggle('logs'); switchIcons('icon-logs-open', 'icon-logs-close'); return false;">
                    <img class="toggle" id="icon-logs-open" alt="+" src="data:image/gif;base64,R0lGODlhEgASAMQTANft99/v+Ga44bHb8ITG52S44dXs9+z1+uPx+YvK6WC24G+944/M6W28443L6dnu+Ge54v/+/l614P///wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAEAABMALAAAAAASABIAQAVS4DQBTiOd6LkwgJgeUSzHSDoNaZ4PU6FLgYBA5/vFID/DbylRGiNIZu74I0h1hNsVxbNuUV4d9SsZM2EzWe1qThVzwWFOAFCQFa1RQq6DJB4iIQA7" style="display: none" />
                    <img class="toggle" id="icon-logs-close" alt="-" src="data:image/gif;base64,R0lGODlhEgASAMQSANft94TG57Hb8GS44ez1+mC24IvK6ePx+Wa44dXs92+942e54o3L6W2844/M6dnu+P/+/l614P///wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAEAABIALAAAAAASABIAQAVCoCQBTBOd6Kk4gJhGBCTPxysJb44K0qD/ER/wlxjmisZkMqBEBW5NHrMZmVKvv9hMVsO+hE0EoNAstEYGxG9heIhCADs=" style="display: inline" />
                </a>
            </h2>

            <?php if ($logger->countErrors()) : ?>
                <div class="error-count">
                    <span>
                        <?php echo $logger->countErrors(); ?> error<?php echo $logger->countErrors() > 1 ? 's' : ''; ?>
                    </span>
                </div>
            <?php endif; ?>
        </div>

        <div id="logs">
            <?php echo $view->render('MauticCoreBundle:Exception:logs.html.php', array(
                'logs' => $logger->getLogs()
            )); ?>
        </div>
    </div>
<?php endif; ?>

<?php if ($currentContent) : ?>
    <div class="block">
        <h2>
            Content of the Output&nbsp;
            <a href="#" onclick="toggle('output-content'); switchIcons('icon-content-open', 'icon-content-close'); return false;">
                <img class="toggle" id="icon-content-close" alt="-" src="data:image/gif;base64,R0lGODlhEgASAMQSANft94TG57Hb8GS44ez1+mC24IvK6ePx+Wa44dXs92+942e54o3L6W2844/M6dnu+P/+/l614P///wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAEAABIALAAAAAASABIAQAVCoCQBTBOd6Kk4gJhGBCTPxysJb44K0qD/ER/wlxjmisZkMqBEBW5NHrMZmVKvv9hMVsO+hE0EoNAstEYGxG9heIhCADs=" style="display: none" />
                <img class="toggle" id="icon-content-open" alt="+" src="data:image/gif;base64,R0lGODlhEgASAMQTANft99/v+Ga44bHb8ITG52S44dXs9+z1+uPx+YvK6WC24G+944/M6W28443L6dnu+Ge54v/+/l614P///wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAEAABMALAAAAAASABIAQAVS4DQBTiOd6LkwgJgeUSzHSDoNaZ4PU6FLgYBA5/vFID/DbylRGiNIZu74I0h1hNsVxbNuUV4d9SsZM2EzWe1qThVzwWFOAFCQFa1RQq6DJB4iIQA7" style="display: inline" />
            </a>
        </h2>

        <div id="output-content" style="display: none">
            <?php echo $currentContent; ?>
        </div>

        <div style="clear: both"></div>
    </div>
<?php endif; ?>

<?php echo $view->render('MauticCoreBundle:Exception:traces_text.html.php', array(
    'exception' => $exception
)); ?>

<script type="text/javascript">//<![CDATA[
    function toggle(id, clazz) {
        var el = document.getElementById(id),
            current = el.style.display,
            i;

        if (clazz) {
            var tags = document.getElementsByTagName('*');
            for (i = tags.length - 1; i >= 0 ; i--) {
                if (tags[i].className === clazz) {
                    tags[i].style.display = 'none';
                }
            }
        }

        el.style.display = current === 'none' ? 'block' : 'none';
    }

    function switchIcons(id1, id2) {
        var icon1, icon2, display1, display2;

        icon1 = document.getElementById(id1);
        icon2 = document.getElementById(id2);

        display1 = icon1.style.display;
        display2 = icon2.style.display;

        icon1.style.display = display2;
        icon2.style.display = display1;
    }
//]]></script>
