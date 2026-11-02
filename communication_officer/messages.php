<?php

require_once __DIR__ . "/errorHandling.php";
session_start();
require_once "Error404Connection.php";
require_once __DIR__ . "/messageCrypto.php";

//  User must be logged in
if (!isset($_SESSION["username"])) {
    header("Location: ../PUBLIC PAGES/login.php");
    exit;
}

$current_user = $_SESSION['username'];$feedback = "";

// Fetch current user's role securely from DB
$user_stmt =$conn->prepare("SELECT User_role FROM user WHERE username = ?");
$user_stmt->bind_param("s", $current_user);$user_stmt->execute();
$user_res =$user_stmt->get_result();

if ($user_res->num_rows === 0) {
    die("Unauthorized access.");
}

$adminName = "";
$adminRole = "";
$profileImage = "";
$profileImageUrl = "../profile.jpg";

// Get administrator information for sidebar display
$stmt =$conn->prepare("
    SELECT
        u.first_Name,
        u.surname,
        u.User_role,
        p.image_path
    FROM `user` u
    LEFT JOIN profile_images p
        ON u.profile_id = p.profile_id
    WHERE u.username = ?
    LIMIT 1
");

if (!$stmt) {
    die(dbErrorNotice("Database error: ", $conn->error));
}

$stmt->bind_param("s", $current_user);$stmt->execute();
$result =$stmt->get_result();

if ($result && $result->num_rows > 0) {$admin = $result->fetch_assoc();$adminName = trim($admin["first_Name"] . " " . ($admin["surname"] ?? ""));
    $adminRole =$admin["User_role"];
    $profileImage =$admin["image_path"] ?? "";
}

$stmt->close();

if (!empty($profileImage)) {
    $possibleProfilePath = __DIR__ . DIRECTORY_SEPARATOR . str_replace("/", DIRECTORY_SEPARATOR, $profileImage);
    if (file_exists($possibleProfilePath)) {$profileImageUrl = $profileImage . "?v=" . filemtime($possibleProfilePath);
    }
}

$current_user_data =$user_res->fetch_assoc();
$current_role =$current_user_data['User_role'];

// Security Check 2: Block Residents from accessing staff messaging
if (strtolower(trim((string)$current_role)) === 'resident') {
    die("Access Denied.");
}

// Handle Form Submission (Sending Message)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    // plain text only (arrays / odd input are treated as empty)
    $receiver_username = isset($_POST['receiver_username']) && is_string($_POST['receiver_username']) ? trim($_POST['receiver_username']) : '';
    $subject = isset($_POST['subject']) && is_string($_POST['subject']) ? trim($_POST['subject']) : '';
    $message_body = isset($_POST['message_body']) && is_string($_POST['message_body']) ? trim($_POST['message_body']) : '';

    if (!empty($receiver_username) && !empty($subject) && !empty($message_body) && mb_strlen($subject, 'UTF-8') > 150) {
        $feedback = "<div class='alert error'>The subject must be 150 characters or fewer.</div>";
    } elseif (!empty($receiver_username) && !empty($subject) && !empty($message_body) && mb_strlen($message_body, 'UTF-8') > 5000) {
        $feedback = "<div class='alert error'>The message must be 5000 characters or fewer.</div>";
    } elseif (!empty($receiver_username) && !empty($subject) && !empty($message_body)) {
        $check_receiver =$conn->prepare("SELECT User_role 
                                        FROM user 
                                        WHERE username = ? AND User_role != 'Resident'");
        $check_receiver->bind_param("s", $receiver_username);$check_receiver->execute();
        $receiver_res =$check_receiver->get_result();

        if ($receiver_res->num_rows > 0) {
            $receiver_data =$receiver_res->fetch_assoc();
            $receiver_role =$receiver_data['User_role'];

            // the subject and message text are stored ENCRYPTED
            try {
                $subject_enc = encryptText($subject);
                $message_body_enc = encryptText($message_body);
            } catch (Throwable $encryptError) {
                error_log("[M-LINK] Message could not be encrypted: " . $encryptError->getMessage());
                $subject_enc = null;
            }

            if ($subject_enc === null) {
                $feedback = "<div class='alert error'>Error sending message. Please try again.</div>";
            } else {
                $insert_stmt =$conn->prepare("INSERT INTO internal_messages (sender_username, sender_role, receiver_username, receiver_role, subject, message_body) VALUES (?, ?, ?, ?, ?, ?)");
                $insert_stmt->bind_param("ssssss", $current_user,$current_role, $receiver_username,$receiver_role, $subject_enc,$message_body_enc);

                if ($insert_stmt->execute()) {$feedback = "<div class='alert success'>Message sent successfully!</div>";
                } else {
                    $feedback = "<div class='alert error'>Error sending message. Please try again.</div>";
                }
                $insert_stmt->close();
            }
        } else {
            $feedback = "<div class='alert error'>Invalid recipient selected.</div>";
        }
        $check_receiver->close();
    } else {
        $feedback = "<div class='alert error'>All fields are required.</div>";
    }
}

// Fetch active conversation recipient if provided in query string
$active_user = isset($_GET['user']) ? trim($_GET['user']) : '';$active_user_data = null;
$conversation_messages = [];

if (!empty($active_user)) {
    // Fetch active recipient details
    $act_stmt =$conn->prepare("
        SELECT u.username, u.first_Name, u.surname, u.User_role, pi.image_path 
        FROM user u 
        LEFT JOIN profile_images pi ON u.profile_id = pi.profile_id 
        WHERE u.username = ? AND u.User_role != 'Resident'
    ");
    $act_stmt->bind_param("s", $active_user);$act_stmt->execute();
    $act_res =$act_stmt->get_result();
    if ($act_res->num_rows > 0) {
        $active_user_data =$act_res->fetch_assoc();

        // Mark incoming unread messages from this sender as read
        $read_stmt =$conn->prepare("UPDATE internal_messages SET is_read = 1 WHERE sender_username = ? AND receiver_username = ?");
        $read_stmt->bind_param("ss", $active_user,$current_user);
        $read_stmt->execute();$read_stmt->close();

        // Fetch past back-and-forth messages between current user and active recipient
        $msg_stmt =$conn->prepare("
            SELECT * FROM internal_messages 
            WHERE (sender_username = ? AND receiver_username = ?) 
               OR (sender_username = ? AND receiver_username = ?) 
            ORDER BY sent_at ASC
        ");
        $msg_stmt->bind_param("ssss", $current_user,$active_user, $active_user,$current_user);
        $msg_stmt->execute();$conversation_messages = $msg_stmt->get_result();$msg_stmt->close();
    }
    $act_stmt->close();
}

// Fetch unread count for badge
$unread_stmt =$conn->prepare("SELECT COUNT(*) AS unread_count FROM internal_messages WHERE receiver_username = ? AND is_read = 0");
$unread_stmt->bind_param("s", $current_user);
$unread_stmt->execute();$unread_res = $unread_stmt->get_result()->fetch_assoc();$unread_count = $unread_res['unread_count'];$unread_stmt->close();

// Fetch list of non-resident staff members for dropdown
$staff_stmt =$conn->prepare("SELECT username, first_Name, surname, User_role FROM user WHERE User_role != 'Resident' AND username != ? ORDER BY first_Name ASC");
$staff_stmt->bind_param("s", $current_user);$staff_stmt->execute();
$staff_list =$staff_stmt->get_result();

// Fetch distinct conversation threads (sent or received) grouped by contact and sorted by latest message
$inbox_query = "SELECT 
        contact.username AS other_username,
        contact.first_Name,
        contact.surname,
        contact.User_role AS contact_role,
        latest.latest_sent_at,
        m_last.message_body
    FROM (
        SELECT 
            IF(sender_username = ?, receiver_username, sender_username) AS contact_username,
            MAX(sent_at) AS latest_sent_at,
            MAX(message_id) AS latest_msg_id
        FROM internal_messages
        WHERE sender_username = ? OR receiver_username = ?
        GROUP BY contact_username
    ) latest
    JOIN user contact ON contact.username = latest.contact_username
    JOIN internal_messages m_last ON m_last.message_id = latest.latest_msg_id
    ORDER BY latest.latest_sent_at DESC
";

$inbox_stmt =$conn->prepare($inbox_query);$inbox_stmt->bind_param("sss", $current_user,$current_user, $current_user);$inbox_stmt->execute();
$inbox_messages =$inbox_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages</title>
    <link rel="stylesheet" href="messages.css">
<!-- FONTS + ICONS (same ones the communication officer dashboard uses) -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;500;600;700&family=Ubuntu:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
 

</head>
<body>

<main class="dashboard-content">

        <div class="messages-heading">
            <span class="messages-eyebrow">Makana Municipality</span>
            <h2>Messages</h2>
            <p><b>Note</b>: Messages are not private.</p>
        </div>

        <div id="feedback-area">
            <?php echo $feedback; ?>
        </div>

        <!-- CHAT APPLICATION CONTAINER -->
        <div class="chat-app">
            <!-- Left Contacts Pane -->
            <div class="chat-contacts">
                <div class="sidebar-header">
                    <h2>Messages</h2>
                </div>
                
                <div class="search-select-wrapper">
                    <span class="material-icons search-icon">search</span>
                    <select id="contactSelect" onchange="if(this.value) { window.location.href='messages.php?user=' + encodeURIComponent(this.value); }">
                        <option value="">Search staff member</option>
                        <?php 
                        if ($staff_list) {$staff_list->data_seek(0);
                            while($staff =$staff_list->fetch_assoc()): 
                        ?>
                            <option value="<?php echo htmlspecialchars($staff['username']); ?>" <?php echo ($active_user ===$staff['username']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($staff['first_Name'] . " " . $staff['surname']); ?>
                            </option>
                        <?php 
                            endwhile; 
                        }
                        ?>
                    </select>
                </div>

                <div class="contact-list">
                    <?php if ($inbox_messages &&$inbox_messages->num_rows > 0): ?>
                        <?php while($msg =$inbox_messages->fetch_assoc()): ?>
                            <a href="messages.php?user=<?php echo urlencode($msg['other_username']); ?>" class="contact-item <?php echo ($active_user ===$msg['other_username']) ? 'active' : ''; ?>">
                                <div class="contact-top">
                                    <span class="contact-name"><?php echo htmlspecialchars($msg['first_Name'] . " " . $msg['surname']); ?></span>
                                    <span class="contact-time"><?php echo date("d M", strtotime($msg['latest_sent_at'])); ?></span>
                                </div>
                                <span class="contact-role"><?php echo htmlspecialchars($msg['contact_role']); ?></span>
                                <div class="contact-bottom">
                                    <span class="contact-preview"><?php echo htmlspecialchars(mb_strimwidth(decryptText($msg['message_body']), 0, 80, '...', 'UTF-8')); ?></span>
                                </div>
                            </a>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="empty-text">No recent conversations.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Message Thread Panel -->
            <div class="thread-panel">
                <?php if ($active_user_data): ?>
                    <!-- Active Thread Header -->
                    <div class="thread-header">
                        <div class="user-info">
                            <h3><?php echo htmlspecialchars($active_user_data['first_Name'] . " " . $active_user_data['surname']); ?></h3>
                            <span class="user-role-badge"><?php echo htmlspecialchars($active_user_data['User_role']); ?></span>
                        </div>
                    </div>

                    <!-- Conversation History Stream -->
                    <div class="messages-stream" id="messagesStream">
                        <?php if ($conversation_messages &&$conversation_messages->num_rows > 0): ?>
                            <?php while($c_msg =$conversation_messages->fetch_assoc()): ?>
                                <?php $is_outgoing = ($c_msg['sender_username'] ===$current_user); ?>
                                <div class="message-bubble <?php echo $is_outgoing ? 'outgoing' : 'incoming'; ?>">
                                    <div class="message-subject"><strong>Subject:</strong> <?php echo htmlspecialchars(decryptText($c_msg['subject'])); ?></div>
                                    <div class="message-body"><?php echo nl2br(htmlspecialchars(decryptText($c_msg['message_body']))); ?></div>
                                    <div class="message-meta"><?php echo date("d M Y, H:i", strtotime($c_msg['sent_at'])); ?></div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="empty-text">No message history with this user yet. Write a message below to start chatting!</p>
                        <?php endif; ?>
                    </div>

                    <!-- Message Sending Form -->
                    <form action="messages.php?user=<?php echo urlencode($active_user); ?>" method="POST" class="message-form">
                        <input type="hidden" name="receiver_username" value="<?php echo htmlspecialchars($active_user); ?>">
                        <div class="form-group">
                            <input type="text" name="subject" placeholder="Subject..." maxlength="150" required class="subject-input">
                        </div>
                        <div class="form-group input-container">
                            <textarea name="message_body" placeholder="Type your message..." maxlength="5000" required rows="2" class="message-textarea"></textarea>
                            <button type="submit" name="send_message" class="send-btn">Send</button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="thread-empty">
                        <p class="empty-text">Select a conversation, or search for a staff member to start a new one.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        </main>

<script src="messages.js"></script>
<!-- Shared communication officer sidebar + topbar -->
<script src="Global dashboard layout.js"></script>
</body>
</html>