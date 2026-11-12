const { test } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const path = require('node:path');
const { spawn } = require('node:child_process');
const readline = require('node:readline');
const { once } = require('node:events');
const { chromium } = require('playwright');
const root = path.resolve(__dirname, '../..');
const manifest = JSON.parse(readFileSync(path.join(root,'public/build/manifest.json')));
const styles = [manifest['resources/css/app.css'].file,...(manifest['resources/js/app.js'].css || [])].map(file=>readFileSync(path.join(root,'public/build',file),'utf8')).join('\n');
const script = require('esbuild').buildSync({ entryPoints:[path.join(root,'resources/js/agent-center.js')],bundle:true,write:false,format:'iife',loader:{'.css':'empty'} }).outputFiles[0].text;
const livewire = readFileSync(path.join(root,'vendor/livewire/livewire/dist/livewire.js'),'utf8');

async function openFixture(browser, mode, width=1366) {
    const php = spawn(process.env.PHP_BINARY || 'php',['tests/Browser/fixtures/agent-center.php',mode],{cwd:root,stdio:['pipe','pipe','inherit']});
    const lines = readline.createInterface({input:php.stdout}); const queue=[];
    lines.on('line',line=>{const item=queue.shift();if(!item)return;try {const data=JSON.parse(line); data.fixture_error?item.reject(Error(data.fixture_error)):item.resolve(data);}catch(e){item.reject(e);}});
    const send = data=>new Promise((resolve,reject)=>{queue.push({resolve,reject});php.stdin.write(JSON.stringify(data)+'\n');});
    const page=await browser.newPage({viewport:{width,height:950}}); const errors=[]; page.on('pageerror',e=>errors.push(e.message));
    await page.route('**/*',async route=>{
        if (new URL(route.request().url()).pathname==='/livewire/update') return route.fulfill({contentType:'application/json',body:JSON.stringify(await send(route.request().postDataJSON()))});
        const result=await send({type:'render'});
        return route.fulfill({contentType:'text/html',body:`<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><style>${styles}</style>${result.styles}</head><body><main class="admin-content">${result.html}</main><script>${script}</script><script>window.livewireScriptConfig={uri:'http://agents.test/livewire/update',csrf:'test'};</script><script>${livewire}</script><script>window.Livewire.start();</script></body></html>`});
    });
    await page.goto('http://agents.test/panel');
    return {page,errors,async close(){await page.close();const exited=once(php,'exit');php.stdin.end();await exited;lines.close();}};
}

async function exerciseOverview(page) {
    await page.getByRole('button',{name:'Dar accesos a usuarios y roles',exact:true}).click();
    const access=page.getByRole('dialog',{name:'Autorizaciones',exact:true});
    await access.waitFor();
    await access.getByRole('button',{name:'Cancelar',exact:true}).click();
    await access.waitFor({state:'hidden'});
    await page.getByRole('button',{name:'Dar accesos a usuarios y roles',exact:true}).click();
    await access.locator('input[aria-label^="Consultar"]:visible').first().check();
    await access.getByRole('button',{name:'Guardar autorizaciones',exact:true}).click();
    await access.waitFor({state:'hidden'});
    await page.getByText('1 usuario autorizado',{exact:true}).waitFor();
    await page.getByRole('button',{name:'Dar accesos a usuarios y roles',exact:true}).click();
    await access.locator('input[aria-label^="Asignar autorización"]:visible').first().uncheck();
    await access.getByRole('button',{name:'Guardar autorizaciones',exact:true}).click();
    await access.waitFor({state:'hidden'});
    await page.getByText('0 usuarios autorizados',{exact:true}).waitFor();
    for (const [label,field,text] of [
        ['Editar información','Descripción','Descripción editada en prueba de navegador.'],
        ['Editar instrucciones','Instrucciones','Instrucciones editadas en prueba de navegador.']
    ]) {
        await page.getByRole('button',{name:label,exact:true}).click();
        const editor=page.getByRole('dialog',{name:label,exact:true});
        const original=await editor.getByLabel(field,{exact:true}).inputValue();
        await editor.getByLabel(field,{exact:true}).fill(text);
        await editor.getByRole('button',{name:'Guardar',exact:true}).click();
        await editor.waitFor({state:'hidden'});
        await page.getByRole('button',{name:label,exact:true}).click();
        assert.equal(await editor.getByLabel(field,{exact:true}).inputValue(),text);
        await editor.getByLabel(field,{exact:true}).fill(original);
        await editor.getByRole('button',{name:'Guardar',exact:true}).click();
        await editor.waitFor({state:'hidden'});
    }
}

