const assert = require('node:assert/strict');
const {readFileSync} = require('node:fs');
const {join} = require('node:path');
const {test} = require('node:test');
const {runInNewContext} = require('node:vm');

const Mautic = {};
runInNewContext(readFileSync(join(__dirname, '../../../Assets/js/7.charts.js'), 'utf8'), {
    Mautic,
    document: {addEventListener() {}}
});

test('gradient uses the dataset colour with fading opacity', () => {
    const dataset = {borderColor: 'rgba(10, 20, 30, 0.8)'};
    const stops = [];
    const gradient = {addColorStop: (offset, colour) => stops.push([offset, colour])};
    Mautic.chartGradientFill([dataset]);
    const chart = {
        chartArea: {top: 5, bottom: 100},
        ctx: {
            createLinearGradient: (...coordinates) => {
                assert.deepEqual(coordinates, [0, 5, 0, 100]);
                return gradient;
            }
        }
    };
    assert.equal(dataset.backgroundColor({chart}), gradient);
    assert.deepEqual(stops, [[0, 'rgba(10, 20, 30,0.35)'], [1, 'rgba(10, 20, 30,0)']]);
    assert.equal(dataset.pointBackgroundColor, dataset.borderColor);
    assert.equal(dataset.backgroundColor({chart: {}}), undefined);
});

for (const renderer of ['renderLineChart', 'renderHourChart']) {
    test(`${renderer} applies gradient styling to its datasets`, () => {
        const charts = [];
        const mautic = {chartObjects: []};
        const context = {Mautic: mautic, document: {addEventListener() {}}};
        runInNewContext(readFileSync(join(__dirname, '../../../Assets/js/7.charts.js'), 'utf8'), context);
        context.Chart = function(canvas, config) {
            charts.push({canvas, config});
        };
        const data = {
            labels: ['first', 'second'],
            datasets: [
                {borderColor: 'rgba(10, 20, 30, 0.8)', data: [1, 2]},
                {borderColor: 'rgba(40, 50, 60, 0.8)', data: [3, 4]}
            ]
        };
        const canvas = {text: () => JSON.stringify(data)};
        const gradientFill = mautic.chartGradientFill;
        let receivedDatasets;
        mautic.chartGradientFill = (datasets) => {
            receivedDatasets = datasets;
            gradientFill(datasets);
        };

        mautic[renderer](canvas);

        assert.equal(charts.length, 1);
        assert.equal(charts[0].canvas, canvas);
        assert.equal(charts[0].config.type, 'line');
        assert.equal(receivedDatasets, charts[0].config.data.datasets);
        assert.equal(receivedDatasets.length, 2);
        for (const dataset of receivedDatasets) {
            assert.equal(typeof dataset.backgroundColor, 'function');
            assert.equal(dataset.pointBackgroundColor, dataset.borderColor);
        }
        assert.equal(mautic.chartObjects.length, 1);
    });
}

for (const color of ['#123456', 'rgba(10 20 30 / 0.8)']) {
    test(`unsupported gradient colour ${color} keeps existing styling`, () => {
        const dataset = {borderColor: color, backgroundColor: '#abcdef', pointBackgroundColor: '#fedcba'};
        Mautic.chartGradientFill([dataset]);
        assert.equal(dataset.backgroundColor, '#abcdef');
        assert.equal(dataset.pointBackgroundColor, '#fedcba');
    });
}

for (const [min, max, midpoint] of [
    [-10, 0, -5],
    [-10, 10, 0],
    [10, 20, 15],
    [0, 10, 5],
    [-1.5, -0.5, -1],
    [0, 0, 0]
]) {
    test(`y-axis ticks stay within ${min} to ${max}`, () => {
        const scale = {min, max};
        Mautic.chartYAxisTicks(scale);
        assert.deepEqual(Array.from(scale.ticks, (tick) => tick.value), [min, midpoint, max]);
    });
}
