function base64_encode(data) {
  //  discuss at: http://phpjs.org/functions/base64_encode/
  // original by: Tyler Akins (http://rumkin.com)
  // improved by: Bayron Guevara
  // improved by: Thunder.m
  // improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
  // improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
  // improved by: Rafał Kukawski (http://kukawski.pl)
  // bugfixed by: Pellentesque Malesuada
  //   example 1: base64_encode('Kevin van Zonneveld');
  //   returns 1: 'S2V2aW4gdmFuIFpvbm5ldmVsZA=='
  //   example 2: base64_encode('a');
  //   returns 2: 'YQ=='
  //   example 3: base64_encode('✓ à la mode');
  //   returns 3: '4pyTIMOgIGxhIG1vZGU='

  var b64 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=';
  var o1, o2, o3, h1, h2, h3, h4, bits, i = 0,
    ac = 0,
    enc = '',
    tmp_arr = [];

  if (!data) {
    return data;
  }

  data = unescape(encodeURIComponent(data));

  do {
    // pack three octets into four hexets
    o1 = data.charCodeAt(i++);
    o2 = data.charCodeAt(i++);
    o3 = data.charCodeAt(i++);

    bits = o1 << 16 | o2 << 8 | o3;

    h1 = bits >> 18 & 0x3f;
    h2 = bits >> 12 & 0x3f;
    h3 = bits >> 6 & 0x3f;
    h4 = bits & 0x3f;

    // use hexets to index into b64, and append result to encoded string
    tmp_arr[ac++] = b64.charAt(h1) + b64.charAt(h2) + b64.charAt(h3) + b64.charAt(h4);
  } while (i < data.length);

  enc = tmp_arr.join('');

  var r = data.length % 3;

  return (r ? enc.slice(0, r - 3) : enc) + '==='.slice(r || 3);
}


function trim(str, charlist) {
    var whitespace, l = 0,
        i = 0;
    str += '';

    if (!charlist) {
        // default list
        whitespace =
            ' \n\r\t\f\x0b\xa0\u2000\u2001\u2002\u2003\u2004\u2005\u2006\u2007\u2008\u2009\u200a\u200b\u2028\u2029\u3000';
    } else {
        // preg_quote custom list
        charlist += '';
        whitespace = charlist.replace(/([\[\]\(\)\.\?\/\*\{\}\+\$\^\:])/g, '$1');
    }

    l = str.length;
    for (i = 0; i < l; i++) {
        if (whitespace.indexOf(str.charAt(i)) === -1) {
            str = str.substring(i);
            break;
        }
    }

    l = str.length;
    for (i = l - 1; i >= 0; i--) {
        if (whitespace.indexOf(str.charAt(i)) === -1) {
            str = str.substring(0, i + 1);
            break;
        }
    }

    return whitespace.indexOf(str.charAt(0)) === -1 ? str : '';
}

