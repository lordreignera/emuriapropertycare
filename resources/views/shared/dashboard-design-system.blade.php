<style>
:is(.dash-shell, .ops-shell) {
    display: flex;
    flex-direction: column;
    gap: 18px;
}

:is(.dash-hero, .ops-hero) {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
}

:is(.dash-hero, .ops-hero) h1 {
    color: #071426;
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0 0 4px;
}

:is(.dash-hero, .ops-hero) p,
:is(.dash-panel-head, .ops-panel-head) p {
    color: #667085;
    margin: 0;
    font-size: .88rem;
}

:is(.dash-primary-action, .ops-primary-action, .dash-small-button, .ops-small-button, .dash-icon-button, .ops-icon-button) {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    border-radius: 7px;
    text-decoration: none;
    white-space: nowrap;
}

:is(.dash-primary-action, .ops-primary-action) {
    min-height: 42px;
    padding: 0 18px;
    background: #3157f6;
    color: #ffffff;
    font-weight: 700;
    box-shadow: 0 10px 20px rgba(49, 87, 246, .2);
}

:is(.dash-primary-action, .ops-primary-action):hover {
    color: #ffffff;
    background: #2748d8;
}

.dash-alert {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 16px;
    border: 1px solid #dfe6ef;
    border-radius: 8px;
    background: #ffffff;
    color: #172033;
}

.dash-alert i {
    font-size: 1.45rem;
}

.dash-alert div {
    display: flex;
    flex-direction: column;
    flex: 1;
}

.dash-alert span {
    color: #667085;
    font-size: .86rem;
}

.dash-alert a {
    color: #2458d6;
    font-weight: 700;
    text-decoration: none;
}

.dash-alert-primary {
    border-left: 4px solid #3157f6;
}

.dash-alert-warning {
    border-left: 4px solid #f59e0b;
}

:is(.dash-kpis, .ops-kpis) {
    display: grid;
    grid-template-columns: repeat(5, minmax(132px, 1fr));
    gap: 12px;
}

:is(.dash-kpi, .ops-kpi, .dash-panel, .ops-panel) {
    background: #ffffff;
    border: 1px solid #dfe6ef;
    border-radius: 8px;
    box-shadow: 0 6px 18px rgba(16, 24, 40, .055);
}

:is(.dash-kpi, .ops-kpi) {
    position: relative;
    overflow: hidden;
    min-height: 108px;
    padding: 12px 13px;
    display: grid;
    grid-template-columns: 36px minmax(0, 1fr);
    grid-template-rows: minmax(0, 1fr) auto;
    column-gap: 11px;
    row-gap: 6px;
    align-items: start;
    border-color: #d8e2ef;
    box-shadow: 0 8px 18px rgba(15, 23, 42, .045), inset 0 1px 0 rgba(255, 255, 255, .9);
}

