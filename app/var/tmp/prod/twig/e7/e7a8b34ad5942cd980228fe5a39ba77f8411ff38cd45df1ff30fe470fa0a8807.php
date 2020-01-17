<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Source;
use Twig\Template;

/* TwigBundle::base_js.html.twig */
class __TwigTemplate_8753c64f59ef96b2737eeece189a00b2d45c860a28bf358ade99119828b509ff extends \Twig\Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 3
        echo '<script';
        if (((isset($context['csp_script_nonce']) || array_key_exists('csp_script_nonce', $context)) && ($context['csp_script_nonce'] ?? null))) {
            echo ' nonce="';
            echo twig_escape_filter($this->env, ($context['csp_script_nonce'] ?? null), 'html', null, true);
            echo '"';
        }
        echo '>/*<![CDATA[*/
    ';
        // line 7
        echo "
    Sfjs = (function() {
        \"use strict\";

        if ('classList' in document.documentElement) {
            var hasClass = function (el, cssClass) { return el.classList.contains(cssClass); };
            var removeClass = function(el, cssClass) { el.classList.remove(cssClass); };
            var addClass = function(el, cssClass) { el.classList.add(cssClass); };
            var toggleClass = function(el, cssClass) { el.classList.toggle(cssClass); };
        } else {
            var hasClass = function (el, cssClass) { return el.className.match(new RegExp('\\\\b' + cssClass + '\\\\b')); };
            var removeClass = function(el, cssClass) { el.className = el.className.replace(new RegExp('\\\\b' + cssClass + '\\\\b'), ' '); };
            var addClass = function(el, cssClass) { if (!hasClass(el, cssClass)) { el.className += \" \" + cssClass; } };
            var toggleClass = function(el, cssClass) { hasClass(el, cssClass) ? removeClass(el, cssClass) : addClass(el, cssClass); };
        }

        var addEventListener;

        var el = document.createElement('div');
        if (!('addEventListener' in el)) {
            addEventListener = function (element, eventName, callback) {
                element.attachEvent('on' + eventName, callback);
            };
        } else {
            addEventListener = function (element, eventName, callback) {
                element.addEventListener(eventName, callback, false);
            };
        }

        return {
            addEventListener: addEventListener,

            createTabs: function() {
                var tabGroups = document.querySelectorAll('.sf-tabs:not([data-processed=true])');

                /* create the tab navigation for each group of tabs */
                for (var i = 0; i < tabGroups.length; i++) {
                    var tabs = tabGroups[i].querySelectorAll('.tab');
                    var tabNavigation = document.createElement('ul');
                    tabNavigation.className = 'tab-navigation';

                    for (var j = 0; j < tabs.length; j++) {
                        var tabId = 'tab-' + i + '-' + j;
                        var tabTitle = tabs[j].querySelector('.tab-title').innerHTML;

                        var tabNavigationItem = document.createElement('li');
                        tabNavigationItem.setAttribute('data-tab-id', tabId);
                        if (j == 0) { addClass(tabNavigationItem, 'active'); }
                        if (hasClass(tabs[j], 'disabled')) { addClass(tabNavigationItem, 'disabled'); }
                        tabNavigationItem.innerHTML = tabTitle;
                        tabNavigation.appendChild(tabNavigationItem);

                        var tabContent = tabs[j].querySelector('.tab-content');
                        tabContent.parentElement.setAttribute('id', tabId);
                    }

                    tabGroups[i].insertBefore(tabNavigation, tabGroups[i].firstChild);
                }

                /* display the active tab and add the 'click' event listeners */
                for (i = 0; i < tabGroups.length; i++) {
                    tabNavigation = tabGroups[i].querySelectorAll('.tab-navigation li');

                    for (j = 0; j < tabNavigation.length; j++) {
                        tabId = tabNavigation[j].getAttribute('data-tab-id');
                        document.getElementById(tabId).querySelector('.tab-title').className = 'hidden';

                        if (hasClass(tabNavigation[j], 'active')) {
                            document.getElementById(tabId).className = 'block';
                        } else {
                            document.getElementById(tabId).className = 'hidden';
                        }

                        tabNavigation[j].addEventListener('click', function(e) {
                            var activeTab = e.target || e.srcElement;

                            /* needed because when the tab contains HTML contents, user can click */
                            /* on any of those elements instead of their parent '<li>' element */
                            while (activeTab.tagName.toLowerCase() !== 'li') {
                                activeTab = activeTab.parentNode;
                            }

                            /* get the full list of tabs through the parent of the active tab element */
                            var tabNavigation = activeTab.parentNode.children;
                            for (var k = 0; k < tabNavigation.length; k++) {
                                var tabId = tabNavigation[k].getAttribute('data-tab-id');
                                document.getElementById(tabId).className = 'hidden';
                                removeClass(tabNavigation[k], 'active');
                            }

                            addClass(activeTab, 'active');
                            var activeTabId = activeTab.getAttribute('data-tab-id');
                            document.getElementById(activeTabId).className = 'block';
                        });
                    }

                    tabGroups[i].setAttribute('data-processed', 'true');
                }
            },

            createToggles: function() {
                var toggles = document.querySelectorAll('.sf-toggle:not([data-processed=true])');

                for (var i = 0; i < toggles.length; i++) {
                    var elementSelector = toggles[i].getAttribute('data-toggle-selector');
                    var element = document.querySelector(elementSelector);

                    addClass(element, 'sf-toggle-content');

                    if (toggles[i].hasAttribute('data-toggle-initial') && toggles[i].getAttribute('data-toggle-initial') == 'display') {
                        addClass(toggles[i], 'sf-toggle-on');
                        addClass(element, 'sf-toggle-visible');
                    } else {
                        addClass(toggles[i], 'sf-toggle-off');
                        addClass(element, 'sf-toggle-hidden');
                    }

                    addEventListener(toggles[i], 'click', function(e) {
                        e.preventDefault();

                        if ('' !== window.getSelection().toString()) {
                            /* Don't do anything on text selection */
                            return;
                        }

                        var toggle = e.target || e.srcElement;

                        /* needed because when the toggle contains HTML contents, user can click */
                        /* on any of those elements instead of their parent '.sf-toggle' element */
                        while (!hasClass(toggle, 'sf-toggle')) {
                            toggle = toggle.parentNode;
                        }

                        var element = document.querySelector(toggle.getAttribute('data-toggle-selector'));

                        toggleClass(toggle, 'sf-toggle-on');
                        toggleClass(toggle, 'sf-toggle-off');
                        toggleClass(element, 'sf-toggle-hidden');
                        toggleClass(element, 'sf-toggle-visible');

                        /* the toggle doesn't change its contents when clicking on it */
                        if (!toggle.hasAttribute('data-toggle-alt-content')) {
                            return;
                        }

                        if (!toggle.hasAttribute('data-toggle-original-content')) {
                            toggle.setAttribute('data-toggle-original-content', toggle.innerHTML);
                        }

                        var currentContent = toggle.innerHTML;
                        var originalContent = toggle.getAttribute('data-toggle-original-content');
                        var altContent = toggle.getAttribute('data-toggle-alt-content');
                        toggle.innerHTML = currentContent !== altContent ? altContent : originalContent;
                    });

                    /* Prevents from disallowing clicks on links inside toggles */
                    var toggleLinks = toggles[i].querySelectorAll('a');
                    for (var j = 0; j < toggleLinks.length; j++) {
                        addEventListener(toggleLinks[j], 'click', function(e) {
                            e.stopPropagation();
                        });
                    }

                    toggles[i].setAttribute('data-processed', 'true');
                }
            }
        };
    })();

    Sfjs.addEventListener(document, 'DOMContentLoaded', function() {
        Sfjs.createTabs();
        Sfjs.createToggles();
    });

/*]]>*/</script>
";
    }

    public function getTemplateName()
    {
        return 'TwigBundle::base_js.html.twig';
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [46 => 7,  37 => 3];
    }

    public function getSourceContext()
    {
        return new Source('', 'TwigBundle::base_js.html.twig', '/Users/mike.shaw/sites/mautic/vendor/symfony/twig-bundle/Resources/views/base_js.html.twig');
    }
}