function parse_str(str) {
    var array = {};
    var strArr = String(str)
            .replace(/^&/, '')
            .replace(/&$/, '')
            .split('&'),
        sal = strArr.length,
        i, j, ct, p, lastObj, obj, lastIter, undef, chr, tmp, key, value,
        postLeftBracketPos, keys, keysLen,
        fixStr = function(str) {
            return decodeURIComponent(str.replace(/\+/g, '%20'));
        };



    for (i = 0; i < sal; i++) {
        tmp = strArr[i].split('=');
        key = fixStr(tmp[0]);
        value = (tmp.length < 2) ? '' : fixStr(tmp[1]);

        while (key.charAt(0) === ' ') {
            key = key.slice(1);
        }
        if (key.indexOf('\x00') > -1) {
            key = key.slice(0, key.indexOf('\x00'));
        }
        if (key && key.charAt(0) !== '[') {
            keys = [];
            postLeftBracketPos = 0;
            for (j = 0; j < key.length; j++) {
                if (key.charAt(j) === '[' && !postLeftBracketPos) {
                    postLeftBracketPos = j + 1;
                } else if (key.charAt(j) === ']') {
                    if (postLeftBracketPos) {
                        if (!keys.length) {
                            keys.push(key.slice(0, postLeftBracketPos - 1));
                        }
                        keys.push(key.substr(postLeftBracketPos, j - postLeftBracketPos));
                        postLeftBracketPos = 0;
                        if (key.charAt(j + 1) !== '[') {
                            break;
                        }
                    }
                }
            }
            if (!keys.length) {
                keys = [key];
            }
            for (j = 0; j < keys[0].length; j++) {
                chr = keys[0].charAt(j);
                if (chr === ' ' || chr === '.' || chr === '[') {
                    keys[0] = keys[0].substr(0, j) + '_' + keys[0].substr(j + 1);
                }
                if (chr === '[') {
                    break;
                }
            }

            obj = array;
            for (j = 0, keysLen = keys.length; j < keysLen; j++) {
                key = keys[j].replace(/^['"]/, '')
                    .replace(/['"]$/, '');
                lastIter = j !== keys.length - 1;
                lastObj = obj;
                if ((key !== '' && key !== ' ') || j === 0) {
                    if (obj[key] === undef) {
                        obj[key] = {};
                    }
                    obj = obj[key];
                } else { // To insert new dimension
                    ct = -1;
                    for (p in obj) {
                        if (obj.hasOwnProperty(p)) {
                            if (+p > ct && p.match(/^\d+$/g)) {
                                ct = +p;
                            }
                        }
                    }
                    key = ct + 1;
                }
            }
            lastObj[key] = value;
        }
    }

    return array;
}

// Modal Plugin
var MauticModal = {
    closeButton: null,
    modal: null,
    overlay: null,
    defaults: {
        className: 'fade-and-drop',
        closeButton: true,
        content: "",
        maxWidth: '600px',
        minWidth: '280px',
        overlay: true
    },

    close: function() {
        var _ = this;
        this.modal.className = this.modal.className.replace(" mauticForm-open", "");
        this.overlay.className = this.overlay.className.replace(" mauticForm-open", "");
        this.modal.addEventListener(this.transitionSelect(), function() {
          _.modal.parentNode.removeChild(_.modal);
        });
        this.overlay.addEventListener(this.transitionSelect(), function() {
          if(_.overlay.parentNode) _.overlay.parentNode.removeChild(_.overlay);
        });
    },

    open: function() {
        if (arguments[0] && typeof arguments[0] === "object") {
          this.options = this.extendDefaults(this.defaults, arguments[0]);
        }
        this.buildOut();
        this.initializeEvents();
        window.getComputedStyle(this.modal).height;
        this.modal.className = this.modal.className + (this.modal.offsetHeight > window.innerHeight ? " mauticForm-open mauticForm-anchored" : " mauticForm-open");
        this.overlay.className = this.overlay.className + " mauticForm-open";
    },

    buildOut: function() {
        var content, contentHolder, docFrag;
        /*
         * If content is an HTML string, append the HTML string.
         * If content is a domNode, append its content.
         */
        if (typeof this.options.content === "string") {
          content = this.options.content;
        } else {
          content = this.options.content.innerHTML;
        }
        // Create a DocumentFragment to build with
        docFrag = document.createDocumentFragment();

        // Create modal element
        this.modal = document.createElement("div");
        this.modal.className = "mauticForm-modal " + this.options.className;
        this.modal.style.minWidth = this.options.minWidth;
        this.modal.style.maxWidth = this.options.maxWidth;

        // If closeButton option is true, add a close button
        if (this.options.closeButton === true) {
          this.closeButton = document.createElement("button");
          this.closeButton.className = "mauticForm-close close-button";
          this.closeButton.innerHTML = "&times;";
          this.modal.appendChild(this.closeButton);
        }

        // If overlay is true, add one
        if (this.options.overlay === true) {
          this.overlay = document.createElement("div");
          this.overlay.className = "mauticForm-overlay " + this.options.className;
          docFrag.appendChild(this.overlay);
        }

        // Create content area and append to modal
        contentHolder = document.createElement("div");
        contentHolder.className = "mauticForm-content";
        contentHolder.innerHTML = content;
        this.modal.appendChild(contentHolder);

        // Append modal to DocumentFragment
        docFrag.appendChild(this.modal);

        // Append DocumentFragment to body
        document.body.appendChild(docFrag);
    },

    extendDefaults: function(source, properties) {
        var property;
        for (property in properties) {
            if (properties.hasOwnProperty(property)) {
                source[property] = properties[property];
            }
        }
        return source;
    },

    initializeEvents: function() {
        if (this.closeButton) {
            this.closeButton.addEventListener('click', this.close.bind(this));
        }
        if (this.overlay) {
            this.overlay.addEventListener('click', this.close.bind(this));
        }
    },

    transitionSelect: function() {
        var el = document.createElement("div");
        if (el.style.WebkitTransition) return "webkitTransitionEnd";
        if (el.style.OTransition) return "oTransitionEnd";
        return 'transitionend';
    }
};

// Core SDK
var MauticSDK = {
    initialise: function (base_url, configuration) {
        this.requester = document.location.pathname;
        this.config = configuration;
        this.clickEvents = [];

        if (this.config.debug) {
            console.log('SDK initialized');
            this.startTime = performance.now();
        }

        if (typeof(this.config.mautic_base_url) == 'undefined') {
            var tmpBaseUrl = base_url.split('/');
            tmpBaseUrl.pop();
            tmpBaseUrl.pop();
            tmpBaseUrl.pop();
            tmpBaseUrl.push('');
            this.config.mautic_base_url = tmpBaseUrl.join('/');
            if (this.config.debug) {
                console.log('Automatic setup mautic_base_url as: ' + this.config.mautic_base_url);
            }
        }

        this.injectModal();
        this.startPlugins();
    },

    startPlugins: function(){
        document.addEventListener("DOMContentLoaded", function(e){
            if (MauticSDK.config.debug) {
                console.log('DOM is ready');
            }
            MauticSDK.initFormPlugin();
        });
    },

    /**
     * Inject Modal into head
     */
    injectModal: function(){
        if (typeof(this.config.modal_css) != 'undefined') {
            if (this.config.debug) {
                console.log('custom modal css style');
            }
            return;
        }

        modalJSStyle = document.createElement('link');
        modalJSStyle.rel = "stylesheet"
        modalJSStyle.type = "text/css"
        modalJSStyle.href = MauticSDK.config.mautic_base_url + 'media/css/modal';
        modalJSStyle.href += + (this.config.debug) ? '' : '.min';
        modalJSStyle.href += '.css';
        document.head.appendChild(modalJSStyle);
    },

    debugTimer: function(){
        MauticSDK.endTime = performance.now();
        MauticSDK.runtime = MauticSDK.endTime - MauticSDK.startTime;
        console.log('Execution time: ' + MauticSDK.runtime+ ' ms.');
    },

    bindClickEvents: function(){
        if (MauticSDK.config.debug) {
            console.log('binding modal click events');
        }
        for(var index in MauticSDK.clickEvents) {
            var current = MauticSDK.clickEvents[index];
            document.querySelector(current.data.element).addEventListener("click", function(){
                if (MauticSDK.config.debug) {
                    console.log('add event to '+current.data.element);
                }

                MauticSDK.openModal(current);
            });
        }

        if (MauticSDK.config.debug) {
            MauticSDK.debugTimer();
        }
    },

    /**
     * Display Modal
     */
    openModal: function(current){
        iframe = this.createIframe(current);

        MauticModal.open({
            content: iframe.outerHTML,
            maxWidth: typeof(current['width']) != 'undefined' ? current['width'] : '800px',
            maxHeight: typeof(current['height']) != 'undefined' ? current['height'] : '600px'
        });
    },

    getFormLink: function(options){
        console.log('---debug---');
        console.log(options.params);
        var link = this.config.mautic_base_url;
        link += (typeof(this.config.debug) != 'undefined') ? 'index_dev.php' : 'index.php';
        link += '/p/form/?' + options.params;

        return link;
    },

    createIframe: function(options)
    {
        var iframe = document.createElement('iframe');
        //iframe config properties
        iframe.frameBorder = typeof(options.data['border']) != 'undefined' ? parseInt(options.data['border']) : '0' ;
        iframe.width = typeof(options.data['width']) != 'undefined' ? options.data['width'] : '600px' ;
        iframe.height = typeof(options.data['height']) != 'undefined' ? options.data['height'] : '400px' ;
        if (typeof(options.data['class']) == 'string') {
            iframe.className = options.data['class'];
        }

        iframe.src = this.getFormLink(options);

        return iframe;
    },

    /**
     * Initialize Form Plugin {mauticform}
     */
    initFormPlugin: function(){
        var re = /{mauticform([^}]+)}/g, text;
        while(text = re.exec(document.body.innerHTML)) {
            var replaceText = text[0];
            var replaceArgs = {data: {}, params: ''};
            var tmpParams = [];
            trim(text[1]).split(/\s+/).forEach(function (strAttribute){
                var tmpAtr = strAttribute.split('=');
                replaceArgs.data[tmpAtr[0]] = tmpAtr[1];
                tmpParams.push(tmpAtr[0]+'='+encodeURIComponent(tmpAtr[1]));
            });
            tmpParams.push('html=1');
            replaceArgs.params = tmpParams.join('&');
            replaceArgs.data['replace'] = replaceText;

            replaceArgs.data['style'] = typeof(replaceArgs.data['style']) == 'undefined' ? 'embed' : replaceArgs.data['style'] ;
            if (MauticSDK.config.debug) {
                console.log(replaceArgs.data['style']+' Mautic Form: '+replaceText);
                console.log(replaceArgs);
            }

            //display form accoding with style
            switch (replaceArgs.data['style'])
            {
                case 'modal':
                    document.body.innerHTML = document.body.innerHTML.replace(replaceText,'');
                    MauticSDK.clickEvents.push(replaceArgs);
                    break;
                case 'embed':
                default:
                    var iframe = this.createIframe(replaceArgs);
                    document.body.innerHTML = document.body.innerHTML.replace(replaceText,iframe.outerHTML);
                    break;
            }
        }

        MauticSDK.bindClickEvents();
    }
}

// read all scripts
var scripts = document.getElementsByTagName('script');
for (var i = 0, len = scripts.length; i < len; i++) {
    var src = scripts[i].getAttribute("src");
    //search for mautic-form file
    if (!src || (src.indexOf('mautic-form.js') == -1))
        continue;
    var srcParts = src.split("?");
    var url = srcParts[0];
    var args = srcParts[1];
    if (!args)
        continue;
    // start SDK
    MauticSDK.initialise(url,parse_str(args));
}