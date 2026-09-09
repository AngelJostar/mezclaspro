import { test } from 'node:test';
import assert from 'node:assert/strict';
import { calculateDispensingProposal, optimizeDispensingSelection, evaluateDispensingSelection } from '../../resources/js/dispensing-proposal.js';

const defaults = { doseMg: 1800, containerMg: 250, containerMl: 10, stock: 98 };
const calculate = values => calculateDispensingProposal({ ...defaults, ...values });

test('proposes eight 250 mg vials for 1800 mg and predicts a 200 mg remainder', () => {
    const result = calculate();
    assert.equal(result.containers, 8);
    assert.equal(result.providedMg, 1800);
    assert.equal(result.estimatedRemainderMg, 200);
    assert.equal(result.covered, true);
});

test('uses existing remainder first and predicts the total left after preparation', () => {
    const result = calculate({ remainderMl: 4 });
    assert.equal(result.containers, 7);
    assert.equal(result.usedRemainderMg, 100);
    assert.equal(result.estimatedRemainderMg, 50);
});

test('needs no new vials when remainder covers the dose, including an empty closed stock', () => {
    const result = calculate({ remainderMl: 80, stock: 0 });
    assert.equal(result.containers, 0);
    assert.equal(result.estimatedRemainderMg, 200);
    assert.equal(result.covered, true);
});

test('does not propose stock that is missing or reserved', () => {
    const result = calculate({ stock: 8, reserved: 3 });
    assert.equal(result.containers, 5);
    assert.equal(result.missingMg, 550);
    assert.equal(result.covered, false);
    assert.equal(result.estimatedRemainderMg, null);
});

test('exact dose coverage has zero remainder', () => {
    assert.equal(calculate({ doseMg: 2000 }).estimatedRemainderMg, 0);
    assert.equal(calculate({ doseMg: 2000 }).containers, 8);
});

test('manual limits retain the inventory service behavior of opening only needed vials', () => {
    assert.equal(calculate({ containers: 7 }).covered, false);
    assert.equal(calculate({ containers: 9 }).estimatedRemainderMg, 200);
    assert.equal(calculate({ containers: 1.5 }).covered, false);
    assert.equal(calculate({ containers: 100 }).covered, false);
});

test('missing concentration and invalid doses never produce a fabricated proposal', () => {
    for (const data of [{ containerMg: null }, { containerMl: 0 }, { doseMg: '' }, { doseMg: -1 }, { doseMg: 'invalid' }]) {
        const result = calculate(data);
        assert.equal(result.valid, false);
        assert.equal(result.containers, 0);
        assert.equal(result.estimatedRemainderMg, null);
    }
});

test('fractional-volume precision does not add a vial at exact coverage', () => {
    assert.equal(calculate({ doseMg: 300, containerMg: 100, containerMl: 0.1 }).containers, 3);
    assert.equal(calculate({ doseMg: 0.1, containerMg: 0.05, containerMl: 1 }).containers, 2);
});

const vial = (id, mg, stock, brand = 'Marca A', extra = {}) => ({
    id, presentationId: id, containerMg: mg, containerMl: mg / 20, stock, brand, ...extra,
});
const used = proposal => proposal.allocations.filter(row => row.providedMg > 0).map(row => [row.id, row.openedContainers]);

test('1000 mg uses two 400 mg and two 100 mg vials of the same brand, largest first', () => {
    const result = optimizeDispensingSelection({ doseMg: 1000, presentations: [vial(1, 100, 2), vial(2, 400, 3)] });
    assert.deepEqual(used(result), [[2, 2], [1, 2]]);
    assert.equal(result.covered, true);
    assert.equal(result.newRemainderMg, 0);
    assert.equal(result.providedMg, 1000);
});

