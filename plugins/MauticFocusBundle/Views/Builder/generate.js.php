<?php

/*
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$style          = $focus['style'];
$props          = $focus['properties'];
$useScrollEvent = in_array($props['when'], ['scroll_slight', 'scroll_middle', 'scroll_bottom']);
$useUnloadEvent = ($props['when'] == 'leave');
$useTimeout     = (int) $props['timeout'];
if ($props['when'] == '5seconds') {
    $useTimeout = 5;
} elseif ($props['when'] == 'minute') {
    $useTimeout = 60;
}
if ($useTimeout) {
    $timeout = $useTimeout * 1000;
}

$debug          = ($app->getEnvironment() == 'dev') ? 'true' : 'false';
$animate        = (!isset($props['animate']) || !empty($props['animate']));
$linkActivation = (!isset($props['link_activation']) || !empty($props['link_activation']));

if (!isset($preview)) {
    $preview = false;
}

if (!isset($clickUrl)) {
    $clickUrl = $props['content']['link_url'];
}
    $cssContent = $view->render(
        'MauticFocusBundle:Builder:style.less.php',
        [
            'preview' => $preview,
            'focus'   => $focus,
        ]
    );
    $cssContent = $view->escape($cssContent, 'js');

    $parentCssContent = $view->render(
        'MauticFocusBundle:Builder:parent.less.php',
        [
            'preview' => $preview,
        ]
    );
    $parentCssContent = $view->escape($parentCssContent, 'js');

switch ($style) {
    case 'bar':
        $iframeClass = "mf-bar-iframe mf-bar-iframe-{$props['bar']['placement']} mf-bar-iframe-{$props['bar']['size']}";
        if ($props['bar']['sticky']) {
            $iframeClass .= ' mf-bar-iframe-sticky';
        }
        break;

    case 'modal':
    case 'notification':
        $placement   = str_replace('_', '-', $props[$style]['placement']);
        $iframeClass = "mf-{$style}-iframe mf-{$style}-iframe-{$placement}";
        break;

    default:
        $iframeClass = 'mf-'.$style.'-iframe';
        break;
}
?>
(function (window) {
    if (typeof window.MauticFocusParentHeadStyleInserted == 'undefined') {
        window.MauticFocusParentHeadStyleInserted = false;
    }

    window.MauticFocus<?php echo $focus['id']; ?> = function () {
        var Focus = {
            debug: <?php echo $debug; ?>,
            modalsDismissed: {},
            ignoreConverted: <?php echo ($focus['type'] !== 'notification' && !empty($props['stop_after_conversion'])) ? 'true' : 'false'; ?>,

            // Initialize the focus
            initialize: function () {
                if (Focus.debug)
                    console.log('initialize()');

                Focus.insertStyleIntoHead();
                Focus.registerFocusEvent();

                // Add class to body
                Focus.addClass(document.getElementsByTagName('body')[0], 'MauticFocus<?php echo ucfirst($style); ?>');
            },

            // Register click events for toggling bar, closing windows, etc
            registerClickEvents: function () {
                <?php if ($style == 'bar'): ?>
                var collapser = document.getElementsByClassName('mf-bar-collapser-<?php echo $focus['id']; ?>');

                collapser[0].addEventListener('click', function () {
                    Focus.toggleBarCollapse(collapser[0], false);
                });

                <?php else: ?>
                var closer = Focus.iframeDoc.getElementsByClassName('mf-<?php echo $style; ?>-close');
                var aTag = closer[0].getElementsByTagName('a');
                var container = Focus.iframeDoc.getElementsByClassName('mf-<?php echo $style; ?>');

                container.onclick = function(e) {
                    if (e) { e.stopPropagation(); }
                    else { window.event.cancelBubble = true; }
                };
                document.onclick = function() {
                    aTag[0].click();
                };

                aTag[0].addEventListener('click', function (event) {
                    // Prevent multiple engagements for link clicks on exit intent
                    Focus.modalsDismissed["<?php echo $focus['id']; ?>"] = true;

                    // Remove iframe
                    Focus.iframe.parentNode.removeChild(Focus.iframe);

                    var overlays = document.getElementsByClassName('mf-modal-overlay-<?php echo $focus['id']; ?>');
                    if (overlays.length) {
                        overlays[0].parentNode.removeChild(overlays[0]);
                    }
                });
                <?php endif; ?>

                <?php if ($focus['type'] == 'click'): ?>
                var links = Focus.iframeDoc.getElementsByClassName('mf-link');
                if (links.length) {
                    links[0].addEventListener('click', function (event) {
                        Focus.convertVisitor();
                    });
                }
                <?php elseif ($focus['type'] == 'form'): ?>
                var buttons = Focus.iframeDoc.getElementsByClassName('mauticform-button');
                if (buttons.length) {
                    buttons[0].addEventListener('click', function (event) {
                        Focus.convertVisitor();
                    });
                }
                <?php endif; ?>
            },

            toggleBarCollapse: function (collapser, useCookie) {
                var svg = collapser.getElementsByTagName('svg');
                var g = svg[0].getElementsByTagName('g');
                var currentSize = svg[0].getAttribute('data-transform-size');
                var currentDirection = svg[0].getAttribute('data-transform-direction');
                var currentScale = svg[0].getAttribute('data-transform-scale');

                if (useCookie) {
                    if (Focus.cookies.hasItem('mf-bar-collapser-<?php echo $focus['id']; ?>')) {
                        var newDirection = Focus.cookies.getItem('mf-bar-collapser-<?php echo $focus['id']; ?>');
                        if (isNaN(newDirection)) {
                            var newDirection = currentDirection;
                        }
                    } else {
                        // Set cookie with current direction
                        var newDirection = currentDirection;
                    }
                } else {
                    var newDirection = (parseInt(currentDirection) * -1);
                    Focus.cookies.setItem('mf-bar-collapser-<?php echo $focus['id']; ?>', newDirection);
                }

                setTimeout(function () {
                    g[0].setAttribute('transform', 'scale(' + currentScale + ') rotate(' + newDirection + ' ' + currentSize + ' ' + currentSize + ')');
                    svg[0].setAttribute('data-transform-direction', newDirection);
                }, 500);

                var isTop = Focus.hasClass(Focus.iframeFocus, 'mf-bar-top');
                if ((!isTop && newDirection == 90) || (isTop && newDirection == -90)) {
                    // Open it up
                    if (isTop) {
                        Focus.iframe.style.marginTop = 0;
                    } else {
                        Focus.iframe.style.marginBottom = 0;
                    }

                    Focus.removeClass(collapser, 'mf-bar-collapsed');
                    Focus.enableIframeResizer();

                } else {
                    // Collapse it
                    var iframeHeight = Focus.iframe.style.height;

                    iframeHeight.replace('px', '');
                    var newMargin = (parseInt(iframeHeight) * -1) + 'px';
                    if (isTop) {
                        Focus.iframe.style.marginTop = newMargin;
                    } else {
                        Focus.iframe.style.marginBottom = newMargin;
                    }

                    Focus.addClass(collapser, 'mf-bar-collapsed');
                    Focus.disableIFrameResizer();
                }
            },

            // Register scroll events, etc
            registerFocusEvent: function () {
                if (Focus.debug)
                    console.log('registerFocusEvent()');

                <?php if ($useScrollEvent): ?>
                if (Focus.debug)
                    console.log('scroll event registered');

                    <?php if ($useTimeout): ?>
                    if (Focus.debug)
                        console.log('timeout event registered');

                    setTimeout(function () {
                        window.addEventListener('scroll', Focus.engageVisitorAtScrollPosition);
                    }, <?php echo $timeout; ?>);

                    <?php else: ?>

                     window.addEventListener('scroll', Focus.engageVisitorAtScrollPosition);

                   <?php endif; ?>

                <?php elseif ($useUnloadEvent): ?>
                if (Focus.debug)
                    console.log('show when visitor leaves');

                    <?php if ($useTimeout): ?>
                    if (Focus.debug)
                        console.log('timeout event registered');

                    setTimeout(function () {
                        document.documentElement.addEventListener('mouseleave', Focus.engageVisitor);
                    }, <?php echo $timeout; ?>);

                    <?php else: ?>

                    document.documentElement.addEventListener('mouseleave', Focus.engageVisitor);

                    <?php endif; ?>

                // Add a listener to every link
                <?php if ($linkActivation): ?>

                var elements = document.getElementsByTagName('a');

                for (var i = 0, len = elements.length; i < len; i++) {
                    var href = elements[i].getAttribute('href');
                    if (href && href.indexOf('#') != 0 && href.indexOf('javascript:') != 0) {
                        elements[i].onclick = function (event) {
                            if (typeof Focus.modalsDismissed["<?php echo $focus['id']; ?>"] == 'undefined') {
                                if (Focus.engageVisitor()) {
                                    event.preventDefault();
                                }
                            }
                        }
                    }
                }
                <?php endif; ?>

                <?php else: ?>
                if (Focus.debug)
                    console.log('show immediately');

                    <?php if ($useTimeout): ?>
                    if (Focus.debug)
                        console.log('timeout event registered');

                    setTimeout(function () {
                        // Give a slight delay to allow browser to process style injection into header
                        Focus.engageVisitor();
                    }, <?php echo $timeout; ?>);

                    <?php else: ?>

                    // Give a slight delay to allow browser to process style injection into header
                    Focus.engageVisitor();

                    <?php endif; ?>

                <?php endif; ?>
            },

            // Insert global style into page head
            insertStyleIntoHead: function () {
                if (!window.MauticFocusParentHeadStyleInserted) {
                    if (Focus.debug)
                        console.log('insertStyleIntoHead()');

                    var css = "<?php echo $parentCssContent; ?>",
                        head = document.head || document.getElementsByTagName('head')[0],
                        style = document.createElement('style');

                    head.appendChild(style);
                    style.type = 'text/css';
                    if (style.styleSheet) {
                        style.styleSheet.cssText = css;
                    } else {
                        style.appendChild(document.createTextNode(css));
                    }
                } else if (Focus.debug) {
                    console.log('Shared style already inserted into head');
                }
            },

            // Inserts styling into the iframe's head
            insertFocusStyleIntoIframeHead: function () {
                // Insert style into iframe header
                var frameDoc = Focus.iframe.contentDocument;
                var frameHead = frameDoc.getElementsByTagName('head').item(0);

                var css = "<?php echo $cssContent; ?>";
                var style = frameDoc.createElement('style');

                style.type = 'text/css';
                if (style.styleSheet) {
                    style.styleSheet.cssText = css;
                } else {
                    style.appendChild(frameDoc.createTextNode(css));
                }
                frameHead.appendChild(style);

                var metaTag = frameDoc.createElement('meta');
                metaTag.name = "viewport"
                metaTag.content = "width=device-width,initial-scale=1,minimum-scale=1.0 maximum-scale=1.0"
                frameHead.appendChild(metaTag);
            },

            // Generates the focus HTML
            engageVisitor: function () {
                var now = Math.floor(Date.now() / 1000);

                if (Focus.cookies.hasItem('mautic_focus_<?php echo $focus['id']; ?>')) {
                    if (Focus.debug)
                        console.log('Cookie exists thus checking frequency');

                    var lastEngaged = parseInt(Focus.cookies.getItem('mautic_focus_<?php echo $focus['id']; ?>')),
                        frequency = '<?php echo $props['frequency']; ?>',
                        engage;

                    if (Focus.ignoreConverted && lastEngaged == -1) {
                        if (Focus.debug)
                            console.log('Visitor converted; abort');

                        return false;
                    }

                    switch (frequency) {
                        case 'once':
                            engage = false;
                            if (Focus.debug)
                                console.log('Engage once, abort');

                            break;
                        case 'everypage':
                            engage = true;
                            if (Focus.debug)
                                console.log('Engage on every page, continue');

                            break;
                        case 'q2min':
                            engage = (now - lastEngaged) >= 120;
                            if (Focus.debug) {
                                var debugMsg = 'Engage q2 minute, ';
                                if (engage) {
                                    debugMsg += 'continue';
                                } else {
                                    debugMsg += 'engage in ' + (120 - (now - lastEngaged)) + ' seconds';
                                }
                                console.log(debugMsg);
                            }

                            break;
                        case 'q15min':
                            engage = (now - lastEngaged) >= 900;
                            if (Focus.debug) {
                                var debugMsg = 'Engage q15 minute, ';
                                if (engage) {
                                    debugMsg += 'continue';
                                } else {
                                    debugMsg += 'engage in ' + (120 - (now - lastEngaged)) + ' seconds';
                                }
                                console.log(debugMsg);
                            }

                            break;
                        case 'hourly':
                            engage = (now - lastEngaged) >= 3600;
                            if (Focus.debug) {
                                var debugMsg = 'Engage hourly, ';
                                if (engage) {
                                    debugMsg += 'continue';
                                } else {
                                    debugMsg += 'engage in ' + (120 - (now - lastEngaged)) + ' seconds';
                                }
                                console.log(debugMsg);
                            }

                            break;
                        case 'daily':
                            engage = (now - lastEngaged) >= 86400;
                            if (Focus.debug) {
                                var debugMsg = 'Engage daily, ';
                                if (engage) {
                                    debugMsg += 'continue';
                                } else {
                                    debugMsg += 'engage in ' + (120 - (now - lastEngaged)) + ' seconds';
                                }
                                console.log(debugMsg);
                            }

                            break;
                    }

                    if (!engage) {

                        return false;
                    }
                }

                if (Focus.debug)
                    console.log('engageVisitor()');

                // Inject iframe
                Focus.createIframe();

                // Inject content into iframe
                Focus.iframeDoc.open();
                <?php
                $content = $view->render(
                    'MauticFocusBundle:Builder:content.html.php',
                    [
                        'focus'    => $focus,
                        'form'     => $form,
                        'clickUrl' => $clickUrl,
                    ]
                );

                if (empty($ignoreMinify)) {
                    $content = \Minify_HTML::minify($content);
                }

                $content = $view->escape($content, 'js');
                ?>

                Focus.iframeDoc.write("<?php echo $content; ?>");
                Focus.iframeDoc.close();

                // Set body margin to 0
                Focus.iframeDoc.getElementsByTagName('body')[0].style.margin = 0;

                // Find elements that should be moved to parent
                var move = Focus.iframeDoc.getElementsByClassName('mf-move-to-parent');
                for (var i = 0; i < move.length; i++) {
                    var bodyFirstChild = document.body.firstChild;
                    bodyFirstChild.parentNode.insertBefore(move[i], Focus.iframe);
                }

                // Find elements that should be copied to parent
                var copy = Focus.iframeDoc.getElementsByClassName('mf-copy-to-parent');
                for (var i = 0; i < copy.length; i++) {
                    var bodyFirstChild = document.body.firstChild;
                    var clone = copy[i].cloneNode(true);
                    bodyFirstChild.parentNode.insertBefore(clone, Focus.iframe);
                }

                // Get the main focus element
                var focus = Focus.iframeDoc.getElementsByClassName('mautic-focus');
                Focus.iframeFocus = focus[0];

                // Insert style into iframe head
                Focus.insertFocusStyleIntoIframeHead();

                // Register events
                Focus.registerClickEvents();

                // Resize iframe
                var animate = <?php echo ($animate) ? 'true' : 'false'; ?>;
                if (Focus.enableIframeResizer()) {
                    // Give iframe chance to resize
                    setTimeout(function () {
                        if (animate) {
                            Focus.addClass(Focus.iframe, "mf-animate");
                        }
                        Focus.addClass(Focus.iframe, "mf-loaded");
                    }, 35);
                } else {
                    if (animate) {
                        Focus.addClass(Focus.iframe, "mf-animate");
                    }
                    Focus.addClass(Focus.iframe, "mf-loaded");
                }

                <?php if ($props['when'] == 'leave'): ?>
                // Ensure user can leave
                document.documentElement.removeEventListener('mouseleave', Focus.engageVisitor);
                <?php endif; ?>

                // Add cookie of last engagement
                if (Focus.debug)
                    console.log('mautic_focus_<?php echo $focus['id']; ?> cookie set for ' + now);

                Focus.cookies.removeItem('mautic_focus_<?php echo $focus['id']; ?>');
                Focus.cookies.setItem('mautic_focus_<?php echo $focus['id']; ?>', now, Infinity);

                <?php if ($style == 'bar'): ?>
                var collapser = document.getElementsByClassName('mf-bar-collapser-<?php echo $focus['id']; ?>');

                if (animate) {
                    // Give iframe chance to resize
                    setTimeout(function () {
                        Focus.toggleBarCollapse(collapser[0], true);
                    }, 35);
                } else {
                    Focus.toggleBarCollapse(collapser[0], true);
                }
                <?php endif; ?>

                return true;
            },

            // Enable iframe resizer
            enableIframeResizer: function () {
                <?php if (in_array($style, ['modal', 'notification', 'bar'])): ?>
                Focus.iframeHeight = 0;
                Focus.iframeWidth = 0;
                Focus.iframeResizeInterval = setInterval(function () {
                    if (Focus.iframeHeight !== Focus.iframe.style.height) {
                        var useHeight = ((window.innerHeight < Focus.iframeFocus.offsetHeight) ?
                            window.innerHeight : Focus.iframeFocus.offsetHeight);

                        useHeight += 10;
                        useHeight = useHeight + 'px';


                        if (Focus.debug) {
                            console.log('window inner height = ' + window.innerHeight);
                            console.log('iframe offset height = ' + Focus.iframeFocus.offsetHeight);
                            console.log('iframe height set to ' + useHeight)
                        }
                        ;
                        Focus.iframe.style.height = useHeight;
                        Focus.iframeHeight = useHeight;
                    }

                    <?php if (in_array($style, ['modal', 'notification'])): ?>
                    if (Focus.iframeWidth !== Focus.iframe.style.width) {
                        if (Focus.debug) {
                            console.log('window inner width = ' + window.innerWidth);
                            console.log('iframe offset width = ' + Focus.iframeFocus.offsetWidth);
                        }

                        if (window.innerWidth < Focus.iframeFocus.offsetWidth) {
                            // Responsive iframe
                            Focus.addClass(Focus.iframeFocus, 'mf-responsive');
                            Focus.addClass(Focus.iframe, 'mf-responsive');
                            Focus.iframe.style.width = window.innerWidth + 'px';
                            Focus.iframe.width = window.innerWidth;
                            if (Focus.debug)
                                console.log('iframe set to responsive width: ');

                        } else {
                            Focus.iframe.style.width = Focus.iframeFocus.offsetWidth + 'px';
                            Focus.iframe.width = Focus.iframeFocus.offsetWidth + 'px';
                            Focus.removeClass(Focus.iframeFocus, 'mf-responsive');
                            Focus.removeClass(Focus.iframe, 'mf-responsive');

                            if (Focus.debug)
                                console.log('iframe not a responsive width');
                        }

                        Focus.iframeWidth = Focus.iframe.style.width;
                    }
                    <?php endif; ?>
                }, 35);

                return true;
                <?php endif; ?>

                return false;
            },

            // Disable iframe resizer
            disableIFrameResizer: function () {
                <?php if (in_array($style, ['modal', 'notification', 'bar'])): ?>
                clearInterval(Focus.iframeResizeInterval);
                <?php endif; ?>
            },

            // Create iframe to load into body
            createIframe: function () {
                if (Focus.debug)
                    console.log('createIframe()');

                Focus.iframe = document.createElement('iframe');
                Focus.iframe.style.border = 0;
                Focus.iframe.style.width = "100%";
                Focus.iframe.style.height = "100%";
                Focus.iframe.src = "about:blank";
                Focus.iframe.scrolling = "no";
                Focus.iframe.className = "<?php echo $iframeClass; ?>";

                var bodyFirstChild = document.body.firstChild;
                bodyFirstChild.parentNode.insertBefore(Focus.iframe, bodyFirstChild);

                Focus.iframeDoc = Focus.iframe.contentWindow.document;
            },

            // Execute event at current position
            engageVisitorAtScrollPosition: function (event) {
                var visualHeight = "innerHeight" in window
                    ? window.innerHeight
                    : document.documentElement.offsetHeight;

                var scrollPos = window.pageYOffset,
                    atPos = 0;

                <?php switch ($props['when']):
                case 'scroll_slight': ?>
                atPos = 10;
                <?php break; ?>

                <?php case 'scroll_middle': ?>
                scrollPos += (visualHeight / 2);
                atPos = (document.body.scrollHeight / 2);
                <?php break; ?>

                <?php case 'scroll_bottom': ?>
                scrollPos += visualHeight;
                atPos = document.body.scrollHeight;
                <?php break; ?>

                <?php endswitch; ?>

                if (Focus.debug)
                    console.log('scrolling: ' + scrollPos + ' >= ' + atPos);

                if (scrollPos >= atPos) {
                    window.removeEventListener('scroll', Focus.engageVisitorAtScrollPosition);
                    Focus.engageVisitor();
                }
            },

            // Create cookie noting visitor has been converted if applicable
            convertVisitor: function () {
                if (Focus.ignoreConverted) {
                    if (Focus.debug)
                        console.log('Visitor converted');

                    Focus.cookies.setItem('mautic_focus_<?php echo $focus['id']; ?>', -1, Infinity);
                } else if (Focus.debug) {
                    console.log('Visitor converted but ignoreConverted not enabled');
                }
            },

            // Element has class
            hasClass: function (element, hasClass) {
                return ( (" " + element.className + " ").replace(/[\n\t]/g, " ").indexOf(" " + hasClass + " ") > -1 );
            },

            // Add class to element
            addClass: function (element, addClass) {
                if (!Focus.hasClass(element, addClass)) {
                    element.className += " " + addClass;
                }
            },

            // Remove class from element
            removeClass: function (element, removeClass) {
                element.className = element.className.replace(new RegExp('\\b' + removeClass + '\\b'), '');
            },

            // Cookie handling
            cookies: {
                /**
                 * :: cookies.js ::
                 * https://developer.mozilla.org/en-US/docs/Web/API/document.cookie
                 * http://www.gnu.org/licenses/gpl-3.0-standalone.html
                 */
                getItem: function (sKey) {
                    if (!sKey) {
                        return null;
                    }
                    return decodeURIComponent(document.cookie.replace(new RegExp("(?:(?:^|.*;)\\s*" + encodeURIComponent(sKey).replace(/[\-\.\+\*]/g, "\\$&") + "\\s*\\=\\s*([^;]*).*$)|^.*$"), "$1")) || null;
                },
                setItem: function (sKey, sValue, vEnd, sPath, sDomain, bSecure) {
                    if (!sKey || /^(?:expires|max\-age|path|domain|secure)$/i.test(sKey)) {
                        return false;
                    }

                    this.removeItem(sKey);

                    var sExpires = "";
                    if (vEnd) {
                        switch (vEnd.constructor) {
                            case Number:
                                sExpires = vEnd === Infinity ? "; expires=Fri, 31 Dec 9999 23:59:59 GMT" : "; max-age=" + vEnd;
                                break;
                            case String:
                                sExpires = "; expires=" + vEnd;
                                break;
                            case Date:
                                sExpires = "; expires=" + vEnd.toUTCString();
                                break;
                        }
                    }
                    document.cookie = encodeURIComponent(sKey) + "=" + encodeURIComponent(sValue) + sExpires + (sDomain ? "; domain=" + sDomain : "") + (sPath ? "; path=" + sPath : "") + (bSecure ? "; secure" : "");
                    return true;
                },
                removeItem: function (sKey, sPath, sDomain) {
                    if (!this.hasItem(sKey)) {
                        return false;
                    }
                    document.cookie = encodeURIComponent(sKey) + "=; expires=Thu, 01 Jan 1970 00:00:00 GMT" + (sDomain ? "; domain=" + sDomain : "") + (sPath ? "; path=" + sPath : "");
                    return true;
                },
                hasItem: function (sKey) {
                    if (!sKey) {
                        return false;
                    }
                    return (new RegExp("(?:^|;\\s*)" + encodeURIComponent(sKey).replace(/[\-\.\+\*]/g, "\\$&") + "\\s*\\=")).test(document.cookie);
                },
                keys: function () {
                    var aKeys = document.cookie.replace(/((?:^|\s*;)[^\=]+)(?=;|$)|^\s*|\s*(?:\=[^;]*)?(?:\1|$)/g, "").split(/\s*(?:\=[^;]*)?;\s*/);
                    for (var nLen = aKeys.length, nIdx = 0; nIdx < nLen; nIdx++) {
                        aKeys[nIdx] = decodeURIComponent(aKeys[nIdx]);
                    }
                    return aKeys;
                }
            }
        };

        return Focus;
    }

    // Initialize
    MauticFocus<?php echo $focus['id']; ?>().initialize();
})(window);