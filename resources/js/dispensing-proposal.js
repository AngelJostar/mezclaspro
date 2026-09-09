export function calculateDispensingProposal({ doseMg, containerMg, containerMl, remainderMl = 0, stock = 0, reserved = 0, containers = null }) {
    const dose = Number(doseMg);
    const mg = Number(containerMg);
    const ml = Number(containerMl);
    const availableMl = Math.max(0, Number(remainderMl) || 0);
    const availableContainers = Math.max(0, Math.floor((Number(stock) || 0) - (Number(reserved) || 0)));
    if (![dose, mg, ml].every(Number.isFinite) || dose <= 0 || mg <= 0 || ml <= 0) {
        return { valid: false, containers: 0, covered: false, estimatedRemainderMg: null };
    }

    const concentration = mg / ml;
    const requiredMl = dose / concentration;
    const usedRemainderMl = Math.min(availableMl, requiredMl);
    // Match the inventory service's four-decimal mL precision and empty-volume tolerance.
    const remainingMl = Math.round(Math.max(0, requiredMl - usedRemainderMl) * 10000) / 10000;
    const neededContainers = remainingMl > 0.0001 ? Math.ceil(remainingMl / ml) : 0;
    const proposedContainers = Math.min(neededContainers, availableContainers);
    const selectedContainers = containers === null ? proposedContainers : Number(containers);
    const validSelection = Number.isInteger(selectedContainers) && selectedContainers >= 0 && selectedContainers <= availableContainers;
    const openedContainers = Math.min(neededContainers, Math.max(0, Math.min(selectedContainers || 0, availableContainers)));
    const covered = validSelection && openedContainers >= neededContainers;
    const missingMg = Math.max(0, (remainingMl - openedContainers * ml) * concentration);

    return {
        valid: true,
        containers: proposedContainers,
        neededContainers,
        availableContainers,
        covered,
        providedMg: Math.min(dose, usedRemainderMl * concentration + openedContainers * mg),
        usedRemainderMg: usedRemainderMl * concentration,
        missingMg,
        estimatedRemainderMg: covered ? Math.max(0, availableMl * concentration + openedContainers * mg - dose) : null,
    };
}

const precision = 10000;
const roundMg = value => Math.round(value * precision) / precision;
const brandKey = row => String(row.brand || '').trim().toLowerCase() || `unknown:${row.presentationId ?? row.id}`;
const descendingContent = (a, b) => b.containerMg - a.containerMg || a.index - b.index;

function candidates(presentations) {
    return presentations.map((row, index) => ({
        ...row, index,
        containerMg: Number(row.containerMg), containerMl: Number(row.containerMl),
        availableContainers: Math.max(0, Math.floor((Number(row.stock) || 0) - (Number(row.reserved) || 0))),
        remainderMg: roundMg(Math.max(0, Number(row.remainderMl) || 0) * Number(row.containerMg) / Number(row.containerMl)),
    })).filter(row => Number.isFinite(row.containerMg) && row.containerMg > 0
        && Number.isFinite(row.containerMl) && row.containerMl > 0).sort(descendingContent);
}

// Allocate existing remainder first, then closed vials in descending mg order.
// This is also used for manual edits, so the preview never counts the full dose once per row.
export function evaluateDispensingSelection({ doseMg, presentations }) {
    const dose = Number(doseMg);
    const selected = presentations.filter(row => row.selected !== false);
    const rows = candidates(selected);
    const valid = Number.isFinite(dose) && dose > 0 && rows.length === selected.length
        && new Set(rows.map(brandKey)).size <= 1
        && rows.every(row => Number.isInteger(Number(row.containers)) && Number(row.containers) >= 0
            && Number(row.containers) <= row.availableContainers);
    let pending = Math.max(0, dose || 0);
    const allocations = rows.map(row => {
        const usedRemainderMg = Math.min(pending, row.remainderMg);
        pending = roundMg(pending - usedRemainderMg);
        return { ...row, usedRemainderMg, providedMg: usedRemainderMg, openedContainers: 0 };
    });
    for (const row of allocations) {
        const usedClosedMg = Math.min(pending, row.containerMg * Math.max(0, Number(row.containers) || 0));
        row.openedContainers = Math.max(0, Math.ceil(roundMg(usedClosedMg) / row.containerMg - 1e-9));
        row.providedMg = roundMg(row.providedMg + usedClosedMg);
        row.newRemainderMg = roundMg(Math.max(0, row.openedContainers * row.containerMg - usedClosedMg));
        row.estimatedRemainderMg = roundMg(row.remainderMg - row.usedRemainderMg + row.newRemainderMg);
        pending = roundMg(pending - usedClosedMg);
    }
    return {
        valid, covered: valid && pending <= 0.0001, missingMg: pending,
        providedMg: roundMg(Math.max(0, dose - pending)),
        usedRemainderMg: roundMg(allocations.reduce((sum, row) => sum + row.usedRemainderMg, 0)),
        newRemainderMg: roundMg(allocations.reduce((sum, row) => sum + row.newRemainderMg, 0)),
        allocations,
    };
}

