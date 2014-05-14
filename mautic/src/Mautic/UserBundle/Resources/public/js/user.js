//UserBundle
Mautic.userOnLoad = function (container) {
    if ($(container + ' form[name="user"]').length) {
        if ($('#user_role_lookup').length) {
            var roles = new Bloodhound({
                datumTokenizer: Bloodhound.tokenizers.obj.whitespace('label'),
                queryTokenizer: Bloodhound.tokenizers.whitespace,
                prefetch: {
                    url: mauticBaseUrl + "ajax?ajaxAction=user:user:rolelist"
                },
                remote: {
                    url: mauticBaseUrl + "ajax?ajaxAction=user:user:rolelist&filter=%QUERY"
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
                    url: mauticBaseUrl + "ajax?ajaxAction=user:user:positionlist"
                },
                remote: {
                    url: mauticBaseUrl + "ajax?ajaxAction=user:user:positionlist&filter=%QUERY"
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
            Mautic.activateSearchAutocomplete('list-search', 'user');
        }
    }
};

Mautic.roleOnLoad = function (container) {
    if ($(container + ' #list-search').length) {
        Mautic.activateSearchAutocomplete('list-search', 'role');
    }
};

