/**
 * Update the value, width and helper text of a progress bar
 *
 * @param container Selector or element wrapping the component
 * @param value     Current value
 * @param percent   Current percentage (0-100)
 * @param max       Maximum value, used for the helper text
 */
Mautic.updateProgressBar = function (container, value, percent, max) {
    const component = mQuery(container).find('.progress-component').addBack('.progress-component');
    const track = component.find('[role="progressbar"]');

    if (component.hasClass('progress-component--indeterminate')) {
        component.removeClass('progress-component--indeterminate');
    }

    track.attr({'aria-valuemin': 0, 'aria-valuemax': max, 'aria-valuenow': value});
    track.find('.progress-bar').css('width', percent + '%');
    component.find('.progress-component__helper-text').text(
        Mautic.translate('mautic.core.progress_bar.count', {current: value, total: max})
    );
};
