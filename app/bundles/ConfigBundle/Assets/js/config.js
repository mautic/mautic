//ConfigBundle
Mautic.configOnLoad = function (container) {
    Mautic.hideSpecificConfigFields();

    mQuery('form[name="config"]').change(function() {
    	Mautic.hideSpecificConfigFields();
	});
};

// show/hide field according to their data-hide-on attribute
Mautic.hideSpecificConfigFields = function() {
	mQuery('form[name="config"]').find('[data-hide-on]').each(function(index, el) {
		var field = mQuery(el);
		var fieldContainer = field.closest('.col-md-6');
		var conditions = jQuery.parseJSON(field.attr('data-hide-on'));

		mQuery.each(conditions, function(fieldId, condition) {
			var sourceFieldVal = mQuery('#' + fieldId).val();
			if (mQuery.inArray(sourceFieldVal, condition) !== -1) {
				fieldContainer.fadeOut();
			} else {
				fieldContainer.fadeIn();
			}
	    });
	});
};
