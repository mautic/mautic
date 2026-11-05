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
