/** CategoryBundle **/

Mautic.categoryOnLoad = function (container, response) {
    if (mQuery(container + ' #list-search').length) {
        Mautic.activateSearchAutocomplete('list-search', 'category');
    }

    if (response && response.closeModal) {
        mQuery(container + ' .modal-form-buttons').html('');
    }
};