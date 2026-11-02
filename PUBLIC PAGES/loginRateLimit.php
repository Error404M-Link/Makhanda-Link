<?php
/*
 * loginRateLimit.php
 * ###################################################################
 * Limits failed login attempts (brute-force protection).
 *
 *  - 5 failed attempts for the same username within 15 minutes locks
 *    that username for the rest of the 15 minutes.
 *  - 20 failed attempts from the same IP address within 15 minutes
 *    locks that IP address for the rest of the 15 minutes.
 *  - a successful login clears the failed attempts for that username.
 *
 * Attempts are stored in the `login_attempts` table. The table is
 * created automatically the first time it is needed (the database user
 * needs the CREATE privilege). If the table cannot be created or read,
 * login still works, but is NOT rate limited - check the PHP error log
 * for "login_attempts" messages.
 *
 * DATABASE TABLE THAT RECORDS THE LOGIN ATTEMPTS:
 *
 *   CREATE TABLE IF NOT EXISTS login_attempts (
 *       attempt_id   INT AUTO_INCREMENT PRIMARY KEY,
 *       username     VARCHAR(50) NOT NULL,
 *       ip_address   VARCHAR(45) NOT NULL,
 *       attempted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 *       INDEX idx_login_user_time (username, attempted_at),
 *       INDEX idx_login_ip_time (ip_address, attempted_at)
 *   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
 * ######################################################################
 */

const LOGIN_MAX_ATTEMPTS_PER_USERNAME = 5;
const LOGIN_MAX_ATTEMPTS_PER_IP       = 20;
const LOGIN_LOCK_WINDOW_MINUTES       = 15;


function loginClientIp()
{
    // REMOTE_ADDR is the only value the visitor cannot fake.
    return substr((string)($_SERVER["REMOTE_ADDR"] ?? "unknown"), 0, 45);
}


function loginEnsureAttemptsTable($conn)
{
    try {

        $conn->query(
            "CREATE TABLE IF NOT EXISTS login_attempts (
                attempt_id   INT AUTO_INCREMENT PRIMARY KEY,
                username     VARCHAR(50) NOT NULL,
                ip_address   VARCHAR(45) NOT NULL,
                attempted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_login_user_time (username, attempted_at),
                INDEX idx_login_ip_time (ip_address, attempted_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        return true;

    } catch (\Throwable $error) {

        error_log("login_attempts table could not be created: " . $error->getMessage());

        return false;

    }
}


/**
 * TRUE when this username or this IP address has made too many failed
 * attempts recently.
 */
function isLoginLocked($conn, $username)
{
    if (!loginEnsureAttemptsTable($conn)) {

        return false;

    }

    $username = strtolower($username);
    $ip       = loginClientIp();
    $window   = (int)LOGIN_LOCK_WINDOW_MINUTES;

    try {

        $stmt = $conn->prepare(
            "SELECT
                SUM(username = ?)   AS username_attempts,
                SUM(ip_address = ?) AS ip_attempts
             FROM login_attempts
             WHERE attempted_at > (NOW() - INTERVAL $window MINUTE)
             AND (username = ? OR ip_address = ?)"
        );

        if (!$stmt) {

            return false;

        }

        $stmt->bind_param("ssss", $username, $ip, $username, $ip);
        $stmt->execute();

        $row = $stmt->get_result()->fetch_assoc();

        $stmt->close();

        return
            (int)($row["username_attempts"] ?? 0) >= LOGIN_MAX_ATTEMPTS_PER_USERNAME ||
            (int)($row["ip_attempts"] ?? 0) >= LOGIN_MAX_ATTEMPTS_PER_IP;

    } catch (\Throwable $error) {

        error_log("login_attempts check failed: " . $error->getMessage());

        return false;

    }
}


function recordFailedLogin($conn, $username)
{
    $username = strtolower(substr($username, 0, 50));
    $ip       = loginClientIp();

    try {

        $stmt = $conn->prepare(
            "INSERT INTO login_attempts (username, ip_address) VALUES (?, ?)"
        );

        if ($stmt) {

            $stmt->bind_param("ss", $username, $ip);
            $stmt->execute();
            $stmt->close();

        }

        // housekeeping: forget attempts older than a day
        $conn->query(
            "DELETE FROM login_attempts
             WHERE attempted_at < (NOW() - INTERVAL 1 DAY)"
        );

    } catch (\Throwable $error) {

        error_log("login_attempts record failed: " . $error->getMessage());

    }
}


function clearFailedLogins($conn, $username)
{
    $username = strtolower($username);

    try {

        $stmt = $conn->prepare(
            "DELETE FROM login_attempts WHERE username = ?"
        );

        if ($stmt) {

            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->close();

        }

    } catch (\Throwable $error) {

        error_log("login_attempts clear failed: " . $error->getMessage());

    }
}


/**
 * Record a failed attempt and send the visitor back to the login page
 * with the generic "invalid username or password" message.
 */
function failLogin($conn, $username)
{
    recordFailedLogin($conn, $username);

    header("Location: login.php?error=invalid");

    exit;
}