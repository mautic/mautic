//ConfigBundle

Mautic.removeConfigValue = function(action, el) {
    Mautic.executeAction(action, function(response) {
    	if (response.success) {
            mQuery(el).parent().addClass('hide');
        }
	});
};

/**
 *
 * @returns string|false
 */
Mautic.parseQuery = function (query) {
    var vars = query.split('&');
    var queryString = {};
    for (var i = 0; i < vars.length; i++) {
        var pair = vars[i].split('=');
        var key = decodeURIComponent(pair[0]);
        var value = decodeURIComponent(pair[1]);
        // If first entry with this name
        if (typeof queryString[key] === 'undefined') {
            queryString[key] = decodeURIComponent(value);
            // If second entry with this name
        } else if (typeof queryString[key] === 'string') {
            var arr = [queryString[key], decodeURIComponent(value)];
            queryString[key] = arr;
            // If third or later entry with this name
        } else {
            queryString[key].push(decodeURIComponent(value));
        }
    }
    return queryString;
}

Mautic.parseUrlHashParameter = function(url) {
    var url = url.split('#');
    if ('undefined' != typeof url[1]) {
        return url[1];
    }

    return false;
}

Mautic.resetEmailsToNotification = function(obj) {
    const send_to_owner = obj.value;
    if (parseInt(send_to_owner, 10) === 1)
    {
        mQuery(obj).closest('.panel-body').find('.notification_email_addresses').val('');
    }
};

Mautic.configDsnTestExecute = function(element, action, key) {
    const $button = mQuery(element),
        $container = $button.closest('.config-dsn-container');

    $container.find('.ri-loader-3-line').removeClass('hide');

    Mautic.ajaxActionRequest(action, {key: key}, function(response) {
        const theClass = (response.success) ? 'has-success' : 'has-error',
            theMessage = response.message;
        $container.find('.config-dsn-test-container').removeClass('has-success has-error').addClass(theClass);
        $container.find('.form-text .status-msg').html(theMessage);
        $container.find('.ri-loader-3-line').addClass('hide');
    });
};

Mautic.configDsnTestDisable = function(element) {
    const $container = mQuery(element).closest('.config-dsn-container');

    $container.find('.form-text .status-msg').html('');
    $container.find('.form-text .save-config-msg').removeClass('hide');
    $container.find('.config-dsn-test-button').prop('disabled', true).addClass('disabled');
};


Mautic.initAiSettings = (container) => {
    const settings = document.querySelector(`${container} [data-ai-settings]`);
    if (!settings || settings.dataset.initialized) {
        return;
    }
    settings.dataset.initialized = 'true';

    const list = settings.querySelector('[data-ai-provider-list]');
    const add = settings.querySelector('[data-ai-add-provider]');
    const template = settings.querySelector('[data-ai-provider-template]');
    const assignments = settings.querySelectorAll('[data-ai-provider-select]');
    const rows = () => [...list.querySelectorAll('[data-ai-provider-row]')];
    let nextId = Math.max(-1, ...rows().map((row) => Number(row.dataset.aiProviderRow))) + 1;

    const refreshAssignments = () => {
        const providers = rows().flatMap((row) => {
            const name = row.querySelector('[data-ai-provider-name]').value.trim();
            const location = row.querySelector('[data-ai-provider-location]');
            return name && location.value ? [[row.dataset.aiProviderRow, `${name} (${location.selectedOptions[0].text})`]] : [];
        });

        assignments.forEach((select) => {
            const selected = select.value;
            select.length = 1;
            providers.forEach(([id, label]) => select.add(new Option(label, id)));
            select.value = providers.some(([id]) => selected === id) ? selected : '';
        });
    };

    add.addEventListener('click', () => {
        list.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__ai_provider__', String(nextId++)));
        list.lastElementChild.querySelector('[data-ai-provider-name]').focus();
        list.dispatchEvent(new Event('change', {bubbles: true}));
    });

    const refreshOnEdit = (event) => {
        if (event.target.matches('[data-ai-provider-name], [data-ai-provider-location]')) {
            refreshAssignments();
        }
    };
    list.addEventListener('input', refreshOnEdit, true);
    list.addEventListener('change', refreshOnEdit, true);
    list.addEventListener('click', (event) => {
        const remove = event.target.closest('[data-ai-remove-provider]');
        if (!remove) {
            return;
        }
        const row = remove.closest('[data-ai-provider-row]');
        const next = row.nextElementSibling || row.previousElementSibling;
        row.remove();
        refreshAssignments();
        (next?.querySelector('[data-ai-remove-provider]') ?? add).focus();
        list.dispatchEvent(new Event('change', {bubbles: true}));
    });
};

Mautic.showAnonymizeWarningMessage = function(anonymize_ip) {
    if (mQuery(anonymize_ip).siblings('.toggle__label').attr('aria-checked') === 'true') {
        mQuery('.anonymize_ip_address').addClass('hide');
    } else {
        mQuery('.anonymize_ip_address').removeClass('hide');
    }
};

/**
 * Each configuration page is saved on its own, so warn before leaving a page with unsaved changes.
 */
Mautic.initConfigUnsavedChangesGuard = function(container) {
    const form = mQuery(container + ' form[name="config"]');

    if (!form.length) {
        return;
    }

    // A page that failed validation was not saved, so its submitted values are unsaved too
    const failedValidation = form.find('.has-error').length > 0;
    const initialState = form.serialize();
    const hasUnsavedChanges = () => failedValidation || form.serialize() !== initialState;

    Mautic.configLeaveGuard = function(event) {
        const link = event.target.closest('a[data-toggle="ajax"]');

        if (!link || event.ctrlKey || event.metaKey || !hasUnsavedChanges()) {
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();

        Mautic.showConfirmation(mQuery('<a />').data({
            'message': Mautic.translate('mautic.config.form.unsaved_changes'),
            'confirm-text': Mautic.translate('mautic.core.form.confirm'),
            'confirm-callback': 'leaveConfigPage',
            'cancel-text': Mautic.translate('mautic.core.form.cancel'),
            'link': link
        }));
    };

    Mautic.configBeforeUnloadGuard = function(event) {
        if (hasUnsavedChanges()) {
            event.preventDefault();
        }
    };

    // Capture phase so the guard runs before the ajax link handlers
    document.addEventListener('click', Mautic.configLeaveGuard, true);
    globalThis.addEventListener('beforeunload', Mautic.configBeforeUnloadGuard);

    // Saving, applying or cancelling may reload the whole page, which must not trigger the guard
    form.on('submit', Mautic.removeConfigUnsavedChangesGuard);
};

Mautic.removeConfigUnsavedChangesGuard = function() {
    document.removeEventListener('click', Mautic.configLeaveGuard, true);
    globalThis.removeEventListener('beforeunload', Mautic.configBeforeUnloadGuard);
};

Mautic.leaveConfigPage = function(route, el) {
    Mautic.dismissConfirmation();
    Mautic.removeConfigUnsavedChangesGuard();
    mQuery(el).data('link').click();
};
