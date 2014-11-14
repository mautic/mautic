//UserBundle
Mautic.userOnLoad = function (container) {
    if (mQuery(container + ' form[name="user"]').length) {
        if (mQuery('#user_position').length) {
            var positions = new Bloodhound({
                datumTokenizer: Bloodhound.tokenizers.obj.whitespace('value'),
                queryTokenizer: Bloodhound.tokenizers.whitespace,
                prefetch: {
                    url: mauticAjaxUrl + "?action=user:positionList"
                },
                remote: {
                    url: mauticAjaxUrl + "?action=user:positionList&filter=%QUERY"
                },
                dupDetector: function (remoteMatch, localMatch) {
                    return (remoteMatch.label == localMatch.label);
                },
                ttl: 1800000,
                limit: 5
            });
            positions.initialize();
            mQuery("#user_position").typeahead(
                {
                    hint: true,
                    highlight: true,
                    minLength: 2
                },
                {
                    name: 'user_position',
                    displayKey: 'value',
                    source: positions.ttAdapter()
                }
            );
        }
    } else {
        if (mQuery(container + ' #list-search').length) {
            Mautic.activateSearchAutocomplete('list-search', 'user.user');
        }
    }
};

Mautic.roleOnLoad = function (container, response) {
    if (mQuery(container + ' #list-search').length) {
        Mautic.activateSearchAutocomplete('list-search', 'user.role');
    }

    if (response && response.permissionList) {
        MauticVars.permissionList = response.permissionList;
    }
};

/**
 * Toggles permission panel visibility for roles
 */
Mautic.togglePermissionVisibility = function () {
    //add a very slight delay in order for the clicked on checkbox to be selected since the onclick action
    //is set to the parent div
    setTimeout(function () {
        if (mQuery('#role_isAdmin_0').prop('checked')) {
            mQuery('#permissions-container').removeClass('hide');
        } else {
            mQuery('#permissions-container').addClass('hide');
        }
    }, 10);
};

/**
 * Toggle permissions, update ratio, etc
 *
 * @param container
 * @param event
 * @param bundle
 */
Mautic.onPermissionChange = function (container, event, bundle) {
    //add a very slight delay in order for the clicked on checkbox to be selected since the onclick action
    //is set to the parent div
    setTimeout(function () {
        var granted = 0;
        var clickedBox = mQuery(event.target).find('input:checkbox').first();
        if (mQuery(clickedBox).prop('checked')) {
            if (mQuery(clickedBox).val() == 'full') {
                //uncheck all of the others
                mQuery(container).find("label input:checkbox:checked").map(function () {
                    if (mQuery(this).val() != 'full') {
                        mQuery(this).prop('checked', false);
                        mQuery(this).parent().toggleClass('active');
                    }
                })
            } else {
                //uncheck full
                mQuery(container).find("label input:checkbox:checked").map(function () {
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
                    if (perm == 'full') {
                        if (mQuery('#role_permissions_' + bundle + '\\:' + level + '_' + perm).prop('checked')) {
                            if (perms.length === 1)
                                granted++;
                            else
                                granted += perms.length - 1;
                        }
                    } else {
                        if (mQuery('#role_permissions_' + bundle + '\\:' + level + '_' + perm).prop('checked'))
                            granted++;
                    }
                });
            });
            mQuery('.' + bundle + '_granted').html(granted);
        }
    }, 10);
};