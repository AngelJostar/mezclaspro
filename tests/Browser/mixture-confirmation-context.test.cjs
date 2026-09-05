const { test } = require('node:test');
const assert = require('node:assert/strict');
const path = require('node:path');
const { buildSync } = require('esbuild');
const { chromium } = require('playwright');

test('preparation and delivery confirmations show their own context as text and cancel without submitting', async () => {
    const source = buildSync({ entryPoints: [path.resolve(__dirname, '../../resources/js/request-process-confirmations.js')],
        bundle: true, write: false, format: 'iife',
    }).outputFiles[0].text;
    const browser = await chromium.launch({ headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || undefined });
    const page = await browser.newPage();
    try {
        await page.setContent(`<div data-mixture-context="Mezcla #38 | Institucion: A | Hospital: Uno">
            <form data-request-process-form data-confirm-title="Preparar"><button>Preparar</button></form></div>
            <div data-mixture-context="Mezcla #39 | Institucion: B | Hospital: &lt;img src=x onerror=alert(1)&gt;">
            <form data-request-process-form data-confirm-title="Entregar"><button>Entregar</button></form></div>`);
        await page.addScriptTag({ content: `window.submissions=0;
            HTMLFormElement.prototype.submit=function(){window.submissions++};
            window.Swal={fire(options){document.querySelector('[role=dialog]')?.remove();
                const popup=document.createElement('div'); popup.setAttribute('role','dialog');
                document.body.append(popup); options.didOpen(popup); return Promise.resolve({isConfirmed:false});}};
            ${source}
            document.dispatchEvent(new Event('livewire:navigated'));` });
        await page.getByRole('button', { name: 'Preparar', exact: true }).click();
        assert.equal(await page.locator('.mixture-confirmation-context').innerText(), 'Mezcla #38 | Institucion: A | Hospital: Uno');
        await page.getByRole('button', { name: 'Entregar', exact: true }).click();
        assert.equal(await page.locator('.mixture-confirmation-context').innerText(), 'Mezcla #39 | Institucion: B | Hospital: <img src=x onerror=alert(1)>');
        assert.equal(await page.locator('[role=dialog] img').count(), 0);
        assert.equal(await page.evaluate(() => window.submissions), 0);
    } finally { await browser.close(); }
});
