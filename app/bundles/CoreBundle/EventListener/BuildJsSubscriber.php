<?php

namespace Mautic\CoreBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\BuildJsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class BuildJsSubscriber implements EventSubscriberInterface
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            CoreEvents::BUILD_MAUTIC_JS => ['onBuildJs', 1000],
        ];
    }

    /**
     * Adds the MauticJS definition and core
     * JS functions for use in Bundles. This
     * must retain top priority of 1000.
     */
    public function onBuildJs(BuildJsEvent $event)
    {
        $js = <<<'JS'
// Polyfill for CustomEvent to support IE 9+
(function () {
    if ( typeof window.CustomEvent === "function" ) return false;
    function CustomEvent ( event, params ) {
        params = params || { bubbles: false, cancelable: false, detail: undefined };
        var evt = document.createEvent( 'CustomEvent' );
        evt.initCustomEvent( event, params.bubbles, params.cancelable, params.detail );
        return evt;
    }
    CustomEvent.prototype = window.Event.prototype;
    window.CustomEvent = CustomEvent;
})();

var MauticJS = MauticJS || {};

MauticJS.serialize = function(obj) {
    if ('string' == typeof obj) {
        return obj;
    }

    return Object.keys(obj).map(function(key) {
        return encodeURIComponent(key) + '=' + encodeURIComponent(obj[key]);
    }).join('&');
};

MauticJS.documentReady = function(f) {
    /in/.test(document.readyState) ? setTimeout(function(){MauticJS.documentReady(f)}, 9) : f();
};

MauticJS.iterateCollection = function(collection) {
    return function(f) {
        for (var i = 0; collection[i]; i++) {
            f(collection[i], i);
        }
    };
};

MauticJS.log = function() {
    var log = {};
    log.history = log.history || [];

    log.history.push(arguments);

    if (window.console) {
        console.log(Array.prototype.slice.call(arguments));
    }
};

MauticJS.setCookie = function(name, value) {
    document.cookie = name+"="+value+"; path=/; secure";
};

MauticJS.createCORSRequest = function(method, url) {
    var xhr = new XMLHttpRequest();
    
    method = method.toUpperCase();
    
    if ("withCredentials" in xhr) {
        xhr.open(method, url, true);
    } else if (typeof XDomainRequest != "undefined") {
        xhr = new XDomainRequest();
        xhr.open(method, url);
    }
    
    return xhr;
};
MauticJS.CORSRequestsAllowed = true;
MauticJS.makeCORSRequest = function(method, url, data, callbackSuccess, callbackError) {
    // Check for stored contact in localStorage
    data = MauticJS.appendTrackedContact(data);
    
    var query = MauticJS.serialize(data);
    if (method.toUpperCase() === 'GET') {
        url = url + '?' + query;
        var query = '';
    }
    
    var xhr = MauticJS.createCORSRequest(method, url);
    var response;
    
    callbackSuccess = callbackSuccess || function(response, xhr) { };
    callbackError = callbackError || function(response, xhr) { };

    if (!xhr) {
        MauticJS.log('MauticJS.debug: Could not create an XMLHttpRequest instance.');
        return false;
    }

    if (!MauticJS.CORSRequestsAllowed) {
        callbackError({}, xhr);
        
        return false;
    }
    
    xhr.onreadystatechange = function (e) {
        if (xhr.readyState === XMLHttpRequest.DONE) {
            response = MauticJS.parseTextToJSON(xhr.responseText);
            if (xhr.status === 200) {
                callbackSuccess(response, xhr);
            } else {
                callbackError(response, xhr);
               
                if (xhr.status === XMLHttpRequest.UNSENT) {
                    // Don't bother with further attempts
                    MauticJS.CORSRequestsAllowed = false;
                }
            }
        }
    };
   
    if (typeof xhr.setRequestHeader !== "undefined"){
        if (method.toUpperCase() === 'POST') {
            xhr.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
        }
    
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.withCredentials = true;
    }
    xhr.send(query);
};

MauticJS.parseTextToJSON = function(maybeJSON) {
    var response;

    try {
        // handle JSON data being returned
        response = JSON.parse(maybeJSON);
    } catch (error) {
        response = maybeJSON;
    }

    return response;
};

MauticJS.insertScript = function (scriptUrl) {
    var scriptsInHead = document.getElementsByTagName('head')[0].getElementsByTagName('script');
    var lastScript    = scriptsInHead[scriptsInHead.length - 1];
    var scriptTag     = document.createElement('script');
    scriptTag.async   = 1;
    scriptTag.src     = scriptUrl;
    
    if (lastScript) {
        lastScript.parentNode.insertBefore(scriptTag, lastScript);
    } else {
        document.getElementsByTagName('head')[0].appendChild(scriptTag);
    }
};

MauticJS.insertStyle = function (styleUrl) {
    var linksInHead = document.getElementsByTagName('head')[0].getElementsByTagName('link');
    var lastLink    = linksInHead[linksInHead.length - 1];
    var linkTag     = document.createElement('link');
    linkTag.rel     = "stylesheet";
    linkTag.type    = "text/css";
    linkTag.href    = styleUrl;
    
    if (lastLink) {
        lastLink.parentNode.insertBefore(linkTag, lastLink.nextSibling);
    } else {
        document.getElementsByTagName('head')[0].appendChild(linkTag);
    }
};

MauticJS.guid = function () {
    function s4() {
        return Math.floor((1 + Math.random()) * 0x10000).toString(16).substring(1);
    }
    
    return s4() + s4() + '-' + s4() + '-' + s4() + '-' + s4() + '-' + s4() + s4() + s4();
};

MauticJS.dispatchEvent = function(name, detail) {
    var event = new CustomEvent(name, {detail: detail});
    document.dispatchEvent(event);
};

function s4() {
  return Math.floor((1 + Math.random()) * 0x10000)
    .toString(16)
    .substring(1);
}

MauticJS.mtcSet = false;
MauticJS.appendTrackedContact = function(data) {
    if (window.localStorage) {
        if (mtcId  = localStorage.getItem('mtc_id')) {
            data['mautic_device_id'] = localStorage.getItem('mautic_device_id');
        }              
    }
    
    return data;
};

MauticJS.getTrackedContact = function () {
    if (MauticJS.mtcSet) {
        // Already set
        return;
    }
    
    MauticJS.makeCORSRequest('GET', MauticJS.contactIdUrl, {}, function(response, xhr) {
        MauticJS.setTrackedContact(response);
    });
};

MauticJS.setTrackedContact = function(response) {
    if (response.id) {
        MauticJS.setCookie('mtc_id', response.id);
        MauticJS.setCookie('mtc_sid', response.sid);
        MauticJS.setCookie('mautic_device_id', response.device_id);
        MauticJS.mtcSet = true;
            
        // Set the id in local storage in case cookies are only allowed for sites visited and Mautic is on a different domain
        // than the current page
        try {
            localStorage.setItem('mtc_id', response.id);
            localStorage.setItem('mtc_sid', response.sid);
            localStorage.setItem('mautic_device_id', response.device_id);
        } catch (e) {
            console.warn('Browser does not allow storing in local storage');
        }
    }
};

// Register events that should happen after the first event is delivered
MauticJS.postEventDeliveryQueue = [];
MauticJS.firstDeliveryMade      = false;
MauticJS.onFirstEventDelivery = function(f) {
    MauticJS.postEventDeliveryQueue.push(f);
};
MauticJS.preEventDeliveryQueue = [];
MauticJS.beforeFirstDeliveryMade = false;
MauticJS.beforeFirstEventDelivery = function(f) {
    MauticJS.preEventDeliveryQueue.push(f);
};
document.addEventListener('mauticPageEventDelivered', function(e) {
    var detail   = e.detail;
    var isImage = detail.image;
    if (isImage && !MauticJS.mtcSet) {
        MauticJS.getTrackedContact();
    } else if (detail.response && detail.response.id) {
        MauticJS.setTrackedContact(detail.response);
    }
    
    if (!isImage && typeof detail.event[3] === 'object' && typeof detail.event[3].onload === 'function') {
       // Execute onload since this is ignored if not an image
       detail.event[3].onload(detail)       
    }
    
    if (!MauticJS.firstDeliveryMade) {
        MauticJS.firstDeliveryMade = true;
        for (var i = 0; i < MauticJS.postEventDeliveryQueue.length; i++) {
            if (typeof MauticJS.postEventDeliveryQueue[i] === 'function') {
                MauticJS.postEventDeliveryQueue[i](detail);
            }
            delete MauticJS.postEventDeliveryQueue[i];
        }
    }
});

/**
* Check if a DOM tracking pixel is present
*/
MauticJS.checkForTrackingPixel = function() {
    if (document.readyState !== 'complete') {
        // Periodically call self until the DOM is completely loaded
        setTimeout(function(){MauticJS.checkForTrackingPixel()}, 9)
    } else {
        // Only fetch once a tracking pixel has been loaded
        var maxChecks  = 3000; // Keep it from indefinitely checking in case the pixel was never embedded
        var checkPixel = setInterval(function() {
            if (maxChecks > 0 && !MauticJS.isPixelLoaded(true)) {
                // Try again
                maxChecks--;
                return;
            }
    
            clearInterval(checkPixel);
            
            if (maxChecks > 0) {
                // DOM image was found 
                var params = {}, hash;
                var hashes = MauticJS.trackingPixel.src.slice(MauticJS.trackingPixel.src.indexOf('?') + 1).split('&');

                for(var i = 0; i < hashes.length; i++) {
                    hash = hashes[i].split('=');
                    params[hash[0]] = hash[1];
                }

                MauticJS.dispatchEvent('mauticPageEventDelivered', {'event': ['send', 'pageview', params], 'params': params, 'image': true});
            }
        }, 1);
    }
}
MauticJS.checkForTrackingPixel();

MauticJS.isPixelLoaded = function(domOnly) {
    if (typeof domOnly == 'undefined') {
        domOnly = false;
    }
    
    if (typeof MauticJS.trackingPixel === 'undefined') {
        // Check the DOM for the tracking pixel
        MauticJS.trackingPixel = null;
        var imgs = Array.prototype.slice.apply(document.getElementsByTagName('img'));
        for (var i = 0; i < imgs.length; i++) {
            if (imgs[i].src.indexOf('mtracking.gif') !== -1) {
                MauticJS.trackingPixel = imgs[i];
                break;
            }
        }
    } else if (domOnly) {
        return false;
    }

    if (MauticJS.trackingPixel && MauticJS.trackingPixel.complete && MauticJS.trackingPixel.naturalWidth !== 0) {
        // All the browsers should be covered by this - image is loaded
        return true;
    }

    return false;
};

if (typeof window[window.MauticTrackingObject] !== 'undefined') {
    MauticJS.input = window[window.MauticTrackingObject];
    if (typeof MauticJS.input.q === 'undefined') {
        // In case mt() is not executed right away
        MauticJS.input.q = [];
    }
    MauticJS.inputQueue = MauticJS.input.q;

    // Dispatch the queue event when an event is added to the queue
    if (!MauticJS.inputQueue.hasOwnProperty('push')) {
        Object.defineProperty(MauticJS.inputQueue, 'push', {
            configurable: false,
            enumerable: false,
            writable: false,
            value: function () {
                for (var i = 0, n = this.length, l = arguments.length; i < l; i++, n++) {
                    MauticJS.dispatchEvent('eventAddedToMauticQueue', arguments[i]);
                }
                return n;
            }
        });
    }

    MauticJS.getInput = function(task, type) {
        var matches = [];
        if (typeof MauticJS.inputQueue !== 'undefined' && MauticJS.inputQueue.length) {
            for (var i in MauticJS.inputQueue) {
                if (MauticJS.inputQueue[i][0] === task && MauticJS.inputQueue[i][1] === type) {
                    matches.push(MauticJS.inputQueue[i]);
                }
            }
        }
        
        return matches; 
    }
}

MauticJS.ensureEventContext = function(event, context0, context1) { 
    return (typeof(event.detail) !== 'undefined'
        && event.detail[0] === context0
        && event.detail[1] === context1);
};
JS;
        $event->appendJs($js, 'Mautic Core');
    }
}
