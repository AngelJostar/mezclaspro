const {test,before,after}=require('node:test');
const assert=require('node:assert/strict');
const {readFileSync}=require('node:fs');
const {execFileSync}=require('node:child_process');
const path=require('node:path');
const {chromium}=require('playwright');
const {buildSync}=require('esbuild');
const root=path.resolve(__dirname,'../..');
const manifest=JSON.parse(readFileSync(path.join(root,'public/build/manifest.json')));
const css=[manifest['resources/css/app.css'].file,...manifest['resources/js/app.js'].css].map(file=>readFileSync(path.join(root,'public/build',file),'utf8')).join('\n');
const script=buildSync({stdin:{contents:"import './resources/js/maintenance-workspace.js'",resolveDir:root},bundle:true,write:false,format:'iife',loader:{'.css':'empty'}}).outputFiles[0].text;
let browser,pages;
before(async()=>{pages=JSON.parse(execFileSync('php',['tests/Browser/fixtures/maintenance-workspace.php'],{cwd:root,encoding:'utf8',maxBuffer:10*1024*1024}));browser=await chromium.launch({headless:true,channel:process.env.PLAYWRIGHT_CHANNEL||undefined});});
after(async()=>{await browser?.close();});
async function fixture(width=1366){
 const page=await browser.newPage({viewport:{width,height:1000}}), state={errors:[],requests:[],fail:false};
 page.on('pageerror',e=>state.errors.push(e.message));
 await page.route('**/*',async route=>{
  const request=route.request(),url=new URL(request.url());
  if(request.method()!=='GET'){
   state.requests.push({url:url.pathname,method:request.method(),body:request.headers()['content-type']?.includes('application/json')?request.postDataJSON():null});
   return route.fulfill(state.fail?{status:422,json:{errors:{asset:['Revisa los datos del servicio.']}}}:{json:{id:1,date:'2026-10-21'}});
  }
  const section=url.searchParams.get('seccion')||'calendario',mode=url.searchParams.get('vista')||'semana';
  const body=pages[section+'-'+mode]||pages[section+'-semana'];
  await route.fulfill({contentType:'text/html',body:`<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf-token" content="test"><style>${css}body{padding:18px;background:#f4f6fa}.admin-content{background:white;border-radius:14px;padding:20px;max-width:1250px;margin:auto}@media(max-width:600px){body{padding:6px}.admin-content{padding:10px}}</style></head><body class="admin-page">${body}<script>${script}</script></body></html>`});
 });
 await page.goto('http://maintenance.test/admin/mantenimiento-calificaciones?fecha=2026-10-21');return{page,state};
}
test('tabs show four distinct screens and calendar modes preserve the selected period',async()=>{
 for(const width of [1366,390]){
  const{page,state}=await fixture(width);
  try{
   const nav=page.getByRole('navigation',{name:'Vistas de mantenimiento'});
   assert.equal(await nav.getByRole('link').count(),4);
   assert.equal(await page.locator('.mw-week').count(),1);
   await page.getByRole('link',{name:'Mes',exact:true}).click();
   assert.equal(await page.locator('.mw-month').count(),1);
   await page.getByRole('link',{name:'Lista',exact:true}).click();
   assert.equal(await page.locator('.mw-calendar').count(),0);
   for(const [label,heading] of [['Catálogo de servicios','Catálogo de servicios'],['Catálogo de proveedores de servicios','Proveedores de servicios'],['Programación y reportes','Programación de servicios y mantenimientos']]){
    await nav.getByRole('link',{name:label,exact:true}).click();
    assert.equal(await page.getByRole('heading',{name:heading,exact:true}).count(),1);
    assert.equal(new URL(page.url()).searchParams.get('fecha'),'2026-10-21');
    if(process.env.MAINTENANCE_SCREENSHOT_DIR)await page.screenshot({path:path.join(process.env.MAINTENANCE_SCREENSHOT_DIR,`workspace-${new URL(page.url()).searchParams.get('seccion')}-${width}.png`),fullPage:true});
   }
   const overflow=await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1);
   assert.equal(overflow,false);
   assert.deepEqual(state.errors,[]);
  }finally{await page.close();}
 }
});
test('service, provider and scheduling dialogs retain failed drafts and submit the correct records',async()=>{
 const{page,state}=await fixture();
 try{
  await page.getByRole('button',{name:'＋ Programar servicio',exact:true}).click();
  let dialog=page.getByRole('dialog',{name:'Programación del servicio',exact:true});
  assert.equal(await dialog.isVisible(),true,JSON.stringify(state.errors));
  await dialog.locator('select[name="service_id"]').selectOption('1');
  await dialog.locator('select[name="provider_id"]').selectOption('1');
  await dialog.locator('select[name="laboratory_id"]').selectOption('1');
  await dialog.getByLabel('Activo / identificación',{exact:true}).fill('Refrigerador RF-99');
  assert.equal(await dialog.getByLabel('Frecuencia',{exact:true}).inputValue(),'Trimestral');
  state.fail=true;await dialog.getByRole('button',{name:'Guardar cambios'}).click();
  await dialog.getByRole('alert').waitFor();
  assert.equal(await dialog.getByLabel('Activo / identificación',{exact:true}).inputValue(),'Refrigerador RF-99');
  state.fail=false;await Promise.all([page.waitForEvent('load'),dialog.getByRole('button',{name:'Guardar cambios'}).click()]);
  assert.equal(state.requests[1].body.asset,'Refrigerador RF-99');
  await page.getByRole('link',{name:'Catálogo de servicios',exact:true}).click();
  await page.getByRole('button',{name:'Editar',exact:true}).first().click();
  dialog=page.getByRole('dialog',{name:'Servicio',exact:true});
  await dialog.getByLabel('Nombre del servicio').fill('Servicio modificado');
  await Promise.all([page.waitForEvent('load'),dialog.getByRole('button',{name:'Guardar cambios'}).click()]);
  assert.equal(state.requests.at(-1).method,'PATCH');
  assert.ok(Array.isArray(state.requests.at(-1).body.provider_ids));
  await page.getByRole('link',{name:'Catálogo de proveedores de servicios',exact:true}).click();
  await page.getByRole('button',{name:'Editar proveedor',exact:true}).click();
  dialog=page.getByRole('dialog',{name:'Proveedor de servicios',exact:true});
  assert.equal(await dialog.getByLabel('Nombre del proveedor').inputValue(),'Proveedor de prueba');
  await dialog.getByRole('button',{name:'Cerrar ventana'}).click();
  await page.getByRole('button',{name:'Adjuntar documento',exact:true}).click();
  assert.equal(await page.getByRole('dialog',{name:'Adjuntar archivo'}).isVisible(),true);
  await page.keyboard.press('Escape');
  assert.deepEqual(state.errors,[]);
 }finally{await page.close();}
});
