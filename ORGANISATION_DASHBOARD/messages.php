<?php
session_start();
require_once "../Error404Connection.php";

//check if the administrator is logged in
if (!isset($_SESSION["username"])) {
    header("Location: ../PUBLIC PAGES/login.php");
    exit;
}

$current_user = $_SESSION['username'];
$feedback = "";

// Fetch current user's role securely from DB
$user_stmt = $conn->prepare("SELECT User_role FROM user WHERE username = ?");
$user_stmt->bind_param("s", $current_user);
$user_stmt->execute();
$user_res = $user_stmt->get_result();

if ($user_res->num_rows === 0) {
    die("Unauthorized access.");
}

$current_user_data = $user_res->fetch_assoc();
$current_role = $current_user_data['User_role'];

// Security Check 2: Block Residents from accessing staff messaging
if ($current_role === 'Resident') {
    die("Access Denied: This portal is reserved for municipal staff only.");
}

// Fetch additional profile and session data
$ward_id = $_SESSION["ward_id"] ?? 0;
$fullName = $_SESSION["fullName"] ?? $current_user;
$userData = ['image_path' => $_SESSION['profile_image'] ?? 'profile.jpg'];

// Handle Form Submission (Sending Message)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $receiver_username = trim($_POST['receiver_username']);
    $subject = trim($_POST['subject']);
    $message_body = trim($_POST['message_body']);

    if (!empty($receiver_username) && !empty($subject) && !empty($message_body)) {

        // Security Check 3: Server-side validation that receiver exists and is staff
        $check_receiver = $conn->prepare("SELECT User_role FROM user WHERE username = ? AND User_role != 'Resident'");
        $check_receiver->bind_param("s", $receiver_username);
        $check_receiver->execute();
        $receiver_res = $check_receiver->get_result();

        if ($receiver_res->num_rows > 0) {
            $receiver_data = $receiver_res->fetch_assoc();
            $receiver_role = $receiver_data['User_role'];

            // Insert into DB using Prepared Statements
            $insert_stmt = $conn->prepare("INSERT INTO internal_messages (sender_username, sender_role, receiver_username, receiver_role, subject, message_body) VALUES (?, ?, ?, ?, ?, ?)");
            $insert_stmt->bind_param("ssssss", $current_user, $current_role, $receiver_username, $receiver_role, $subject, $message_body);

            if ($insert_stmt->execute()) {
                header("Location: messages.php?with=" . urlencode($receiver_username));
                exit();
            } else {
                $feedback = "<div class='alert error'>Error sending message. Please try again.</div>";
            }
            $insert_stmt->close();
        } else {
            $feedback = "<div class='alert error'>Invalid recipient selected.</div>";
        }
        $check_receiver->close();
    } else {
        $feedback = "<div class='alert error'>All fields are required.</div>";
    }
}

