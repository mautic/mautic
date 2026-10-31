window.MauticInstallLegacyTransition = function (jQuery) {
/* ========================================================================
 * Bootstrap: transition.js v3.4.1
 * https://getbootstrap.com/docs/3.4/javascript/#transitions
 * ========================================================================
 * Copyright 2011-2019 Twitter, Inc.
 * Licensed under MIT (https://github.com/twbs/bootstrap/blob/master/LICENSE)
 * ======================================================================== */


+function ($) {
  'use strict';

  // CSS TRANSITION SUPPORT (Shoutout: https://modernizr.com/)
  // ============================================================

  function transitionEnd() {
    var el = document.createElement('bootstrap')

    var transEndEventNames = {
      WebkitTransition : 'webkitTransitionEnd',
      MozTransition    : 'transitionend',
      OTransition      : 'oTransitionEnd otransitionend',
      transition       : 'transitionend'
    }

    for (var name in transEndEventNames) {
      if (el.style[name] !== undefined) {
        return { end: transEndEventNames[name] }
      }
    }

    return false // explicit for ie8 (  ._.)
  }

  // https://blog.alexmaccaw.com/css-transitions
  $.fn.emulateTransitionEnd = function (duration) {
    var called = false
    var $el = this
    $(this).one('bsTransitionEnd', function () { called = true })
    var callback = function () { if (!called) $($el).trigger($.support.transition.end) }
    setTimeout(callback, duration)
    return this
  }

  $(function () {
    $.support.transition = transitionEnd()

    if (!$.support.transition) return

    $.event.special.bsTransitionEnd = {
      bindType: $.support.transition.end,
      delegateType: $.support.transition.end,
      handle: function (e) {
        if ($(e.target).is(this)) return e.handleObj.handler.apply(this, arguments)
      }
    }
  })

}(jQuery);

};

window.MauticInstallLegacyAffix = function (jQuery) {
/* ========================================================================
 * Bootstrap: affix.js v3.4.1
 * https://getbootstrap.com/docs/3.4/javascript/#affix
 * ========================================================================
 * Copyright 2011-2019 Twitter, Inc.
 * Licensed under MIT (https://github.com/twbs/bootstrap/blob/master/LICENSE)
 * ======================================================================== */


+function ($) {
  'use strict';

  // AFFIX CLASS DEFINITION
  // ======================

  var Affix = function (element, options) {
    this.options = $.extend({}, Affix.DEFAULTS, options)

    var target = this.options.target === Affix.DEFAULTS.target ? $(this.options.target) : $(document).find(this.options.target)

    this.$target = target
      .on('scroll.bs.affix.data-api', $.proxy(this.checkPosition, this))
      .on('click.bs.affix.data-api',  $.proxy(this.checkPositionWithEventLoop, this))

    this.$element     = $(element)
    this.affixed      = null
    this.unpin        = null
    this.pinnedOffset = null

    this.checkPosition()
  }

  Affix.VERSION  = '3.4.1'

  Affix.RESET    = 'affix affix-top affix-bottom'

  Affix.DEFAULTS = {
    offset: 0,
    target: window
  }

  Affix.prototype.getState = function (scrollHeight, height, offsetTop, offsetBottom) {
    var scrollTop    = this.$target.scrollTop()
    var position     = this.$element.offset()
    var targetHeight = this.$target.height()

    if (offsetTop != null && this.affixed == 'top') return scrollTop < offsetTop ? 'top' : false

    if (this.affixed == 'bottom') {
      if (offsetTop != null) return (scrollTop + this.unpin <= position.top) ? false : 'bottom'
      return (scrollTop + targetHeight <= scrollHeight - offsetBottom) ? false : 'bottom'
    }

    var initializing   = this.affixed == null
    var colliderTop    = initializing ? scrollTop : position.top
    var colliderHeight = initializing ? targetHeight : height

    if (offsetTop != null && scrollTop <= offsetTop) return 'top'
    if (offsetBottom != null && (colliderTop + colliderHeight >= scrollHeight - offsetBottom)) return 'bottom'

    return false
  }

  Affix.prototype.getPinnedOffset = function () {
    if (this.pinnedOffset) return this.pinnedOffset
    this.$element.removeClass(Affix.RESET).addClass('affix')
    var scrollTop = this.$target.scrollTop()
    var position  = this.$element.offset()
    return (this.pinnedOffset = position.top - scrollTop)
  }

  Affix.prototype.checkPositionWithEventLoop = function () {
    setTimeout($.proxy(this.checkPosition, this), 1)
  }

  Affix.prototype.checkPosition = function () {
    if (!this.$element.is(':visible')) return

    var height       = this.$element.height()
    var offset       = this.options.offset
    var offsetTop    = offset.top
    var offsetBottom = offset.bottom
    var scrollHeight = Math.max($(document).height(), $(document.body).height())

    if (typeof offset != 'object')         offsetBottom = offsetTop = offset
    if (typeof offsetTop == 'function')    offsetTop    = offset.top(this.$element)
    if (typeof offsetBottom == 'function') offsetBottom = offset.bottom(this.$element)

    var affix = this.getState(scrollHeight, height, offsetTop, offsetBottom)

    if (this.affixed != affix) {
      if (this.unpin != null) this.$element.css('top', '')

      var affixType = 'affix' + (affix ? '-' + affix : '')
      var e         = $.Event(affixType + '.bs.affix')

      this.$element.trigger(e)

      if (e.isDefaultPrevented()) return

      this.affixed = affix
      this.unpin = affix == 'bottom' ? this.getPinnedOffset() : null

      this.$element
        .removeClass(Affix.RESET)
        .addClass(affixType)
        .trigger(affixType.replace('affix', 'affixed') + '.bs.affix')
    }

    if (affix == 'bottom') {
      this.$element.offset({
        top: scrollHeight - height - offsetBottom
      })
    }
  }


  // AFFIX PLUGIN DEFINITION
  // =======================

  function Plugin(option) {
    return this.each(function () {
      var $this   = $(this)
      var data    = $this.data('bs.affix')
      var options = typeof option == 'object' && option

      if (!data) $this.data('bs.affix', (data = new Affix(this, options)))
      if (typeof option == 'string') data[option]()
    })
  }

  var old = $.fn.affix

  $.fn.affix             = Plugin
  $.fn.affix.Constructor = Affix


  // AFFIX NO CONFLICT
  // =================

  $.fn.affix.noConflict = function () {
    $.fn.affix = old
    return this
  }


  // AFFIX DATA-API
  // ==============

  $(window).on('load', function () {
    $('[data-spy="affix"]').each(function () {
      var $spy = $(this)
      var data = $spy.data()

      data.offset = data.offset || {}

      if (data.offsetBottom != null) data.offset.bottom = data.offsetBottom
      if (data.offsetTop    != null) data.offset.top    = data.offsetTop

      Plugin.call($spy, data)
    })
  })

}(jQuery);

};

