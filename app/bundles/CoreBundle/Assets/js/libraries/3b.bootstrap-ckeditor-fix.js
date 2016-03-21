jQuery.fn.modal.Constructor.prototype.enforceFocus = function() {
    var $modalElement = this.$element;
    jQuery(document).on('focusin.modal',function(e) {
        var $parent = jQuery(e.target.parentNode);
        if ($modalElement[0] !== e.target
            && !$modalElement.has(e.target).length
            && jQuery(e.target).parentsUntil('*[role="dialog"]').length === 0) {
            $modalElement.focus();
        }
    });
};