// Fetch list of non-resident staff members with profile images
$staff_stmt = $conn->prepare("
    SELECT 
        u.username,
        u.first_Name,
        u.surname,
        u.User_role,
        pi.image_path
    FROM user u
    LEFT JOIN profile_images pi ON u.profile_id = pi.profile_id
    WHERE u.User_role != 'Resident'
      AND u.username != ?
    ORDER BY u.first_Name ASC
");

$staff_stmt->bind_param("s", $current_user);
$staff_stmt->execute();
$staff_list = $staff_stmt->get_result();

$staff_rows = [];

while ($row = $staff_list->fetch_assoc()) {
    $staff_rows[] = $row;
}

$staff_stmt->close();

// Fetch active contacts
$contacts_query = "
    SELECT 
    other.username,
    other.first_Name,
    other.surname,
    other.User_role,
    pi.image_path,
        (SELECT im.message_body FROM internal_messages im
            WHERE (im.sender_username = ? AND im.receiver_username = other.username)
               OR (im.sender_username = other.username AND im.receiver_username = ?)
            ORDER BY im.sent_at DESC LIMIT 1) AS last_message,
        (SELECT im.sent_at FROM internal_messages im
            WHERE (im.sender_username = ? AND im.receiver_username = other.username)
               OR (im.sender_username = other.username AND im.receiver_username = ?)
            ORDER BY im.sent_at DESC LIMIT 1) AS last_time,
        (SELECT COUNT(*) FROM internal_messages im
            WHERE im.sender_username = other.username AND im.receiver_username = ? AND im.is_read = 0) AS unread_count
    FROM user other
LEFT JOIN profile_images pi ON other.profile_id = pi.profile_id
    WHERE other.username IN (
        SELECT sender_username FROM internal_messages WHERE receiver_username = ?
        UNION
        SELECT receiver_username FROM internal_messages WHERE sender_username = ?
    )
    ORDER BY last_time DESC
";
$contacts_stmt = $conn->prepare($contacts_query);
$contacts_stmt->bind_param(
    "sssssss",
    $current_user, $current_user, $current_user, $current_user,
    $current_user, $current_user, $current_user
);
$contacts_stmt->execute();
$contacts_res = $contacts_stmt->get_result();
$contacts = [];
while ($row = $contacts_res->fetch_assoc()) {
    $contacts[] = $row;
}
$contacts_stmt->close();

// Active thread logic
$with = isset($_GET['with']) ? trim($_GET['with']) : "";
$with_data = null;

if ($with !== "") {
    $with_stmt = $conn->prepare("SELECT username, first_Name, surname, User_role FROM user WHERE username = ? AND User_role != 'Resident'");
    $with_stmt->bind_param("s", $with);
    $with_stmt->execute();
    $with_res = $with_stmt->get_result();
    if ($with_res->num_rows > 0) {
        $with_data = $with_res->fetch_assoc();
    } else {
        $with = "";
    }
    $with_stmt->close();
}

$thread_messages = [];
if ($with_data !== null) {
    // Mark messages as read
    $mark_read_stmt = $conn->prepare("UPDATE internal_messages SET is_read = 1 WHERE sender_username = ? AND receiver_username = ? AND is_read = 0");
    $mark_read_stmt->bind_param("ss", $with, $current_user);
    $mark_read_stmt->execute();
    $mark_read_stmt->close();

    // Fetch conversation thread
    $thread_stmt = $conn->prepare("
        SELECT * FROM internal_messages
        WHERE (sender_username = ? AND receiver_username = ?)
           OR (sender_username = ? AND receiver_username = ?)
        ORDER BY sent_at ASC
    ");
    $thread_stmt->bind_param("ssss", $current_user, $with, $with, $current_user);
    $thread_stmt->execute();
    $thread_res = $thread_stmt->get_result();
    while ($row = $thread_res->fetch_assoc()) {
        $thread_messages[] = $row;
    }
    $thread_stmt->close();
}

function preview_text($text, $len = 40) {
    $text = trim($text);
    if (strlen($text) > $len) {
        return substr($text, 0, $len) . "...";
    }
    return $text;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>M-LINK Messages</title>

    <!-- Standalone CSS File Only -->
    <link rel="stylesheet" href="messages.css">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>

<div class="app-layout">

    <!-- ================= HEADER / TOPBAR ================= -->
    <header class="topbar">
        <!-- Sidebar Toggle Button -->
        <button class="menu-btn" id="menuBtn" title="Toggle Sidebar">
            <i class="fa-solid fa-bars"></i>
        </button>

        <div class="top-logo">
            <h2>M-LINK</h2>
            <p>Makhanda-Link</p>
        </div>

        <div class="top-right">
            

            <div class="icon">
                <i class="fa-regular fa-envelope"></i>
                
            </div>

            <div class="user">
                <img src="<?php echo htmlspecialchars($userData['image_path'] ?? 'profile.jpg'); ?>" alt="Profile Picture">
                <div>
                    <h4><?php echo htmlspecialchars($fullName); ?></h4>
                    <small>Ward Councillor</small>
                </div>
            </div>
        </div>
    </header>

    <div class="app-body">
        <!-- Overlay for Mobile Sidebar Toggle -->
        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <!-- ================= SIDEBAR ================= -->
        <aside class="sidebar" id="sidebar">
            <div class="profile">
                <img src="<?php echo htmlspecialchars($userData['image_path'] ?? 'profile.jpg'); ?>" alt="Profile Picture">
                <h3><?php echo htmlspecialchars($fullName); ?></h3>
                <p>Ward Councillor</p>
                <span>Ward <?php echo htmlspecialchars($ward_id); ?></span>
            </div>

            <ul class="menu">
                <li>
                    <a href="wardcouncilorDashboard.php">
                        <i class="fa-solid fa-house"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                <li class="menu-title">TICKETS MANAGEMENT</li>
                <li>
                    <a href="WardTicketManagement.php">
                        <i class="fa-solid fa-ticket"></i>
                        <span>Ward Tickets</span>
                    </a>
                </li>

                <li class="menu-title">COMMUNICATION</li>
                <li>
                    <a href="ward_summaries.php">
                        <i class="fa-solid fa-file-lines"></i>
                        <span>Ward Summaries</span>
                    </a>
                </li>

                <li class="menu-title">ANALYTICS</li>
                <li>
                    <a href="Analytics.php">
                        <i class="fa-solid fa-chart-column"></i>
                        <span>Analytics & Reports</span>
                    </a>
                </li>

                <li class="menu-title">ACCOUNT</li>
                <li class="active">
                    <?php
                    $unread_stmt = $conn->prepare("SELECT COUNT(*) AS unread_count FROM internal_messages WHERE receiver_username = ? AND is_read = 0");
                    $unread_stmt->bind_param("s", $current_user);
                    $unread_stmt->execute();
                    $unread_res = $unread_stmt->get_result()->fetch_assoc();
                    $unread_count = $unread_res['unread_count'];
                    ?>
                    <a href="messages.php">
                        <i class="fa-regular fa-envelope"></i>
                        <span> Messages
                            <?php if ($unread_count > 0): ?>
                                <b class="nav-badge"><?php echo $unread_count; ?></b>
                            <?php endif; ?>
                        </span>
                    </a>
                </li>

                <li>
                    <a href="settings.php">
                        <i class="fa-solid fa-user"></i>
                        <span>Profile</span>
                    </a>
                </li>

                <li>
                    <a href="logout.php">
                        <i class="fa-solid fa-right-from-bracket"></i>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </aside>

        <!-- ================= MAIN CONTENT AREA ================= -->
        <main class="main-content" id="mainContent">

            <div id="feedback-area">
                <?php echo $feedback; ?>
            </div>

            <!-- CHAT APP -->
            <div class="chat-app">

                <!-- Left Column: Search & Contact List -->
                <div class="chat-sidebar">
                    <div class="sidebar-header">
                        <h2>Messages</h2>
                    </div>

                    <div class="search-select-wrapper">
                        <i class="fa-solid fa-magnifying-glass search-icon"></i>
                        <select id="staffSearchSelect">
                            <option value="">Search staff member</option>
                            <?php foreach ($staff_rows as $staff): ?>
                                <option value="<?php echo htmlspecialchars($staff['username']); ?>"
                                    <?php echo ($with === $staff['username']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($staff['first_Name'] . " " . $staff['surname'] . " (" . $staff['User_role'] . ")"); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="contact-list">
                        <?php if (count($contacts) === 0): ?>
                            <p class="empty-text">No conversations yet. Search for a staff member above to start one.</p>
                        <?php else: ?>
                            <?php foreach ($contacts as $contact): ?>
                                <a class="contact-item <?php echo ($with === $contact['username']) ? 'active' : ''; ?>"
                                   href="messages.php?with=<?php echo urlencode($contact['username']); ?>">
                                    <div class="contact-top">
                                        <span class="contact-name"><?php echo htmlspecialchars($contact['first_Name'] . " " . $contact['surname']); ?></span>
                                        <span class="contact-time"><?php echo $contact['last_time'] ? date("d M", strtotime($contact['last_time'])) : ""; ?></span>
                                    </div>
                                    <span class="contact-role"><?php echo htmlspecialchars($contact['User_role']); ?></span>
                                    <div class="contact-bottom">
                                        <span class="contact-preview"><?php echo htmlspecialchars(preview_text($contact['last_message'] ?? "")); ?></span>
                                        <?php if ($contact['unread_count'] > 0): ?>
                                            <span class="unread-badge"><?php echo (int)$contact['unread_count']; ?></span>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Right Column: Active Thread -->
                <div class="thread-panel">
                    <?php if ($with_data === null): ?>
                        <div class="thread-empty">
                            <p class="empty-text">Select a conversation or search for a staff member to start a new message thread.</p>
                        </div>
                    <?php else: ?>
                        <div class="thread-header">
                            <div class="thread-contact-info">
                                <span class="thread-name"><?php echo htmlspecialchars($with_data['first_Name'] . " " . $with_data['surname']); ?></span>
                                <span class="thread-role"><?php echo htmlspecialchars($with_data['User_role']); ?></span>
                            </div>
                        </div>

                        <div class="thread-messages" id="threadMessages">
                            <?php if (count($thread_messages) === 0): ?>
                                <p class="empty-text">No messages yet. Say hello!</p>
                            <?php else: ?>
                                <?php foreach ($thread_messages as $msg): ?>
                                    <?php $is_sent = ($msg['sender_username'] === $current_user); ?>
                                    <div class="bubble-row <?php echo $is_sent ? 'sent' : 'received'; ?>">
                                        <div class="bubble">
                                            <div class="bubble-subject"><?php echo htmlspecialchars($msg['subject']); ?></div>
                                            <div class="bubble-text"><?php echo nl2br(htmlspecialchars($msg['message_body'])); ?></div>
                                            <div class="bubble-time"><?php echo date("d M Y, H:i", strtotime($msg['sent_at'])); ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <form action="messages.php?with=<?php echo urlencode($with); ?>" method="POST" id="messageForm" class="thread-input-form">
                            <input type="hidden" name="receiver_username" id="receiver_username" value="<?php echo htmlspecialchars($with); ?>">
                            <input type="text" name="subject" id="subject" class="subject-input" maxlength="150" placeholder="Subject" required>
                            <input type="text" name="message_body" id="message_body" class="message-input" placeholder="Type your message..." required>
                            <button type="submit" name="send_message" class="send-btn">
                                <i class="fa-solid fa-paper-plane"></i>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

            </div>

            <footer>
                <p>© 2026 M-LINK | Empowering Communities</p>
            </footer>
        </main>
    </div>
</div>

<!-- Inline JavaScript for Sidebar Toggle and Dropdown Navigation -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const menuBtn = document.getElementById('menuBtn');
        const sidebar = document.getElementById('sidebar');
        const staffSelect = document.getElementById('staffSearchSelect');
        const threadMessages = document.getElementById('threadMessages');

        // Toggle Sidebar Open/Closed (Closed by default)
        if (menuBtn && sidebar) {
            menuBtn.addEventListener('click', function() {
                sidebar.classList.toggle('open');
            });
        }

        // Dropdown change navigation
        if (staffSelect) {
            staffSelect.addEventListener('change', function() {
                if (this.value) {
                    window.location.href = 'messages.php?with=' + encodeURIComponent(this.value);
                }
            });
        }

        // Auto scroll chat thread to bottom
        if (threadMessages) {
            threadMessages.scrollTop = threadMessages.scrollHeight;
        }
    });
</script>
<script src="messages.js"></script>
</body>
</html>