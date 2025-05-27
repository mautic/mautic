//UserBundle
Mautic.userOnLoad = function (container) {
    if (mQuery(container + ' form[name="user"]').length) {
        if (mQuery('#user_position').length) {
            Mautic.activateTypeahead('#user_position', { displayKey: 'position' });
        }
    } else {
        if (mQuery(container + ' #list-search').length) {
            Mautic.activateSearchAutocomplete('list-search', 'user.user');
        }
    }

    /**
     * Initializes radio button states for UI settings based on localStorage settings.
     * Saves settings to localStorage only when the Save button is clicked.
     *
     * @constant {string} prefix - Prefix for localStorage keys.
     */
    const prefix = 'm-toggle-setting-';
    let temporarySettings = {};

    // Load settings from localStorage on page load or use the checked attribute if not set
    document.querySelectorAll('input[type="radio"][data-attribute-toggle]').forEach(radio => {
        const attributeName = radio.dataset.attributeToggle;
        const settingKey = `${prefix}${attributeName}`;
        const savedValue = localStorage.getItem(settingKey);

        if (savedValue) {
            // If a saved value exists in localStorage, apply it
            const correspondingRadio = document.querySelector(`input[name="${attributeName}"][data-attribute-value="${savedValue}"]`);
            if (correspondingRadio) correspondingRadio.checked = true;
        } else if (radio.checked) {
            // Use the checked state from the HTML as the default if nothing is saved
            localStorage.setItem(settingKey, radio.dataset.attributeValue); // Persist default value to localStorage
        }
    });

    // Handle radio button changes - just store in temporary settings
    document.querySelectorAll('input[type="radio"][data-attribute-toggle]').forEach(radio => {
        radio.addEventListener('change', function () {
            if (this.checked) {
                const attributeName = this.dataset.attributeToggle;
                temporarySettings[attributeName] = this.dataset.attributeValue;
            }
        });
    });

    // Save button functionality - persist the settings in localStorage
    document.getElementById('user_buttons_save_toolbar').addEventListener('click', () => {
        Object.entries(temporarySettings).forEach(([attributeName, value]) => {
            localStorage.setItem(`${prefix}${attributeName}`, value);
            document.documentElement.setAttribute(attributeName, value);
        });
        temporarySettings = {};
    });

    // Cancel button functionality - discard temporary settings and revert changes
    document.getElementById('user_buttons_cancel_toolbar').addEventListener('click', () => {
        Object.keys(temporarySettings).forEach(attributeName => {
            const storedValue = localStorage.getItem(`${prefix}${attributeName}`);
            if (storedValue) {
                document.documentElement.setAttribute(attributeName, storedValue);
                const radio = document.querySelector(`input[name="${attributeName}"][data-attribute-value="${storedValue}"]`);
                if (radio) radio.checked = true;
            } else {
                document.documentElement.removeAttribute(attributeName);
                const radios = document.querySelectorAll(`input[name="${attributeName}"]`);
                radios.forEach(radio => radio.checked = false);
            }
        });
        temporarySettings = {};
    });
};

Mautic.roleOnLoad = function (container, response) {
    if (mQuery(container + ' #list-search').length) {
        Mautic.activateSearchAutocomplete('list-search', 'user.role');
    }

    if (response && response.permissionList) {
        MauticVars.permissionList = response.permissionList;
    }
    Mautic.togglePermissionVisibility();
};

/**
 * Toggles permission panel visibility for roles
 */
Mautic.togglePermissionVisibility = function () {
    //add a very slight delay in order for the clicked on checkbox to be selected since the onclick action
    //is set to the parent div
    setTimeout(function () {
        if (mQuery('#role_isAdmin_0').prop('checked')) {
            mQuery('#rolePermissions').removeClass('hide');
            mQuery('#isAdminMessage').addClass('hide');
            mQuery('#permissions-tab').removeClass('disabled');
        } else {
            mQuery('#rolePermissions').addClass('hide');
            mQuery('#isAdminMessage').removeClass('hide');
            mQuery('#permissions-tab').addClass('disabled');
        }
    }, 10);
};

/**
 * Toggle permissions, update ratio, etc
 *
 * @param changedPermission
 * @param bundle
 */
Mautic.onPermissionChange = function (changedPermission, bundle) {
    var granted = 0;

    if (mQuery(changedPermission).prop('checked')) {
        if (mQuery(changedPermission).val() == 'full') {
            //uncheck all of the others
            mQuery(changedPermission).closest('.choice-wrapper').find("label input:checkbox:checked").map(function () {
                if (mQuery(this).val() != 'full') {
                    mQuery(this).prop('checked', false);
                    mQuery(this).parent().toggleClass('active');
                }
            })
        } else {
            //uncheck full
            mQuery(changedPermission).closest('.choice-wrapper').find("label input:checkbox:checked").map(function () {
                if (mQuery(this).val() == 'full') {
                    granted = granted - 1;
                    mQuery(this).prop('checked', false);
                    mQuery(this).parent().toggleClass('active');
                }
            })
        }
    }

    //update granted numbers
    if (mQuery('.' + bundle + '_granted').length) {
        var granted = 0;
        var levelPerms = MauticVars.permissionList[bundle];
        mQuery.each(levelPerms, function (level, perms) {
            mQuery.each(perms, function (index, perm) {
                var isChecked = mQuery('input[data-permission="' + bundle + ':' + level + ':' + perm + '"]').prop('checked');
                if (perm == 'full') {
                    if (isChecked) {
                        if (perms.length === 1) {
                            granted++;
                        } else {
                            granted += perms.length - 1;
                        }
                    }
                } else if (isChecked) {
                    granted++;
                }
            });
        });
        mQuery('.' + bundle + '_granted').html(granted);
    }
};