test('finds an exact combination even when greedily using the biggest vial would waste more', () => {
    const result = optimizeDispensingSelection({ doseMg: 600, presentations: [vial(1, 400, 5), vial(2, 300, 5)] });
    assert.deepEqual(used(result), [[2, 2]]);
    assert.equal(result.newRemainderMg, 0);
});

test('equal waste prefers more of the largest presentation and never underfills a dose', () => {
    const rows = [vial(1, 100, 30), vial(2, 400, 3)];
    assert.deepEqual(used(optimizeDispensingSelection({ doseMg: 800, presentations: rows })), [[2, 2]]);
    const result = optimizeDispensingSelection({ doseMg: 950, presentations: rows });
    assert.deepEqual(used(result), [[2, 2], [1, 2]]);
    assert.equal(result.providedMg, 950);
    assert.equal(result.newRemainderMg, 50);
});

test('chooses a single brand with minimum waste, never making an exact dose from two brands', () => {
    const result = optimizeDispensingSelection({ doseMg: 500, presentations: [vial(1, 400, 3), vial(2, 100, 1, 'Marca B')] });
    assert.deepEqual(used(result), [[1, 2]]);
    assert.equal(result.newRemainderMg, 300);
    const better = optimizeDispensingSelection({ doseMg: 500, presentations: [vial(1, 400, 3), vial(2, 250, 4, 'Marca B')] });
    assert.deepEqual(used(better), [[2, 2]]);
});

test('uses valid remainders from smaller same-brand presentations before opening new vials', () => {
    const result = optimizeDispensingSelection({ doseMg: 430, presentations: [vial(1, 400, 3), vial(2, 100, 0, ' MARCA A ', { remainderMl: 1.5 })] });
    assert.deepEqual(used(result), [[1, 1], [2, 0]]);
    assert.equal(result.usedRemainderMg, 30);
    assert.equal(result.newRemainderMg, 0);
});

test('insufficient stock stays incomplete, respecting reservations and unknown brands', () => {
    const result = optimizeDispensingSelection({ doseMg: 1000, presentations: [vial(1, 400, 3, 'Marca A', { reserved: 2 }), vial(2, 100, 2)] });
    assert.equal(result.covered, false);
    assert.equal(result.missingMg, 400);
    const unknown = optimizeDispensingSelection({ doseMg: 500, presentations: [vial(1, 400, 1, ''), vial(2, 100, 1, '')] });
    assert.equal(unknown.covered, false);
});

test('manual mixed brands, fractional or overstock quantities cannot be submitted', () => {
    for (const rows of [
        [vial(1, 400, 3, 'A', { containers: 1 }), vial(2, 100, 1, 'B', { containers: 1 })],
        [vial(1, 400, 3, 'A', { containers: 1.5 })],
        [vial(1, 400, 3, 'A', { containers: 4 })],
    ]) assert.equal(evaluateDispensingSelection({ doseMg: 500, presentations: rows }).covered, false);
});

test('bounded optimization matches exhaustive small-stock combinations with decimals and remainders', () => {
    for (let seed = 1; seed <= 80; seed++) {
        const rows = [vial(1, (seed % 9 + 2) / 10, seed % 4), vial(2, (seed % 5 + 1) / 10, 3), vial(3, 0.1, 2)];
        const dose = (seed % 19 + 1) / 10;
        const result = optimizeDispensingSelection({ doseMg: dose, presentations: rows });
        let minTotal = Infinity;
        for (let a = 0; a <= rows[0].stock; a++) for (let b = 0; b <= 3; b++) for (let c = 0; c <= 2; c++) {
            const total = a * rows[0].containerMg + b * rows[1].containerMg + c * rows[2].containerMg;
            if (total + 1e-9 >= dose) minTotal = Math.min(minTotal, total);
        }
        assert.equal(result.covered, Number.isFinite(minTotal), `seed ${seed}`);
        if (result.covered) assert.ok(Math.abs(result.newRemainderMg - (minTotal - dose)) < 0.0001, `seed ${seed}`);
    }
});
