/** DynamicContentBundle **/
Mautic.dynamicContentOnLoad = function (container, response) {
    if (response && response.updateSelect) {
        //added dwc through a popup
        var newOption = mQuery('<option />').val(response.dynamicContentId);
        newOption.html(response.dynamicContentName);

        var opener = window.opener;
        if(opener) {
            var el = '#' + response.updateSelect;
            console.log(el);
            var optgroup = el + " optgroup[label=" + response.dynamicContentLang + "]";
            if (opener.mQuery(optgroup).length) {
                // update option when new option equal with option item in group.
                var firstOptionGroups = opener.mQuery(el + ' optgroup');
                var isUpdateOption = false;
                firstOptionGroups.each(function() {
                    var firstOptions = mQuery(this).children();
                    for (var i = 0; i < firstOptions.length; i++) {
                        if (firstOptions[i].value === response.dynamicContentId.toString()) {
                            firstOptions[i].text = response.dynamicContentName;
                            isUpdateOption = true;
                            break;
                        }
                    }
                });

                if (!isUpdateOption) {
                    //the optgroup exist so append to it
                    opener.mQuery(optgroup + " option:last").prev().before(newOption);
                }
            } else {
                //create the optgroup
                var newOptgroup = mQuery('<optgroup label="' + response.dynamicContentLang + '" />');
                newOption.appendTo(newOptgroup);
                opener.mQuery(newOptgroup).appendTo(opener.mQuery(el));
            }

            var chooseOneOption = opener.mQuery(el + ' option:first');

            var optionGroups = opener.mQuery(el + ' optgroup');
            optionGroups.sort(function(a, b) {
                var aLabel = mQuery(a).attr('label');
                var bLabel = mQuery(b).attr('label');

                if (aLabel > bLabel) {
                    return 1;
                } else if (aLabel < bLabel) {
                    return -1;
                } else {
                    return 0;
                }
            });

            optionGroups.each(function() {
                var options = mQuery(this).children();
                options.sort(function(a, b) {
                    if (a.text > b.text) {
                        return 1;
                    } else if (a.text < b.text) {
                        return -1;
                    } else {
                        return 0;
                    }
                });
                mQuery(this).html(options);
            });

            if (opener.mQuery(el).prop('disabled')) {
                opener.mQuery(el).prop('disabled', false);
                chooseOneOption = mQuery('<option value="">' + mauticLang.chosenChooseOne + '</option>');
            }

            opener.mQuery(el).html(chooseOneOption);
            optionGroups.appendTo(opener.mQuery(el));

            newOption.prop('selected', true);

            opener.mQuery(el).trigger("chosen:updated");

            Mautic.disabledDynamicContentAction(opener);
        }

        window.close();
    } else if (mQuery(container + ' #list-search').length) {
        Mautic.activateSearchAutocomplete('list-search', 'dwc');
    }
};

Mautic.loadNewDynamicContentWindow = function(options) {
    if (options.windowUrl) {
        Mautic.startModalLoadingBar();

        setTimeout(function() {
            var generator = window.open(options.windowUrl, 'newDynamicContentwindow', 'height=600,width=530');

            if (!generator || generator.closed || typeof generator.closed == 'undefined') {
                alert(response.popupBlockerMessage);
            } else {
                generator.onload = function () {
                    Mautic.stopModalLoadingBar();
                    Mautic.stopIconSpinPostEvent();
                };
            }
        }, 100);
    }
};

Mautic.standardDynamicContentUrl = function(options) {
    if (!options) {
        return;
    }

    var url = options.windowUrl;
    if (url) {
        var editDynamicContentKey = '/dwc/edit/dynamicContentId';
        var previewDynamicContentKey = '/dwc/preview/dynamicContentId';
        if (url.indexOf(editDynamicContentKey) > -1 ||
            url.indexOf(previewDynamicContentKey) > -1) {
            options.windowUrl = url.replace('dynamicContentId', mQuery('#campaignevent_properties_dynamicContent').val());
        }
    }

    return options;
};

Mautic.disabledDynamicContentAction = function(opener) {
    if (typeof opener == 'undefined') {
        opener = window;
    }

    var dynamicContent = opener.mQuery('#campaignevent_properties_dynamicContent').val();

    var disabled = dynamicContent === '' || dynamicContent === null;

    opener.mQuery('#campaignevent_properties_editDynamicContentButton').prop('disabled', disabled);
};