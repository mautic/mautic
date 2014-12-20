//ConfigBundle
Mautic.configOnLoad = function (container) {
    Mautic.hideSpecificConfigFields();

    mQuery('form[name="config"]').change(function() {
    	Mautic.hideSpecificConfigFields();
	});
};

// show/hide field according to their data-hide-on and data-show-on attribute
Mautic.hideSpecificConfigFields = function() {
	var form = mQuery('form[name="config"]');
	var fields = {};
	// find all fields to hide
	form.find('[data-hide-on]').each(function(index, el) {
		var field = mQuery(el);
		var hideOn = jQuery.parseJSON(field.attr('data-hide-on'));

		mQuery.each(hideOn, function(fieldId, condition) {
			var sourceFieldVal = mQuery('#' + fieldId).val();
			if (mQuery.inArray(sourceFieldVal, condition) !== -1 && fields[field.attr('id')] !== true) {
				fields[field.attr('id')] = false;
			} else {
				fields[field.attr('id')] = true;
			}
	    });

	});
	// find all fields to show
	form.find('[data-show-on]').each(function(index, el) {
		var field = mQuery(el);
		var showOn = jQuery.parseJSON(field.attr('data-show-on'));

	    mQuery.each(showOn, function(fieldId, condition) {
			var sourceFieldVal = mQuery('#' + fieldId).val();
			if (mQuery.inArray(sourceFieldVal, condition) === -1 && fields[field.attr('id')] !== true) {
				fields[field.attr('id')] = false;
			} else {
				fields[field.attr('id')] = true;
			}
	    });
	});
	// show/hide according to conditions
	mQuery.each(fields, function(fieldId, show) {
		var fieldContainer = mQuery('#' + fieldId).closest('.col-md-6');;
		if (show) {
			fieldContainer.fadeIn();
		} else {
			fieldContainer.fadeOut();
		}
    });
};
