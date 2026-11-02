document.addEventListener("DOMContentLoaded", function () {
    if (document.getElementById("sidebar") || document.body.classList.contains("dashboard-wrapped")) {
        return;
    }

    // Read the user name and role dynamically if set from PHP session
    const currentUserName = window.CURRENT_USER_NAME || "";
    const currentUserRole = window.CURRENT_USER_ROLE || "Communication Officer";

    // 1. Inject Stylesheets & Custom CSS
    const head = document.head;
    
    if (!document.querySelector("link[href*='fonts.googleapis.com']")) {
        const fontLink = document.createElement("link");
        fontLink.rel = "stylesheet";
        fontLink.href = "https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,700;1,700&family=Ubuntu:wght@400;700&display=swap";
        head.appendChild(fontLink);
    }

    const styleTag = document.createElement("style");
    styleTag.textContent = `
        :root {
            --makana-green: #3d5a47;
            --makana-dark-green: #294334;
            --makana-brown: #8a6848;
            --cream: #f7f5ef;
            --white: #ffffff;
            --text: #29342d;
            --muted: #737a73;
            --border: #dedfd8;
        }

        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            font-family: 'Ubuntu', sans-serif !important;
            background: var(--cream) !important;
            color: var(--text);
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 270px;
            height: 100vh;
            background: var(--makana-dark-green);
            color: var(--white);
            padding: 30px 20px;
            z-index: 1000;
            box-sizing: border-box;
        }

        .sidebar .brand {
            padding: 0 0 24px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.12);
            margin-bottom: 22px;
        }

        .navigation {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 14px 16px;
            color: rgba(255, 255, 255, 0.85);
            text-decoration: none;
            border-radius: 9px;
            font-size: 15px;
            font-weight: 700;
        }

        .nav-link:hover, .nav-link.active {
            background: rgba(255, 255, 255, 0.15);
            color: var(--white);
        }

        .main-content-wrapper {
            margin-left: 270px;
            min-height: 100vh;
            padding: 32px 42px;
            box-sizing: border-box;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 38px;
        }

        .topbar h1 {
            margin: 4px 0 0 0;
            font-size: 30px;
            font-family: 'Lora', serif;
            font-weight: 700;
        }

        /* Simplified Profile Container (No Image / Chevron) */
        .topbar-user-profile {
            display: flex;
            align-items: center;
            background: var(--white);
            padding: 10px 20px;
            border-radius: 40px;
            border: 1px solid var(--border);
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
        }

        .user-info-text {
            display: flex;
            flex-direction: column;
            text-align: right;
        }

        .user-info-text .user-name {
            font-size: 15px;
            font-weight: 700;
            color: var(--text);
        }

        .user-subtext-top {
            font-size: 12px;
            color: var(--muted);
            margin-top: 2px;
        }
    `;
    head.appendChild(styleTag);

    const currentPage = window.location.pathname.split("/").pop();

    // 2. Build Sidebar
    const sidebarNode = document.createElement("aside");
    sidebarNode.className = "sidebar";
    sidebarNode.id = "sidebar";
    sidebarNode.innerHTML = `
        <div class="brand">
            <h2 style="margin:0; font-family:'Lora', serif; font-size:20px; color:#fff;">Makana</h2>
        </div>
        <nav class="navigation">
            <a href="communication_officer_dashboard.php" class="nav-link ${currentPage === 'communication_officer_dashboard.php' ? 'active' : ''}">Dashboard</a>
            <a href="all_incidents.php" class="nav-link ${currentPage === 'all_incidents.php' ? 'active' : ''}">View Incident Tickets</a>
            <a href="manageNotices.php" class="nav-link ${currentPage === 'manageNotices.php' ? 'active' : ''}">Create Notice</a>
            <a href="viewnotices.php" class="nav-link ${currentPage === 'viewnotices.php' ? 'active' : ''}">View Notices</a>
            <a href="fault_progress.php" class="nav-link ${currentPage === 'fault_progress.php' ? 'active' : ''}">View Fault Progress</a>
            <a href="logout.php" class="nav-link" style="margin-top:20px;">Logout</a>
        </nav>
    `;

    // 3. Build Topbar (Cleaned badge containing only Name and Role)
    const topbarNode = document.createElement("header");
    topbarNode.className = "topbar";
    topbarNode.innerHTML = `
        <div class="topbar-left">
            <p style="color:var(--makana-brown); font-size:12px; font-weight:bold; letter-spacing:1.4px; margin:0;">MAKANA MUNICIPALITY</p>
            <h1>Communication Officer</h1>
        </div>
        <div class="topbar-user-profile">
            <div class="user-info-text">
                ${currentUserName ? `<span class="user-name">${currentUserName}</span>` : ''}
                <small class="user-subtext-top">${currentUserRole}</small>
            </div>
        </div>
    `;

    // 4. Wrap Page Content
    const originalContent = Array.from(document.body.childNodes);
    const mainContentWrapper = document.createElement("main");
    mainContentWrapper.className = "main-content-wrapper";

    mainContentWrapper.appendChild(topbarNode);
    originalContent.forEach(node => mainContentWrapper.appendChild(node));

    document.body.innerHTML = "";
    document.body.appendChild(sidebarNode);
    document.body.appendChild(mainContentWrapper);
    document.body.classList.add("dashboard-wrapped");
});