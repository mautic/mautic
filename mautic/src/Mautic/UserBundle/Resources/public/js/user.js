//UserBundle
Mautic.userOnLoad = function (container) {
    if ($(container + ' form[name="user"]').length) {
        if ($('#user_role_lookup').length) {
            var roles = new Bloodhound({
                datumTokenizer: Bloodhound.tokenizers.obj.whitespace('label'),
                queryTokenizer: Bloodhound.tokenizers.whitespace,
                prefetch: {
                    url: mauticBaseUrl + "ajax?action=user:roleList",
                    ajax: {
                        beforeSend: function () {
                            MauticVars.showLoadingBar = false;
                        }
                    }
                },
                remote: {
                    url: mauticBaseUrl + "ajax?action=user:roleList&filter=%QUERY",
                    ajax: {
                        beforeSend: function () {
                            MauticVars.showLoadingBar = false;
                        }
                    }
                },
                dupDetector: function (remoteMatch, localMatch) {
                    return (remoteMatch.label == localMatch.label);
                },
                ttl: 1800000,
                limit: 5
            });
            roles.initialize();
            roles.clearPrefetchCache();
            $("#user_role_lookup").typeahead(
                {
                    hint: true,
                    highlight: true,
                    minLength: 2
                },
                {
                    name: 'user_role',
                    displayKey: 'label',
                    source: roles.ttAdapter()
                }).on('typeahead:selected', function (event, datum) {
                    $("#user_role").val(datum["value"]);
                }).on('typeahead:autocompleted', function (event, datum) {
                    $("#user_role").val(datum["value"]);
                }
            );
        }
        if ($('#user_position').length) {
            var positions = new Bloodhound({
                datumTokenizer: Bloodhound.tokenizers.obj.whitespace('value'),
                queryTokenizer: Bloodhound.tokenizers.whitespace,
                prefetch: {
                    url: mauticBaseUrl + "ajax?action=user:positionList"
                },
                remote: {
                    url: mauticBaseUrl + "ajax?action=user:positionList&filter=%QUERY"
                },
                dupDetector: function (remoteMatch, localMatch) {
                    return (remoteMatch.label == localMatch.label);
                },
                ttl: 1800000,
                limit: 5
            });
            positions.initialize();
            $("#user_position").typeahead(
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
        if ($(container + ' #list-search').length) {
            Mautic.activateSearchAutocomplete('list-search', 'user.user');
        }
    }
};

Mautic.roleOnLoad = function (container, response) {
    if ($(container + ' #list-search').length) {
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
        if ($('#role_isAdmin_0').prop('checked')) {
            $('#permissions-container').removeClass('hide');
        } else {
            $('#permissions-container').addClass('hide');
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
        var clickedBox = $(event.target).find('input:checkbox').first();
        if ($(clickedBox).prop('checked')) {
            if ($(clickedBox).val() == 'full') {
                //uncheck all of the others
                $(container).find("label input:checkbox:checked").map(function () {
                    if ($(this).val() != 'full') {
                        $(this).prop('checked', false);
                        $(this).parent().toggleClass('active');
                    }
                })
            } else {
                //uncheck full
                $(container).find("label input:checkbox:checked").map(function () {
                    if ($(this).val() == 'full') {
                        granted = granted - 1;
                        $(this).prop('checked', false);
                        $(this).parent().toggleClass('active');
                    }
                })
            }
        }

        //update granted numbers
        if ($('.' + bundle + '_granted').length) {
            var granted = 0;
            var levelPerms = MauticVars.permissionList[bundle];
            $.each(levelPerms, function(level, perms) {
                $.each(perms, function(index, perm) {
                    if (perm == 'full') {
                        if ($('#role_permissions_' + bundle + '\\:' + level + '_' + perm).prop('checked')) {
                            if (perms.length === 1)
                                granted++;
                            else
                                granted += perms.length - 1;
                        }
                    } else {
                        if ($('#role_permissions_' + bundle + '\\:' + level + '_' + perm).prop('checked'))
                            granted++;
                    }
                });
            });
            $('.' + bundle + '_granted').html(granted);
        }
    }, 10);
};