<?php
$dashboardUrl = $dashboardUrl ?? 'wardcouncilorDashboard.php';

$activePage    = $activePage ?? '';
$sidebarImage = $userData['image_path'] ?? $loggedInProfileImage ?? 'profile.jpg';
$sidebarName  = $fullName ?? $loggedInFullName ?? '';
$sidebarWard  = (int) ($ward_id ?? $loggedInWardNum ?? 0);
$sidebarUnread = (int) ($unread_count ?? 0);


$sidebarSections = [
    [
        'title' => '',
        'links' => [
            ['dashboard', $dashboardUrl, 'dashboard.svg', 'Dashboard'],
        ],
    ],
    [
        'title' => 'TICKETS MANAGEMENT',
        'links' => [
            ['tickets', 'WardTicketManagement.php', 'local_activity.svg', 'Ward Tickets'],
        ],
    ],
    [
        'title' => 'COMMUNICATION',
        'links' => [
            ['summaries', 'ward_summaries.php', 'information.svg', 'Ward Summaries'],
        ],
    ],
    [
        'title' => 'ANALYTICS',
        'links' => [
            ['analytics', 'Analytics.php', 'history.svg', 'Analytics & Reports'],
        ],
    ],
    [
        'title' => 'ACCOUNT',
        'links' => [
            ['messages', 'messages.php', 'message.svg', 'Messages'],
            ['profile', 'settings.php', 'profile.svg', 'Profile'],
            ['logout', 'logout.php', 'logout.svg', 'Logout'],
        ],
    ],
];
?>


<aside class="sidebar" id="sidebar">

    <!-- Profile -->
    <div class="sidebar-profile">

        <div class="sidebar-avatar">
            <img
                src="<?php echo htmlspecialchars($sidebarImage); ?>"
                alt="Profile picture"
            >
        </div>

        <div class="sidebar-profile-info">
            <strong><?php echo htmlspecialchars($sidebarName); ?></strong>
            <span>Ward Councillor</span>
            <span>Ward <?php echo $sidebarWard; ?></span>
        </div>

    </div>


    <!-- Navigation -->
    <nav class="sidebar-menu">

        <?php foreach ($sidebarSections as $section): ?>

            <?php if ($section['title'] !== ''): ?>
                <p class="sidebar-section">
                    <?php echo htmlspecialchars($section['title']); ?>
                </p>
            <?php endif; ?>

            <?php foreach ($section['links'] as [$key, $href, $icon, $label]): ?>

                <?php $isActive = ($activePage === $key); ?>

                <a
                    href="<?php echo htmlspecialchars($href); ?>"
                    class="sidebar-link<?php echo $isActive ? ' active' : ''; ?>"
                    title="<?php echo htmlspecialchars($label); ?>"
                    <?php echo $isActive ? 'aria-current="page"' : ''; ?>
                >

                    <img
    src="../icons/<?php echo htmlspecialchars($icon); ?>"
    alt=""
    class="sidebar-icon"
>

                    <span class="sidebar-label">
                        <?php echo htmlspecialchars($label); ?>
                    </span>

                    <?php if ($key === 'messages' && $sidebarUnread > 0): ?>
                        <b class="badge"><?php echo $sidebarUnread; ?></b>
                    <?php endif; ?>

                </a>

            <?php endforeach; ?>

        <?php endforeach; ?>

    </nav>

</aside>

<!-- Dims the page behind the sidebar on small screens only -->
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
<script>
(function () {
    var btn = document.getElementById("menuBtn");
    if (!btn) return;

    var html = document.documentElement;
    var body = document.body;
    var desktop = window.matchMedia("(min-width: 800px)");

    // Dark overlay behind the sidebar on small screens
    var backdrop = document.querySelector(".sidebar-backdrop");
    if (!backdrop) {
        backdrop = document.createElement("div");
        backdrop.className = "sidebar-backdrop";
        body.appendChild(backdrop);
    }

    function isOpen() {
        return desktop.matches
            ? !html.classList.contains("sidebar-collapsed")
            : body.classList.contains("sidebar-open");
    }

    function syncButton() {
        btn.setAttribute("aria-expanded", isOpen() ? "true" : "false");
    }

    btn.addEventListener("click", function () {
        if (desktop.matches) {
            var collapsed = html.classList.toggle("sidebar-collapsed");
            try { localStorage.setItem("sidebarState", collapsed ? "closed" : "open"); } catch (e) {}
        } else {
            body.classList.toggle("sidebar-open");
        }
        syncButton();
    });

    backdrop.addEventListener("click", function () {
        body.classList.remove("sidebar-open");
        syncButton();
    });

    desktop.addEventListener("change", function () {
        body.classList.remove("sidebar-open");
        syncButton();
    });

    syncButton();
})();
                    </script>