/** CategoryBundle **/

Mautic.categoryOnLoad = function (container) {
    if (mQuery(container + ' #list-search').length) {
        Mautic.activateSearchAutocomplete('list-search', 'category.category');
    }
};