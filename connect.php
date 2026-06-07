<?php
/**
 * Database Connection File
 * This file contains the database connection configuration
 * Include this file in other PHP files to access the database
 */

// Database configuration
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'viotrack');

// Create connection
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4 for proper character encoding
$conn->set_charset("utf8mb4");

/**
 * Function to close database connection
 * Call this at the end of your scripts
 */
function closeConnection() {
    global $conn;
    if ($conn) {
        $conn->close();
    }
}

/**
 * Function to sanitize input data
 * Use this to prevent SQL injection and XSS attacks
 */
function sanitize($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $conn->real_escape_string($data);
}

/**
 * Function to execute a query and return results
 */
function executeQuery($sql) {
    global $conn;
    $result = $conn->query($sql);
    if (!$result) {
        error_log("Database Error: " . $conn->error);
        return false;
    }
    return $result;
}

/**
 * Function to get single row as associative array
 */
function fetchSingle($sql) {
    $result = executeQuery($sql);
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    return null;
}

/**
 * Function to get all rows as associative array
 */
function fetchAll($sql) {
    $result = executeQuery($sql);
    $data = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    return $data;
}

/**
 * Function to execute INSERT, UPDATE, DELETE queries
 * Returns true on success, false on failure
 */
function executeNonQuery($sql) {
    global $conn;
    if ($conn->query($sql) === TRUE) {
        return true;
    } else {
        error_log("Database Error: " . $conn->error);
        return false;
    }
}

/**
 * Function to get the last inserted ID
 */
function getLastInsertId() {
    global $conn;
    return $conn->insert_id;
}

/**
 * Function to begin transaction
 */
function beginTransaction() {
    global $conn;
    return $conn->begin_transaction();
}

/**
 * Function to commit transaction
 */
function commitTransaction() {
    global $conn;
    return $conn->commit();
}

/**
 * Function to rollback transaction
 */
function rollbackTransaction() {
    global $conn;
    return $conn->rollback();
}

/**
 * Function to check if connection is active
 */
function isConnected() {
    global $conn;
    return $conn && $conn->ping();
}

// Optional: Set timezone (adjust as needed)
date_default_timezone_set('Asia/Manila');

// Optional: Enable error reporting for development (disable in production)
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);