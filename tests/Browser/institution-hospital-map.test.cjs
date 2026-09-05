const { test, before, after } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { execFileSync } = require('node:child_process');
const path = require('node:path');
const { buildSync } = require('esbuild');
const { chromium } = require('playwright');

const root = path.resolve(__dirname, '../..');
const source = buildSync({
    entryPoints: [path.join(root, 'resources/js/institution-hospital-map.js')],
    bundle: true, write: false, format: 'iife', loader: { '.css': 'empty' },
}).outputFiles[0].text;
const css = ['node_modules/leaflet/dist/leaflet.css', 'resources/css/institution-hospital-map.css']
    .map((file) => readFileSync(path.join(root, file), 'utf8')).join('\n');
const hospitals = [
    { id: 1, name: 'Activo con ruta', is_active: true, has_assigned_route: true, latitude: 19.42, longitude: -99.16 },
    { id: 2, name: 'Activo sin ruta', is_active: true, has_assigned_route: false, latitude: 19.44, longitude: -99.13 },
    { id: 3, name: 'Inactivo con ruta', is_active: false, has_assigned_route: true, latitude: 19.38, longitude: -99.14 },
    { id: 4, name: 'Inactivo sin ruta', is_active: false, has_assigned_route: false, latitude: 19.4, longitude: -99.19, estimated: true },
    { id: 5, name: 'Sin coordenadas', is_active: true, has_assigned_route: false, latitude: null, longitude: null },
].map((hospital) => ({ address: 'Direccion de prueba', estimated: false, ...hospital }));

// Render the real partial with fixtures only. No hospital or route records are read or changed.
const render = (records) => execFileSync('php', ['-r', `
    require 'vendor/autoload.php';
    $app = require 'bootstrap/app.php';
    $app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();
    $mapHospitals = collect(json_decode($argv[1], true));
    echo view('admin.instituciones.partials.hospital-map', [
        'mapHospitals' => $mapHospitals,
        'totalHospitals' => $mapHospitals->count(),
        'institucion' => (object) ['nombre' => 'Institucion de prueba'],
    ])->render();
`, JSON.stringify(records)], { cwd: root, encoding: 'utf8' });
let browser;
let shell;

before(async () => {
    shell = render(hospitals);
    browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
});
after(async () => { await browser?.close(); });

async function fixture(width = 1440, body = shell) {
    const page = await browser.newPage({ viewport: { width, height: 900 } });
    await page.route('**/*', (route) => {
        if (route.request().url().startsWith('https://tile.openstreetmap.org/')) {
            return route.fulfill({ contentType: 'image/png', body: Buffer.from(
                'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aM5kAAAAASUVORK5CYII=', 'base64',
            ) });
        }
        return route.fulfill({ contentType: 'text/html', body: `<!doctype html><html><head>
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <style>*{box-sizing:border-box}body{margin:16px;font-family:Arial}.sr-only{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0)}${css}</style>
            </head><body>${body}<script>${source}</script></body></html>` });
    });
    await page.goto('http://institution-map.test/');
    return page;
}

const markerColors = (page) => page.locator('.institution-hospital-marker').evaluateAll(
    (markers) => markers.map((marker) => getComputedStyle(marker).backgroundColor),
);

test('color modes use independent status and route assignment with matching legends', async () => {
    const page = await fixture();
    try {
        assert.equal(await page.locator('.institution-hospital-marker').count(), 4);
        assert.deepEqual(await markerColors(page), ['rgb(37, 99, 235)', 'rgb(37, 99, 235)', 'rgb(148, 163, 184)', 'rgb(148, 163, 184)']);
        assert.equal(await page.locator('[data-map-legend="status"]:visible').count(), 2);
        assert.equal(await page.locator('[data-map-legend="route"]:visible').count(), 0);
        await page.getByLabel('Colorear hospitales por').selectOption('route');
        assert.deepEqual(await markerColors(page), ['rgb(22, 163, 74)', 'rgb(250, 204, 21)', 'rgb(22, 163, 74)', 'rgb(250, 204, 21)']);
        assert.equal(await page.locator('[data-map-legend="status"]:visible').count(), 0);
        assert.equal(await page.locator('[data-map-legend="route"]:visible').count(), 2);
        assert.equal(await page.locator('.institution-marker-estimated').count(), 1);

        await page.getByLabel('Localizar hospital', { exact: true }).selectOption('3');
        await page.locator('.leaflet-popup-content').waitFor();
        assert.match(await page.locator('.leaflet-popup-content').innerText(), /Inactivo[\s\S]*Ruta asignada/);
        await page.waitForFunction(() => !document.querySelector('.leaflet-pan-anim'));
        const mapPosition = await page.locator('.leaflet-map-pane').getAttribute('style');
        await page.getByLabel('Colorear hospitales por').selectOption('status');
        assert.equal(await page.locator('.leaflet-map-pane').getAttribute('style'), mapPosition);
        assert.equal(await page.getByLabel('Localizar hospital', { exact: true }).inputValue(), '3');
        assert.equal(await page.locator('.leaflet-popup-content').isVisible(), true);
        await page.getByLabel('Colorear hospitales por').selectOption('route');
        await page.getByRole('button', { name: 'Mostrar todos los hospitales' }).click();
        assert.equal(await page.getByLabel('Colorear hospitales por').inputValue(), 'route');
        assert.equal(await page.getByLabel('Localizar hospital', { exact: true }).inputValue(), '');
        assert.equal(await page.locator('.institution-hospital-marker').count(), 4);
    } finally { await page.close(); }
});

test('map controls and markers fit desktop and mobile widths', async () => {
    for (const width of [1440, 390, 320]) {
        const page = await fixture(width);
        try {
            await page.getByLabel('Colorear hospitales por').selectOption('route');
            const boxes = await page.locator('.institution-hospital-map-tools select, .institution-hospital-map-tools button')
                .evaluateAll((elements) => elements.map((element) => {
                    const r = element.getBoundingClientRect();
                    return { left: r.left, right: r.right, top: r.top, bottom: r.bottom, width: r.width };
                }));
            boxes.forEach((box) => {
                assert.ok(box.left >= 0 && box.right <= width && box.width >= 38);
            });
            for (let i = 0; i < boxes.length; i += 1) {
                for (let j = i + 1; j < boxes.length; j += 1) {
                    const a = boxes[i]; const b = boxes[j];
                    assert.ok(a.right <= b.left || b.right <= a.left || a.bottom <= b.top || b.bottom <= a.top);
                }
            }
            assert.equal(await page.locator('.institution-hospital-marker').count(), 4);
            assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth), true);
            if (process.env.MAP_SCREENSHOT_DIR) {
                await page.screenshot({ path: path.join(process.env.MAP_SCREENSHOT_DIR, `hospital-map-colors-${width}.png`), fullPage: true });
            }
        } finally { await page.close(); }
    }
});

test('empty institutions retain their empty state without a broken selector', async () => {
    const page = await fixture(390, render([]));
    try {
        assert.equal(await page.locator('[data-map-color-mode]').count(), 0);
        assert.equal(await page.locator('.institution-hospital-marker').count(), 0);
        assert.match(await page.locator('.institution-hospital-map-empty').innerText(), /no tiene hospitales registrados/);
    } finally { await page.close(); }
});
