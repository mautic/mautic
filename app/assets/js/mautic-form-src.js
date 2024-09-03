/**
 * Autocomplete library.
 * 
 * @see https://github.com/TarekRaafat/autoComplete.js/blob/master/dist/autoComplete.min.js
 * @version 10.2.8
 */
var t,e;t=this,e=function(){"use strict";function t(t,e){var n=Object.keys(t);if(Object.getOwnPropertySymbols){var r=Object.getOwnPropertySymbols(t);e&&(r=r.filter((function(e){return Object.getOwnPropertyDescriptor(t,e).enumerable}))),n.push.apply(n,r)}return n}function e(e){for(var n=1;n<arguments.length;n++){var i=null!=arguments[n]?arguments[n]:{};n%2?t(Object(i),!0).forEach((function(t){r(e,t,i[t])})):Object.getOwnPropertyDescriptors?Object.defineProperties(e,Object.getOwnPropertyDescriptors(i)):t(Object(i)).forEach((function(t){Object.defineProperty(e,t,Object.getOwnPropertyDescriptor(i,t))}))}return e}function n(t){return n="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},n(t)}function r(t,e,n){return e in t?Object.defineProperty(t,e,{value:n,enumerable:!0,configurable:!0,writable:!0}):t[e]=n,t}function i(t){return function(t){if(Array.isArray(t))return s(t)}(t)||function(t){if("undefined"!=typeof Symbol&&null!=t[Symbol.iterator]||null!=t["@@iterator"])return Array.from(t)}(t)||o(t)||function(){throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.")}()}function o(t,e){if(t){if("string"==typeof t)return s(t,e);var n=Object.prototype.toString.call(t).slice(8,-1);return"Object"===n&&t.constructor&&(n=t.constructor.name),"Map"===n||"Set"===n?Array.from(t):"Arguments"===n||/^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)?s(t,e):void 0}}function s(t,e){(null==e||e>t.length)&&(e=t.length);for(var n=0,r=new Array(e);n<e;n++)r[n]=t[n];return r}var u=function(t){return"string"==typeof t?document.querySelector(t):t()},a=function(t,e){var n="string"==typeof t?document.createElement(t):t;for(var r in e){var i=e[r];if("inside"===r)i.append(n);else if("dest"===r)u(i[0]).insertAdjacentElement(i[1],n);else if("around"===r){var o=i;o.parentNode.insertBefore(n,o),n.append(o),null!=o.getAttribute("autofocus")&&o.focus()}else r in n?n[r]=i:n.setAttribute(r,i)}return n},c=function(t,e){return t=String(t).toLowerCase(),e?t.normalize("NFD").replace(/[\u0300-\u036f]/g,"").normalize("NFC"):t},l=function(t,n){return a("mark",e({innerHTML:t},"string"==typeof n&&{class:n})).outerHTML},f=function(t,e){e.input.dispatchEvent(new CustomEvent(t,{bubbles:!0,detail:e.feedback,cancelable:!0}))},p=function(t,e,n){var r=n||{},i=r.mode,o=r.diacritics,s=r.highlight,u=c(e,o);if(e=String(e),t=c(t,o),"loose"===i){var a=(t=t.replace(/ /g,"")).length,f=0,p=Array.from(e).map((function(e,n){return f<a&&u[n]===t[f]&&(e=s?l(e,s):e,f++),e})).join("");if(f===a)return p}else{var d=u.indexOf(t);if(~d)return t=e.substring(d,d+t.length),d=s?e.replace(t,l(t,s)):e}},d=function(t,e){return new Promise((function(n,r){var i;return(i=t.data).cache&&i.store?n():new Promise((function(t,n){return"function"==typeof i.src?new Promise((function(t,n){return"AsyncFunction"===i.src.constructor.name?i.src(e).then(t,n):t(i.src(e))})).then(t,n):t(i.src)})).then((function(e){try{return t.feedback=i.store=e,f("response",t),n()}catch(t){return r(t)}}),r)}))},h=function(t,e){var n=e.data,r=e.searchEngine,i=[];n.store.forEach((function(s,u){var a=function(n){var o=n?s[n]:s,u="function"==typeof r?r(t,o):p(t,o,{mode:r,diacritics:e.diacritics,highlight:e.resultItem.highlight});if(u){var a={match:u,value:s};n&&(a.key=n),i.push(a)}};if(n.keys){var c,l=function(t,e){var n="undefined"!=typeof Symbol&&t[Symbol.iterator]||t["@@iterator"];if(!n){if(Array.isArray(t)||(n=o(t))||e&&t&&"number"==typeof t.length){n&&(t=n);var r=0,i=function(){};return{s:i,n:function(){return r>=t.length?{done:!0}:{done:!1,value:t[r++]}},e:function(t){throw t},f:i}}throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.")}var s,u=!0,a=!1;return{s:function(){n=n.call(t)},n:function(){var t=n.next();return u=t.done,t},e:function(t){a=!0,s=t},f:function(){try{u||null==n.return||n.return()}finally{if(a)throw s}}}}(n.keys);try{for(l.s();!(c=l.n()).done;)a(c.value)}catch(t){l.e(t)}finally{l.f()}}else a()})),n.filter&&(i=n.filter(i));var s=i.slice(0,e.resultsList.maxResults);e.feedback={query:t,matches:i,results:s},f("results",e)},m="aria-expanded",b="aria-activedescendant",y="aria-selected",v=function(t,n){t.feedback.selection=e({index:n},t.feedback.results[n])},g=function(t){t.isOpen||((t.wrapper||t.input).setAttribute(m,!0),t.list.removeAttribute("hidden"),t.isOpen=!0,f("open",t))},w=function(t){t.isOpen&&((t.wrapper||t.input).setAttribute(m,!1),t.input.setAttribute(b,""),t.list.setAttribute("hidden",""),t.isOpen=!1,f("close",t))},O=function(t,e){var n=e.resultItem,r=e.list.getElementsByTagName(n.tag),o=!!n.selected&&n.selected.split(" ");if(e.isOpen&&r.length){var s,u,a=e.cursor;t>=r.length&&(t=0),t<0&&(t=r.length-1),e.cursor=t,a>-1&&(r[a].removeAttribute(y),o&&(u=r[a].classList).remove.apply(u,i(o))),r[t].setAttribute(y,!0),o&&(s=r[t].classList).add.apply(s,i(o)),e.input.setAttribute(b,r[e.cursor].id),e.list.scrollTop=r[t].offsetTop-e.list.clientHeight+r[t].clientHeight+5,e.feedback.cursor=e.cursor,v(e,t),f("navigate",e)}},A=function(t){O(t.cursor+1,t)},k=function(t){O(t.cursor-1,t)},L=function(t,e,n){(n=n>=0?n:t.cursor)<0||(t.feedback.event=e,v(t,n),f("selection",t),w(t))};function j(t,n){var r=this;return new Promise((function(i,o){var s,u;return s=n||((u=t.input)instanceof HTMLInputElement||u instanceof HTMLTextAreaElement?u.value:u.innerHTML),function(t,e,n){return e?e(t):t.length>=n}(s=t.query?t.query(s):s,t.trigger,t.threshold)?d(t,s).then((function(n){try{return t.feedback instanceof Error?i():(h(s,t),t.resultsList&&function(t){var n=t.resultsList,r=t.list,i=t.resultItem,o=t.feedback,s=o.matches,u=o.results;if(t.cursor=-1,r.innerHTML="",s.length||n.noResults){var c=new DocumentFragment;u.forEach((function(t,n){var r=a(i.tag,e({id:"".concat(i.id,"_").concat(n),role:"option",innerHTML:t.match,inside:c},i.class&&{class:i.class}));i.element&&i.element(r,t)})),r.append(c),n.element&&n.element(r,o),g(t)}else w(t)}(t),c.call(r))}catch(t){return o(t)}}),o):(w(t),c.call(r));function c(){return i()}}))}var S=function(t,e){for(var n in t)for(var r in t[n])e(n,r)},T=function(t){var n,r,i,o=t.events,s=(n=function(){return j(t)},r=t.debounce,function(){clearTimeout(i),i=setTimeout((function(){return n()}),r)}),u=t.events=e({input:e({},o&&o.input)},t.resultsList&&{list:o?e({},o.list):{}}),a={input:{input:function(){s()},keydown:function(e){!function(t,e){switch(t.keyCode){case 40:case 38:t.preventDefault(),40===t.keyCode?A(e):k(e);break;case 13:e.submit||t.preventDefault(),e.cursor>=0&&L(e,t);break;case 9:e.resultsList.tabSelect&&e.cursor>=0&&L(e,t);break;case 27:e.input.value="",w(e)}}(e,t)},blur:function(){w(t)}},list:{mousedown:function(t){t.preventDefault()},click:function(e){!function(t,e){var n=e.resultItem.tag.toUpperCase(),r=Array.from(e.list.querySelectorAll(n)),i=t.target.closest(n);i&&i.nodeName===n&&L(e,t,r.indexOf(i))}(e,t)}}};S(a,(function(e,n){(t.resultsList||"input"===n)&&(u[e][n]||(u[e][n]=a[e][n]))})),S(u,(function(e,n){t[e].addEventListener(n,u[e][n])}))};function E(t){var n=this;return new Promise((function(r,i){var o,s,u;if(o=t.placeHolder,u={role:"combobox","aria-owns":(s=t.resultsList).id,"aria-haspopup":!0,"aria-expanded":!1},a(t.input,e(e({"aria-controls":s.id,"aria-autocomplete":"both"},o&&{placeholder:o}),!t.wrapper&&e({},u))),t.wrapper&&(t.wrapper=a("div",e({around:t.input,class:t.name+"_wrapper"},u))),s&&(t.list=a(s.tag,e({dest:[s.destination,s.position],id:s.id,role:"listbox",hidden:"hidden"},s.class&&{class:s.class}))),T(t),t.data.cache)return d(t).then((function(t){try{return c.call(n)}catch(t){return i(t)}}),i);function c(){return f("init",t),r()}return c.call(n)}))}function x(t){var e=t.prototype;e.init=function(){E(this)},e.start=function(t){j(this,t)},e.unInit=function(){if(this.wrapper){var t=this.wrapper.parentNode;t.insertBefore(this.input,this.wrapper),t.removeChild(this.wrapper)}var e;S((e=this).events,(function(t,n){e[t].removeEventListener(n,e.events[t][n])}))},e.open=function(){g(this)},e.close=function(){w(this)},e.goTo=function(t){O(t,this)},e.next=function(){A(this)},e.previous=function(){k(this)},e.select=function(t){L(this,null,t)},e.search=function(t,e,n){return p(t,e,n)}}return function t(e){this.options=e,this.id=t.instances=(t.instances||0)+1,this.name="autoComplete",this.wrapper=1,this.threshold=1,this.debounce=0,this.resultsList={position:"afterend",tag:"ul",maxResults:5},this.resultItem={tag:"li"},function(t){var e=t.name,r=t.options,i=t.resultsList,o=t.resultItem;for(var s in r)if("object"===n(r[s]))for(var a in t[s]||(t[s]={}),r[s])t[s][a]=r[s][a];else t[s]=r[s];t.selector=t.selector||"#"+e,i.destination=i.destination||t.selector,i.id=i.id||e+"_list_"+t.id,o.id=o.id||e+"_result",t.input=u(t.selector)}(this),x.call(this,t),E(this)}},"object"==typeof exports&&"undefined"!=typeof module?module.exports=e():"function"==typeof define&&define.amd?define(e):(t="undefined"!=typeof globalThis?globalThis:t||self).autoComplete=e();

/**
 * Mautic form embed.
 */
(function( window ) {
    'use strict';
    function define_library(){
        //Core library
        var Core = {};
        //Mautic Modal
        var Modal = {
            closeButton: null,
            modal: null,
            overlay: null,
            defaults: {
                className: 'fade-and-drop',
                closeButton: true,
                content: "",
                width: '600px',
                height: '480px',
                overlay: true
            }
        };
        //Mautic Form
        var Form = {
            clickEvents: [],
            clonedNodes: {},
        };
        //Mautic Profiler
        var Profiler = {};

        //global configuration
        var config = {devmode: false, debug: false};

        Profiler.startTime = function() {
            this._startTime = performance.now();
        };

        Profiler.runTime = function() {
            this._endTime = performance.now();
            this._runtime = this._endTime - this._startTime;
            if (Core.debug()) console.log('Execution time: ' + this._runtime + ' ms.');
        };

        Form.initialize = function(){
            var re = /{mauticform([^}]+)}/g, text;
            while(text = re.exec(document.body.innerHTML)) {
                var replaceText = text[0];
                var replaceArgs = {data: {}, params: ''};
                var tmpParams = [];
                text[1].trim().split(/\s+/).forEach(function (strAttribute){
                    var tmpAtr = strAttribute.split('=');
                    replaceArgs.data[tmpAtr[0]] = tmpAtr[1];
                    if (tmpAtr[0] != 'id')
                        tmpParams.push(tmpAtr[0]+'='+encodeURIComponent(tmpAtr[1]));
                });
                tmpParams.push('html=1');
                replaceArgs.params = tmpParams.join('&');
                replaceArgs.data['replace'] = replaceText;

                replaceArgs.data['style'] = typeof(replaceArgs.data['style']) == 'undefined' ? 'embed' : replaceArgs.data['style'] ;
                if (Core.debug()) console.log(replaceArgs.data['style']+' Mautic Form: '+replaceText);

                //display form according with style
                switch (replaceArgs.data['style'])
                {
                    case 'modal':
                        document.body.innerHTML = document.body.innerHTML.replace(replaceText,'');
                        Form.clickEvents.push(replaceArgs);
                        break;
                    case 'embed':
                    default:
                        document.body.innerHTML = document.body.innerHTML.replace(replaceText,this.createIframe(replaceArgs).outerHTML);
                        break;
                }
            }

            this.bindClickEvents();
            Profiler.runTime();
            Form.initCompanyLookup();
        };

        Form.initCompanyLookup = function() {
            // Do not initialize if the company lookup field is not in the DOM.
            if (!document.querySelector('input[data-toggle=field-company-lookup]')) {
                return;
            }

            const autoCompleteJS = new autoComplete(
                {
                    selector: 'input[data-toggle=field-company-lookup]',
                    data: {
                        src: async (query) => {
                            try {
                                const source = await fetch(

                                    MauticDomain + '/form/company-lookup/autocomplete',
                                    {
                                        method: 'POST',
                                        body: JSON.stringify({
                                            search: query,
                                            formId: autoCompleteJS.input.closest('form').querySelector('input[name="mauticform[formId]"]').value
                                        })
                                    }
                                );
                                return await source.json();
                            } catch (error) {
                                return error;
                            }
                          },
                          keys: ['companyname']
                    },
                    resultItem: {
                        highlight: true,
                        tag: 'li',
                        element: (item, data) => {
                            const detailsWrapper = document.createElement('span');
                            const city = data.value.companycity ?? '';
                            const state = data.value.companystate ?? '';
                            const comma = city && state ? ', ' : '';
                            detailsWrapper.textContent = city + comma + state;
                            item.appendChild(detailsWrapper);
                        },
                    },
                    resultsList: {
                        element: (list, data) => {
                            if (!data.results.length) {
                                const message = document.createElement('li');
                                message.setAttribute('class', 'no-result');
                                message.innerHTML = '<span>Found No Results for "' + data.query + '"</span>';
                                list.prepend(message);
                            }
                        },
                        noResults: true,
                        maxResults: 50,
                    },
                    threshold: 3,
                    debounce: 300,
                    events: {
                        input: {
                            selection: (event) => {
                                const company = event.detail.selection.value;
                                autoCompleteJS.input.value = company.companyname;
                                document.getElementById(autoCompleteJS.input.getAttribute('data-set-id')).value = company.id;
                            }
                        }
                    }
                }
            );
        };

        Form.bindClickEvents = function() {
            if (Core.debug()) console.log('binding modal click events');
            for(var index in Form.clickEvents) {
                var current = Form.clickEvents[index];
                document.querySelector(current.data.element).setAttribute("_mautic_form_index", index);
                document.querySelector(current.data.element).addEventListener("click", function(){
                    if (Core.debug()) console.log('add event to '+current.data.element);
                    Form.openModal(Form.clickEvents[this.getAttribute('_mautic_form_index')]);
                });
            }
        };

        Form.openModal = function(options){
            Core.openModal({
                content: Form.createIframe(options, true).outerHTML,
                width: typeof(options.data['width']) != 'undefined' ? options.data['width'] : '600px',
                height: typeof(options.data['height']) != 'undefined' ? options.data['height'] : '480px'
            });
        };

        Form.getFormLink = function(options) {
            return Core.getMauticBaseUrl() + 'index.php/form/' + options.data['id'] + '?' + options.params;
        };

        Form.createIframe = function(options, embed) {
            var embed = (typeof(embed) == 'undefined') ? false : true ;
            var iframe = document.createElement('iframe');
            //iframe config properties
            iframe.frameBorder = typeof(options.data['border']) != 'undefined' ? parseInt(options.data['border']) : '0' ;
            iframe.width = (embed) ? '100%' : typeof(options.data['width']) != 'undefined' ? options.data['width'] : '600px' ;
            iframe.height = (embed) ? '100%' : typeof(options.data['height']) != 'undefined' ? options.data['height'] : '400px' ;
            iframe.className = (typeof(options.data['class']) == 'string') ? options.data['class'] : '' ;
            iframe.src = this.getFormLink(options);

            return iframe;
        };

        Form.customCallbackHandler = function(formId, event, data) {
            if (typeof MauticFormCallback !== 'undefined' &&
                typeof MauticFormCallback[formId] !== 'undefined' &&
                typeof MauticFormCallback[formId][event] == 'function'
            ) {
                if (typeof data == 'undefined') {
                    data = null;
                }

                return MauticFormCallback[formId][event](data);
            }

            return null;
        };

        Form.prepareForms = function() {
            if (Core.debug()) console.log('Preparing forms found on the page');

            var forms = document.getElementsByTagName('form');
            for (var i = 0, n = forms.length; i < n; i++) {
                var formId = forms[i].getAttribute('data-mautic-form');
                if (formId !== null) {
                    Form.prepareMessengerForm(formId);
                    Form.prepareValidation(formId);
                    Form.prepareShowOn(formId);
                    Form.preparePagination(formId);

                    Form.populateValuesWithGetParameters();
                }
            }
        };

        Form.prepareMessengerForm = function(formId) {
            var theForm = document.getElementById('mauticform_' + formId);

            // Check for an onsubmit attribute
            if (!theForm.getAttribute('onsubmit')) {
                theForm.onsubmit = function (event) {
                    event.preventDefault();

                    Core.validateForm(formId, true);
                }
            }

            // Check to see if the iframe exists
            if (!document.getElementById('mauticiframe_' + formId)) {
                // Likely an editor has stripped out the iframe so let's dynamically create it
                var ifrm = document.createElement("IFRAME");
                ifrm.style.display = "none";
                ifrm.style.margin = 0;
                ifrm.style.padding = 0;
                ifrm.style.border = "none";
                ifrm.style.width = 0;
                ifrm.style.heigh = 0;
                ifrm.setAttribute( 'id', 'mauticiframe_' + formId);
                ifrm.setAttribute('name', 'mauticiframe_' + formId);
                document.body.appendChild(ifrm);

                theForm.target = 'mauticiframe_' + formId;
            }

            if (!document.getElementById('mauticform_' + formId + '_messenger')) {
                var messengerInput = document.createElement('INPUT');
                messengerInput.type = 'hidden';
                messengerInput.setAttribute('name', 'mauticform[messenger]');
                messengerInput.setAttribute('id', 'mauticform_' + formId + '_messenger');
                messengerInput.value = 1;

                theForm.appendChild(messengerInput);
            }
        };

        Form.prepareValidation = function(formId) {
            if (typeof window.MauticFormValidations[formId] == 'undefined') {
                window.MauticFormValidations[formId] = {};

                var theForm = document.getElementById('mauticform_' + formId);

                // Find validations via data-attributes
                var validations = theForm.querySelectorAll('[data-validate]');
                [].forEach.call(validations, function (container) {
                    var alias = container.getAttribute('data-validate');
                    window.MauticFormValidations[formId][alias] = {
                        type: container.getAttribute('data-validation-type'),
                        name: alias,
                        multiple: container.getAttribute('data-validate-multiple'),
                    }
                });
            }
        };

        Form.prepareShowOn = function (formId) {
            var theForm = document.getElementById('mauticform_' + formId);
            var showOnDataAttribute = 'data-mautic-form-show-on';

            var parents = {};
            var showOn = theForm.querySelectorAll('['+showOnDataAttribute+']');
            [].forEach.call(showOn, function (container) {
                var condition = container.getAttribute(showOnDataAttribute);
                var returnArray = condition.split(':');

                var idOnChangeElem  = "mauticform_" + formId+"_"+returnArray[0];
                var elemToShow = container.getAttribute('id');
                var elemShowOnValues = (returnArray[1]).split('|');

                if(!parents[idOnChangeElem]){
                    parents[idOnChangeElem] = {};
                }

                parents[idOnChangeElem][elemToShow] = elemShowOnValues;

            });

            Object.keys(parents).forEach(function(key) {
                var containerElement = document.getElementById(key);

                Form.doShowOn(parents, key, Form.getSelectedValues(containerElement));

                containerElement.onchange = function (evt) {
                    var selectElement = evt.target;
                    Form.doShowOn(parents, key, Form.getSelectedValues(evt.currentTarget));
                }
            });
        };

        Form.doShowOn = function (parents, key, selectedValues) {

            Object.keys((parents[key])).forEach(function(key2) {
                Form.hideField(document.getElementById(key2));
            });

            Object.keys((parents[key])).forEach(function(key2) {
                [].forEach.call(selectedValues, function (selectedValue) {
                    
                    var el = document.getElementById(key2);
                    if (selectedValue) {
                        if (el.getAttribute('data-mautic-form-expr') == 'notIn') {
                            if (!(parents[key][key2]).includes(selectedValue)) {
                                Form.showField(el, selectedValue);
                            }
                        }
                        else if ((parents[key][key2]).includes(selectedValue) || ((parents[key][key2]).includes('*'))) {
                            Form.showField(el, selectedValue);
                        }
                    }
                })
            });
        };

        Form.hideField = function(element) {
            element.style.display = 'none';
            element.setAttribute('data-validate-disable', 1);
        }

        Form.showField = function(element, selectedValue) {
            element.style.display = 'block';
            element.removeAttribute('data-validate-disable');
            Form.filterOptGroups(element, selectedValue);
        }

        Form.getSelectedValues = function(containerElement) {
            if (containerElement.querySelectorAll('input[type=checkbox], input[type=radio]').length) {
                return Array.from(containerElement.querySelectorAll('input:checked'))
                    .map(option => option.value);

            }else if(containerElement.querySelectorAll('select').length){
                return Array.from(containerElement.querySelectorAll('option:checked'))
                    .map(option => option.value);
            }
        }

        Form.filterOptGroups = function(selectElement, optGroupValue) {
            // safari doesn't support hiding optgroup, so we need to restore the select before filtering
            selectElement.querySelectorAll('select').forEach(function (select) {
                if (typeof Form.clonedNodes[select.id] === 'undefined') {
                    Form.clonedNodes[select.id] = select.cloneNode(true);
                }

                select.innerHTML = Form.clonedNodes[select.id].innerHTML;
            });

            const matchingOptionGroups = selectElement.querySelectorAll('optgroup[label="' + optGroupValue + '"]');
            const notMatchingOptionGroups = selectElement.querySelectorAll('optgroup:not([label="' + optGroupValue + '"])');

            // hide if all option groups don't match
            if (!matchingOptionGroups.length && notMatchingOptionGroups.length) {
                Form.hideField(selectElement);

                return;
            }

            [].forEach.call(notMatchingOptionGroups, function (notMatchingOptionGroup) {
                notMatchingOptionGroup.remove();
            });
        };

        Form.preparePagination = function(formId) {
            var theForm        = document.getElementById('mauticform_'+formId);
            var pages          = theForm.querySelectorAll('[data-mautic-form-page]');
            var lastPageNumber = pages.length;

            [].forEach.call(pages, function (page) {
                var pageNumber       = parseInt(page.getAttribute('data-mautic-form-page'));
                var pageBreak        = theForm.querySelector('[data-mautic-form-pagebreak="'+pageNumber+'"]');

                if (pageNumber > 1) {
                    // Hide other pages by default
                    page.style.display = 'none';
                }

                if (pageBreak) {
                    var prevPageNumber    = pageNumber - 1;
                    var nextPageNumber    = pageNumber + 1;

                    var prevButton = pageBreak.querySelector('[data-mautic-form-pagebreak-button="prev"]');
                    var nextButton = pageBreak.querySelector('[data-mautic-form-pagebreak-button="next"]');

                    // Add button handlers
                    prevButton.onclick = function(formId, theForm, showPageNumber) {
                        return function() {
                            Form.customCallbackHandler(formId, 'onShowPreviousPage', showPageNumber);
                            Form.switchPage(theForm, showPageNumber);
                        }
                    } (formId, theForm, prevPageNumber);

                    nextButton.onclick = function(formId, theForm, hidePageNumber, showPageNumber) {
                        return function () {
                            // Validate fields first
                            var validations = theForm.querySelector('[data-mautic-form-page="' + hidePageNumber + '"]').querySelectorAll('[data-validate]');
                            var isValid = true;
                            [].forEach.call(validations, function (container) {
                                var fieldKey = container.getAttribute('data-validate');
                                if (!Core.getValidator(formId).validateField(theForm, fieldKey)) {
                                    isValid = false;
                                }
                            });
                            if (!isValid) {
                                return;
                            }

                            Form.customCallbackHandler(formId, 'onShowNextPage', showPageNumber);
                            Form.switchPage(theForm, showPageNumber);
                        }
                    } (formId, theForm, pageNumber, nextPageNumber);

                    if (1 === pageNumber) {
                        prevButton.setAttribute('disabled', 'disabled');
                        pageBreak.style.display = 'block';
                    } else {
                        if (lastPageNumber === pageNumber) {
                            var theSubmit = theForm.querySelector('button[type="submit"]').parentNode;
                            nextButton.parentNode.appendChild(theSubmit);
                            nextButton.remove();
                        }
                    }
                }
            });
        };

        Form.switchPage = function(theForm, showPageNumber) {
            var pages          = theForm.querySelectorAll('[data-mautic-form-page]');
            [].forEach.call(pages, function (page) {
                // Hide all pages
                page.style.display = 'none';

                var pageNumber = parseInt(page.getAttribute('data-mautic-form-page'));
                var pageBreak  = theForm.querySelector('[data-mautic-form-pagebreak="'+pageNumber+'"]');
                if (pageBreak) {
                    pageBreak.style.display = 'none';
                }
            });

            // Show the wanted page
            var thePage = theForm.querySelector('[data-mautic-form-page="' + showPageNumber + '"]');
            if (thePage) {
                thePage.style.display = 'block'
            }
            var showPageBreak = theForm.querySelector('[data-mautic-form-pagebreak="' + showPageNumber + '"]');
            if (showPageBreak) {
                showPageBreak.style.display = 'block';
            }
        };

        Form.getPageForField = function (formId, fieldId, switchPage) {
            if (typeof switchPage === 'undefined') {
                switchPage = true;
            }
            var containerId = Form.getFieldContainerId(formId, fieldId);
            var container   = document.getElementById(containerId);
            if (!container) {
                return;
            }

            // If within a page break - go back to the page that includes this field
            var pageBreak = Form.findAncestor(container, 'mauticform-page-wrapper');
            if (pageBreak) {
                var page = pageBreak.getAttribute('data-mautic-form-page');
                if (switchPage) {
                    Form.switchPage(document.getElementById('mauticform_' + formId), page);
                }

                return page;
            }
        };

        Form.findAncestor = function (el, cls) {
            var ancestor = false;
            while (true) {
                var parent = el.parentElement;
                if (!parent || Form.hasClass(parent, 'mauticform-innerform')) {
                    break;
                } else if (Form.hasClass(parent, cls)) {
                    ancestor = parent;
                    break;
                } else {
                   el = parent;
                }
            }

            return ancestor;
        };

        Form.hasClass = function (el, cls) {
            return (' ' + el.className + ' ').indexOf(' ' + cls + ' ') > -1;
        };

        Form.validator = function(formId) {
            var validator = {
                validateForm: function (submitForm) {
                    if (!submitForm) {
                        Form.prepareMessengerForm(formId);
                    }

                    var elId              = 'mauticform_' + formId;
                    var theForm           = document.getElementById(elId);
                    var formValid         = Form.customCallbackHandler(formId, 'onValidate');
                    var firstInvalidField = false;

                    validator.disableSubmitButton();

                    // If true or false, then a callback handled it
                    if (formValid === null) {
                        Form.customCallbackHandler(formId, 'onValidateStart');

                        // Remove success class if applicable
                        var formContainer = document.getElementById('mauticform_wrapper_' + formId);
                        if (formContainer) {
                            formContainer.className = formContainer.className.replace(" mauticform-post-success", "");
                        }

                        validator.setMessage('', 'message');
                        validator.setMessage('', 'error');

                        var formValid = true;

                        // Find each required element
                        for (var fieldKey in MauticFormValidations[formId]) {
                            if (!validator.validateField(theForm, fieldKey)) {
                                formValid = false;
                                if (!firstInvalidField) {
                                    firstInvalidField = fieldKey;
                                }
                            }
                        }

                        if (formValid) {
                            document.getElementById(elId + '_return').value = document.URL;
                        }
                    }

                    if (Form.customCallbackHandler(formId, 'onValidateEnd', formValid) === false) {
                        // A custom validation failed
                        formValid = false;
                    }

                    if (formValid && submitForm) {
                        theForm.submit();
                    } else {
                        // Activate the page with the first validation error
                        Form.getPageForField(formId, firstInvalidField);

                        // Enable submit button after response is handled
                        validator.enableSubmitButton();

                        // Focus on the first invalid field
                        if (MauticFormValidations[formId] && MauticFormValidations[formId][firstInvalidField]) {
                            const invalidField = MauticFormValidations[formId][firstInvalidField];
                            const invalidElement = document.getElementsByName('mauticform[' + invalidField.name + ']')[0];
                            if (invalidElement) {
                                invalidElement.focus();
                            }
                        }
                    }

                    return formValid;
                },

                validateField: function(theForm, fieldKey) {
                    var field = MauticFormValidations[formId][fieldKey];

                    var containerId = Form.getFieldContainerId(formId, fieldKey);

                    // Skip conditonal hidden field
                    if (document.getElementById(containerId).getAttribute('data-validate-disable')) {
                        return true;
                    }

                    var valid = Form.customCallbackHandler(formId, 'onValidateField', {fieldKey: fieldKey, field: field});

                    // If true, then a callback handled it
                    if (valid === null) {
                        var name = 'mauticform[' + field.name + ']';

                        if (field.multiple == 'true' || field.type == 'checkboxgrp') {
                            name = name + '[]';
                        }

                        var valid = true;
                        if (typeof theForm.elements[name] != 'undefined') {
                            switch (field.type) {
                                case 'radiogrp':
                                    var elOptions = theForm.elements[name];
                                    valid = validator.validateOptions(elOptions);
                                    break;

                                case 'checkboxgrp':
                                    var elOptions = theForm.elements[name];
                                    valid = validator.validateOptions(elOptions);
                                    break;

                                case 'email':
                                    valid = validator.validateEmail(theForm.elements[name].value);
                                    break;

                                default:
                                    valid = (theForm.elements[name].value != '')
                                    break;
                            }
                        }

                        if (!valid) {
                            validator.markError(containerId, valid);
                        } else {
                            validator.clearError(containerId);
                        }
                    }

                    return valid;
                },

                validateOptions: function(elOptions) {
                    if (typeof elOptions === 'undefined') {
                        return;
                    }

                    var optionsValid = false;

                    if (elOptions.length == undefined) {
                        elOptions = [elOptions];
                    }

                    for (var i = 0; i < elOptions.length; i++) {
                        if (elOptions[i].checked) {
                            optionsValid = true;
                            break;
                        }
                    }

                    return optionsValid;
                },

                validateEmail: function(email) {
                    var atpos = email.indexOf("@");
                    var dotpos = email.lastIndexOf(".");
                    var valid = (atpos < 1 || dotpos < atpos + 2 || dotpos + 2 >= email.length) ? false : true;
                    return valid;
                },

                markError: function(containerId, valid, validationMessage) {
                    var elErrorSpan = false;
                    var callbackValidationMessage = validationMessage;
                    var elContainer = document.getElementById(containerId);
                    if (elContainer) {
                        elErrorSpan = elContainer.querySelector('.mauticform-errormsg');
                        if (typeof validationMessage == 'undefined' && elErrorSpan) {
                            callbackValidationMessage = elErrorSpan.innerHTML;
                        }
                    }

                    var callbackData = {
                        containerId: containerId,
                        valid: valid,
                        validationMessage: callbackValidationMessage
                    };

                    // If true, a callback handled it
                    if (!Form.customCallbackHandler(formId, 'onErrorMark', callbackData)) {
                        if (elErrorSpan) {
                            if (typeof validationMessage !== 'undefined') {
                                elErrorSpan.innerHTML = validationMessage;
                            }

                            elErrorSpan.style.display = (valid) ? 'none' : '';
                            elContainer.className = elContainer.className + " mauticform-has-error";
                        }
                    }
                },

                clearErrors: function() {
                    var theForm    = document.getElementById('mauticform_' + formId);
                    var hasErrors  = theForm.querySelectorAll('.mauticform-has-error');
                    var that       = this;
                    [].forEach.call(hasErrors, function(container) {
                        that.clearError(container.id);
                    });
                },

                clearError: function(containerId) {
                    // If true, a callback handled it
                    if (!Form.customCallbackHandler(formId, 'onErrorClear', containerId)) {
                        var elContainer = document.getElementById(containerId);
                        if (elContainer) {
                            var elErrorSpan = elContainer.querySelector('.mauticform-errormsg');
                            if (elErrorSpan) {
                                elErrorSpan.style.display = 'none';
                                elContainer.className = elContainer.className.replace(" mauticform-has-error", "");
                            }
                        }
                    }
                },

                parseFormResponse: function (response) {
                    // Reset the iframe so that back doesn't repost for some browsers
                    var ifrm = document.getElementById('mauticiframe_' + formId);
                    if (ifrm) {
                        ifrm.src = 'about:blank';
                    }

                    // If true, a callback handled response parsing
                    if (!Form.customCallbackHandler(formId, 'onResponse', response)) {

                        Form.customCallbackHandler(formId, 'onResponseStart', response);
                        if (response.download) {
                            // Hit the download in the iframe
                            document.getElementById('mauticiframe_' + formId).src = response.download;

                            // Register a callback for a redirect
                            if (response.redirect) {
                                setTimeout(function () {
                                    window.top.location = response.redirect;
                                }, 2000);
                            }
                        } else if (response.redirect) {
                            window.top.location = response.redirect;
                        } else if (response.validationErrors) {
                            var firstPage = false;
                            for (var field in response.validationErrors) {
                                var elPage = Form.getPageForField(formId, field, false);
                                if (elPage) {
                                    elPage = parseInt(elPage);
                                    if (firstPage) {
                                        firstPage = (firstPage < elPage) ? firstPage : elPage;
                                    } else {
                                        firstPage = elPage;
                                    }
                                }
                                this.markError('mauticform_' + formId + '_' + field, false, response.validationErrors[field]);
                            }

                            if (firstPage) {
                                Form.switchPage(document.getElementById('mauticform_' + formId), firstPage);
                            }
                        } else if (response.errorMessage) {
                            this.setMessage(response.errorMessage, 'error');
                        }

                        if (response.success) {
                            if (response.successMessage) {
                                this.setMessage(response.successMessage, 'message');
                            }

                            // Add a post success class
                            var formContainer = document.getElementById('mauticform_wrapper_' + formId);
                            if (formContainer) {
                                formContainer.className = formContainer.className + " mauticform-post-success";
                            }

                            // Reset the form
                            this.resetForm();
                        }

                        validator.enableSubmitButton();

                        Form.customCallbackHandler(formId, 'onResponseEnd', response);
                    }
                },

                setMessage: function (message, type) {
                    // If true, a callback handled it
                    if (!Form.customCallbackHandler(formId, 'onMessageSet', {message: message, type: type})) {
                        var container = document.getElementById('mauticform_' + formId + '_' + type);
                        if (container) {
                            container.innerHTML = message;
                        } else if (message) {
                            alert(message);
                        }
                    }
                },

                resetForm: function () {

                    this.clearErrors();

                    Form.switchPage(document.getElementById('mauticform_' + formId), 1);

                    document.getElementById('mauticform_' + formId).reset();

                    Form.prepareShowOn(formId); // Hides conditional fields again.
                },

                disableSubmitButton: function() {
                    // If true, then a callback handled it
                    if (!Form.customCallbackHandler(formId, 'onSubmitButtonDisable')) {
                        var submitButton = document.getElementById('mauticform_' + formId).querySelector('.mauticform-button');

                        if (submitButton) {
                            MauticLang.submitMessage = submitButton.innerHTML;
                            submitButton.innerHTML = MauticLang.submittingMessage;
                            submitButton.disabled = 'disabled';
                        }
                    }
                },

                enableSubmitButton: function() {
                    // If true, then a callback handled it
                    if (!Form.customCallbackHandler(formId, 'onSubmitButtonEnable')) {
                        var submitButton = document.getElementById('mauticform_' + formId).querySelector('.mauticform-button');
                        if (submitButton) {
                            submitButton.innerHTML = MauticLang.submitMessage;
                            submitButton.disabled = '';
                        }
                    }
                }
            };

            return validator;
        };

        Form.registerFormMessenger = function() {
            window.addEventListener('message', function(event) {
                if (Core.debug()) console.log(event);

                if (MauticDomain.indexOf(event.origin) !== 0) return;

                try {
                    var response = JSON.parse(event.data);

                    if (response && response.formName) {
                        Core.getValidator(response.formName).parseFormResponse(response);
                    }
                } catch (err) {
                    if (Core.debug()) console.log(err);
                }
            }, false);

            if (Core.debug()) console.log('Messenger listener started.');
        };

        Form.getFieldContainerId = function(formId, fieldKey) {
            var containerId = 'mauticform_' + formId + '_' + fieldKey;
            if (!document.getElementById(containerId)) {
                containerId = 'mauticform_' + fieldKey;
            }

            return containerId;
        };

        Form.populateValuesWithGetParameters = function() {
            if (document.forms.length !== 0 && window.location.search) {
                const queryString = window.location.search;
                const urlParams = new URLSearchParams(queryString);
                const entries = urlParams.entries();

                for (const entry of entries) {
                    const inputs = document.getElementsByName(`mauticform[${entry[0]}]`);
                    inputs.forEach(function (input) {
                        if (input.type !== 'hidden' && input.value === '') {
                            input.value = entry[1].replace(/<[^>]*>?/gm, '');
                        }
                    });
                }
            }
        };

        Core.getValidator = function(formId) {
            return Form.validator(formId);
        };

        Core.validateForm = function(formId, submit) {
            if (typeof submit == 'undefined') {
                submit = false;
            }
            return Core.getValidator(formId).validateForm(submit);
        };

        Core.prepareForm = function(formId) {
            return Form.prepareForm(formId);
        }

        Modal.loadStyle = function() {
            if (typeof(config.modal_css) != 'undefined' && parseInt(config.modal_css) != 'Nan' && config.modal_css == 0) {
                if (Core.debug()) console.log('custom modal css style');
                return;
            }

            var s = document.createElement('link');
            s.rel = "stylesheet"
            s.type = "text/css"
            s.href = Core.debug() ? Core.getMauticBaseUrl() + 'app/assets/css/modal.css' : Core.getMauticBaseUrl() + 'media/css/modal.min.css';
            document.head.appendChild(s);
            if (Core.debug()) console.log(s);
        };

        Modal.open = function() {
            if (arguments[0] && typeof arguments[0] === "object") {
                this.options = this.extendDefaults(this.defaults, arguments[0]);
            }
            this.buildOut();
            this.initializeEvents();
            window.getComputedStyle(this.modal).height;
            this.modal.className = this.modal.className + (this.modal.offsetHeight > window.innerHeight ? " mauticForm-open mauticForm-anchored" : " mauticForm-open");
            this.overlay.className = this.overlay.className + " mauticForm-open";
        };

        Modal.buildOut = function() {
            var content, contentHolder, docFrag;
            content = typeof(this.options.content) == 'string' ? this.options.content : this.options.content.innerHTML;
            // Create a DocumentFragment to build with
            docFrag = document.createDocumentFragment();

            // Create modal element
            this.modal = document.createElement("div");
            this.modal.className = "mauticForm-modal " + this.options.className;
            this.modal.style.width = this.options.width;
            this.modal.style.height = this.options.height;

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
        };

        Modal.extendDefaults = function(source, properties) {
            for (var property in properties) {
                if (properties.hasOwnProperty(property)) source[property] = properties[property];
            }
            return source;
        };

        Modal.initializeEvents = function() {
            if (this.closeButton) this.closeButton.addEventListener('click', this.close.bind(this));
            if (this.overlay) this.overlay.addEventListener('click', this.close.bind(this));
        };

        Modal.transitionSelect = function() {
            var el = document.createElement("div");
            return (el.style.WebkitTransition) ? "webkitTransitionEnd" : (el.style.WebkitTransition) ? "webkitTransitionEnd" : "oTransitionEnd" ;
        };

        Modal.close = function() {
            var _ = this;
            this.modal.className = this.modal.className.replace(" mauticForm-open", "");
            this.overlay.className = this.overlay.className.replace(" mauticForm-open", "");
            this.modal.addEventListener(this.transitionSelect(), function() {
                _.modal.parentNode.removeChild(_.modal);
            });
            this.overlay.addEventListener(this.transitionSelect(), function() {
                if(_.overlay.parentNode) _.overlay.parentNode.removeChild(_.overlay);
            });

            //remove modal and overlay
            this.overlay.parentNode.removeChild(this.overlay);
            this.modal.parentNode.removeChild(this.modal);
        };

        Core.parseToObject = function(params) {
            return params.split('&')
                .reduce((params, param) => {
                    const item = param.split('=');
                    const key = decodeURIComponent(item[0] || '');
                    const value = decodeURIComponent(item[1] || '');
                    if (key) {
                        params[key] = value;
                    }
                    return params;
                }, {});
        };

        Core.setConfig = function (options) {
            config = options;
        };

        Core.getConfig = function() {
            return config;
        };

        Core.debug = function() {
            return (typeof(config.debug) != 'undefined' && parseInt(config.debug) != 'Nan' && config.debug == 1) ? true : false ;
        };

        Core.devMode = function() {
            return (typeof(config.devmode) != 'undefined' && parseInt(config.devmode) != 'Nan' && config.devmode == 1) ? true : false ;
        };

        Core.setMauticBaseUrl = function(base_url) {
            config.mautic_base_url = base_url.split('/').slice(0,-3).join('/')+'/';
        };

        // Be aware that this base URL may contain asset_prefix if set.
        Core.getMauticBaseUrl = function() {
            return config.mautic_base_url;
        };

        Core.initialize = function(base_url) {
            Profiler.startTime();
            if (Core.debug()) console.log('SDK initialized');
            if (typeof(config.mautic_base_url) == 'undefined') Core.setMauticBaseUrl(base_url);
            if (Core.debug()) console.log('Automatic setup mautic_base_url as: ' + config.mautic_base_url);
            Modal.loadStyle();

            if (document.readyState !== 'loading') {
                Form.initialize();
            } else {
                document.addEventListener("DOMContentLoaded", function(e){
                    if (Core.debug()) console.log('DOMContentLoaded dispatched as DOM is ready');
                    Form.initialize();
                });
            }
        };

        Core.openModal = function(options){
            Modal.open(options);
        };

        Core.onLoad = function() {
            if (Core.debug()) console.log('Object onLoad called');

            Core.setupForms();
        };

        Core.setupForms = function() {
            if (Core.debug()) console.log('DOM ready state is ' + document.readyState);

            // Landing pages and form "previews" cannot process till the DOM is fully loaded
            if ("complete" !== document.readyState) {
                setTimeout(function () { Core.setupForms(); }, 1);

                return;
            }

            Form.prepareForms();
            Form.registerFormMessenger();
        };

        return Core;
    }

    if (typeof(MauticSDK) === 'undefined') {
        window.MauticSDK = define_library();
        var sjs = document.getElementsByTagName('script'), tjs = sjs.length;
        for (var i = 0; i < sjs.length; i++) {
            if (!sjs[i].hasAttribute('src') || sjs[i].getAttribute("src").indexOf('mautic-form-src.js') == -1) continue;
            var sParts = sjs[i].getAttribute("src").split("?");
            if (sParts[1]) MauticSDK.setConfig(MauticSDK.parseToObject(sParts[1]));
            MauticSDK.initialize(sParts[0]);
            break;
        }
    }
    if (typeof window.MauticFormValidations == 'undefined') {
        window.MauticFormValidations = {};
    }
})( window );
