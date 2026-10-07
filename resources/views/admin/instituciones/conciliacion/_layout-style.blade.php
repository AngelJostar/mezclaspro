<style>
    /* Both modes keep the same page geometry, including the browser scrollbar gutter. */
    html:has([data-conciliation-view]) { scrollbar-gutter: stable; }
    .admin-content:has([data-conciliation-view]) {
        min-height: calc(100dvh - var(--corporate-header-height) - 12px - 1.5rem);
        display: flex;
        flex-direction: column;
    }
    .conciliation-modes { display: flex; flex-wrap: wrap; gap: 8px; margin: 0 0 18px; flex-shrink: 0; }
    .conciliation-modes a {
        display: inline-flex; align-items: center; justify-content: center; width: 118px; min-height: 46px;
        padding: 10px 16px; border: 1px solid #cbd5e1; border-radius: 5px; font-size: 13px; font-weight: 600;
        background: white; color: #3d377b; transition: background-color .12s ease, color .12s ease;
    }
    .conciliation-modes a[aria-current="page"] { color: white; background: #3d377b; border-color: #3d377b; }
    .conciliation-modes a:hover:not([aria-current]) { background: #f4f3fa; }
    .conciliation-view { display: flex; flex-direction: column; flex: 1; min-width: 0; }
    .conciliation-view .conciliation-context { display: flex; align-items: center; gap: 8px; min-height: 32px; margin: 0 0 18px; }
    .conciliation-view .conciliation-context.ht-quick-filters { flex-wrap: nowrap; overflow-x: auto; }
    .conciliation-view .conciliation-context a {
        box-sizing: border-box; flex: 0 0 128px; width: 128px; min-width: 128px; height: 34px; min-height: 34px;
        padding: 6px 10px; font-size: 12px; line-height: 20px; white-space: nowrap;
    }
    .conciliation-context [data-conciliation-count] { font-variant-numeric: tabular-nums; }
    .conciliation-view .conciliation-filters {
        display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) minmax(0, 1.5fr) auto;
        align-items: end; gap: 16px; padding: 20px; margin: 0 0 16px; background: #f7f9fb; border-radius: 6px;
    }
    .conciliation-filters .conciliation-field { display: flex; flex-direction: column; gap: 6px; min-width: 0; max-width: none; }
    .conciliation-filters label { display: block; font-size: 12px; line-height: 18px; margin: 0; }
    .conciliation-filters .conciliation-field :is(input, select) {
        box-sizing: border-box; width: 100%; min-width: 0; height: 42px; min-height: 42px;
        border: 1px solid #d9e0eb; border-radius: 6px; background-color: white; font-size: 13px; line-height: 20px;
        padding: 9px 12px;
    }
    .conciliation-filters .conciliation-field select { padding-right: 32px; }
    .conciliation-filter-detail { display: grid; grid-template-columns: minmax(0, 1fr); gap: 16px; min-width: 0; }
    .conciliation-filter-detail--dates { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .conciliation-filter-actions { display: flex; align-items: center; gap: 16px; min-height: 42px; }
    .conciliation-filter-actions .ht-primary { min-height: 42px; height: 42px; padding: 8px 16px; white-space: nowrap; background: #3d377b; border-color: #3d377b; }
    .conciliation-filter-actions .ht-primary:hover { background: #302b64; }
    .conciliation-filter-actions .ht-clear { display: inline-flex; align-items: center; min-height: 42px; padding: 0; }
    .conciliation-view .conciliation-help { font-size: 13px; line-height: 20px; color: #7081a0; min-height: 20px; margin: 0 0 12px; }
    .conciliation-view .conciliation-table { min-width: 1050px; }
    .conciliation-view .conciliation-table th { height: 54px; padding: 10px; white-space: normal; vertical-align: middle; background: #f7f9fb; font-size: 12px; line-height: 16px; }
    .conciliation-view .conciliation-table td { height: 60px; padding: 10px; font-size: 13px; line-height: 20px; vertical-align: middle; }
    .conciliation-view .conciliation-column { display: flex; align-items: center; gap: 8px; }
    .conciliation-column > span { flex: 1; min-width: 0; }
    .conciliation-column > span > span { display: block; }
    .conciliation-view .conciliation-column-filter { display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; width: 24px; height: 24px; border: 1px solid #cbd5e1; border-radius: 3px; background: white; color: #475569; }
    .conciliation-view .conciliation-column-filter:hover { background: #e2e8f0; }
    .conciliation-view .conciliation-column-filter.bg-blue-100 { background: #dbeafe; border-color: #60a5fa; color: #1d4ed8; }
    .conciliation-view .conciliation-table :is([data-inbox-price], .cp-amount) { display: block; box-sizing: border-box; width: 100%; min-width: 120px; min-height: 36px; border: 1px solid #cbd5e1; border-radius: 4px; padding: 7px 8px; background: white; font: inherit; line-height: 20px; }
    .conciliation-view .conciliation-table .ht-view { min-width: 68px; min-height: 34px; padding: 6px 15px; font-size: 13px; line-height: 20px; }
    .conciliation-view .conciliation-table .cp-primary { display: inline-flex; align-items: center; justify-content: center; min-height: 34px; padding: 6px 15px; font-size: 13px; line-height: 20px; }
    .conciliation-view .conciliation-table [data-period-folio] { line-height: 14px; margin-top: 2px; }
    .conciliation-view .conciliation-table [data-period-folio]:empty { display: none; }
    .conciliation-view > .conciliation-footer { margin-top: auto; padding-top: 16px; font-size: 13px; line-height: 20px; color: #7081a0; }
    .conciliation-footer p { margin: 0; }
    @media (max-width: 1100px) {
        .conciliation-view .conciliation-filters { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .conciliation-filter-actions { justify-content: flex-end; align-self: end; }
        .conciliation-view .conciliation-help { min-height: 40px; }
    }
    @media (max-width: 767px) {
        .admin-content:has([data-conciliation-view]) { min-height: calc(100dvh - var(--corporate-header-height) - 12px - .75rem); }
        .conciliation-view .conciliation-filters { padding: 12px; gap: 14px; }
        .conciliation-filter-detail { grid-column: 1 / -1; }
        .conciliation-filter-actions { grid-column: 1 / -1; justify-content: flex-start; }
        .conciliation-view .conciliation-help { min-height: 60px; }
    }
    @view-transition { navigation: auto; }
    ::view-transition-old(root), ::view-transition-new(root) { animation-duration: 120ms; animation-timing-function: ease-out; }
    @media (prefers-reduced-motion: reduce) {
        .conciliation-modes a { transition: none; }
        ::view-transition-group(*), ::view-transition-old(*), ::view-transition-new(*) { animation-duration: 0s; }
    }
</style>
