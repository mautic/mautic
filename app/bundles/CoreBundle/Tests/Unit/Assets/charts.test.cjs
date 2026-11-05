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
