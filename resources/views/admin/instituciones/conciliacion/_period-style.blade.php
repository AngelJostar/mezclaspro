<style>
    .cp-primary, .cp-secondary { border-radius: 6px; padding: .65rem 1.1rem; font-size: .85rem; font-weight: 600; white-space: nowrap; border: 1px solid transparent; }
    .cp-primary { background: #009b79; color: white; }
    .cp-secondary { background: white; color: #3d377b; border-color: #3d377b; }
    .cp-primary:hover:not(:disabled) { background: #007c63; }
    .cp-primary:disabled { opacity: .55; cursor: default; }
    .cp-panel button:focus-visible { outline: 2px solid #3d377b; outline-offset: 3px; }
    .cp-table small { display: block; margin-top: .25rem; font-size: .7rem; }
    .conciliation-view .cp-table.conciliation-table { min-width: 1550px; }
    .cp-reconciliation-status { display: inline-flex; justify-content: center; align-items: center; min-height: 30px; padding: 5px 12px; border-radius: 999px; font-size: 12px; font-weight: 600; white-space: nowrap; background: #fef3c7; color: #92400e; }
    .cp-reconciliation-status[data-period-status="Aceptada"] { background: #d1fae5; color: #047857; }
    .cp-reconciliation-status[data-period-status="Con cambios"] { background: #fee2e2; color: #b91c1c; }
    .cp-reconciliation-status:disabled { cursor: default; }
    .cp-table .ht-view[data-period-observations]:disabled { background: #e5e7eb; color: #6b7280; cursor: not-allowed; opacity: 1; }
    .cp-authorize { min-height: 34px; font-size: 12px; padding: 6px 10px; }
    .cp-authorize:disabled { opacity: .45; cursor: default; }
    .cp-observations-table { min-width: 850px; width: 100%; }
    .cp-observations-table th, .cp-observations-table td { padding: 12px; vertical-align: top; }
    .cp-observations-table th { position: sticky; top: 0; background: #f6f8fa; }
    .cp-observation-text { white-space: pre-wrap; overflow-wrap: anywhere; }
    .cp-authorized { color: #047857; margin: 16px 0; }
    .cp-note { font-size: .8rem; color: #7081a0; margin: 1rem 0; }
    .cp-dialog { width: min(1280px, calc(100vw - 2rem)); max-width: none; max-height: calc(100dvh - 2rem); border: 1px solid #dbe2ee; border-radius: 12px; padding: 1.5rem; color: #263660; box-shadow: 0 20px 60px #13204744; }
    .cp-dialog::backdrop { background: #111e43a6; }
    .cp-dialog header { display: flex; justify-content: space-between; align-items: center; }
    .cp-dialog h2 { font-size: 1.45rem; font-weight: 650; color: #1e285f; }
    .cp-close { font-size: 1.8rem; padding: 0 .3rem; line-height: 1; }
    .cp-subtitle { margin: .4rem 0 1rem; }
    .cp-institution { max-width: 70%; margin-bottom: 1rem; }
    .cp-institution > span { font-size: .75rem; color: #7081a0; }
    .cp-institution p { border: 1px solid #d3dbe7; border-radius: 5px; padding: .6rem; }
    .cp-summary { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin: 1.25rem 0; }
    .cp-summary > div { border: 1px solid #dbeafe; border-radius: 10px; background: #eff6ff; padding: 1.1rem; }
    .cp-summary span, .cp-summary strong, .cp-summary small { display: block; }
    .cp-summary span { font-size: .85rem; font-weight: 600; }
    .cp-summary strong { font-size: 1.45rem; margin: .15rem 0; }
    .cp-summary .cp-excluded { background: #fff5f5; border-color: #fee2e2; color: #dc2626; }
    .cp-summary .cp-new { background: #effbf6; border-color: #d1fae5; color: #065f46; }
    .cp-summary small { font-size: .8rem; }
    .cp-detail-scroll { overflow: auto; max-height: 43dvh; }
    .cp-detail-table { min-width: 1040px; }
    .cp-detail-table th { position: sticky; top: 0; z-index: 1; background: #f6f8fa; white-space: normal; }
    .cp-detail-table td { padding: .8rem .5rem; vertical-align: top; }
    .cp-detail-table th:nth-child(4), .cp-detail-table td:nth-child(4) { min-width: 145px; }
    .cp-line + .cp-line { margin-top: .6rem; }
    .cp-toggle { display: inline-flex; gap: 8px; white-space: nowrap; }
    .cp-toggle button { border-radius: 999px; padding: 5px 10px; min-width: 36px; font-size: 11px; font-weight: 600; background: #d1fae5; color: #047857; }
    .cp-toggle [data-period-choice="0"] { background: #fee2e2; color: #dc2626; }
    .cp-toggle [data-period-choice="1"][aria-pressed="true"] { background: #009b79; color: white; }
    .cp-toggle [data-period-choice="0"][aria-pressed="true"] { background: #dc2626; color: white; }
    .cp-status { display: inline-flex; padding: 4px 9px; border-radius: 999px; font-size: 11px; background: #fef3c7; color: #92400e; }
    .cp-status[data-status="Conciliado"] { background: #d1fae5; color: #047857; }
    .cp-status[data-status="Enviada"] { background: #ede9fe; color: #6d28d9; }
    .cp-status[data-status="Recibida"] { background: #dbeafe; color: #1d4ed8; }
    .cp-dialog footer { display: flex; justify-content: space-between; gap: 1rem; padding-top: 1rem; border-top: 1px solid #dbe2ee; }
    .cp-error { color: #b91c1c; margin: 1rem 0; }
    [data-period-feedback] { margin: 1rem 0; }
    .cp-list-actions { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; }
    .cp-list-actions .conciliation-context { min-width: 0; }
    .cp-create-launch { height: 34px; padding: 0 16px; flex-shrink: 0; }
    .cp-created { margin-bottom: 12px; color: #047857; }
    .cp-create-dialog { margin: auto; width: min(1480px, calc(100vw - 32px)); background: white; }
    .cp-create-dialog [hidden] { display: none !important; }
    .cp-create-filters { display: grid; grid-template-columns: minmax(0, 1.4fr) minmax(0, 1.4fr) minmax(0, 1fr) minmax(0, 1fr) auto; gap: 12px; align-items: end; padding: 0 0 16px; border-bottom: 1px solid #dbe2ee; }
    .cp-create-filters label { display: flex; flex-direction: column; gap: 5px; font-size: 12px; }
    .cp-required { color: #b91c1c; font-weight: 700; }
    .cp-create-filters input, .cp-create-filters select { width: 100%; min-width: 0; height: 36px; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 5px; background-color: white; color: #263660; font-size: 12px; }
    .cp-create-filters select { padding-right: 30px; }
    .cp-create-filters .cp-secondary { min-height: 36px; padding: 6px 12px; font-size: 12px; }
    .cp-create-results { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin: 16px 0; }
    .cp-create-results h3 { font-size: 18px; font-weight: 600; }
    .cp-create-results > span { font-size: 13px; }
    .cp-create-scroll { overflow: auto; max-height: 48dvh; border-bottom: 1px solid #dbe2ee; }
    .cp-create-table { min-width: 1040px; width: 100%; }
    .cp-create-table th { position: sticky; top: 0; z-index: 1; background: #f6f8fa; }
    .cp-create-table th, .cp-create-table td { padding: 12px 10px; white-space: normal; font-size: 12px; }
    .cp-create-table td:nth-child(7) { white-space: nowrap; }
    .cp-create-table th:last-child, .cp-create-table td:last-child { width: 44px; min-width: 44px; max-width: 44px; box-sizing: border-box; text-align: center; }
    .cp-create-bulk { flex-shrink: 0; }
    .cp-create-bulk:disabled { opacity: .5; cursor: default; }
    .cp-create-table tr[data-selected="true"] { background: #e9f9f7; }
    .cp-create-table small { display: block; color: #7081a0; font-size: 11px; }
    .cp-create-dialog :is([data-create-choice], [data-create-toggle-all]) { width: 20px; height: 20px; color: #009b79; accent-color: #009b79; border-radius: 3px; cursor: pointer; }
    .cp-create-summary { display: flex; flex-wrap: wrap; gap: 6px 14px; width: fit-content; max-width: 100%; padding: 8px 12px; background: #e9f9f7; border-radius: 6px; margin: 10px 0; align-items: center; }
    .cp-create-summary > div { display: flex; flex-wrap: wrap; align-items: baseline; gap: 4px 6px; }
    .cp-create-summary > div + div { border-left: 1px solid #c7e6e0; padding-left: 14px; }
    .cp-create-summary span { font-size: 11px; line-height: 16px; }
    .cp-create-summary strong { font-size: 12px; line-height: 18px; font-weight: 600; }
    .cp-create-dialog footer { align-items: center; }
    .cp-create-dialog footer p { font-size: 12px; color: #7081a0; text-align: center; }
    @media(max-width: 900px) { .cp-create-filters { grid-template-columns: repeat(2, minmax(0, 1fr)); } .cp-create-filters .cp-secondary { justify-self: start; } }
    @media(max-width: 520px) { .cp-list-actions { flex-wrap: wrap; margin-bottom: 12px; } .cp-list-actions .conciliation-context { margin-bottom: 0; } .cp-create-summary > div + div { border: 0; padding-left: 0; } .cp-create-results, .cp-create-dialog footer { flex-wrap: wrap; } .cp-create-dialog footer p { order: 3; width: 100%; } }
    @media(max-width: 900px) { .cp-dialog { padding: 1rem; } .cp-summary { gap: .5rem; } .cp-summary > div { padding: .7rem; } .cp-summary strong { font-size: 1.1rem; } }
    @media(max-width: 520px) { .cp-summary { grid-template-columns: 1fr; } .cp-summary > div { display: flex; flex-wrap: wrap; gap: .4rem; align-items: center; } .cp-summary small { margin-left: auto; } .cp-institution { max-width: 100%; } }
</style>
