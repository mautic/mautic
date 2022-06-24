window.onload = function() {
    jQuery(function () {
        jQuery('*[data-toggle="field-company-lookup"]').autocomplete({
            source: function (request, response) {
                jQuery.ajax({
                    url: MauticDomain + "/form/company-lookup/autocomplete",
                    type: 'post',
                    dataType: "json",
                    data: {
                        search: request.term,
                        field: jQuery(this.element).attr('data-type')
                    },
                    success: function( data ) {
                        response( data );
                    }
                });
            },
            html: true,
            select: function (event, res) {
                jQuery(this).val(res.item.value);
                jQuery('#'+jQuery(this).attr('data-set-id')).val(res.item.id);
                return false;
            },
            change: function(event, ui) {
                if (ui.item === null) {
                    jQuery('#'+jQuery(this).attr('data-set-id')).val('');
                }
            },
            minLength: 3,
            delay: 1000,
        });
    });
}
