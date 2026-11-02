<?php
/**
 * layout_user_info.php
 * ---------------------------------------------------------------
 * Tiny JSON endpoint used by global_dashboard_layout.js.
 * Put it in the SAME folder as communication_officer_dashboard.php
 * and global_dashboard_layout.js.
 *
 * It returns the logged-in officer's real name, profile picture and
 * unread-notification count so every page can show them in the
 * shared sidebar / topbar (JavaScript can't read the PHP session).
 * ---------------------------------------------------------------
 */
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
 
if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['enabled' => false, 'error' => 'not_logged_in']);
    exit;
}
 
/* ---------------------------------------------------------------
   OPTIONAL: only show the officer layout to certain roles.
   Leave the list EMPTY to show it to every logged-in user.
   Set it (e.g. ['communication_officer']) if other roles, such as
   residents, can open the same pages (reportFaultTicketForm.php).
   Other roles then keep the page's own sidebar.
---------------------------------------------------------------- */
$allowedRoles = [];   // e.g. ['communication_officer', 'communication officer']
$roleSessionKeys = ['User_role', 'user_role', 'role', 'user_type', 'role_name', 'account_type'];
 
function layoutNormalise($value): string
{
    return strtolower(trim(str_replace(['_', '-'], ' ', (string)$value)));
}
 
$enabled = true;
if (!empty($allowedRoles)) {
    $sessionRole = '';
    foreach ($roleSessionKeys as $key) {
        if (!empty($_SESSION[$key])) {
            $sessionRole = layoutNormalise($_SESSION[$key]);
            break;
        }
    }
    $enabled = in_array($sessionRole, array_map('layoutNormalise', $allowedRoles), true);
}
 
/* ---------------------------------------------------------------
   Database connection (same file the dashboard uses)
---------------------------------------------------------------- */
$conn = null;
ob_start(); // swallow any stray output from the connection file so the JSON stays clean
foreach ([__DIR__ . '/Error404Connection.php', __DIR__ . '/../Error404Connection.php'] as $connectionFile) {
    if (is_file($connectionFile)) {
        try {
            require_once $connectionFile;
        } catch (Throwable $connectionError) {
            $conn = null;
        }
        break;
    }
}
 
/* ---------------------------------------------------------------
   Name / picture  (same lookup as the dashboard; an email address
   is never used as the display name)
---------------------------------------------------------------- */
function layoutIsEmailLike($value): bool
{
    return strpos((string)$value, '@') !== false;
}
function layoutPickName(...$candidates): string
{
    foreach ($candidates as $candidate) {
        $candidate = trim((string)$candidate);
        if ($candidate !== '' && !layoutIsEmailLike($candidate)) {
            return $candidate;
        }
    }
    return '';
}
 
// Same session keys and same lookup as communication_officer_dashboard.php
$sessionUserId   = $_SESSION['username'] ?? $_SESSION['profile_id'] ?? $_SESSION['User_role'] ?? null;
$sessionUsername = $_SESSION['username'] ?? $_SESSION['first_name'] ?? $_SESSION['surname'] ?? $_SESSION['profile_id'] ?? null;
 
$name = '';
$pic  = $_SESSION['profile_id'] ?? '';
 
if ($enabled && $sessionUserId && isset($conn) && $conn instanceof mysqli) {
    try {
        // numeric session value = profile_id, otherwise it is the username
        if (is_numeric($sessionUserId)) {
            $stmt = $conn->prepare("SELECT surname, first_Name, username, profile_id FROM user WHERE profile_id = ? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param("i", $sessionUserId);
            }
        } else {
            $stmt = $conn->prepare("SELECT surname, first_Name, username, profile_id FROM user WHERE username = ? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param("s", $sessionUserId);
            }
        }
 
        if ($stmt) {
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $fullName = trim(($row['first_Name'] ?? '') . ' ' . ($row['surname'] ?? ''));
                $name = layoutPickName(
                    $fullName,
                    $row['first_Name'] ?? '',
                    $row['surname'] ?? '',
                    $row['username'] ?? ''
                );
                if (!empty($row['profile_id'])) {
                    $pic = $row['profile_id'];
                }
            }
            $stmt->close();
        }
    } catch (Throwable $e) {
        // fall back below
    }
}
 
// Fallback: a non-email session value, otherwise a neutral label (never the raw email address)
if ($name === '') {
    $name = layoutPickName($sessionUsername, $_SESSION['name'] ?? '') ?: 'Officer';
}
 
/* ---------------------------------------------------------------
   Unread notifications (bell badge)
---------------------------------------------------------------- */
$unread = 0;
if ($enabled && isset($conn) && $conn instanceof mysqli) {
    try {
        $stmt = $conn->prepare(
            "SELECT COUNT(*) AS unread_count
             FROM notification
             WHERE username = ?
             AND notification_type = 'ticket_update'
             AND is_read = 0"
        );
        if ($stmt) {
            $username = (string)$_SESSION['username'];
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && ($row = $res->fetch_assoc())) {
                $unread = (int)$row['unread_count'];
            }
            $stmt->close();
        }
    } catch (Throwable $e) {
        $unread = 0;
    }
}
 
/* ---------------------------------------------------------------
   Unread internal messages (sidebar "Messages" badge)
---------------------------------------------------------------- */
$unreadMessages = 0;
if ($enabled && isset($conn) && $conn instanceof mysqli) {
    try {
        $stmt = $conn->prepare(
            "SELECT COUNT(*) AS unread_count
             FROM internal_messages
             WHERE receiver_username = ?
             AND is_read = 0"
        );
        if ($stmt) {
            $username = (string)$_SESSION['username'];
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && ($row = $res->fetch_assoc())) {
                $unreadMessages = (int)$row['unread_count'];
            }
            $stmt->close();
        }
    } catch (Throwable $e) {
        $unreadMessages = 0;
    }
}
 
ob_end_clean();
 
echo json_encode([
    'enabled' => $enabled,
    'uid'     => (string)$_SESSION['username'],
    'name'    => $name,
    'pic'     => (string)$pic,
    'unread'  => $unread,
    'messages' => $unreadMessages,
]);
 