const gcd = (a, b) => b ? gcd(b, a % b) : a;
const preferLarger = (left, right) => {
    for (let i = 0; i < left.length; i++) {
        if (left[i] !== right[i]) return left[i] > right[i];
    }
    return false;
};

function prefersLargerVials(left, right) {
    const sizes = [...new Set([...left.allocations, ...right.allocations].map(row => row.containerMg))].sort((a, b) => b - a);
    const counts = proposal => sizes.map(size => proposal.allocations
        .filter(row => row.containerMg === size).reduce((sum, row) => sum + row.openedContainers, 0));
    return preferLarger(counts(left), counts(right));
}

function optimizeBrand(dose, rows) {
    const remainderMg = rows.reduce((sum, row) => sum + row.remainderMg, 0);
    const sizes = rows.map(row => Math.round(row.containerMg * precision));
    if (sizes.some(size => size <= 0 || !Number.isSafeInteger(size))) return null;
    const divisor = sizes.reduce(gcd);
    const weights = sizes.map(size => size / divisor);
    const target = Math.max(0, Math.ceil(Math.round((dose - remainderMg) * precision) / divisor));
    const capacity = rows.reduce((sum, row, index) => sum + weights[index] * row.availableContainers, 0);
    let counts = rows.map(() => 0);
    if (target > capacity) counts = rows.map(row => row.availableContainers);
    else if (target > 0) {
        // Bounded subset sum with binary stock bundles. One state per attainable mg total;
        // ties keep more of the larger presentation, without a greedy excess-dose shortcut.
        const limit = Math.min(capacity, target + Math.max(...weights) - 1);
        const states = new Map([[0, counts]]);
        for (let index = 0; index < rows.length; index++) {
            let remaining = Math.min(rows[index].availableContainers, Math.ceil(target / weights[index]));
            for (let bundle = 1; remaining > 0; bundle *= 2) {
                const quantity = Math.min(bundle, remaining);
                remaining -= quantity;
                for (const [amount, previous] of [...states]) {
                    const nextAmount = amount + weights[index] * quantity;
                    if (nextAmount > limit) continue;
                    const next = [...previous];
                    next[index] += quantity;
                    const existing = states.get(nextAmount);
                    if (!existing || preferLarger(next, existing)) states.set(nextAmount, next);
                }
                // Do not freeze the form or silently substitute a nonoptimal proposal.
                if (states.size > 200000) return null;
            }
        }
        let bestAmount = Infinity;
        for (const [amount, choice] of states) {
            if (amount >= target && amount < bestAmount) {
                bestAmount = amount;
                counts = choice;
            }
        }
    }
    return evaluateDispensingSelection({ doseMg: dose, presentations: rows.map((row, index) => ({ ...row, containers: counts[index] })) });
}

export function optimizeDispensingSelection({ doseMg, presentations, brand = null }) {
    const dose = Number(doseMg);
    const rows = candidates(presentations).filter(row => row.availableContainers > 0 || row.remainderMg > 0);
    const groups = new Map();
    for (const row of rows) {
        const key = brandKey(row);
        if (brand !== null && key !== brand) continue;
        if (!groups.has(key)) groups.set(key, []);
        groups.get(key).push(row);
    }
    if (!Number.isFinite(dose) || dose <= 0) return evaluateDispensingSelection({ doseMg, presentations: [] });
    let best = null;
    for (const group of groups.values()) {
        const proposal = optimizeBrand(dose, group);
        if (!proposal) return { valid: false, covered: false, missingMg: dose, providedMg: 0, allocations: [] };
        if (!best || (proposal.covered && !best.covered)
            || (proposal.covered === best.covered && (proposal.missingMg < best.missingMg
                || (proposal.missingMg === best.missingMg && (proposal.newRemainderMg < best.newRemainderMg
                    || (proposal.newRemainderMg === best.newRemainderMg && prefersLargerVials(proposal, best))))))) best = proposal;
    }
    return best || evaluateDispensingSelection({ doseMg, presentations: [] });
}

export { brandKey as dispensingBrandKey };