test('drag and keyboard reorder agents, persist after reload, and keep selection separate',async()=>{
    const browser=await chromium.launch({headless:true,channel:process.env.PLAYWRIGHT_CHANNEL||undefined});
    const f=await openFixture(browser,'8');
    try {
        const cards=f.page.locator('[data-agent-select]');
        const initial=await cards.evaluateAll(els=>els.map(el=>el.dataset.agentSelect));
        const first=await cards.nth(0).boundingBox();const second=await cards.nth(1).boundingBox();
        await f.page.mouse.move(first.x+30,first.y+50);await f.page.mouse.down();
        await f.page.mouse.move(second.x+second.width-10,second.y+50,{steps:15});await f.page.mouse.up();
        await f.page.getByText('Orden de agentes guardado para tu usuario.').waitFor();
        assert.equal(await f.page.locator('[data-agent-information]').count(),0);
        await f.page.reload();
        assert.deepEqual((await cards.evaluateAll(els=>els.map(el=>el.dataset.agentSelect))).slice(0,2),[initial[1],initial[0]]);
        await cards.nth(1).press('Alt+ArrowLeft');
        await f.page.getByText('Orden de agentes guardado para tu usuario.').waitFor();
        await f.page.reload();
        assert.deepEqual(await cards.evaluateAll(els=>els.map(el=>el.dataset.agentSelect)),initial);
        assert.deepEqual(f.errors,[]);
    } finally {await f.close();await browser.close();}
});

test('operational agents configure recipients and show a patient comparison on desktop and mobile',async()=>{
    const browser=await chromium.launch({headless:true,channel:process.env.PLAYWRIGHT_CHANNEL||undefined});
    try {for(const width of [1366,390]) {
        const f=await openFixture(browser,'operational',width);
        try {
            await f.page.getByRole('button',{name:'Envío automático de conciliaciones',exact:true}).click();
            await exerciseOverview(f.page);
            await f.page.getByRole('checkbox',{name:'Seleccionar todos los hospitales activos'}).check();
            await f.page.getByText('2 seleccionados',{exact:true}).waitFor();
            await f.page.getByRole('button',{name:'Guardar programación',exact:true}).click();
            await f.page.getByText(/Programación guardada/).waitFor();
            await f.page.reload();
            await f.page.getByRole('button',{name:'Envío automático de conciliaciones',exact:true}).click();
            assert.equal(await f.page.getByRole('checkbox',{name:'Seleccionar todos los hospitales activos'}).isChecked(),true);
            if(process.env.AGENT_SCREENSHOTS) await f.page.screenshot({path:path.join(process.env.AGENT_SCREENSHOTS,`agente-conciliaciones-${width}.png`),fullPage:true});
            await f.page.getByRole('button',{name:'Comparativo de nutriciones por paciente',exact:true}).click();
            await exerciseOverview(f.page);
            await f.page.getByRole('switch',{name:'Estado de Comparativo de nutriciones por paciente'}).click();
            await f.page.getByLabel('Hospital',{exact:true}).selectOption('1');
            await f.page.getByLabel('Expediente / registro',{exact:true}).fill('EXP-PRUEBA');
            await f.page.getByLabel('Desde',{exact:true}).fill('2026-09-01');await f.page.getByLabel('Hasta',{exact:true}).fill('2026-09-30');
            await f.page.getByRole('button',{name:'Analizar nutriciones',exact:true}).click();
            await f.page.getByText('Comparativo por día y nutriente',{exact:true}).waitFor();
            assert.equal(await f.page.getByRole('link',{name:'Descargar PDF',exact:true}).isVisible(),true);
            const overflow=await f.page.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1);
            assert.equal(overflow,false,`page overflows at ${width}`);
            assert.deepEqual(f.errors,[]);
            if(process.env.AGENT_SCREENSHOTS) await f.page.screenshot({path:path.join(process.env.AGENT_SCREENSHOTS,`agente-nutriciones-${width}.png`),fullPage:true});
        } catch(error) {
            if(process.env.AGENT_SCREENSHOTS) await f.page.screenshot({path:path.join(process.env.AGENT_SCREENSHOTS,`agente-error-${width}.png`),fullPage:true});
            console.error((await f.page.locator('.operational-agent').innerText()).slice(0,2200));
            throw error;
        } finally {await f.close();}
    }} finally {await browser.close();}
});
