//ConfigBundle
/**
 * @deprecated - use Mautic.initializeFormFieldVisibilitySwitcher() instead
 * @param formName
 */
Mautic.hideSpecificConfigFields = function(formName) {
	initializeFormFieldVisibilitySwitcher(formName);
};

Mautic.removeConfigValue = function(action, el) {
    Mautic.executeAction(action, function(response) {
    	if (response.success) {
            mQuery(el).parent().addClass('hide');
        }
	});
};