(function (window) {
    'use strict';

    var buttonStates = new WeakMap();
    var remoteLoads = new WeakMap();

    function buttonState(jQuery, collection, option, configuration) {
        collection.each(function () {
            var element = jQuery(this);
            var state = buttonStates.get(this);
            var accessor = element.is('input') ? 'val' : 'html';

            if (!state) {
                state = {options: Object.assign({loadingText: 'loading...'}, window.bootstrap.Button.DEFAULTS, configuration || {}), isLoading: false};
                buttonStates.set(this, state);
            }
            var native = window.bootstrap.Button.getOrCreateInstance(this);
            var facade = element.data('bs.button') || {native: native};
            facade.options = state.options;
            facade.$element = element;
            facade.setState = function (nextState) {
                buttonState(jQuery, element, nextState);
            };
            facade.toggle = function () {
                if (!buttonGroup(jQuery, element[0], native)) {
                    native.toggle();
                }
            };
            if (!Object.getOwnPropertyDescriptor(facade, 'isLoading')) {
                Object.defineProperty(facade, 'isLoading', {get: function () { return state.isLoading; }});
            }
            element.data('bs.button', facade);
            if (undefined === option) {
                return;
            }
            var data = element.data();
            if (null == data.resetText) {
                element.data('resetText', element[accessor]());
            }
            window.setTimeout(function () {
                var value = element.data(option + 'Text');
                element[accessor](null == value ? state.options[option + 'Text'] : value);
                if ('loading' === option) {
                    state.isLoading = true;
                    element.addClass('disabled').attr('disabled', 'disabled').prop('disabled', true);
                } else if (state.isLoading) {
                    state.isLoading = false;
                    element.removeClass('disabled').removeAttr('disabled').prop('disabled', false);
                }
            }, 0);
        });
        return collection;
    }

    function buttonGroup(jQuery, element, nativeInstance) {
        var button = jQuery(element);
        var group = button.closest('[data-toggle="buttons"]');
        var input = button.find('input');
        if (!group.length || !input.length) {
            return false;
        }
        var changed = true;
        if ('radio' === input.prop('type')) {
            changed = !input.prop('checked');
            group.find('.btn.active').each(function () {
                if (this !== element) {
                    window.bootstrap.Button.getOrCreateInstance(this).toggle();
                }
            });
            if (!button.hasClass('active')) {
                nativeInstance.toggle();
            }
        } else if ('checkbox' === input.prop('type')) {
            changed = input.prop('checked') === button.hasClass('active');
            nativeInstance.toggle();
        }
        input.prop('checked', button.hasClass('active'));
        if (changed) {
            input.trigger('change');
        }
        return true;
    }

    function loadRemote(jQuery, element, options) {
        var remote = options && options.remote || jQuery(element).data('remote');
        if (!remote || remoteLoads.has(element)) {
            return;
        }
        remoteLoads.set(element, true);
        jQuery(element).find('.modal-content').load(remote, function () {
            jQuery(element).trigger('loaded.bs.modal');
        });
    }

    function translateTemplate(template, pluginName) {
        if ('string' !== typeof template) {
            return template;
        }
        var replacements = 'popover' === pluginName
            ? {arrow: 'popover-arrow', 'popover-title': 'popover-header', 'popover-content': 'popover-body'}
            : {arrow: 'tooltip-arrow'};
        return template.replace(/class\s*=\s*(["'])(.*?)\1/g, function (attribute, quote, classes) {
            return 'class=' + quote + classes.split(/\s+/).map(function (name) {
                return replacements[name] || name;
            }).join(' ') + quote;
        });
    }

    function hideTip(jQuery, element, pluginName, instance, callback) {
        var hidden = 'hidden.bs.' + pluginName;
        var hiding = 'hide.bs.' + pluginName;
        if (!instance.tip || !instance.tip.classList.contains('show')) {
            var event = jQuery.Event(hiding);
            element.trigger(event);
            if (!event.isDefaultPrevented()) {
                element.trigger(hidden);
                if ('function' === typeof callback) {
                    callback();
                }
            }
            return;
        }
        if ('function' !== typeof callback) {
            instance.hide();
            return;
        }
        var hideEvent;
        var observeHide = function (event) {
            hideEvent = event;
        };
        var complete = function () {
            callback();
        };
        element.one(hiding, observeHide).one(hidden, complete);
        instance.hide();
        element.off(hiding, observeHide);
        if (hideEvent && hideEvent.isDefaultPrevented()) {
            element.off(hidden, complete);
        }
    }

    function destroyTip(jQuery, collection, pluginName, BootstrapConstructor) {
        return collection.each(function () {
            var element = jQuery(this);
            var instance = BootstrapConstructor.getInstance(this);
            if (!instance) {
                return;
            }
            hideTip(jQuery, element, pluginName, instance, function () {
                instance.dispose();
                element.removeData('bs.' + pluginName);
            });
        });
    }

    function prepareMarkup(container) {
        if (!container.querySelectorAll) {
            return;
        }
        var elements = Array.from(container.querySelectorAll('.collapse.in, .modal.in, .carousel-inner > .item'));
        if (container.matches && container.matches('.collapse.in, .modal.in, .carousel-inner > .item')) {
            elements.push(container);
        }
        elements.forEach(function (element) {
            if (element.classList.contains('item')) {
                element.classList.add('carousel-item');
            } else {
                var BootstrapConstructor = window.bootstrap[element.classList.contains('modal') ? 'Modal' : 'Collapse'];
                if (!BootstrapConstructor.getInstance(element)) {
                    element.classList.add('show');
                }
            }
        });
    }

    function scrollspy(jQuery, collection, option, BootstrapConstructor) {
        return collection.each(function () {
            var element = this;
            var legacy = jQuery(element).data('bs.scrollspy');
            if (!legacy || !legacy.native) {
                var options = Object.assign({offset: 10}, BootstrapConstructor.DEFAULTS, jQuery(element).data(), 'object' === typeof option ? option : {});
                var native = BootstrapConstructor.getOrCreateInstance(element, {target: options.target || document.body});
                native._observer.disconnect();
                var scrollElement = jQuery(element).is(document.body) ? jQuery(window) : jQuery(element);
                legacy = {native: native, options: options, offsets: [], targets: [], activeTarget: null};
                legacy.getScrollHeight = function () {
                    return scrollElement[0].scrollHeight || Math.max(document.body.scrollHeight, document.documentElement.scrollHeight);
                };
                legacy.refresh = function () {
                    legacy.offsets = [];
                    legacy.targets = [];
                    legacy.scrollHeight = legacy.getScrollHeight();
                    var windowScroll = scrollElement[0] === window;
                    var base = windowScroll ? 0 : scrollElement.scrollTop();
                    var targets = [];
                    jQuery((options.target || '') + ' .nav li > a').each(function () {
                        var href = jQuery(this).data('target') || this.getAttribute('href');
                        var section = /^#./.test(href) && jQuery(href);
                        if (section && section.length && section.is(':visible')) {
                            targets.push({offset: section[windowScroll ? 'offset' : 'position']().top + base, href: href, link: this});
                        }
                    });
                    targets.sort(function (first, second) { return first.offset - second.offset; });
                    targets.forEach(function (target) {
                        legacy.offsets.push(target.offset);
                        legacy.targets.push(target.href);
                    });
                };
                legacy.clear = function () {
                    native._clearActiveClass(native._config.target);
                    jQuery((options.target || '') + ' .nav li > a').parentsUntil(options.target, '.active').removeClass('active');
                };
                legacy.activate = function (target) {
                    if (!target) {
                        return;
                    }
                    legacy.activeTarget = target;
                    legacy.clear();
                    var links = jQuery((options.target || '') + ' .nav li > a').filter(function () {
                        return this.getAttribute('href') === target || jQuery(this).data('target') === target;
                    });
                    var parents = links.parents('li').addClass('active');
                    native._activeTarget = null;
                    var originalElement = native._element;
                    try {
                        native._element = parents[0] || originalElement;
                        native._process(links[0]);
                    } finally {
                        native._element = originalElement;
                    }
                };
                legacy.process = function () {
                    var top = scrollElement.scrollTop() + options.offset;
                    var height = legacy.getScrollHeight();
                    if (height !== legacy.scrollHeight) {
                        legacy.refresh();
                    }
                    if (top >= options.offset + height - scrollElement.height()) {
                        var last = legacy.targets[legacy.targets.length - 1];
                        if (legacy.activeTarget !== last) {
                            legacy.activate(last);
                        }
                        return;
                    }
                    if (legacy.activeTarget && top < legacy.offsets[0]) {
                        legacy.activeTarget = null;
                        legacy.clear();
                        return;
                    }
                    for (var index = legacy.offsets.length - 1; index >= 0; index--) {
                        if (top >= legacy.offsets[index] && (undefined === legacy.offsets[index + 1] || top < legacy.offsets[index + 1])) {
                            if (legacy.activeTarget !== legacy.targets[index]) {
                                legacy.activate(legacy.targets[index]);
                            }
                            break;
                        }
                    }
                };
                jQuery(element).data('bs.scrollspy', legacy);
                scrollElement.on('scroll.mautic.legacyScrollspy', legacy.process);
                legacy.refresh();
                legacy.process();
            }
            if ('string' === typeof option) {
                legacy[option]();
            }
        });
    }

    var installed = new WeakSet();

    function adaptTip(instance, pluginName) {
        var tip = instance.tip || instance._getTipElement();
        var selectors = 'popover' === pluginName
            ? {'.popover-arrow': 'arrow', '.popover-header': 'popover-title', '.popover-body': 'popover-content'}
            : {'.tooltip-arrow': 'arrow'};
        Object.keys(selectors).forEach(function (selector) {
            var element = tip.querySelector(selector);
            if (element) {
                element.classList.add(selectors[selector]);
            }
        });
        return tip;
    }

    function activateLegacyTab(jQuery, element, container, callback) {
        var active = container.children('.active');
        var transition = callback && jQuery.support.transition && (active.hasClass('fade') || container.children('.fade').length);
        var complete = function () {
            active.removeClass('active show').find('> .dropdown-menu > .active').removeClass('active');
            active.find('[data-toggle="tab"]').attr('aria-expanded', false);
            element.addClass('active').find('[data-toggle="tab"]').attr('aria-expanded', true);
            if (transition) {
                element[0].offsetWidth;
                element.addClass('in');
            } else {
                element.removeClass('fade');
            }
            if (element.parent('.dropdown-menu').length) {
                element.closest('li.dropdown').addClass('active');
                element.find('[data-toggle="tab"]').attr('aria-expanded', true);
            }
            if (callback) {
                callback();
            }
        };
        if (active.length && transition) {
            active.one('bsTransitionEnd', complete).emulateTransitionEnd(150);
        } else {
            complete();
        }
        active.removeClass('in show');
    }

    function showLegacyFadedTab(jQuery, element) {
        var trigger = jQuery(element);
        if (!element.hasAttribute('data-toggle')) {
            return false;
        }
        var selector = trigger.attr('data-target') || trigger.attr('href');
        selector = selector && selector.replace(/.*(?=#[^\s]*$)/, '');
        var target = selector && jQuery(document).find(selector);
        var navigation = trigger.closest('ul:not(.dropdown-menu)');
        if (!target || !target.length || !navigation.length || !target.parent().children('.fade').length) {
            return false;
        }
        if (trigger.parent('li').hasClass('active')) {
            trigger.addClass('active').attr('aria-selected', true);
            return true;
        }
        var previous = navigation.find('li.active:last > a');
        var hideEvent = jQuery.Event('hide.bs.tab', {relatedTarget: element});
        var showEvent = jQuery.Event('show.bs.tab', {relatedTarget: previous[0]});
        previous.trigger(hideEvent);
        trigger.trigger(showEvent);
        if (showEvent.isDefaultPrevented() || hideEvent.isDefaultPrevented()) {
            return true;
        }
        previous.removeClass('active').attr('aria-selected', false).attr('tabindex', '-1');
        trigger.addClass('active').attr('aria-selected', true).removeAttr('tabindex');
        activateLegacyTab(jQuery, trigger.closest('li'), navigation);
        activateLegacyTab(jQuery, target, target.parent(), function () {
            previous.trigger({type: 'hidden.bs.tab', relatedTarget: element});
            trigger.trigger({type: 'shown.bs.tab', relatedTarget: previous[0]});
        });
        return true;
    }

    function synchronizeDropdownState(jQuery, method, eventName, open) {
        var prototype = window.bootstrap.Dropdown.prototype;
        var nativeMethod = prototype[method];
        prototype[method] = function () {
            if (!this._element.hasAttribute('data-toggle')) {
                return nativeMethod.apply(this, arguments);
            }
            var trigger = jQuery(this._element);
            var parent = jQuery(this._parent);
            var synchronize = function () {
                parent.toggleClass('open', open);
            };
            trigger.one(eventName, synchronize);
            try {
                return nativeMethod.apply(this, arguments);
            } finally {
                trigger.off(eventName, synchronize);
            }
        };
    }

    function install(jQuery) {
        if (installed.has(jQuery)) {
            return;
        }
        installed.add(jQuery);
        window.MauticInstallLegacyTransition(jQuery);
        window.MauticInstallLegacyAffix(jQuery);
        synchronizeDropdownState(jQuery, 'show', 'shown.bs.dropdown', true);
        synchronizeDropdownState(jQuery, '_completeHide', 'hidden.bs.dropdown', false);
        var tabChildren = window.bootstrap.Tab.prototype._getChildren;
        window.bootstrap.Tab.prototype._getChildren = function () {
            var children = tabChildren.call(this);
            if (this._element.hasAttribute('data-toggle')) {
                return children.filter(function (child) {
                    return !child.matches('li') || !child.querySelector('[data-toggle="tab"], [data-toggle="pill"]');
                });
            }
            return children;
        };
        jQuery(document).on('shown.bs.collapse hidden.bs.collapse shown.bs.modal hidden.bs.modal', function (event) {
            jQuery(event.target).toggleClass('in', 'shown' === event.type);
        });
        jQuery(document).on('shown.bs.tab', function (event) {
            jQuery(event.relatedTarget).parent('li').removeClass('active');
            jQuery(event.target).parent('li').addClass('active');
        });
        ['modal', 'collapse', 'tab'].forEach(function (pluginName) {
            var BootstrapConstructor = window.bootstrap[pluginName.charAt(0).toUpperCase() + pluginName.slice(1)];
            var nativeShow = BootstrapConstructor.prototype.show;
            BootstrapConstructor.prototype.show = function () {
                if ('collapse' !== pluginName) {
                    window.MauticBootstrapCompatibility.exposeLegacyInstance(jQuery, pluginName, BootstrapConstructor, this._element);
                }
                if ('tab' === pluginName && showLegacyFadedTab(jQuery, this._element)) {
                    return;
                }
                var result = nativeShow.apply(this, arguments);
                if ('collapse' === pluginName) {
                    window.MauticBootstrapCompatibility.exposeLegacyInstance(jQuery, pluginName, BootstrapConstructor, this._element);
                }
                return result;
            };
        });
        ['tooltip', 'popover'].forEach(function (pluginName) {
            var BootstrapConstructor = window.bootstrap['popover' === pluginName ? 'Popover' : 'Tooltip'];
            var nativeShow = BootstrapConstructor.prototype.show;
            BootstrapConstructor.prototype.show = function () {
                if (this._config.mauticLegacyInput) {
                    window.MauticBootstrapCompatibility.exposeLegacyInstance(jQuery, pluginName, BootstrapConstructor, this._element);
                }
                return nativeShow.apply(this, arguments);
            };
            jQuery(document).on('inserted.bs.' + pluginName + ' shown.bs.' + pluginName + ' hide.bs.' + pluginName, function (event) {
                var instance = BootstrapConstructor.getInstance(event.target);
                if (instance && instance.tip) {
                    var tip = adaptTip(instance, pluginName);
                    jQuery(tip).toggleClass('in', 'shown' === event.type);
                }
            });
        });
        jQuery(document).on('click.mautic.legacyRemote', '[data-toggle="modal"]', function () {
            var trigger = jQuery(this);
            var href = trigger.attr('href');
            var target = trigger.attr('data-target') || href && href.replace(/.*(?=#[^\s]+$)/, '');
            if (target && /^#/.test(target)) {
                jQuery(target).each(function () {
                    loadRemote(jQuery, this, {remote: trigger.data('remote') || href && !/#/.test(href) && href});
                });
            }
        });
        jQuery(window).on('load.mautic.legacyScrollspy', function () {
            jQuery('[data-spy="scroll"]').scrollspy();
        });
        jQuery(document).on('click.mautic.legacyButtons', '[data-toggle="buttons"] .btn', function (event) {
            jQuery(this).button('toggle');
            if (!jQuery(event.target).is('input[type="radio"], input[type="checkbox"]')) {
                event.preventDefault();
                jQuery(this).find('input:visible,button:visible').first().trigger('focus');
            }
        });
    }

    window.MauticBootstrapLegacyExceptions = Object.freeze({
        install: install,
        scrollspy: scrollspy,
        adaptTip: adaptTip,
        prepareMarkup: prepareMarkup,
        hideTip: hideTip,
        destroyTip: destroyTip,
        buttonState: buttonState,
        buttonGroup: buttonGroup,
        loadRemote: loadRemote,
        translateTemplate: translateTemplate
    });
})(window);

(function (window, document) {
    'use strict';

    var pluginMap = {
        alert: 'Alert',
        button: 'Button',
        carousel: 'Carousel',
        collapse: 'Collapse',
        dropdown: 'Dropdown',
        modal: 'Modal',
        offcanvas: 'Offcanvas',
        popover: 'Popover',
        scrollspy: 'ScrollSpy',
        tab: 'Tab',
        toast: 'Toast',
        tooltip: 'Tooltip'
    };

    var dataAttributes = [
        'animation',
        'backdrop',
        'boundary',
        'container',
        'content',
        'custom-class',
        'delay',
        'dismiss',
        'display',
        'fallback-placements',
        'html',
        'interval',
        'keyboard',
        'offset',
        'parent',
        'pause',
        'placement',
        'popper-config',
        'reference',
        'ride',
        'sanitize',
        'selector',
        'show',
        'slide',
        'slide-to',
        'spy',
        'target',
        'template',
        'title',
        'toggle',
        'touch',
        'trigger',
        'viewport',
        'wrap'
    ];

    var selector = dataAttributes
        .map(function (name) {
            return '[data-' + name + ']';
        })
        .concat(['[data-original-title]'])
        .join(',');

    var mirroredAttributes = new WeakMap();

    function mirrorAttribute(element, legacyName, bootstrapName) {
        var owned = mirroredAttributes.get(element) || {};
        var legacyValue = element.getAttribute(legacyName);
        var currentValue = element.getAttribute(bootstrapName);

        if (null === currentValue || owned[bootstrapName] === currentValue) {
            if (null === legacyValue) {
                element.removeAttribute(bootstrapName);
                delete owned[bootstrapName];
            } else {
                element.setAttribute(bootstrapName, legacyValue);
                owned[bootstrapName] = legacyValue;
            }
            mirroredAttributes.set(element, owned);
        }
    }

    function mirrorParentAttribute(trigger) {
        var parent = trigger.getAttribute('data-parent');
        var targetSelector = trigger.getAttribute('data-target') || trigger.getAttribute('href');

        if (null === targetSelector) {
            return;
        }

        targetSelector = targetSelector.replace(/.*(?=#[^\\s]*$)/, '');
        try {
            document.querySelectorAll(targetSelector).forEach(function (target) {
                var owned = mirroredAttributes.get(target) || {};
                var current = target.getAttribute('data-bs-parent');

                if (null === parent) {
                    if (owned['data-bs-parent'] === current) {
                        target.removeAttribute('data-bs-parent');
                        delete owned['data-bs-parent'];
                    }
                } else if (null === current || owned['data-bs-parent'] === current) {
                    target.setAttribute('data-bs-parent', parent);
                    owned['data-bs-parent'] = parent;
                }
                mirroredAttributes.set(target, owned);
            });
        } catch (error) {
            return;
        }
    }

    const bootstrapToggleValues = ['collapse', 'dropdown', 'modal', 'popover', 'tab', 'tooltip'];

    function mirrorBootstrapAttributes(element) {
        const legacyToggle = element.getAttribute('data-toggle');
        const isBootstrapToggle = bootstrapToggleValues.indexOf(legacyToggle) !== -1;

        dataAttributes.forEach(function (name) {
            if ('toggle' === name && !isBootstrapToggle) {
                const owned = mirroredAttributes.get(element) || {};
                if (owned['data-bs-toggle'] !== undefined && owned['data-bs-toggle'] === element.getAttribute('data-bs-toggle')) {
                    element.removeAttribute('data-bs-toggle');
                    delete owned['data-bs-toggle'];
                    mirroredAttributes.set(element, owned);
                }
                return;
            }
            if ('target' === name && !isBootstrapToggle) {
                return;
            }
            mirrorAttribute(element, 'data-' + name, 'data-bs-' + name);
        });
        if ('collapse' === legacyToggle) {
            mirrorParentAttribute(element);
        }
        if (element.hasAttribute('data-template')) {
            var pluginName = element.getAttribute('data-toggle');
            if ('tooltip' === pluginName || 'popover' === pluginName) {
                var template = window.MauticBootstrapLegacyExceptions.translateTemplate(element.getAttribute('data-template'), pluginName);
                var owned = mirroredAttributes.get(element);
                if (owned && owned['data-bs-template'] === element.getAttribute('data-bs-template')) {
                    element.setAttribute('data-bs-template', template);
                    owned['data-bs-template'] = template;
                }
            }
        }
        if (!element.hasAttribute('data-title')) {
            mirrorAttribute(element, 'data-original-title', 'data-bs-title');
        }
    }

    function mirrorLegacyMarkup(container) {
        if (!container || Node.ELEMENT_NODE !== container.nodeType && Node.DOCUMENT_NODE !== container.nodeType) {
            return;
        }

        if (Node.ELEMENT_NODE === container.nodeType && container.matches(selector)) {
            mirrorBootstrapAttributes(container);
        }

        container.querySelectorAll(selector).forEach(mirrorBootstrapAttributes);
    }

    function normalizeMethod(method) {
        return 'destroy' === method ? 'dispose' : method;
    }

    function normalizeOptions(pluginName, options, element) {
        if ('object' !== typeof options || null === options) {
            return options;
        }

        var normalized = Object.assign({}, options);
        if ('tooltip' === pluginName || 'popover' === pluginName) {
            normalized.mauticLegacyInput = true;
        }

        if (('tooltip' === pluginName || 'popover' === pluginName) && normalized.whiteList) {
            normalized.allowList = normalized.whiteList;
            delete normalized.whiteList;
        }

        if (('tooltip' === pluginName || 'popover' === pluginName) && normalized.template) {
            normalized.template = window.MauticBootstrapLegacyExceptions.translateTemplate(normalized.template, pluginName);
        }

        if (('tooltip' === pluginName || 'popover' === pluginName) && 'string' === typeof normalized.placement && /^auto(?:\s|$)/.test(normalized.placement)) {
            normalized.placement = normalized.placement.replace(/^auto\s*/, '') || 'top';
            normalized.fallbackPlacements = ['top', 'right', 'bottom', 'left'];
        }

        if (('tooltip' === pluginName || 'popover' === pluginName) && undefined !== normalized.viewport) {
            if ('function' === typeof normalized.viewport) {
                var collection = (window.mQuery || window.jQuery)(element);
                normalized.viewport = normalized.viewport.call({$element: collection, options: normalized}, collection);
            }
            if (undefined === normalized.boundary) {
                normalized.boundary = normalized.viewport;
                if (normalized.viewport && normalized.viewport.jquery) {
                    normalized.boundary = normalized.viewport[0] === window ? 'window' : normalized.viewport[0];
                }
                if (false === normalized.viewport) {
                    normalized.boundary = 'clippingParents';
                    normalized.popperConfig = {modifiers: [{name: 'preventOverflow', enabled: false}, {name: 'flip', enabled: false}]};
                }
                if (normalized.viewport && 'object' === typeof normalized.viewport && normalized.viewport.selector) {
                    normalized.boundary = document.querySelector(normalized.viewport.selector) || 'clippingParents';
                    var padding = normalized.viewport.padding || 0;
                    var popperConfig = normalized.popperConfig;
                    normalized.popperConfig = function (configuration) {
                        var custom = 'function' === typeof popperConfig ? popperConfig(configuration) : popperConfig;
                        var merged = Object.assign({}, configuration, custom || {});
                        merged.modifiers = (merged.modifiers || []).concat([{name: 'preventOverflow', options: {padding: padding}}]);
                        return merged;
                    };
                }
            }
            delete normalized.viewport;
        }

        if ('modal' === pluginName) {
            delete normalized.show;
            delete normalized.remote;
        }

        return normalized;
    }


    function exposeLegacyInstance(jQuery, pluginName, BootstrapConstructor, element) {
        var key = 'bs.' + pluginName;
        var instance = BootstrapConstructor.getInstance(element);

        if (!instance) {
            jQuery(element).removeData(key);
            return;
        }

        var current = jQuery(element).data(key);
        if (current && current.native === instance) {
            return;
        }

        var facade = {
            native: instance,
            options: instance._config
        };

        ['show', 'hide', 'toggle', 'enable', 'disable', 'toggleEnabled', 'handleUpdate', 'next', 'prev', 'pause', 'cycle', 'to', 'refresh'].forEach(function (method) {
            if ('function' === typeof instance[method]) {
                facade[method] = instance[method].bind(instance);
            }
        });
        facade.$element = jQuery(element);

        if ('tooltip' === pluginName || 'popover' === pluginName) {
            facade.tip = function () {
                return jQuery(window.MauticBootstrapLegacyExceptions.adaptTip(instance, pluginName));
            };
            facade.inState = instance._activeTrigger;
            facade.hide = function (callback) {
                window.MauticBootstrapLegacyExceptions.hideTip(jQuery, jQuery(element), pluginName, instance, callback);
                return facade;
            };
            facade.destroy = function () {
                window.MauticBootstrapLegacyExceptions.destroyTip(jQuery, jQuery(element), pluginName, BootstrapConstructor);
            };
            facade.fixTitle = function () {
                routeFixTitle(jQuery, BootstrapConstructor, jQuery(element));
            };
        }

        if ('modal' === pluginName) {
            facade.hide = function () {
                hideLegacyModal(jQuery, instance);
            };
            Object.defineProperty(facade, 'isShown', {
                get: function () {
                    return instance._isShown;
                }
            });
        }

        jQuery(element).data(key, facade);
    }

    function hideLegacyModal(jQuery, instance) {
        if (instance._isShown && instance._isTransitioning) {
            jQuery(instance._element).one('shown.bs.modal', function () {
                instance.hide();
            });
        } else {
            instance.hide();
        }
    }

    function routeFixTitle(jQuery, BootstrapConstructor, collection) {
        collection.each(function () {
            var title = this.getAttribute('title') || this.getAttribute('data-original-title');
            var instance = BootstrapConstructor.getInstance(this);

            if (null !== title) {
                this.setAttribute('data-bs-title', title);
                this.setAttribute('data-bs-original-title', title);
            }

            if (instance) {
                if (title) {
                    instance._config.title = title;
                }
                instance._fixTitle();
            }
        });

        return collection;
    }

    function createJQueryRouter(jQuery, pluginName, BootstrapConstructor) {
        var nativeInterface = BootstrapConstructor.jQueryInterface;
        var previousInterface = jQuery.fn[pluginName];
        if (!BootstrapConstructor.DEFAULTS) {
            BootstrapConstructor.DEFAULTS = Object.assign({}, BootstrapConstructor.Default);
            if ('modal' === pluginName) {
                BootstrapConstructor.DEFAULTS.show = true;
            }
            if ('button' === pluginName) {
                BootstrapConstructor.DEFAULTS.loadingText = 'loading...';
            }
            if ('scrollspy' === pluginName) {
                BootstrapConstructor.DEFAULTS.offset = 10;
            }
            if ('tooltip' === pluginName || 'popover' === pluginName) {
                BootstrapConstructor.DEFAULTS.whiteList = BootstrapConstructor.Default.allowList;
            }
        }
        var originalDefaults = Object.assign({}, BootstrapConstructor.DEFAULTS);

        var router = function (option) {
            var args = Array.prototype.slice.call(arguments, 1);
            var normalizedMethod = normalizeMethod(option);

            mirrorLegacyMarkup(document);
            window.MauticBootstrapLegacyExceptions.prepareMarkup(document);

            if ('scrollspy' === pluginName) {
                return window.MauticBootstrapLegacyExceptions.scrollspy(jQuery, this, option, BootstrapConstructor);
            }

            if ('button' === pluginName && 'string' === typeof option && 'toggle' !== option && 'dispose' !== normalizedMethod) {
                return window.MauticBootstrapLegacyExceptions.buttonState(jQuery, this, option);
            }

            if ('button' === pluginName && 'object' === typeof option) {
                return window.MauticBootstrapLegacyExceptions.buttonState(jQuery, this, undefined, option);
            }
            if ('button' === pluginName && undefined === option) {
                return window.MauticBootstrapLegacyExceptions.buttonState(jQuery, this);
            }

            if ('button' === pluginName && 'toggle' === option) {
                return this.each(function () {
                    var nativeButton = BootstrapConstructor.getOrCreateInstance(this);
                    exposeLegacyInstance(jQuery, pluginName, BootstrapConstructor, this);
                    if (!window.MauticBootstrapLegacyExceptions.buttonGroup(jQuery, this, nativeButton)) {
                        nativeButton.toggle();
                    }
                    exposeLegacyInstance(jQuery, pluginName, BootstrapConstructor, this);
                });
            }

            if ('modal' === pluginName) {
                this.each(function () {
                    window.MauticBootstrapLegacyExceptions.loadRemote(jQuery, this, 'object' === typeof option ? option : null);
                });
            }

            if (('tooltip' === pluginName || 'popover' === pluginName) && 'fixTitle' === normalizedMethod) {
                return routeFixTitle(jQuery, BootstrapConstructor, this);
            }

            if (('tooltip' === pluginName || 'popover' === pluginName) && 'destroy' === option) {
                return window.MauticBootstrapLegacyExceptions.destroyTip(jQuery, this, pluginName, BootstrapConstructor);
            }

            return this.each(function () {
                var elementOptions = Object.assign({}, jQuery(this).data());
                if ('tooltip' === pluginName || 'popover' === pluginName) {
                    delete elementOptions.sanitize;
                    delete elementOptions.sanitizeFn;
                    delete elementOptions.whiteList;
                    delete elementOptions.allowList;
                }
                var defaultOverrides = {};
                Object.keys(BootstrapConstructor.DEFAULTS).forEach(function (name) {
                    if (BootstrapConstructor.DEFAULTS[name] !== originalDefaults[name]) {
                        defaultOverrides[name] = BootstrapConstructor.DEFAULTS[name];
                    }
                });
                var legacyOptions = Object.assign({}, defaultOverrides, elementOptions, option && 'object' === typeof option ? option : {});
                var shouldShowModal = 'modal' === pluginName
                    && (undefined === option || option && 'object' === typeof option) && false !== legacyOptions.show;
                var normalizedOption = normalizeOptions(pluginName, 'string' === typeof normalizedMethod || 'number' === typeof normalizedMethod ? normalizedMethod : legacyOptions, this);
                var instance = BootstrapConstructor.getInstance(this);
                if (!instance && ('tooltip' === pluginName || 'popover' === pluginName) && 'hide' === option) {
                    return;
                }
                var toggleCollapse = !instance && 'collapse' === pluginName && 'string' !== typeof option && false !== legacyOptions.toggle;
                var constructorOptions = normalizedOption && 'object' === typeof normalizedOption ? normalizedOption : normalizeOptions(pluginName, legacyOptions, this);
                if ('collapse' === pluginName) {
                    constructorOptions.toggle = false;
                }
                instance = instance || BootstrapConstructor.getOrCreateInstance(this, constructorOptions);
                exposeLegacyInstance(jQuery, pluginName, BootstrapConstructor, this);
                if ('modal' === pluginName && 'hide' === normalizedMethod) {
                    hideLegacyModal(jQuery, instance);
                } else {
                    nativeInterface.apply(jQuery(this), [normalizedOption].concat(args));
                }
                if (toggleCollapse) {
                    instance.toggle();
                }

                if (shouldShowModal && instance && !instance._isShown) {
                    instance.show(args[0]);
                }

                exposeLegacyInstance(jQuery, pluginName, BootstrapConstructor, this);
            });
        };

        Object.keys(nativeInterface).forEach(function (key) {
            router[key] = nativeInterface[key];
        });
        router.Constructor = BootstrapConstructor;
        router.mauticBootstrapCompatibility = true;
        router.noConflict = function () {
            jQuery.fn[pluginName] = previousInterface;
            return router;
        };

        return router;
    }

    function bridgeJQueryPlugins() {
        var jQuery = window.mQuery || window.jQuery;

        if (!jQuery || !window.bootstrap) {
            return;
        }

        window.MauticBootstrapLegacyExceptions.install(jQuery);

        Object.keys(pluginMap).forEach(function (pluginName) {
            var BootstrapConstructor = window.bootstrap[pluginMap[pluginName]];

            if (BootstrapConstructor && 'function' === typeof BootstrapConstructor.jQueryInterface && !(jQuery.fn[pluginName] && jQuery.fn[pluginName].mauticBootstrapCompatibility)) {
                jQuery.fn[pluginName] = createJQueryRouter(jQuery, pluginName, BootstrapConstructor);
            }
        });
    }

    function prepareLegacyTabs(container) {
        if (!window.bootstrap || !window.bootstrap.Tab || !container.querySelectorAll) {
            return;
        }

        container.querySelectorAll(
            'li.active > [data-toggle="tab"], li.active > [data-toggle="pill"], li.active > [data-toggle="list"]'
        ).forEach(function (trigger) {
            if (!trigger.matches('.active')) {
                window.bootstrap.Tab.getOrCreateInstance(trigger).show();
            }
        });
    }

    function observeLegacyAttributes() {
        if (!window.MutationObserver) {
            return;
        }

        new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                if ('attributes' === mutation.type) {
                    mirrorBootstrapAttributes(mutation.target);
                    return;
                }

                mutation.addedNodes.forEach(function (node) {
                    if (Node.ELEMENT_NODE === node.nodeType) {
                        mirrorLegacyMarkup(node);
                        window.MauticBootstrapLegacyExceptions.prepareMarkup(node);
                        prepareLegacyTabs(node);
                    }
                });
            });
        }).observe(document.documentElement, {
            attributeFilter: dataAttributes.map(function (name) {
                return 'data-' + name;
            }).concat(['data-original-title']),
            attributes: true,
            childList: true,
            subtree: true
        });
    }

    window.MauticBootstrapCompatibility = Object.freeze({
        bridgeJQueryPlugins: bridgeJQueryPlugins,
        exposeLegacyInstance: exposeLegacyInstance,
        mirrorLegacyMarkup: mirrorLegacyMarkup,
        plugins: Object.freeze(Object.keys(pluginMap)),
        ready: Promise.resolve(),
        version: '5.3.8-router'
    });

    mirrorLegacyMarkup(document);
    bridgeJQueryPlugins();
    window.MauticBootstrapLegacyExceptions.prepareMarkup(document);
    prepareLegacyTabs(document);
    window.addEventListener('click', function (event) {
        if (event.target instanceof Element && event.target.closest(selector)) {
            mirrorLegacyMarkup(document);
            window.MauticBootstrapLegacyExceptions.prepareMarkup(document);
        }
    }, true);
    observeLegacyAttributes();

    document.addEventListener('DOMContentLoaded', function () {
        mirrorLegacyMarkup(document);
        bridgeJQueryPlugins();
        window.MauticBootstrapLegacyExceptions.prepareMarkup(document);
        prepareLegacyTabs(document);
    });
})(window, document);
