
(function () {
    'use strict';
 
    if (window.__olGlobalLayoutLoaded) { return; }
    window.__olGlobalLayoutLoaded = true;
 
    var doc  = document;
    var root = doc.documentElement;
 
    var scriptEl = doc.currentScript || doc.querySelector('script[src*="global_dashboard_layout"]');
    var scriptSrc = (scriptEl && scriptEl.src) ? scriptEl.src : location.href;
    scriptSrc = scriptSrc.split('#')[0].split('?')[0];
    var DIR = scriptSrc.substring(0, scriptSrc.lastIndexOf('/') + 1);
 
    function toUrl(rel) {
        try { return new URL(rel, DIR).href; } catch (e) { return rel; }
    }
 
    var USER_ENDPOINT = toUrl(
        (scriptEl && scriptEl.getAttribute('data-user-endpoint')) || 'layout_user_info.php'
    );
 
    var ROLE_LABEL   = 'Communication Officer';
    var CACHE_KEY    = 'olLayoutUser';
    var COLLAPSE_KEY = 'olSidebarCollapsed';
 
    var NAV = [
        { label: 'Dashboard',href: 'communication_officer_dashboard.php',img: '../icons/home.svg', icon: 'home' },
        { label: 'View Incident Tickets',href: 'all_incidents.php',also: ['view_incident.php'], icon: 'confirmation_number' },
        { label: 'Create Notice',href: 'manageNotices.php',  icon: 'edit_note' },
        { label: 'View Notices', href: 'viewnotices.php', icon: 'campaign' },
        { label: 'Messages', href: 'messages.php', icon: 'chat', badge: 'messages' },
        { label: 'Report an Incident', href: '../USER_DASHBOARD/reportFaultTicketForm.php',icon: 'report_problem' },
        { label: 'Service Fault Progress', href: 'fault_progress.php',                            icon: 'engineering' },
        { label: 'Profile',href: 'settings.php', icon: 'settings' },
        { label: 'Logout', href: 'logout.php', icon: 'logout', logout: true }
    ];
 
    var HOME_HREF       = '../PUBLIC%20PAGES/homepage.php';
    var NOTIFY_HREF     = '../USER_DASHBOARD/notifications.php';
    var PROFILE_HREF    = '../USER_DASHBOARD/settings.php';
    var NOTIFY_IMG      = '../icons/add_alert.svg';
    var LOGO_CANDIDATES = [
        '../USER_DASHBOARD/mlinkLogo.png',
        'mlinkLogo.png',
        '../assets/images/mlinkLogo.png',
        '../icons/mlinkLogo.png'
    ];
 
    /* =====================================================================
       1. SMALL HELPERS
    ===================================================================== */
    function store(kind) {
        try { return window[kind]; } catch (e) { return null; }
    }
    function readCache() {
        try {
            var s = store('sessionStorage');
            var raw = s && s.getItem(CACHE_KEY);
            return raw ? JSON.parse(raw) : null;
        } catch (e) { return null; }
    }
    function writeCache(obj) {
        try { var s = store('sessionStorage'); if (s) { s.setItem(CACHE_KEY, JSON.stringify(obj)); } } catch (e) {}
    }
    function clearCache() {
        try { var s = store('sessionStorage'); if (s) { s.removeItem(CACHE_KEY); } } catch (e) {}
    }
    function readCollapsed() {
        try { var l = store('localStorage'); return !!(l && l.getItem(COLLAPSE_KEY) === '1'); } catch (e) { return false; }
    }
    function writeCollapsed(v) {
        try { var l = store('localStorage'); if (l) { l.setItem(COLLAPSE_KEY, v ? '1' : '0'); } } catch (e) {}
    }
 
    function el(tag, cls, attrs) {
        var n = doc.createElement(tag);
        if (cls) { n.className = cls; }
        if (attrs) { Object.keys(attrs).forEach(function (k) { n.setAttribute(k, attrs[k]); }); }
        return n;
    }
    function materialIcon(name, cls) {
        var s = el('span', 'material-icons' + (cls ? ' ' + cls : ''), { 'aria-hidden': 'true' });
        s.textContent = name;
        return s;
    }
    function pathOf(u) {
        try { return decodeURIComponent(new URL(u, location.href).pathname).toLowerCase(); } catch (e) { return ''; }
    }
    function baseOf(p) { return p.split('/').pop(); }
 
    var mq = window.matchMedia
        ? window.matchMedia('(max-width: 750px)')
        : { matches: false, addEventListener: function () {} };
 
    /* =====================================================================
       2. STYLES  (everything is scoped to #olSidebar / #olTopbar / html.ol-layout-on
          so it can't clash with a page's own CSS)
    ===================================================================== */
    var CSS = [
    /* ---- variables + page offset ---- */
    'html.ol-layout-on{--ol-sidebar-width:270px;--ol-collapsed-width:85px;--ol-offset:270px;--ol-topbar-height:95px;',
    '--ol-green:#4b561b;--ol-brown:#955b24;--ol-text:#263238;--ol-white:#ffffff;--ol-border:#e4e0d7;',
    'box-sizing:border-box!important;padding-left:var(--ol-offset)!important;padding-top:var(--ol-topbar-height)!important;',
    'transition:padding-left .3s ease}',
    'html.ol-layout-on.ol-collapsed{--ol-offset:85px}',
    'html.ol-layout-on body,html.ol-layout-on .page{min-height:calc(100vh - var(--ol-topbar-height))!important}',
 
    /* ---- legacy layouts that already reserved room for an old sidebar ---- */
    'html.ol-layout-on body>aside.sidebar{display:none!important}',
    'html.ol-layout-on .formContainer{width:calc(100% - 70px)!important;margin-left:35px!important;margin-right:35px!important}',
 
    /* ---- common resets inside our own elements ---- */
    '#olSidebar,#olSidebar *,#olTopbar,#olTopbar *{box-sizing:border-box}',
    '#olSidebar a,#olSidebar a:hover,#olSidebar a:focus,#olTopbar a,#olTopbar a:hover,#olTopbar a:focus{text-decoration:none}',
    '#olSidebar img,#olTopbar img{max-width:none}',
 
    /* ---- sidebar ---- */
    '#olSidebar{position:fixed;top:0;left:0;bottom:0;width:var(--ol-sidebar-width);margin:0;background:var(--ol-green);color:var(--ol-white);',
    'padding:30px 18px;z-index:1200;overflow-x:hidden;overflow-y:auto;font-family:"Ubuntu",sans-serif;transition:width .3s ease}',
    '#olSidebar .ol-sidebar-profile{display:flex;align-items:center;gap:13px;padding:5px 7px 25px;margin:0 0 20px;',
    'border-bottom:1px solid rgba(255,255,255,.2);overflow:hidden}',
    '#olSidebar .ol-sidebar-avatar{width:48px;height:48px;min-width:48px;border-radius:50%;background:rgba(255,255,255,.15);',
    'display:flex;align-items:center;justify-content:center;overflow:hidden}',
    '#olSidebar .ol-sidebar-avatar img{width:100%;height:100%;object-fit:cover;display:block}',
    '#olSidebar .ol-sidebar-avatar .material-icons{font-size:42px;color:var(--ol-white)}',
    '#olSidebar .ol-sidebar-profile-info{display:flex;flex-direction:column;min-width:0}',
    '#olSidebar .ol-sidebar-profile-info strong{font-family:"Ubuntu",sans-serif;font-size:14px;font-weight:600;color:var(--ol-white);',
    'white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:170px}',
    '#olSidebar .ol-sidebar-profile-info span{margin-top:3px;font-size:11px;font-weight:400;color:rgba(255,255,255,.75)}',
    '#olSidebar .ol-sidebar-menu{display:flex;flex-direction:column;gap:8px}',
    '#olSidebar .ol-sidebar-link{min-height:48px;display:flex;align-items:center;gap:15px;padding:11px 14px;margin:0;border-radius:8px;',
    'color:rgba(255,255,255,.85);font-family:"Ubuntu",sans-serif;font-size:14px;font-weight:500;line-height:1.2;white-space:nowrap;',
    'background:transparent;transition:background .2s ease,color .2s ease}',
    '#olSidebar .ol-sidebar-link:hover{background:rgba(255,255,255,.12);color:var(--ol-white)}',
    '#olSidebar .ol-sidebar-link.active{background:rgba(255,255,255,.18);color:var(--ol-white)}',
    '#olSidebar .ol-sidebar-icon{width:24px;height:24px;min-width:24px;object-fit:contain;filter:brightness(0) invert(1)}',
    '#olSidebar .ol-sidebar-material-icon{width:24px;min-width:24px;font-size:24px;text-align:center;color:var(--ol-white)}',
    '#olSidebar.collapsed{width:var(--ol-collapsed-width)}',
    '#olSidebar.collapsed .ol-sidebar-profile{justify-content:center;padding-left:0;padding-right:0}',
    '#olSidebar.collapsed .ol-sidebar-profile-info,#olSidebar.collapsed .ol-sidebar-link span:not(.material-icons){display:none}',
    '#olSidebar.collapsed .ol-sidebar-link{justify-content:center;padding-left:0;padding-right:0}',
    '#olSidebar .ol-sidebar-link{position:relative}',
    '#olSidebar .ol-nav-badge{margin-left:auto;min-width:20px;height:20px;padding:0 6px;display:flex;align-items:center;justify-content:center;',
    'background:var(--ol-brown);color:var(--ol-white);border-radius:20px;font-size:11px;font-weight:700;line-height:1}',
    '#olSidebar.collapsed .ol-sidebar-link span.ol-nav-badge{display:flex;position:absolute;top:5px;right:8px;margin-left:0;min-width:17px;height:17px;padding:0 4px;',
    'font-size:9px;box-shadow:0 0 0 2px var(--ol-green)}',
    '#olSidebarOverlay{display:none;position:fixed;top:0;right:0;bottom:0;left:0;background:rgba(0,0,0,.45);z-index:1190}',
 
    /* ---- topbar ---- */
    '#olTopbar{position:fixed;top:0;left:var(--ol-offset);right:0;height:var(--ol-topbar-height);margin:0;z-index:1100;',
    'display:flex;align-items:center;justify-content:space-between;padding:0 35px;background:var(--ol-white);',
    'border-bottom:1px solid var(--ol-border);font-family:"Ubuntu",sans-serif;color:var(--ol-text);transition:left .3s ease}',
    '#olTopbar .ol-topbar-left,#olTopbar .ol-topbar-right{display:flex;align-items:center}',
    '#olTopbar .ol-topbar-left{gap:20px}',
    '#olTopbar .ol-topbar-right{gap:28px}',
    '#olTopbar .ol-menu-btn{width:42px;height:42px;min-width:0;padding:0;margin:0;border:none;border-radius:0;box-shadow:none;background:transparent;',
    'display:flex;align-items:center;justify-content:center;cursor:pointer;color:var(--ol-text)}',
    '#olTopbar .ol-menu-btn:hover,#olTopbar .ol-menu-btn:focus{background:transparent;color:var(--ol-green);box-shadow:none}',
    '#olTopbar .ol-menu-btn .material-icons{font-size:29px}',
    '#olTopbar .ol-topbar-logo-link{display:flex;align-items:center}',
    '#olTopbar .ol-topbar-logo{width:64px;height:64px;object-fit:contain;display:block}',
    '#olTopbar .ol-topbar-icon-btn{display:flex;align-items:center;gap:8px;color:var(--ol-text);font-size:12px;font-weight:500;white-space:nowrap;transition:color .2s ease}',
    '#olTopbar .ol-topbar-icon-btn:hover{color:var(--ol-brown)}',
    '#olTopbar .ol-topbar-icon-img{width:26px;height:26px;object-fit:contain;display:block}',
    '#olTopbar .ol-notification-icon-wrapper{position:relative;width:28px;height:28px;display:flex;align-items:center;justify-content:center;flex-shrink:0}',
    '#olTopbar .ol-notification-icon-wrapper .material-icons{font-size:26px}',
    '#olTopbar .ol-notification-badge{position:absolute;top:-8px;right:-9px;min-width:19px;height:19px;padding:0 5px;display:flex;align-items:center;justify-content:center;',
    'background:var(--ol-brown);color:var(--ol-white);border:2px solid var(--ol-white);border-radius:20px;font-family:"Ubuntu",sans-serif;font-size:9px;font-weight:700;line-height:1}',
    '#olTopbar .ol-notification-text{font-size:12px;font-weight:500;color:var(--ol-text)}',
    '#olTopbar .ol-topbar-icon-btn:hover .ol-notification-text{color:var(--ol-brown)}',
    '#olTopbar .ol-topbar-profile{display:flex;align-items:center;gap:10px;padding-left:20px;border-left:1px solid var(--ol-border);color:var(--ol-text)}',
    '#olTopbar .ol-topbar-avatar{width:42px;height:42px;border-radius:50%;overflow:hidden;flex-shrink:0;display:flex;align-items:center;justify-content:center}',
    '#olTopbar .ol-topbar-avatar img{width:100%;height:100%;object-fit:cover;display:block}',
    '#olTopbar .ol-topbar-avatar .material-icons{font-size:40px;color:var(--ol-green)}',
    '#olTopbar .ol-topbar-profile-info{display:flex;flex-direction:column}',
    '#olTopbar .ol-topbar-profile-info strong{font-family:"Ubuntu",sans-serif;font-size:13px;font-weight:600;color:var(--ol-text)}',
    '#olTopbar .ol-topbar-profile-info span{margin-top:2px;font-size:10px;font-weight:400;color:#777}',
 
    /* ---- tablet ---- */
    '@media (max-width:1000px){#olTopbar .ol-topbar-right{gap:18px}#olTopbar .ol-notification-text{display:none}}',
 
    /* ---- mobile: sidebar becomes a slide-in drawer ---- */
    '@media (max-width:750px){',
    'html.ol-layout-on,html.ol-layout-on.ol-collapsed{--ol-offset:0px;--ol-topbar-height:80px}',
    'html.ol-layout-on .formContainer{width:calc(100% - 30px)!important;margin-left:15px!important;margin-right:15px!important}',
    '#olSidebar{transform:translateX(-100%);transition:transform .3s ease}',
    '#olSidebar.mobile-open{transform:translateX(0)}',
    '#olSidebarOverlay.show{display:block}',
    '#olTopbar{padding:0 18px}',
    '#olTopbar .ol-topbar-left{gap:12px}',
    '#olTopbar .ol-topbar-right{gap:14px}',
    '#olTopbar .ol-topbar-logo{width:52px;height:52px}',
    '#olTopbar .ol-topbar-profile-info{display:none}',
    '#olTopbar .ol-topbar-profile{padding-left:12px}',
    '#olTopbar .ol-topbar-avatar{width:37px;height:37px}',
    '}',
    '@media (max-width:480px){',
    '#olTopbar{padding:0 12px}',
    '#olTopbar .ol-topbar-right{gap:10px}',
    '#olTopbar .ol-topbar-logo{width:45px;height:45px}',
    '#olTopbar .ol-menu-btn{width:34px;height:34px}',
    '#olTopbar .ol-menu-btn .material-icons{font-size:26px}',
    '#olTopbar .ol-topbar-profile{padding-left:8px}',
    '#olTopbar .ol-topbar-avatar{width:34px;height:34px}',
    '}',
 
    /* ---- printing: hide the chrome ---- */
    '@media print{#olSidebar,#olTopbar,#olSidebarOverlay{display:none!important}',
    'html.ol-layout-on{padding-left:0!important;padding-top:0!important}}'
    ].join('\n');
 
    function injectHeadAssets() {
        var head = doc.head || doc.getElementsByTagName('head')[0] || root;
 
        if (!doc.getElementById('olLayoutStyle')) {
            var style = doc.createElement('style');
            style.id = 'olLayoutStyle';
            style.appendChild(doc.createTextNode(CSS));
            head.appendChild(style);
        }
        function addLink(id, match, href) {
            if (doc.getElementById(id) || doc.querySelector('link[href*="' + match + '"]')) { return; }
            var l = doc.createElement('link');
            l.id = id; l.rel = 'stylesheet'; l.href = href;
            head.appendChild(l);
        }
        addLink('olMaterialIcons', 'family=Material+Icons', 'https://fonts.googleapis.com/icon?family=Material+Icons');
        addLink('olUbuntuFont', 'family=Ubuntu', 'https://fonts.googleapis.com/css2?family=Ubuntu:wght@400;500;700&display=swap');
    }
 
    /* =====================================================================
       3. BUILD THE SIDEBAR + TOPBAR
    ===================================================================== */
    var built   = false;
    var blocked = false;      // true when the page already has the native dashboard layout
    var refs    = {};
    var user  = { uid: '', name: '', pic: '', unread: 0, messages: 0 };
 
    function renderAvatar(box, pic) {
        box.textContent = '';
        if (pic) {
            var img = el('img', '', { alt: 'Profile' });
            img.addEventListener('error', function () {
                box.textContent = '';
                box.appendChild(materialIcon('account_circle'));
            });
            img.src = pic;
            box.appendChild(img);
        } else {
            box.appendChild(materialIcon('account_circle'));
        }
    }
 
    function applyUser() {
        var name = user.name || ROLE_LABEL;
        var showRole = !!user.name;
 
        refs.sideName.textContent = name;
        refs.topName.textContent  = name;
        refs.sideRole.style.display = showRole ? '' : 'none';
        refs.topRole.style.display  = showRole ? '' : 'none';
 
        var picUrl = user.pic ? toUrl(user.pic) : '';
        if (refs.picUrl !== picUrl) {
            refs.picUrl = picUrl;
            renderAvatar(refs.sideAvatar, picUrl);
            renderAvatar(refs.topAvatar, picUrl);
        }
 
        // any spot on the page that should carry the officer's name (e.g. manageNotices' header)
        if (user.name) {
            var labels = doc.querySelectorAll('header.topbar > .user, [data-ol-username]');
            for (var i = 0; i < labels.length; i++) { labels[i].textContent = user.name; }
        }
 
        var badges = refs.navBadges || {};
        Object.keys(badges).forEach(function (key) {
            var count = parseInt(user[key], 10) || 0;
            badges[key].textContent = count > 99 ? '99+' : String(count);
            badges[key].style.display = count > 0 ? '' : 'none';
        });
 
        var n = parseInt(user.unread, 10) || 0;
        if (n > 0) {
            refs.badge.textContent = n > 99 ? '99+' : String(n);
            refs.badge.style.display = '';
        } else {
            refs.badge.style.display = 'none';
        }
    }
 
    function build() {
        if (built) { return; }
        if (doc.querySelector('.ol-sidebar')) {          // page already has the native layout
            blocked = true;
            teardown();
            return;
        }
        built = true;
 
        var here     = pathOf(location.href);
        var hereBase = baseOf(here);
 
        /* ---------- sidebar ---------- */
        var sidebar = el('div', '', { id: 'olSidebar', role: 'navigation', 'aria-label': 'Main Navigation' });
 
        var profile = el('div', 'ol-sidebar-profile');
        refs.sideAvatar = el('div', 'ol-sidebar-avatar');
        var pInfo = el('div', 'ol-sidebar-profile-info');
        refs.sideName = el('strong');
        refs.sideRole = el('span');
        refs.sideRole.textContent = ROLE_LABEL;
        pInfo.appendChild(refs.sideName);
        pInfo.appendChild(refs.sideRole);
        profile.appendChild(refs.sideAvatar);
        profile.appendChild(pInfo);
        sidebar.appendChild(profile);
 
        var menu = el('nav', 'ol-sidebar-menu');
        NAV.forEach(function (item) {
            var url  = toUrl(item.href);
            var a    = el('a', 'ol-sidebar-link');
            a.href = url;
 
            var itemPath = pathOf(url);
            var isActive = itemPath === here;
            if (!isActive && item.also) { isActive = item.also.indexOf(hereBase) !== -1; }
            if (isActive) { a.classList.add('active'); a.setAttribute('aria-current', 'page'); }
 
            if (item.img) {
                var img = el('img', 'ol-sidebar-icon', { alt: '' });
                img.addEventListener('error', function () {
                    img.replaceWith(materialIcon(item.icon, 'ol-sidebar-material-icon'));
                });
                img.src = toUrl(item.img);
                a.appendChild(img);
            } else {
                a.appendChild(materialIcon(item.icon, 'ol-sidebar-material-icon'));
            }
            var label = el('span');
            label.textContent = item.label;
            a.appendChild(label);
 
            if (item.badge) {
                var navBadge = el('span', 'ol-nav-badge');
                navBadge.style.display = 'none';
                a.appendChild(navBadge);
                (refs.navBadges = refs.navBadges || {})[item.badge] = navBadge;
            }
 
            if (item.logout) { a.addEventListener('click', clearCache); }
            menu.appendChild(a);
        });
        sidebar.appendChild(menu);
 
        var overlay = el('div', '', { id: 'olSidebarOverlay' });
 
        /* ---------- topbar ---------- */
        var top = el('div', '', { id: 'olTopbar', role: 'banner' });
 
        var left = el('div', 'ol-topbar-left');
        var btn = el('button', 'ol-menu-btn', { type: 'button', id: 'olMenuBtn', 'aria-label': 'Toggle Navigation', 'aria-controls': 'olSidebar' });
        btn.appendChild(materialIcon('menu'));
        var logoLink = el('a', 'ol-topbar-logo-link', { title: 'Go to M-LINK Homepage' });
        logoLink.href = toUrl(HOME_HREF);
        var logo = el('img', 'ol-topbar-logo', { alt: 'M-LINK Logo' });
        var logoIdx = 0;
        logo.addEventListener('error', function () {
            logoIdx++;
            if (logoIdx < LOGO_CANDIDATES.length) { logo.src = toUrl(LOGO_CANDIDATES[logoIdx]); }
        });
        logo.src = toUrl(LOGO_CANDIDATES[0]);
        logoLink.appendChild(logo);
        left.appendChild(btn);
        left.appendChild(logoLink);
 
        var right = el('div', 'ol-topbar-right');
 
        var bell = el('a', 'ol-topbar-icon-btn', { title: 'Notifications' });
        bell.href = toUrl(NOTIFY_HREF);
        var bellWrap = el('div', 'ol-notification-icon-wrapper');
        var bellImg = el('img', 'ol-topbar-icon-img', { alt: 'Notifications' });
        bellImg.addEventListener('error', function () { bellImg.replaceWith(materialIcon('notifications')); });
        bellImg.src = toUrl(NOTIFY_IMG);
        refs.badge = el('span', 'ol-notification-badge');
        refs.badge.style.display = 'none';
        bellWrap.appendChild(bellImg);
        bellWrap.appendChild(refs.badge);
        var bellText = el('span', 'ol-notification-text');
        bellText.textContent = 'Notifications';
        bell.appendChild(bellWrap);
        bell.appendChild(bellText);
 
        var prof = el('a', 'ol-topbar-profile');
        prof.href = toUrl(PROFILE_HREF);
        refs.topAvatar = el('div', 'ol-topbar-avatar');
        var tInfo = el('div', 'ol-topbar-profile-info');
        refs.topName = el('strong');
        refs.topRole = el('span');
        refs.topRole.textContent = ROLE_LABEL;
        tInfo.appendChild(refs.topName);
        tInfo.appendChild(refs.topRole);
        prof.appendChild(refs.topAvatar);
        prof.appendChild(tInfo);
 
        right.appendChild(bell);
        right.appendChild(prof);
        top.appendChild(left);
        top.appendChild(right);
 
        var body = doc.body;
        body.appendChild(sidebar);
        body.appendChild(overlay);
        body.appendChild(top);
 
        refs.sidebar = sidebar;
        refs.overlay = overlay;
        refs.btn     = btn;
 
        applyUser();
        wireBehaviour();
    }
 
    /* =====================================================================
       4. BEHAVIOUR  (desktop = collapse to icons, mobile = slide-in drawer)
    ===================================================================== */
    function refreshLayout() {
        // let maps / charts re-measure once the width transition is finished
        setTimeout(function () {
            try { window.dispatchEvent(new Event('resize')); } catch (e) {}
        }, 320);
    }
 
    function setMobileOpen(open) {
        refs.sidebar.classList.toggle('mobile-open', open);
        refs.overlay.classList.toggle('show', open);
        refs.btn.setAttribute('aria-expanded', String(open));
    }
 
    function setCollapsed(collapsed) {
        refs.sidebar.classList.toggle('collapsed', collapsed);
        root.classList.toggle('ol-collapsed', collapsed);
        refs.btn.setAttribute('aria-expanded', String(!collapsed));
    }
 
    function syncToBreakpoint() {
        setMobileOpen(false);
        if (mq.matches) {
            setCollapsed(false);
            refs.btn.setAttribute('aria-expanded', 'false');
        } else {
            setCollapsed(readCollapsed());
        }
    }
 
    function wireBehaviour() {
        refs.btn.addEventListener('click', function () {
            if (mq.matches) {
                setMobileOpen(!refs.sidebar.classList.contains('mobile-open'));
            } else {
                var next = !refs.sidebar.classList.contains('collapsed');
                setCollapsed(next);
                writeCollapsed(next);
                refreshLayout();
            }
        });
        refs.overlay.addEventListener('click', function () { setMobileOpen(false); });
        doc.addEventListener('keydown', function (ev) {
            if (ev.key === 'Escape' && refs.sidebar) { setMobileOpen(false); }
        });
        refs.sidebar.addEventListener('click', function (ev) {
            if (mq.matches && ev.target.closest && ev.target.closest('a')) { setMobileOpen(false); }
        });
        if (mq.addEventListener) { mq.addEventListener('change', syncToBreakpoint); }
        else if (mq.addListener) { mq.addListener(syncToBreakpoint); }
 
        syncToBreakpoint();
    }
 
    /* =====================================================================
       5. TURN THE LAYOUT OFF (e.g. server says this user shouldn't get it)
    ===================================================================== */
    function teardown() {
        ['olSidebar', 'olSidebarOverlay', 'olTopbar', 'olLayoutStyle'].forEach(function (id) {
            var n = doc.getElementById(id);
            if (n && n.parentNode) { n.parentNode.removeChild(n); }
        });
        root.classList.remove('ol-layout-on', 'ol-collapsed');
        built = false;
    }
 
    /* =====================================================================
       6. USER INFO  (name / photo / unread badge from layout_user_info.php)
    ===================================================================== */
    function fetchUser() {
        if (!window.fetch) { return; }
        fetch(USER_ENDPOINT, { credentials: 'same-origin', cache: 'no-store', headers: { 'Accept': 'application/json' } })
            .then(function (r) {
                var type = r.headers.get('content-type') || '';
                if (!r.ok || type.indexOf('json') === -1) { throw new Error('layout user info unavailable'); }
                return r.json();
            })
            .then(function (data) {
                if (!data || typeof data !== 'object') { return; }
 
                // a different person logged in inside this tab: forget the old name/photo
                if (user.uid && data.uid && user.uid !== data.uid) { user.name = ''; user.pic = ''; user.unread = 0; user.messages = 0; }
                user.uid = data.uid || user.uid;
 
                writeCache({
                    enabled: data.enabled !== false,
                    uid:     user.uid,
                    name:    data.name || user.name,
                    pic:     data.pic  || user.pic,
                    unread:  data.unread || 0,
                    messages: data.messages || 0
                });
 
                if (data.enabled === false) { teardown(); return; }
 
                // keep the name the dashboard already showed if the server sends nothing better
                user.name   = data.name || user.name;
                user.pic    = data.pic  || user.pic;
                user.unread = data.unread || 0;
                user.messages = data.messages || 0;
 
                if (!built) { enable(); }
                if (built)  { applyUser(); }
            })
            .catch(function () { /* endpoint missing: keep the defaults, the layout still works */ });
    }
 
    /* =====================================================================
       7. START
    ===================================================================== */
    function enable() {
        if (blocked) { return; }
        injectHeadAssets();
        root.classList.add('ol-layout-on');
        if (doc.body) { build(); }
    }
 
    var cached = readCache();
    if (cached) {
        user.uid    = cached.uid    || '';
        user.name   = cached.name   || '';
        user.pic    = cached.pic    || '';
        user.unread = cached.unread || 0;
        user.messages = cached.messages || 0;
    }
    var startEnabled = !(cached && cached.enabled === false);
 
    if (startEnabled) { enable(); }        // applies the page offset right away = no layout jump
 
    function ready() {
        if (startEnabled && !built && root.classList.contains('ol-layout-on')) { build(); }
        fetchUser();
    }
    if (doc.readyState === 'loading') { doc.addEventListener('DOMContentLoaded', ready); }
    else { ready(); }
})();
 


















