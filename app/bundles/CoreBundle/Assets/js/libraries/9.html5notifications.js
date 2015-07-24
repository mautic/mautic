/**
 * Copyright 2012 Tsvetan Tsvetkov
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * Author: Tsvetan Tsvetkov (tsekach@gmail.com)
 */
(function (win) {
    /*
     Safari native methods required for Notifications do NOT run in strict mode.
     */
    //"use strict";
    var PERMISSION_DEFAULT = "default",
        PERMISSION_GRANTED = "granted",
        PERMISSION_DENIED = "denied",
        PERMISSION = [PERMISSION_GRANTED, PERMISSION_DEFAULT, PERMISSION_DENIED],
        defaultSetting = {
            pageVisibility: false,
            autoClose: 0
        },
        empty = {},
        emptyString = "",
        isSupported = (function () {
            var isSupported = false;
            /*
             * Use try {} catch() {} because the check for IE may throws an exception
             * if the code is run on browser that is not Safar/Chrome/IE or
             * Firefox with html5notifications plugin.
             *
             * Also, we canNOT detect if msIsSiteMode method exists, as it is
             * a method of host object. In IE check for existing method of host
             * object returns undefined. So, we try to run it - if it runs
             * successfully - then it is IE9+, if not - an exceptions is thrown.
             */
            try {
                isSupported = !!(/* Safari, Chrome */win.Notification || /* Chrome & ff-html5notifications plugin */win.webkitNotifications || /* Firefox Mobile */navigator.mozNotification || /* IE9+ */(win.external && win.external.msIsSiteMode() !== undefined));
            } catch (e) {}
            return isSupported;
        }()),
        ieVerification = Math.floor((Math.random() * 10) + 1),
        isFunction = function (value) { return (value && (value).constructor === Function); },
        isString = function (value) {return (value && (value).constructor === String); },
        isObject = function (value) {return (value && (value).constructor === Object); },
        /**
         * Dojo Mixin
         */
        mixin = function (target, source) {
            var name, s;
            for (name in source) {
                s = source[name];
                if (!(name in target) || (target[name] !== s && (!(name in empty) || empty[name] !== s))) {
                    target[name] = s;
                }
            }
            return target; // Object
        },
        noop = function () {},
        settings = defaultSetting;
    function getNotification(title, options) {
        var notification;
        if (win.Notification) { /* Safari 6, Chrome (23+) */
            notification =  new win.Notification(title, {
                /* The notification's icon - For Chrome in Windows, Linux & Chrome OS */
                icon: isString(options.icon) ? options.icon : options.icon.x32,
                /* The notification’s subtitle. */
                body: options.body || emptyString,
                /*
                 The notification’s unique identifier.
                 This prevents duplicate entries from appearing if the user has multiple instances of your website open at once.
                 */
                tag: options.tag || emptyString
            });
        } else if (win.webkitNotifications) { /* FF with html5Notifications plugin installed */
            notification = win.webkitNotifications.createNotification(options.icon, title, options.body);
            notification.show();
        } else if (navigator.mozNotification) { /* Firefox Mobile */
            notification = navigator.mozNotification.createNotification(title, options.body, options.icon);
            notification.show();
        } else if (win.external && win.external.msIsSiteMode()) { /* IE9+ */
            //Clear any previous notifications
            win.external.msSiteModeClearIconOverlay();
            win.external.msSiteModeSetIconOverlay((isString(options.icon) ? options.icon : options.icon.x16), title);
            win.external.msSiteModeActivate();
            notification = {
                "ieVerification": ieVerification + 1
            };
        }
        return notification;
    }
    function getWrapper(notification) {
        return {
            close: function () {
                if (notification) {
                    if (notification.close) {
                        //http://code.google.com/p/ff-html5notifications/issues/detail?id=58
                        notification.close();
                    }
                    else if (notification.cancel) {
                        notification.cancel();
                    } else if (win.external && win.external.msIsSiteMode()) {
                        if (notification.ieVerification === ieVerification) {
                            win.external.msSiteModeClearIconOverlay();
                        }
                    }
                }
            }
        };
    }
    function requestPermission(callback) {
        if (!isSupported) { return; }
        var callbackFunction = isFunction(callback) ? callback : noop;
        if (win.webkitNotifications && win.webkitNotifications.checkPermission) {
            /*
             * Chrome 23 supports win.Notification.requestPermission, but it
             * breaks the browsers, so use the old-webkit-prefixed
             * win.webkitNotifications.checkPermission instead.
             *
             * Firefox with html5notifications plugin supports this method
             * for requesting permissions.
             */
            win.webkitNotifications.requestPermission(callbackFunction);
        } else if (win.Notification && win.Notification.requestPermission) {
            win.Notification.requestPermission(callbackFunction);
        }
    }
    function permissionLevel() {
        var permission;
        if (!isSupported) { return; }
        if (win.Notification && win.Notification.permissionLevel) {
            //Safari 6
            permission = win.Notification.permissionLevel();
        } else if (win.webkitNotifications && win.webkitNotifications.checkPermission) {
            //Chrome & Firefox with html5-notifications plugin installed
            permission = PERMISSION[win.webkitNotifications.checkPermission()];
        } else if (win.Notification && win.Notification.permission) {
            // Firefox 23+
            permission = win.Notification.permission;
        } else if (navigator.mozNotification) {
            //Firefox Mobile
            permission = PERMISSION_GRANTED;
        } else if (win.external && (win.external.msIsSiteMode() !== undefined)) { /* keep last */
            //IE9+
            permission = win.external.msIsSiteMode() ? PERMISSION_GRANTED : PERMISSION_DEFAULT;
        }
        return permission;
    }
    /**
     *
     */
    function config(params) {
        if (params && isObject(params)) {
            mixin(settings, params);
        }
        return settings;
    }

    function createNotification(title, options) {
        var notification,
            notificationWrapper;
        /*
         Return undefined if notifications are not supported.

         Return undefined if no permissions for displaying notifications.

         Title and icons are required. Return undefined if not set.
         */
        if (isSupported && isString(title) && (options && (isString(options.icon) || isObject(options.icon))) && (permissionLevel() === PERMISSION_GRANTED)) {
            notification = getNotification(title, options);
        }
        notificationWrapper = getWrapper(notification);
        //Auto-close notification
        if (settings.autoClose && notification && !notification.ieVerification && notification.addEventListener) {
            notification.addEventListener("show", function () {
                var notification = notificationWrapper;
                win.setTimeout(function () {
                    notification.close();
                }, settings.autoClose);
            });
        }
        return notificationWrapper;
    }
    win.notify = {
        PERMISSION_DEFAULT: PERMISSION_DEFAULT,
        PERMISSION_GRANTED: PERMISSION_GRANTED,
        PERMISSION_DENIED: PERMISSION_DENIED,
        isSupported: isSupported,
        config: config,
        createNotification: createNotification,
        permissionLevel: permissionLevel,
        requestPermission: requestPermission
    };
    if (isFunction(Object.seal)) {
        Object.seal(win.notify);
    }
}(window));