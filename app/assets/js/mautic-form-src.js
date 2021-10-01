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
            clickEvents: []
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

                //display form accoding with style
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
            var index = (Core.devMode()) ? 'index_dev.php' : 'index.php';
            return Core.getMauticBaseUrl() + index + '/form/' + options.data['id'] + '?' + options.params;
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
            var forms = document.getElementsByTagName('form');
            for (var i = 0, n = forms.length; i < n; i++) {
                var formId = forms[i].getAttribute('data-mautic-form');
                if (formId !== null) {
                    Form.prepareMessengerForm(formId);
                    Form.prepareValidation(formId);
                    Form.preparePagination(formId);
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
                                firstInvalidField = fieldKey;
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
                    }

                    return formValid;
                },

                validateField: function(theForm, fieldKey) {
                    var field = MauticFormValidations[formId][fieldKey];
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

                        var containerId = Form.getFieldContainerId(formId, fieldKey);

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
            s.href = Core.debug() ? Core.getMauticBaseUrl() + 'media/css/modal.css' : Core.getMauticBaseUrl() + 'media/css/modal.min.css';
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
            return JSON.parse(
                '{"' +
                decodeURI(url)
                  .replace(/\s/g, "")
                  .split("=")
                  .filter((n) => n)
                  .toString()
                  .replace(/,/g, '":"')
                  .replace(/&/g, '","')
                  .replace(/\?/g, "") +
                '"}'
              );
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

        Core.getMauticBaseUrl = function() {
            return config.mautic_base_url;
        };

        Core.initialize = function(base_url) {
            Profiler.startTime();
            if (Core.debug()) console.log('SDK initialized');
            if (typeof(config.mautic_base_url) == 'undefined') Core.setMauticBaseUrl(base_url);
            if (Core.debug()) console.log('Automatic setup mautic_base_url as: ' + config.mautic_base_url);
            Modal.loadStyle();
            document.addEventListener("DOMContentLoaded", function(e){
                if (Core.debug()) console.log('DOM is ready');
                Form.initialize();
            });
        };

        Core.openModal = function(options){
            Modal.open(options);
        };

        Core.onLoad = function() {
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
