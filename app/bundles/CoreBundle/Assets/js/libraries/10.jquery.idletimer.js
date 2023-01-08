// idle.js (c) Alexios Chouchoulas 2009
// Released under the terms of the GNU Public License version 2.0 (or later).

// Namespaced, scoped, and extra options added by Alan Hartless 2014

var IdleTimer = (function($) {
    var activityHelper = {
        _idleTimeout: 30000,    // 30 seconds
        _awayTimeout: 600000,   // 10 minutes
        _idleNow: false,
        _idleTimestamp: null,
        _idleTimer: null,
        _awayNow: false,
        _awayTimestamp: null,
        _awayTimer: null,
        _onIdleCallback: null,
        _onAwayCallback: null,
        _onBackCallback: null,
        _debug: false,
        _lastActive: new Date().getTime(),
        _sessionKeepAliveInterval: null,
        _keepSessionAlive: null,

        _makeIdle: function () {
            var t = new Date().getTime();
            if (t < this._idleTimestamp) {
                if (this._debug) console.log('Not idle yet. Idle in ' + (this._idleTimestamp - t + 50));
                this._idleTimer = setTimeout(function() {activityHelper._makeIdle()}, this._idleTimestamp - t + 50);
                return;
            }
            if (this._debug) console.log('** IDLE **');
            this._idleNow = true;

            try {
                if (this._onIdleCallback) this._onIdleCallback();
            } catch (err) {
            }
        },

        _makeAway: function () {
            var t = new Date().getTime();
            if (t < this._awayTimestamp) {
                if (this._debug) console.log('Not away yet. Away in ' + (this._awayTimestamp - t + 50));
                this._awayTimer = setTimeout(function() {activityHelper._makeAway()}, this._awayTimestamp - t + 50);
                return;
            }
            if (this._debug) console.log('** AWAY **');
            this._awayNow = true;

            if (this._keepSessionAlive){
                this._sessionKeepAliveInterval = setInterval(function(){
                    activityHelper._keepSessionAlive();
                }, this._awayTimeout);
            }

            try {
                if (this._onAwayCallback) this._onAwayCallback();
            } catch (err) {
            }
        },
        
        _active: function (timer) {
            var t = new Date().getTime();

            this._lastActive    = t;
            this._idleTimestamp = t + this._idleTimeout;
            this._awayTimestamp = t + this._awayTimeout;
            if (this._debug) console.log('not idle.');

            if (this._idleNow) {
                timer.setIdleTimeout(this._idleTimeout);
            }

            if (this._awayNow) {
                clearTimeout(this._sessionKeepAliveInterval);
                timer.setAwayTimeout(this._awayTimeout);
            }

            try {
                if (this._idleNow || this._awayNow) {
                    if (this._debug) console.log('** BACK **');
                    if (this._onBackCallback) this._onBackCallback(this._idleNow, this._awayNow);
                }
            } catch (err) {}

            this._idleNow = false;
            this._awayNow = false;
        },

        _onStatusChange: function(url, status) {
            $.ajax({
                url: url,
                type: 'post',
                dataType: 'json',
                data: 'status=' + status
            });
        }
    };

    return {
        getLastActive: function() {
            var t = new Date().getTime();
            return Math.ceil((t - activityHelper._lastActive) / 1000);
        },

        isIdle: function() {
            return activityHelper._idleNow;
        },

        isAway: function() {
            return activityHelper._awayNow;
        },

        setIdleTimeout: function (ms) {
            activityHelper._idleTimeout = ms;
            activityHelper._idleTimestamp = new Date().getTime() + ms;
            if (activityHelper._idleTimer != null) {
                clearTimeout(activityHelper._idleTimer);
            }
            activityHelper._idleTimer = setTimeout(function() {activityHelper._makeIdle()}, ms + 50);
            if (activityHelper._debug) console.log('idle in ' + ms + ', tid = ' + activityHelper._idleTimer);
        },

        setAwayTimeout: function (ms) {
            activityHelper._awayTimeout = ms;
            activityHelper._awayTimestamp = new Date().getTime() + ms;
            if (activityHelper._awayTimer != null) {
                clearTimeout(activityHelper._awayTimer);
            }
            activityHelper._awayTimer = setTimeout(function() {activityHelper._makeAway()}, ms + 50);
            if (activityHelper._debug) console.log('away in ' + ms);
        },

        init: function (options) {
            if (options) {
                if (options.debug) {
                    activityHelper._debug = options.debug;

                    console.log('IdleTimer initiated');
                    console.log(options);
                }

                if (options.statusChangeUrl) {
                    activityHelper._onIdleCallback = function() {
                        activityHelper._onStatusChange(options.statusChangeUrl, 'idle');
                    };

                    activityHelper._onAwayCallback = function() {
                        activityHelper._onStatusChange(options.statusChangeUrl, 'away');
                    };

                    activityHelper._onBackCallback = function() {
                        activityHelper._onStatusChange(options.statusChangeUrl, 'back');
                    };

                    activityHelper._keepSessionAlive = function() {
                        activityHelper._onStatusChange(options.statusChangeUrl, 'keepalive');
                    };
                }

                if (options.idleTimeout) {
                    this.setIdleTimeout(options.idleTimeout);
                }

                if (options.awayTimeout) {
                    this.setAwayTimeout(options.awayTimeout);
                }

                if (options.idle) {
                    this.setOnIdleCallback(options.idle);
                }

                if (options.away) {
                    this.setOnAwayCallback(options.away);
                }

                if (options.back) {
                    this.setOnBackCallback(options.back);
                }
            }

            var doc = $(document);
            var me = this;
            doc.on('mousemove', function() {activityHelper._active(me)});
            try {
                doc.on('mouseenter', function() {activityHelper._active(me)});
            } catch (err) {}
            try {
                doc.on('scroll', function() {activityHelper._active(me)});
            } catch (err) {}
            try {
                doc.on( 'keydown', function() {activityHelper._active(me)});
            } catch (err) {}
            try {
                doc.on('click', function() {activityHelper._active(me)});
            } catch (err) {}
            try {
                doc.on('dblclick', function() {activityHelper._active(me)});
            } catch (err) {}
        }
    };
})(jQuery);
