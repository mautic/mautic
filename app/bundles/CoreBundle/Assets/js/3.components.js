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

/**
 * Initialize data tables inside a container: sync selection, batch bar and zebra striping with the server-rendered state
 *
 * @param container Selector or element wrapping the data tables
 */
Mautic.initDataTable = function (container) {
    mQuery(container).find('[data-data-table]').addBack('[data-data-table]').each(function () {
        Mautic.updateDataTableSelection(this);
        Mautic.updateDataTableZebra(this);
        Mautic.updateDataTableExpandAll(this);
    });
};

Mautic.getDataTableRows = function (table) {
    return Array.from(table.querySelectorAll('tbody > tr[data-parent-row]'));
};

Mautic.getDataTableChildRow = function (row) {
    const button = row.querySelector('[data-data-table-expand]');

    return button ? document.getElementById(button.getAttribute('aria-controls')) : null;
};

Mautic.updateDataTableZebra = function (table) {
    Mautic.getDataTableRows(table).filter((row) => !row.hidden).forEach((row, index) => {
        row.classList.toggle('data-table__row--alt', index % 2 === 1);
    });
};

Mautic.setDataTableRowExpanded = function (table, row, expanded) {
    const button = row.querySelector('[data-data-table-expand]');
    const child = Mautic.getDataTableChildRow(row);

    if (!button || !child) {
        return;
    }

    button.setAttribute('aria-expanded', expanded ? 'true' : 'false');
    button.setAttribute('aria-label', table.dataset[expanded ? 'labelCollapse' : 'labelExpand']);
    row.classList.toggle('data-table__row--expanded', expanded);
    child.hidden = row.hidden || !expanded;
};

Mautic.updateDataTableExpandAll = function (table) {
    const button = table.querySelector('[data-data-table-expand-all]');

    if (!button) {
        return;
    }

    const buttons = Array.from(table.querySelectorAll('[data-data-table-expand]'));
    const allExpanded = buttons.length > 0 && buttons.every((item) => item.getAttribute('aria-expanded') === 'true');

    button.setAttribute('aria-expanded', allExpanded ? 'true' : 'false');
    button.setAttribute('aria-label', table.dataset[allExpanded ? 'labelCollapseAll' : 'labelExpandAll']);
};

Mautic.updateDataTableSelection = function (table) {
    const checkboxes = Array.from(table.querySelectorAll('tbody [data-data-table-select]'));

    checkboxes.forEach((input) => {
        input.closest('tr').classList.toggle('data-table__row--selected', input.checked);
    });

    if (table.dataset.selection !== 'multi') {
        return;
    }

    const selected = checkboxes.filter((input) => input.checked && !input.disabled).length;
    const selectable = checkboxes.filter((input) => !input.disabled && !input.closest('tr').hidden);
    const visibleSelected = selectable.filter((input) => input.checked).length;
    const selectAll = table.querySelector('[data-data-table-select-all]');
    const batch = table.querySelector('.data-table__batch-actions');

    if (selectAll) {
        selectAll.checked = selectable.length > 0 && visibleSelected === selectable.length;
        selectAll.indeterminate = visibleSelected > 0 && visibleSelected < selectable.length;
    }

    if (batch) {
        batch.hidden = selected === 0;
        batch.querySelector('.data-table__batch-summary').textContent = (selected === 1 ? table.dataset.labelSelectedOne : table.dataset.labelSelected).replace('99999', selected);
    }
};

Mautic.sortDataTable = function (table, button) {
    const th = button.closest('th');
    const direction = th.getAttribute('aria-sort') === 'ascending' ? 'descending' : (th.getAttribute('aria-sort') === 'descending' ? 'none' : 'ascending');
    const index = Array.from(th.parentNode.children).indexOf(th);
    const body = table.querySelector('tbody');
    const anchor = body.querySelector('.data-table__empty-row');
    const rows = Mautic.getDataTableRows(table);

    rows.forEach((row, position) => {
        row.dataset.position = row.dataset.position || position;
    });

    table.querySelectorAll('thead th[aria-sort]').forEach((header) => header.setAttribute('aria-sort', 'none'));
    th.setAttribute('aria-sort', direction);

    const value = (row) => row.children[index].dataset.sortValue;
    const factor = direction === 'descending' ? -1 : 1;

    rows.sort((a, b) => {
        if (direction === 'none') {
            return a.dataset.position - b.dataset.position;
        }

        return factor * value(a).localeCompare(value(b), undefined, {numeric: true, sensitivity: 'base'});
    });

    rows.forEach((row) => {
        body.insertBefore(row, anchor);
        const child = Mautic.getDataTableChildRow(row);
        if (child) {
            body.insertBefore(child, anchor);
        }
    });

    Mautic.updateDataTableZebra(table);
};

Mautic.filterDataTable = function (table, query) {
    const needle = query.trim().toLowerCase();
    let visible = 0;

    Mautic.getDataTableRows(table).forEach((row) => {
        const match = needle === '' || row.textContent.toLowerCase().includes(needle);
        const child = Mautic.getDataTableChildRow(row);
        row.hidden = !match;
        if (child && !match) {
            child.hidden = true;
        } else if (child && match) {
            child.hidden = row.querySelector('[data-data-table-expand]').getAttribute('aria-expanded') !== 'true';
        }
        visible += match ? 1 : 0;
    });

    const emptyRow = table.querySelector('.data-table__empty-row');

    if (emptyRow) {
        emptyRow.hidden = visible > 0;
    }
    Mautic.updateDataTableZebra(table);
    Mautic.updateDataTableSelection(table);
};

document.addEventListener('click', (event) => {
    const target = event.target.closest('[data-data-table-expand], [data-data-table-expand-all], [data-data-table-sort], [data-data-table-cancel]');
    const table = target && target.closest('[data-data-table]');

    if (!table) {
        return;
    }

    if (target.matches('[data-data-table-expand]')) {
        Mautic.setDataTableRowExpanded(table, target.closest('tr'), target.getAttribute('aria-expanded') !== 'true');
        Mautic.updateDataTableExpandAll(table);
    } else if (target.matches('[data-data-table-expand-all]')) {
        const expand = target.getAttribute('aria-expanded') !== 'true';
        Mautic.getDataTableRows(table).forEach((row) => Mautic.setDataTableRowExpanded(table, row, expand));
        Mautic.updateDataTableExpandAll(table);
    } else if (target.matches('[data-data-table-sort]')) {
        Mautic.sortDataTable(table, target);
    } else {
        table.querySelectorAll('tbody [data-data-table-select]:checked').forEach((input) => {
            input.checked = false;
            input.dispatchEvent(new Event('change', {bubbles: true}));
        });
        Mautic.updateDataTableSelection(table);
    }
});

document.addEventListener('change', (event) => {
    const table = event.target.closest('[data-data-table]');

    if (!table || !event.target.matches('[data-data-table-select-all]')) {
        return;
    }

    const checked = event.target.checked;
    table.querySelectorAll('tbody tr:not([hidden]) [data-data-table-select]:not(:disabled)').forEach((input) => {
        input.checked = checked;
        input.dispatchEvent(new Event('change', {bubbles: true}));
    });
    Mautic.updateDataTableSelection(table);
});

mQuery(document).off('change.dataTable').on('change.dataTable', '[data-data-table-select]', function () {
    const table = this.closest('[data-data-table]');

    if (table) {
        Mautic.updateDataTableSelection(table);
    }
});

document.addEventListener('input', (event) => {
    const table = event.target.closest('[data-data-table]');

    if (table && event.target.matches('[data-data-table-search]')) {
        Mautic.filterDataTable(table, event.target.value);
    }
});

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
