/**
 * Used in data-lookup-callback attr of form field in ExampleSendType
 * Take a look at https://github.com/twitter/typeahead.js/
 */
Mautic.activateExampleContactLookupField = function(fieldOptions, filterId) {

    const lookupElementId = 'example_send_contact';
    const action          = mQuery('#'+ lookupElementId).attr('data-chosen-lookup');

    const options = {
        limit: 20,
        'searchKey': 'lead.lead',
    };

    Mautic.activateFieldTypeahead(lookupElementId, filterId, options, action);

    mQuery('#'+ lookupElementId).on("change",function(event) {
        if (event.target.value === '') {
            // Delete selected contact ID from hidden field
            mQuery('#example_send_contact_id').val('');
        }
    });
};

/**
 * Used in data-lookup-callback attr of form field in ExampleSendType
 */
Mautic.updateExampleContactLookupListFilter = function(field, item) {
    if (item && item.id) {
        mQuery('#example_send_contact_id').val(item.id);
        mQuery(field).val(item.value);
    }
};
