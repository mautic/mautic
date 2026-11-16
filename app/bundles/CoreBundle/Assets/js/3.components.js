/**
 * Update the arc and value text of a gauge
 *
 * @param container Selector or element wrapping the component
 * @param value     Percentage (0-100), or the amount when total is given
 * @param total     Optional total; the arc shows value/total
 */
Mautic.updateGauge = function (container, value, total) {
    const gauge = mQuery(container).find('.gauge').addBack('.gauge');
    const hasTotal = total > 0;
    const raw = hasTotal ? (parseFloat(value) || 0) / total * 100 : parseFloat(value) || 0;
    const percent = Math.round(Math.min(Math.max(raw, 0), 100) * 10) / 10;
    const chart = gauge.find('[role="meter"], [role="progressbar"]');
    const unit = gauge.attr('data-unit') || '';
    const number = hasTotal ? value : percent;
    const spoken = hasTotal ? Mautic.translate('mautic.core.gauge.of', {value: value, total: total}) : percent + unit;
    const text = mQuery('<span class="gauge__number"></span>').text(number);

    gauge.removeClass('gauge--loading');
    chart.removeAttr('aria-busy').attr({role: 'meter', 'aria-valuenow': percent, 'aria-valuetext': spoken});
    gauge.find('.gauge__arc').attr('stroke-dasharray', percent + ' 100');
    gauge.find('.gauge__value').empty().append(text)
        .append(hasTotal ? mQuery('<span class="gauge__total"></span>').text('/' + total) : (unit ? mQuery('<span class="gauge__unit"></span>').text(unit) : ''));
};