:is(.dash-kpi, .ops-kpi)::before {
    content: "";
    position: absolute;
    inset: 0 0 auto;
    height: 3px;
    background: linear-gradient(90deg, #3157f6, #14b8a6);
    opacity: .68;
}

:is(.dash-kpi, .ops-kpi) > div {
    grid-column: 2;
    grid-row: 1;
    min-width: 0;
}

:is(.dash-kpi, .ops-kpi) small {
    display: block;
    color: #344054;
    font-size: .66rem;
    font-weight: 700;
    text-transform: uppercase;
}

:is(.dash-kpi, .ops-kpi) strong {
    display: block;
    color: #071426;
    font-size: 1.45rem;
    line-height: 1.1;
    margin-top: 3px;
}

:is(.dash-kpi, .ops-kpi) span {
    color: #667085;
    font-size: .74rem;
}

:is(.dash-kpi, .ops-kpi) a {
    grid-column: 2;
    grid-row: 2;
    margin-top: 0;
    color: #2458d6;
    font-size: .74rem;
    font-weight: 700;
    text-decoration: none;
}

:is(.dash-icon, .ops-icon) {
    grid-column: 1;
    grid-row: 1 / span 2;
    width: 34px;
    height: 34px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 auto;
    border-radius: 8px;
    border: 1px solid rgba(15, 23, 42, .04);
}

:is(.dash-icon, .ops-icon) i {
    font-size: 1.05rem;
}

:is(.dash-icon-blue, .dash-icon-primary, .ops-icon-blue, .ops-icon-primary) {
    background: #e8f0ff;
    color: #3157f6;
}

:is(.dash-icon-green, .dash-icon-success, .ops-icon-green, .ops-icon-success) {
    background: #dcfce7;
    color: #16a34a;
}

:is(.dash-icon-purple, .dash-icon-warning, .ops-icon-purple, .ops-icon-warning) {
    background: #f3e8ff;
    color: #9333ea;
}

:is(.dash-icon-orange, .dash-icon-danger, .ops-icon-orange, .ops-icon-danger) {
    background: #ffedd5;
    color: #f97316;
}

:is(.dash-icon-cyan, .dash-icon-info, .ops-icon-cyan, .ops-icon-info, .ops-icon-secondary) {
    background: #ccfbf1;
    color: #0f766e;
}

:is(.dash-grid, .ops-grid) {
    display: grid;
    grid-template-columns: minmax(0, 2fr) minmax(300px, 1fr);
    gap: 16px;
    align-items: start;
}

:is(.dash-grid-stacked, .ops-grid-stacked) {
    align-items: start;
}

:is(.dash-column, .ops-column) {
    display: flex;
    min-width: 0;
    flex-direction: column;
    gap: 16px;
}

:is(.dash-panel, .ops-panel) {
    padding: 16px;
    min-width: 0;
}

:is(.dash-panel-wide, .ops-panel-wide) {
    grid-row: span 2;
}

:is(.dash-panel-head, .ops-panel-head) {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 14px;
}

:is(.dash-panel-head, .ops-panel-head) h2 {
    color: #071426;
    font-size: 1rem;
    font-weight: 700;
    margin: 0 0 3px;
}

:is(.dash-small-button, .ops-small-button) {
    min-height: 30px;
    padding: 0 10px;
    border: 1px solid #dbe3ef;
    background: #f8fbff;
    color: #2458d6;
    font-size: .78rem;
    font-weight: 700;
}

:is(.dash-table-wrap, .ops-table-wrap) {
    overflow-x: auto;
}

:is(.dash-table, .ops-table) {
    width: 100%;
    border-collapse: collapse;
}

:is(.dash-table, .ops-table) th {
    padding: 12px 14px;
    background: #f4f7fb;
    color: #344054;
    font-size: .72rem;
    font-weight: 800;
    text-transform: uppercase;
    border-bottom: 1px solid #dfe6ef;
}

:is(.dash-table, .ops-table) td {
    padding: 14px;
    color: #172033;
    border-bottom: 1px solid #edf2f7;
    font-size: .86rem;
    vertical-align: middle;
}

:is(.dash-property-cell, .ops-property-cell) {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 220px;
}

:is(.dash-property-cell, .ops-property-cell) > span {
    width: 44px;
    height: 44px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    background: #e8f0ff;
    color: #3157f6;
    font-size: 1.35rem;
}

:is(.dash-property-cell, .dash-action-list, .dash-mini-list, .dash-activity, .ops-property-cell, .ops-action-list, .ops-mini-list, .ops-activity) strong {
    display: block;
    color: #172033;
    font-size: .86rem;
    font-weight: 700;
}

:is(.dash-property-cell, .dash-action-list, .dash-mini-list, .dash-activity, .ops-property-cell, .ops-action-list, .ops-mini-list, .ops-activity) small {
    display: block;
    color: #667085;
    font-size: .76rem;
}

:is(.dash-pill, .ops-pill) {
    display: inline-flex;
    align-items: center;
    min-height: 24px;
    padding: 0 9px;
    border-radius: 999px;
    font-size: .72rem;
    font-weight: 800;
}

:is(.dash-pill-blue, .ops-pill-blue) {
    color: #3157f6;
    background: #e8f0ff;
}

:is(.dash-pill-green, .ops-pill-green) {
    color: #15803d;
    background: #dcfce7;
}

.ops-pill-orange {
    color: #c2410c;
    background: #ffedd5;
}

:is(.dash-icon-button, .ops-icon-button) {
    width: 34px;
    height: 34px;
    border: 1px solid #dbe3ef;
    background: #ffffff;
    color: #2458d6;
}

:is(.dash-action-list, .dash-mini-list, .dash-activity-list, .ops-action-list, .ops-mini-list, .ops-activity-list) {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

:is(.dash-action-list a, .dash-mini-list a, .dash-activity, .ops-action-list a, .ops-mini-list a, .ops-activity) {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    border: 1px solid #eef2f7;
    border-radius: 8px;
    background: #ffffff;
    color: inherit;
    text-decoration: none;
}

:is(.dash-action-list a, .dash-mini-list a, .dash-activity, .ops-action-list a, .ops-mini-list a, .ops-activity) > div {
    flex: 1;
    min-width: 0;
}

.ops-action-list b {
    min-width: 32px;
    text-align: center;
    color: #071426;
    font-size: 1rem;
}

:is(.dash-action-list, .ops-action-list) .mdi-chevron-right {
    color: #98a2b3;
}

.dash-donut-row {
    display: flex;
    align-items: center;
    gap: 22px;
}

.dash-donut {
    width: 118px;
    height: 118px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    flex: 0 0 auto;
    border-radius: 50%;
    background:
        radial-gradient(circle at center, #ffffff 0 52%, transparent 53%),
        conic-gradient(#3157f6 0 calc(var(--active) * 1%), #f59e0b calc(var(--active) * 1%) calc((var(--active) + var(--inspect)) * 1%), #e5e7eb 0);
}

.dash-donut strong {
    color: #071426;
    font-size: 1.35rem;
}

.dash-donut span {
    color: #667085;
    font-size: .75rem;
}

.dash-legend {
    display: flex;
    flex-direction: column;
    gap: 9px;
    color: #667085;
    font-size: .82rem;
}

.dash-legend span {
    display: flex;
    align-items: center;
    gap: 8px;
}

.dash-legend strong {
    margin-left: auto;
    color: #172033;
}

.legend-dot {
    width: 8px;
    height: 8px;
    display: inline-flex;
    border-radius: 999px;
}

.legend-blue { background: #3157f6; }
.legend-orange { background: #f59e0b; }
.legend-gray { background: #cbd5e1; }

:is(.dash-activity, .ops-activity) time {
    color: #667085;
    font-size: .72rem;
    white-space: nowrap;
}

:is(.dash-empty, .ops-empty) {
    min-height: 155px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    gap: 6px;
    color: #667085;
    text-align: center;
}

.dash-empty {
    min-height: 210px;
}

:is(.dash-empty, .ops-empty) i {
    color: #cbd5e1;
    font-size: 3rem;
}

:is(.dash-empty, .ops-empty) strong {
    color: #172033;
}

:is(.dash-empty-small, .ops-empty-row) {
    min-height: 90px;
}

@media (max-width: 1280px) {
    :is(.dash-kpis, .ops-kpis) {
        grid-template-columns: repeat(3, minmax(150px, 1fr));
    }
}

@media (max-width: 991px) {
    :is(.dash-hero, .ops-hero, .dash-donut-row) {
        align-items: flex-start;
        flex-direction: column;
    }

    :is(.dash-kpis, .ops-kpis, .dash-grid, .ops-grid) {
        grid-template-columns: 1fr;
    }

    :is(.dash-panel-wide, .ops-panel-wide) {
        grid-row: auto;
    }
}

@media (max-width: 575px) {
    :is(.dash-primary-action, .ops-primary-action) {
        width: 100%;
    }

    :is(.dash-panel-head, .ops-panel-head, .dash-alert) {
        flex-direction: column;
    }

    .dash-alert {
        align-items: flex-start;
    }
}
</style>
