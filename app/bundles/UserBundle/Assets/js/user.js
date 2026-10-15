//UserBundle
Mautic.userOnLoad = function (container) {
    if (mQuery(container + ' form[name="user"]').length) {
        if (mQuery('#user_position').length) {
            Mautic.activateTypeahead('#user_position', { displayKey: 'position' });
        }

        Mautic.preventPasswordAutofill(container);
        Mautic.preventPasswordMismatchClose(container);

        if (mQuery('#changePasswordModal').data('auto-open')) {
            mQuery('#changePasswordModal').modal('show');
        }
    } else {
        if (mQuery(container + ' #list-search').length) {
            Mautic.activateSearchAutocomplete('list-search', 'user.user');
        }

        if (mQuery('#InviteUserModal').data('auto-open')) {
            mQuery('#InviteUserModal').modal('show');
        }
    }

    /**
     * Initializes radio button states for UI settings and updates hidden inputs
     * when settings are changed.
     */
    // Initialize radio buttons based on hidden input values
    document.querySelectorAll('input[type="radio"][data-attribute-toggle]').forEach(radio => {
        const attributeName = radio.dataset.attributeToggle;
        const hiddenInput = document.getElementById(`user_preferences_${attributeName.replace('-', '_')}`);

        if (hiddenInput?.value) {
            // If hidden input has a value, set the corresponding radio
            const correspondingRadio = document.querySelector(
                `input[name="${attributeName}"][data-attribute-value="${hiddenInput.value}"]`
            );
            if (correspondingRadio) correspondingRadio.checked = true;
        } else if (radio.checked && hiddenInput) {
            // Use the checked state from the HTML as the default
            hiddenInput.value = radio.dataset.attributeValue;
        }
    });

    // Handle radio button changes - update hidden inputs and HTML attributes
    document.querySelectorAll('input[type="radio"][data-attribute-toggle]').forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.checked) {
                const attributeName = this.dataset.attributeToggle;
                const hiddenInput = document.getElementById(`user_preferences_${attributeName.replace('-', '_')}`);

                if (hiddenInput) {
                    hiddenInput.value = this.dataset.attributeValue;
                }
            }
        });
    });

    const saveButton = document.querySelector('[id^="user_buttons_save_toolbar"]');
    if (saveButton) {
        saveButton.addEventListener('click', function() {
            document.querySelectorAll('input[type="radio"][data-attribute-toggle]:checked').forEach(radio => {
                const attributeToggle = radio.dataset.attributeToggle;
                const attributeValue = radio.dataset.attributeValue;
                document.documentElement.setAttribute(attributeToggle, attributeValue);
            });
        });
    }

};

/**
 * Stops browsers from auto-filling the account "change password" fields with a
 * previously saved credential. autocomplete="new-password" alone is not always
 * honored, so the fields are kept readonly until the user deliberately focuses
 * them - that's the one technique modern browsers do respect. They're left alone
 * after that so a password the user actually typed still gets submitted when the
 * page is saved.
 */
Mautic.preventPasswordAutofill = function (container) {
    var passwordFields = mQuery(container + ' #user_plainPassword_password, ' + container + ' #user_plainPassword_confirm');

    if (!passwordFields.length) {
        return;
    }

    passwordFields.each(function () {
        mQuery(this).val('').attr('readonly', 'readonly').attr('autocomplete', 'new-password');
    });

    passwordFields.on('focus', function () {
        mQuery(this).removeAttr('readonly');
    });

    mQuery('#changePasswordModal').on('hidden.bs.modal', function () {
        var modal = mQuery(this);
        var hasNewPassword = mQuery(container + ' #user_plainPassword_password').val().length > 0;
        var message = hasNewPassword
            ? Mautic.translate(modal.data('staged-message'))
            : Mautic.translate('mautic.user.config.account.password.change.unchanged');

        Mautic.setFlashes(Mautic.addInfoFlashMessage(message));
    });
};

/**
 * Live-checks the password/confirm fields and blocks the change-password modal
 * from closing (Done button, the X button, a backdrop click, or Escape) while
 * they don't match, so a mismatch is caught right there instead of only
 * surfacing after a full page save. The mismatch check only kicks in once both
 * fields have been touched (blurred at least once) and both have a value -
 * otherwise a user still typing their confirmation would see a false "doesn't
 * match yet" error on every keystroke before they're even done typing.
 */
Mautic.preventPasswordMismatchClose = function (container) {
    var passwordField = mQuery(container + ' #user_plainPassword_password');
    var confirmField   = mQuery(container + ' #user_plainPassword_confirm');
    var mismatchError  = mQuery('#passwordMismatchError');
    var dismissButtons = mQuery('#changePasswordModal [data-dismiss="modal"]');

    if (!passwordField.length || !confirmField.length || !mismatchError.length) {
        return;
    }

    mismatchError.text(Mautic.translate('mautic.user.config.account.password.mismatch'));

    var passwordTouched = false;
    var confirmTouched  = false;

    var bothTouchedAndFilled = function () {
        return passwordTouched && confirmTouched
            && passwordField.val().length > 0
            && confirmField.val().length > 0;
    };

    var passwordsMismatch = function () {
        return bothTouchedAndFilled() && passwordField.val() !== confirmField.val();
    };

    var validate = function () {
        var mismatch = passwordsMismatch();

        mismatchError.toggleClass('hide', !mismatch);
        dismissButtons.prop('disabled', mismatch);
    };

    passwordField.on('blur', function () {
        passwordTouched = true;
        validate();
    });
    confirmField.on('blur', function () {
        confirmTouched = true;
        validate();
    });

    passwordField.on('input', validate);
    confirmField.on('input', validate);

    validate();

    mQuery('#changePasswordModal').on('hide.bs.modal', function (event) {
        if (passwordsMismatch()) {
            event.preventDefault();
        }
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
