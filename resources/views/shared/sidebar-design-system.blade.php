<style>
    :root {
        --etogo-sidebar-width: 280px;
        --etogo-sidebar-collapsed-width: 84px;
        --etogo-topbar-height: 72px;
        --etogo-page-bg: #f4f7fb;
        --etogo-sidebar-bg: #031b46;
        --etogo-sidebar-bg-deep: #021437;
        --etogo-sidebar-line: rgba(255, 255, 255, .09);
        --etogo-sidebar-text: #eaf2ff;
        --etogo-sidebar-muted: #93a8c7;
        --etogo-sidebar-active: #465cff;
        --etogo-sidebar-active-2: #5b6cff;
    }

    html,
    body,
    body.light-theme {
        min-height: 100%;
        background: var(--etogo-page-bg) !important;
        overflow-x: hidden !important;
    }

    .admin-client-sidebar,
    .client-clean-sidebar,
    body .admin-client-sidebar,
    body .client-clean-sidebar,
    body.light-theme .admin-client-sidebar,
    body.light-theme .client-clean-sidebar {
        width: 280px !important;
        background:
            linear-gradient(180deg, rgba(8, 43, 103, .96) 0%, var(--etogo-sidebar-bg) 42%, var(--etogo-sidebar-bg-deep) 100%) !important;
        border-right: 1px solid rgba(2, 6, 23, .45) !important;
        box-shadow: 10px 0 28px rgba(2, 6, 23, .18) !important;
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        bottom: 0 !important;
        height: 100vh !important;
        display: flex !important;
        flex-direction: column !important;
        overflow-y: hidden !important;
        overflow-x: hidden !important;
        z-index: 999 !important;
        scrollbar-width: thin;
        scrollbar-color: rgba(255,255,255,.22) transparent;
        transition: width .18s ease, transform .2s ease !important;
    }

    .admin-client-sidebar::-webkit-scrollbar,
    .client-clean-sidebar::-webkit-scrollbar {
        width: 6px;
    }

    .admin-client-sidebar::-webkit-scrollbar-track,
    .client-clean-sidebar::-webkit-scrollbar-track {
        background: transparent;
    }

    .admin-client-sidebar::-webkit-scrollbar-thumb,
    .client-clean-sidebar::-webkit-scrollbar-thumb {
        background-color: rgba(255,255,255,.22);
        border-radius: 3px;
    }

    .container-scroller {
        display: block !important;
        width: 100% !important;
        max-width: none !important;
        min-width: 0 !important;
        min-height: 100vh !important;
        background: var(--etogo-page-bg) !important;
        overflow-x: hidden !important;
    }

    .container-scroller .page-body-wrapper,
    .container-scroller .page-body-wrapper.container-fluid {
        display: flex !important;
        margin-left: var(--etogo-sidebar-width) !important;
        width: calc(100vw - var(--etogo-sidebar-width)) !important;
        max-width: none !important;
        min-width: 0 !important;
        min-height: 100vh !important;
        background: var(--etogo-page-bg) !important;
        padding-top: 0 !important;
        padding-left: 0 !important;
        padding-right: 0 !important;
        transition: margin-left .18s ease, width .18s ease !important;
    }

    .container-scroller .page-body-wrapper .main-panel {
        width: 100% !important;
        max-width: none !important;
        min-width: 0 !important;
        flex: 0 0 100% !important;
        margin-left: 0 !important;
        min-height: 100vh !important;
        padding-top: var(--etogo-topbar-height) !important;
        background: var(--etogo-page-bg) !important;
        overflow-x: hidden !important;
    }

    .container-scroller .page-body-wrapper .content-wrapper {
        width: 100% !important;
        max-width: none !important;
        min-width: 0 !important;
        min-height: calc(100vh - var(--etogo-topbar-height)) !important;
        box-sizing: border-box !important;
        background: var(--etogo-page-bg) !important;
    }

    .navbar.fixed-top {
        left: var(--etogo-sidebar-width) !important;
        right: 0 !important;
        width: auto !important;
        max-width: none !important;
        transition: left .18s ease !important;
    }

    @media (min-width: 992px) {
        body.sidebar-minimized .admin-client-sidebar,
        body.sidebar-minimized .client-clean-sidebar,
        body.light-theme.sidebar-minimized .admin-client-sidebar,
        body.light-theme.sidebar-minimized .client-clean-sidebar {
            width: var(--etogo-sidebar-collapsed-width) !important;
        }

        body.sidebar-minimized .container-scroller .page-body-wrapper,
        body.sidebar-minimized .container-scroller .page-body-wrapper.container-fluid {
            margin-left: var(--etogo-sidebar-collapsed-width) !important;
            width: calc(100vw - var(--etogo-sidebar-collapsed-width)) !important;
        }

        body.sidebar-minimized .navbar.fixed-top {
            left: var(--etogo-sidebar-collapsed-width) !important;
        }

        body.sidebar-minimized .admin-client-sidebar .admin-client-sidebar-inner,
        body.sidebar-minimized .client-clean-sidebar .client-sidebar-inner {
            padding: 18px 10px 16px !important;
        }

        body.sidebar-minimized .admin-client-sidebar .admin-client-brand a,
        body.sidebar-minimized .client-clean-sidebar .client-brand a {
            justify-content: center !important;
            padding: 0 !important;
        }

        body.sidebar-minimized .admin-client-sidebar .admin-client-brand a::after,
        body.sidebar-minimized .client-clean-sidebar .client-brand a::after,
        body.sidebar-minimized .admin-client-sidebar .admin-client-section-title,
        body.sidebar-minimized .client-clean-sidebar .client-section-title,
        body.sidebar-minimized .admin-client-sidebar .admin-client-arrow,
        body.sidebar-minimized .client-clean-sidebar .client-arrow,
        body.sidebar-minimized .admin-client-sidebar .admin-client-submenu,
        body.sidebar-minimized .client-clean-sidebar .client-submenu,
        body.sidebar-minimized .admin-client-sidebar .admin-client-link > span:not(.admin-client-summary-left):not(.admin-client-badge),
        body.sidebar-minimized .client-clean-sidebar .client-link > span:not(.client-summary-left):not(.client-badge),
        body.sidebar-minimized .admin-client-sidebar .admin-client-summary-left > span,
        body.sidebar-minimized .client-clean-sidebar .client-summary-left > span,
        body.sidebar-minimized .admin-client-sidebar .admin-sidebar-collapse-label,
        body.sidebar-minimized .admin-client-sidebar .admin-client-version-label,
        body.sidebar-minimized .client-clean-sidebar .client-version-label {
            display: none !important;
        }

        body.sidebar-minimized .admin-client-sidebar .admin-client-link,
        body.sidebar-minimized .client-clean-sidebar .client-link {
            justify-content: center !important;
            padding: .55rem 0 !important;
            gap: 0 !important;
        }

        body.sidebar-minimized .admin-client-sidebar .admin-client-summary-left,
        body.sidebar-minimized .client-clean-sidebar .client-summary-left {
            flex: 0 0 auto !important;
            justify-content: center !important;
            gap: 0 !important;
        }

        body.sidebar-minimized .admin-client-sidebar .admin-client-icon,
        body.sidebar-minimized .admin-client-sidebar .admin-client-icon-dashboard,
        body.sidebar-minimized .admin-client-sidebar .admin-client-icon-property,
        body.sidebar-minimized .admin-client-sidebar .admin-client-icon-services,
        body.sidebar-minimized .admin-client-sidebar .admin-client-icon-projects,
        body.sidebar-minimized .admin-client-sidebar .admin-client-icon-billing,
        body.sidebar-minimized .admin-client-sidebar .admin-client-icon-reports,
        body.sidebar-minimized .admin-client-sidebar .admin-client-icon-access,
        body.sidebar-minimized .admin-client-sidebar .admin-client-icon-settings,
        body.sidebar-minimized .client-clean-sidebar .client-link i {
            margin: 0 !important;
        }

        body.sidebar-minimized .admin-client-version-bar,
        body.sidebar-minimized .client-version-bar {
            padding: 10px 10px 14px !important;
        }

        body.sidebar-minimized .admin-client-version-button,
        body.sidebar-minimized .client-version-button {
            justify-content: center !important;
            padding: 4px !important;
        }

        body.sidebar-minimized .admin-sidebar-collapse-button {
            justify-content: center !important;
            padding: 8px !important;
        }

        body.sidebar-minimized .admin-client-version-badge,
        body.sidebar-minimized .client-version-badge {
            padding: 3px 8px !important;
        }
    }

    @media (max-width: 991.98px) {
        .admin-client-sidebar,
        .client-clean-sidebar,
        body.light-theme .admin-client-sidebar,
        body.light-theme .client-clean-sidebar {
            transform: translateX(-100%) !important;
            transition: transform .2s ease !important;
        }

        body.sidebar-mobile-open .admin-client-sidebar,
        body.sidebar-mobile-open .client-clean-sidebar {
            transform: translateX(0) !important;
        }

        .container-scroller .page-body-wrapper,
        .container-scroller .page-body-wrapper.container-fluid {
            margin-left: 0 !important;
            width: 100vw !important;
            max-width: none !important;
            flex-basis: 100vw !important;
        }

        .navbar.fixed-top {
            left: 0 !important;
            right: 0 !important;
            width: auto !important;
            max-width: none !important;
        }
    }

    .admin-client-sidebar,
    .admin-client-sidebar *,
    .client-clean-sidebar,
    .client-clean-sidebar * {
        font-family: var(--emuria-font-sans, Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif) !important;
        letter-spacing: 0 !important;
    }

    .admin-client-sidebar .admin-client-sidebar-inner,
    .client-clean-sidebar .client-sidebar-inner {
        flex: 1 1 auto !important;
        min-height: 0 !important;
        overflow-y: auto !important;
        overflow-x: hidden !important;
        padding: 18px 12px 16px !important;
        scrollbar-width: thin;
        scrollbar-color: rgba(255,255,255,.22) transparent;
    }

    .admin-client-sidebar .admin-client-sidebar-inner::-webkit-scrollbar,
    .client-clean-sidebar .client-sidebar-inner::-webkit-scrollbar {
        width: 6px;
    }

    .admin-client-sidebar .admin-client-sidebar-inner::-webkit-scrollbar-track,
    .client-clean-sidebar .client-sidebar-inner::-webkit-scrollbar-track {
        background: transparent;
    }

    .admin-client-sidebar .admin-client-sidebar-inner::-webkit-scrollbar-thumb,
    .client-clean-sidebar .client-sidebar-inner::-webkit-scrollbar-thumb {
        background-color: rgba(255,255,255,.22);
        border-radius: 3px;
    }

    .admin-client-sidebar .admin-client-brand,
    .client-clean-sidebar .client-brand {
        display: flex !important;
        justify-content: center !important;
        padding: 0 0 18px !important;
    }

    .admin-client-sidebar .admin-client-brand a,
    .client-clean-sidebar .client-brand a {
        width: 100% !important;
        height: 54px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: flex-start !important;
        gap: 10px !important;
        background: transparent !important;
        border-radius: 8px !important;
        box-shadow: none !important;
        text-decoration: none !important;
        padding: 0 8px !important;
    }

    .admin-client-sidebar .admin-client-brand a::after,
    .client-clean-sidebar .client-brand a::after {
        content: "ETOGO";
        color: #ffffff !important;
        font-size: 1rem !important;
        font-weight: 800 !important;
        letter-spacing: .02em !important;
    }

    .admin-client-sidebar .admin-client-brand-logo,
    .client-clean-sidebar .client-brand-logo {
        width: 38px !important;
        height: 38px !important;
        object-fit: contain !important;
        background: #ffffff !important;
        border: 1px solid rgba(255,255,255,.22) !important;
        border-radius: 8px !important;
        padding: 3px !important;
        filter: none !important;
        box-shadow: 0 6px 16px rgba(2, 6, 23, .22) !important;
    }

    .admin-client-sidebar .admin-client-user,
    .client-clean-sidebar .client-user {
        display: flex !important;
        align-items: center !important;
        gap: .7rem !important;
        margin-bottom: .6rem !important;
        padding: 12px !important;
        background: rgba(255,255,255,.06) !important;
        border: 1px solid var(--etogo-sidebar-line) !important;
        border-radius: 8px !important;
        box-shadow: none !important;
    }

    .admin-client-sidebar .admin-client-avatar,
    .client-clean-sidebar .client-avatar {
        width: 34px !important;
        height: 34px !important;
        border-radius: 999px !important;
        object-fit: cover !important;
    }

    .admin-client-sidebar .admin-client-name,
    .client-clean-sidebar .client-name {
        color: var(--etogo-sidebar-text) !important;
        font-size: .9rem !important;
        font-weight: 600 !important;
        line-height: 1.15 !important;
    }

    .admin-client-sidebar .admin-client-role,
    .admin-client-sidebar .admin-client-section-title,
    .client-clean-sidebar .client-role,
    .client-clean-sidebar .client-section-title {
        color: var(--etogo-sidebar-muted) !important;
    }

    .admin-client-sidebar .admin-client-role,
    .client-clean-sidebar .client-role {
        font-size: .8rem !important;
        line-height: 1.2 !important;
    }

    .admin-client-sidebar .admin-client-section-title,
    .client-clean-sidebar .client-section-title {
        padding: 1rem .55rem .45rem !important;
        font-size: .68rem !important;
        font-weight: 600 !important;
        text-transform: uppercase !important;
        letter-spacing: .08em !important;
        opacity: .78 !important;
    }

    .admin-client-sidebar .admin-client-link,
    .admin-client-sidebar .admin-client-sublink,
    .client-clean-sidebar .client-link,
    .client-clean-sidebar .client-sublink {
        display: flex !important;
        align-items: center !important;
        gap: .7rem !important;
        color: var(--etogo-sidebar-text) !important;
        text-decoration: none !important;
        border-radius: 7px !important;
        box-shadow: none !important;
        font-weight: 500 !important;
    }

    .admin-client-sidebar .admin-client-link,
    .client-clean-sidebar .client-link {
        min-height: 44px !important;
        padding: .55rem !important;
        justify-content: flex-start !important;
    }

    .admin-client-sidebar .admin-client-summary-left,
    .client-clean-sidebar .client-summary-left {
        display: flex !important;
        align-items: center !important;
        gap: .7rem !important;
        flex: 1 !important;
        min-width: 0 !important;
    }

    .admin-client-sidebar .admin-client-link span,
    .admin-client-sidebar .admin-client-sublink,
    .admin-client-sidebar .admin-client-sublink:visited,
    .admin-client-sidebar .admin-client-arrow,
    .client-clean-sidebar .client-link span,
    .client-clean-sidebar .client-sublink,
    .client-clean-sidebar .client-sublink:visited,
    .client-clean-sidebar .client-arrow {
        color: var(--etogo-sidebar-text) !important;
    }

    .admin-client-sidebar .admin-client-icon,
    .admin-client-sidebar .admin-client-icon-dashboard,
    .admin-client-sidebar .admin-client-icon-property,
    .admin-client-sidebar .admin-client-icon-services,
    .admin-client-sidebar .admin-client-icon-projects,
    .admin-client-sidebar .admin-client-icon-billing,
    .admin-client-sidebar .admin-client-icon-reports,
    .admin-client-sidebar .admin-client-icon-access,
    .admin-client-sidebar .admin-client-icon-settings,
    .client-clean-sidebar .client-link i,
    .client-clean-sidebar .icon-success,
    .client-clean-sidebar .icon-primary,
    .client-clean-sidebar .icon-info,
    .client-clean-sidebar .icon-warning,
    .client-clean-sidebar .icon-danger {
        width: 30px !important;
        height: 30px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        flex-shrink: 0 !important;
        background: rgba(255,255,255,.08) !important;
        color: #d8e6ff !important;
        border-radius: 7px !important;
        box-shadow: none !important;
    }

    .admin-client-sidebar .admin-client-group,
    .client-clean-sidebar .client-group {
        margin: 0 !important;
    }

    .admin-client-sidebar .admin-client-group summary,
    .client-clean-sidebar .client-group summary {
        list-style: none !important;
        cursor: pointer !important;
    }

    .admin-client-sidebar .admin-client-group summary::-webkit-details-marker,
    .client-clean-sidebar .client-group summary::-webkit-details-marker {
        display: none !important;
    }

    .admin-client-sidebar .admin-client-arrow,
    .client-clean-sidebar .client-arrow {
        margin-left: auto !important;
        opacity: .72 !important;
        transition: transform .15s ease !important;
    }

    .admin-client-sidebar details[open] .admin-client-arrow,
    .client-clean-sidebar details[open] .client-arrow {
        transform: rotate(180deg) !important;
    }

    .admin-client-sidebar .admin-client-submenu,
    .client-clean-sidebar .client-submenu {
        padding: .18rem 0 .52rem 0 !important;
    }

    .admin-client-sidebar .admin-client-sublink,
    .client-clean-sidebar .client-sublink {
        justify-content: space-between !important;
        margin-left: 2.65rem !important;
        padding: .42rem .5rem !important;
        font-size: .86rem !important;
        font-weight: 400 !important;
        color: var(--etogo-sidebar-muted) !important;
    }

    .admin-client-sidebar .admin-client-link:hover,
    .admin-client-sidebar .admin-client-sublink:hover,
    .client-clean-sidebar .client-link:hover,
    .client-clean-sidebar .client-sublink:hover {
        background: rgba(255,255,255,.08) !important;
        color: #ffffff !important;
    }

    .admin-client-sidebar .admin-client-link.is-active,
    .admin-client-sidebar .admin-client-sublink.is-active,
    .client-clean-sidebar .client-link.is-active,
    .client-clean-sidebar .client-sublink.is-active,
    .client-clean-sidebar .is-active {
        background: linear-gradient(135deg, var(--etogo-sidebar-active), var(--etogo-sidebar-active-2)) !important;
        color: #ffffff !important;
        border-left: 0 !important;
        box-shadow: 0 10px 24px rgba(70, 92, 255, .26) !important;
        font-weight: 600 !important;
    }

    .admin-client-sidebar .admin-client-link.is-active span,
    .admin-client-sidebar .admin-client-sublink.is-active,
    .admin-client-sidebar .admin-client-link:hover span,
    .admin-client-sidebar .admin-client-sublink:hover,
    .client-clean-sidebar .client-link.is-active span,
    .client-clean-sidebar .client-sublink.is-active,
    .client-clean-sidebar .client-link:hover span,
    .client-clean-sidebar .client-sublink:hover {
        color: #ffffff !important;
    }

    .admin-client-sidebar .admin-client-link.is-active .admin-client-icon,
    .admin-client-sidebar .admin-client-link:hover .admin-client-icon,
    .client-clean-sidebar .client-link.is-active i,
    .client-clean-sidebar .client-link:hover i {
        background: rgba(255,255,255,.18) !important;
        color: #ffffff !important;
    }

    .admin-client-sidebar .admin-client-badge,
    .client-clean-sidebar .client-badge {
        min-width: 20px !important;
        height: 20px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        padding: 0 .45rem !important;
        border-radius: 999px !important;
        background: #ef4444 !important;
        color: #ffffff !important;
        font-size: .72rem !important;
        font-weight: 600 !important;
    }

    .admin-client-version-bar,
    .client-version-bar {
        flex: 0 0 auto !important;
        margin-top: auto !important;
        padding: 10px 14px 14px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        flex-direction: column !important;
        gap: 8px !important;
        border-top: 1px solid var(--etogo-sidebar-line) !important;
    }

    .admin-sidebar-collapse-button {
        width: 100% !important;
        min-height: 36px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: .5rem !important;
        padding: 8px 10px !important;
        border: 1px solid rgba(255, 255, 255, .14) !important;
        border-radius: 8px !important;
        background: rgba(255, 255, 255, .06) !important;
        color: #eaf2ff !important;
        font-size: .82rem !important;
        font-weight: 700 !important;
        line-height: 1.1 !important;
        cursor: pointer !important;
        transition: background .16s ease, border-color .16s ease, color .16s ease !important;
    }

    .admin-sidebar-collapse-button:hover,
    .admin-sidebar-collapse-button:focus {
        background: rgba(255, 255, 255, .11) !important;
        border-color: rgba(255, 255, 255, .24) !important;
        color: #ffffff !important;
        outline: none !important;
    }

    .admin-sidebar-collapse-button i {
        font-size: 1.05rem !important;
        line-height: 1 !important;
    }

    .admin-client-version-button,
    .client-version-button {
        width: 100% !important;
        min-height: 34px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        gap: .55rem !important;
        border: 1px solid var(--etogo-sidebar-line) !important;
        border-radius: 999px !important;
        background: rgba(255,255,255,.06) !important;
        color: var(--etogo-sidebar-text) !important;
        box-shadow: none !important;
        padding: 4px 6px 4px 12px !important;
        cursor: default !important;
        font-size: .76rem !important;
        font-weight: 500 !important;
        text-transform: none !important;
    }

    .admin-client-version-label,
    .client-version-label {
        color: var(--etogo-sidebar-muted) !important;
        font-weight: 500 !important;
    }

    .admin-client-version-badge,
    .client-version-badge {
        background: rgba(70, 92, 255, .22) !important;
        color: #ffffff !important;
        border-radius: 999px !important;
        padding: 3px 10px !important;
        font-weight: 600 !important;
    }
</style>
