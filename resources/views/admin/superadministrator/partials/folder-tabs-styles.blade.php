<style>
    .superadministrator-folders {
        min-width: 0;
        margin-top: 28px;
    }

    .superadministrator-folder-tabs {
        display: flex;
        align-items: flex-end;
        gap: 2px;
        min-height: 52px;
        padding: 4px 18px 0 18px;
        overflow-x: auto;
        scrollbar-width: thin;
        scrollbar-color: #b8cde9 #f4f8ff;
    }

    .superadministrator-folder-tab {
        position: relative;
        isolation: isolate;
        display: inline-flex;
        flex: 0 0 auto;
        align-items: center;
        justify-content: center;
        gap: 10px;
        min-height: 46px;
        padding: 12px 26px;
        border: 0;
        background: transparent;
        color: #24548b;
        font-size: 13px;
        font-weight: 600;
        white-space: nowrap;
        cursor: pointer;
    }

    .superadministrator-folder-tab::before {
        position: absolute;
        z-index: -1;
        inset: 0;
        content: '';
        transform: skewX(20deg);
        transform-origin: bottom left;
        border: 1px solid #c7daf3;
        border-bottom: 0;
        border-radius: 10px 10px 0 0;
        background: linear-gradient(180deg, #edf5ff, #e1efff);
    }

    .superadministrator-folder-tab:hover::before {
        background: #d9eaff;
        border-color: #9dbfe9;
    }

    .superadministrator-folder-tab[aria-selected="true"] {
        z-index: 2;
        color: #164f9f;
    }

    .superadministrator-folder-tab[aria-selected="true"]::before {
        border-color: #4984d3;
        background: #ffffff;
    }

    .superadministrator-folder-tab:focus-visible {
        outline: 2px solid #2563eb;
        outline-offset: -5px;
        border-radius: 6px;
    }

    .superadministrator-folder-count {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 20px;
        height: 20px;
        padding: 0 5px;
        border-radius: 999px;
        background: #d4e6ff;
        color: #1f5eae;
        font-size: 10px;
        font-weight: 700;
    }

    .superadministrator-folder-tab[aria-selected="true"] .superadministrator-folder-count {
        background: #edf4ff;
    }

    .superadministrator-folder-panel {
        min-width: 0;
        padding: 22px 16px 16px;
        border: 1px solid #d4e2f5;
        border-top-color: #e4edf9;
        border-radius: 0 12px 12px 12px;
        background: linear-gradient(180deg, #f8fbff 0, #ffffff 80px);
    }

    .superadministrator-folder-panel:focus-visible {
        outline: 2px solid #4984d3;
        outline-offset: 2px;
    }

    .superadministrator-folder-panel > .agent-center {
        margin-top: 0;
    }

    @media (max-width: 640px) {
        .superadministrator-folder-tab {
            min-height: 44px;
            padding: 12px 20px;
            font-size: 12px;
        }

        .superadministrator-folder-panel {
            padding: 14px 8px 8px;
        }
    }
</style>
