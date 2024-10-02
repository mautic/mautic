//UserBundle
Mautic.userOnLoad = function (container) {
    if (mQuery(container + ' form[name="user"]').length) {
        if (mQuery('#user_position').length) {
            Mautic.activateTypeahead('#user_position', {displayKey: 'position'});
        }
    } else {
        if (mQuery(container + ' #list-search').length) {
            Mautic.activateSearchAutocomplete('list-search', 'user.user');
        }
    }

    const prefix = 'm-toggle-setting-';
    Object.keys(localStorage)
        .filter(key => key.startsWith(prefix))
        .forEach(setting => {
            const attributeName = setting.replace(prefix, '');
            const value = localStorage.getItem(setting);

            if (value) {
                // Set the correct radio button
                const radio = document.querySelector(`input[name="${attributeName}"][data-attribute-value="${value}"]`);
                if (radio) radio.checked = true;
            }
        });

    // Handle radio button changes
    document.querySelectorAll('input[type="radio"][data-attribute-toggle]').forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.checked) {
                const attributeName = this.dataset.attributeToggle;
                const newValue = this.dataset.attributeValue;
                document.documentElement.setAttribute(attributeName, newValue);
                localStorage.setItem(`${prefix}${attributeName}`, newValue);
            }
        });
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
        mQuery.each(levelPerms, function(level, perms) {
            mQuery.each(perms, function(index, perm) {
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
