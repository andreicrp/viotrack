<?php
// activity.php - Improved Version
include 'auth_check.php';

// Require login and admin access only
requireAdmin();

// Check user type (Admin or Teacher)
$isAdmin = (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin');
$isTeacher = (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'teacher');

include 'header.php';
include 'sidebar.php';

// Pagination settings
$activitiesPerPage = 25;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $activitiesPerPage;

// Get filter parameters
$filterAction = isset($_GET['filter']) ? trim($_GET['filter']) : 'all';
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$startDate = isset($_GET['start']) ? trim($_GET['start']) : '';
$endDate = isset($_GET['end']) ? trim($_GET['end']) : '';

// Build WHERE clause with prepared statement placeholders
$whereConditions = [];
$params = [];
$types = '';

// Date range filter
if (!empty($startDate) && !empty($endDate)) {
    $whereConditions[] = "a.date >= ? AND a.date <= ?";
    $params[] = $startDate . ' 00:00:00';
    $params[] = $endDate . ' 23:59:59';
    $types .= 'ss';
} else {
    // Default to last 30 days if no date range specified
    $whereConditions[] = "a.date >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
}

// Only apply search filter if there's actually a search term
$hasSearchFilter = false;

// Enhanced Action filter with better categorization
if ($filterAction !== 'all') {
    switch($filterAction) {
        case 'created':
            $whereConditions[] = "(a.description LIKE '%Insert%' OR a.description LIKE '%Add%' OR a.description LIKE '%New%')";
            break;
        case 'updated':
            $whereConditions[] = "(a.description LIKE '%Edit%' OR a.description LIKE '%Update%' OR a.description LIKE '%Modify%')";
            break;
        case 'deleted':
            $whereConditions[] = "a.description LIKE '%Delete%'";
            break;
        case 'logins':
            $whereConditions[] = "(a.description LIKE '%Login%' OR a.description LIKE '%Logged%')";
            break;
        case 'records':
            $whereConditions[] = "a.description LIKE '%Record%'";
            break;
        case 'violations':
            $whereConditions[] = "a.description LIKE '%Violation%'";
            break;
        case 'students':
            $whereConditions[] = "a.description LIKE '%Student%'";
            break;
        case 'teachers':
            $whereConditions[] = "a.description LIKE '%Teacher%'";
            break;
        case 'sms':
            $whereConditions[] = "a.description LIKE '%SMS%'";
            break;
    }
}

// Enhanced Search term - search in description and teacher name
if (!empty($searchTerm)) {
    $whereConditions[] = "(a.description LIKE ? OR t.fname LIKE ? OR t.lname LIKE ? OR adm.fname LIKE ? OR adm.lname LIKE ?)";
    $params[] = '%' . $searchTerm . '%';
    $params[] = '%' . $searchTerm . '%';
    $params[] = '%' . $searchTerm . '%';
    $params[] = '%' . $searchTerm . '%';
    $params[] = '%' . $searchTerm . '%';
    $types .= 'sssss';
    $hasSearchFilter = true;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Fetch total activities count
$countQuery = "SELECT COUNT(*) AS total FROM activity a 
              LEFT JOIN admin adm ON a.aid = adm.id 
              LEFT JOIN teacher t ON a.aid = t.id 
              $whereClause";
$countStmt = $conn->prepare($countQuery);
if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$countResult = $countStmt->get_result();
$countRow = $countResult->fetch_assoc();
$total_activities = $countRow['total'] ?? 0;
$countStmt->close();

// Fetch activities with pagination
$query = "SELECT a.id, a.description, a.date, a.aid, 
                 COALESCE(adm.fname, t.fname) as fname, 
                 COALESCE(adm.lname, t.lname) as lname, 
                 COALESCE(adm.mname, t.mname) as mname
          FROM activity a
          LEFT JOIN admin adm ON a.aid = adm.id 
          LEFT JOIN teacher t ON a.aid = t.id 
          $whereClause
          ORDER BY a.date DESC
          LIMIT ?, ?";

$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

// Bind parameters
$allParams = array_merge($params, [$offset, $activitiesPerPage]);
$allTypes = $types . 'ii';
$stmt->bind_param($allTypes, ...$allParams);

if (!$stmt->execute()) {
    die("Execute failed: " . $stmt->error);
}

$result = $stmt->get_result();

// Fetch statistics - today's activities
$todayStart = date('Y-m-d 00:00:00');
$todayEnd = date('Y-m-d 23:59:59');
$todayQuery = "SELECT COUNT(*) AS today_count FROM activity WHERE date >= ? AND date <= ?";
$todayStmt = $conn->prepare($todayQuery);
$todayStmt->bind_param('ss', $todayStart, $todayEnd);
$todayStmt->execute();
$todayRow = $todayStmt->get_result()->fetch_assoc();
$today_actions = $todayRow['today_count'] ?? 0;
$todayStmt->close();

// Fetch statistics - this week
$weekStart = date('Y-m-d 00:00:00', strtotime('-7 days'));
$weekQuery = "SELECT COUNT(*) AS week_count FROM activity WHERE date >= ?";
$weekStmt = $conn->prepare($weekQuery);
$weekStmt->bind_param('s', $weekStart);
$weekStmt->execute();
$weekRow = $weekStmt->get_result()->fetch_assoc();
$this_week = $weekRow['week_count'] ?? 0;
$weekStmt->close();

// Calculate percentage changes - comparing last 30 days with previous 30 days
$thirtyDaysAgo = date('Y-m-d 00:00:00', strtotime('-30 days'));
$sixtyDaysAgo = date('Y-m-d 00:00:00', strtotime('-60 days'));

$prevQuery = "SELECT COUNT(*) AS prev_count FROM activity WHERE date >= ? AND date < ?";
$prevStmt = $conn->prepare($prevQuery);
$prevStmt->bind_param('ss', $sixtyDaysAgo, $thirtyDaysAgo);
$prevStmt->execute();
$prevRow = $prevStmt->get_result()->fetch_assoc();
$prev_total_activities = $prevRow['prev_count'] ?? 1;
$prevStmt->close();

// Calculate percentage change (avoid division by zero)
if ($prev_total_activities > 0) {
    $total_change = round((($total_activities - $prev_total_activities) / $prev_total_activities) * 100);
} else {
    $total_change = 0;
}

// Calculate today's trend (compare today with yesterday)
$yesterdayStart = date('Y-m-d 00:00:00', strtotime('-1 day'));
$yesterdayEnd = date('Y-m-d 23:59:59', strtotime('-1 day'));
$yesterdayQuery = "SELECT COUNT(*) AS yesterday_count FROM activity WHERE date >= ? AND date <= ?";
$yesterdayStmt = $conn->prepare($yesterdayQuery);
$yesterdayStmt->bind_param('ss', $yesterdayStart, $yesterdayEnd);
$yesterdayStmt->execute();
$yesterdayRow = $yesterdayStmt->get_result()->fetch_assoc();
$yesterday_actions = $yesterdayRow['yesterday_count'] ?? 1;
$yesterdayStmt->close();

$today_trend = $yesterday_actions > 0 ? round((($today_actions - $yesterday_actions) / $yesterday_actions) * 100) : 0;

// Calculate this week's trend (compare this week with last week)
$thisWeekStart = date('Y-m-d 00:00:00', strtotime('-7 days'));
$lastWeekStart = date('Y-m-d 00:00:00', strtotime('-14 days'));
$lastWeekEnd = date('Y-m-d 23:59:59', strtotime('-8 days'));

$lastWeekQuery = "SELECT COUNT(*) AS last_week_count FROM activity WHERE date >= ? AND date <= ?";
$lastWeekStmt = $conn->prepare($lastWeekQuery);
$lastWeekStmt->bind_param('ss', $lastWeekStart, $lastWeekEnd);
$lastWeekStmt->execute();
$lastWeekRow = $lastWeekStmt->get_result()->fetch_assoc();
$last_week = $lastWeekRow['last_week_count'] ?? 1;
$lastWeekStmt->close();

$week_trend = $last_week > 0 ? round((($this_week - $last_week) / $last_week) * 100) : 0;

// Group activities by date
$activities_grouped = [];

// Enhanced activity parsing function
function parseActivity($description) {
    $desc = $description ?? '';
    $action = 'System';
    $icon = 'info';
    $type = 'System';
    $details = '';
    
    // Determine action and type based on description patterns
    if (preg_match('/Insert New (Teacher|Student|Violation|Record)/i', $desc, $matches)) {
        $action = 'Created';
        $icon = 'created';
        $type = $matches[1];
        if (preg_match('/\(ID: (\d+)(?:, Name: (.+?))?\)/i', $desc, $detailMatch)) {
            $details = "ID: " . $detailMatch[1];
            if (isset($detailMatch[2])) $details .= " - " . $detailMatch[2];
        }
    } elseif (preg_match('/Edit (Teacher|Student|Profile|Violation)/i', $desc, $matches)) {
        $action = 'Updated';
        $icon = 'updated';
        $type = $matches[1] === 'Profile' ? 'Profile' : $matches[1];
        if (preg_match('/\(ID: (\d+)(?:, Name: (.+?))?\)/i', $desc, $detailMatch)) {
            $details = "ID: " . $detailMatch[1];
            if (isset($detailMatch[2])) $details .= " - " . $detailMatch[2];
        }
    } elseif (preg_match('/Delete (Teacher|Student|Record)/i', $desc, $matches)) {
        $action = 'Deleted';
        $icon = 'deleted';
        $type = $matches[1];
        if (preg_match('/\(ID: (\d+)\)/i', $desc, $detailMatch)) {
            $details = "ID: " . $detailMatch[1];
        }
    } elseif (preg_match('/Update Record Status to (.+)/i', $desc, $matches)) {
        $action = 'Updated';
        $icon = 'updated';
        $type = 'Record Status';
        $details = "Status: " . $matches[1];
    } elseif (preg_match('/Added (\d+) violation record/i', $desc, $matches)) {
        $action = 'Created';
        $icon = 'created';
        $type = 'Violation Records';
        $details = $matches[1] . " record(s) added";
        if (preg_match('/student ID (\d+)/i', $desc, $studentMatch)) {
            $details .= " for Student ID: " . $studentMatch[1];
        }
    } elseif (preg_match('/SMS report sent/i', $desc)) {
        $action = 'Sent';
        $icon = 'viewed';
        $type = 'SMS Report';
        if (preg_match('/to (\d+) for report type: (.+?) on/i', $desc, $smsMatch)) {
            $details = "To: " . $smsMatch[1] . " - Type: " . $smsMatch[2];
        }
    } elseif (preg_match('/Login|Logged/i', $desc)) {
        $action = 'Login';
        $icon = 'login';
        $type = 'Authentication';
    }
    
    return [
        'action' => $action,
        'icon' => $icon,
        'type' => $type,
        'details' => $details
    ];
}

while ($row = $result->fetch_assoc()) {
    // Skip invalid entries
    if (empty($row['date'])) {
        continue;
    }
    
    // Get user name with fallback
    $userName = 'System';
    if (!empty($row['fname']) || !empty($row['lname'])) {
        $parts = [];
        if (!empty($row['fname'])) $parts[] = $row['fname'];
        if (!empty($row['mname'])) $parts[] = $row['mname'];
        if (!empty($row['lname'])) $parts[] = $row['lname'];
        $userName = !empty($parts) ? implode(' ', $parts) : 'System';
    }
    
    // Validate and parse date
    $activityDate = strtotime($row['date']);
    if ($activityDate === false) {
        error_log("Invalid date format: " . $row['date']);
        continue;
    }
    
    $dateKey = date('Y-m-d', $activityDate);
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    
    // Determine date label
    if ($dateKey === $today) {
        $dateLabel = 'TODAY';
    } elseif ($dateKey === $yesterday) {
        $dateLabel = 'YESTERDAY';
    } else {
        $dateLabel = strtoupper(date('F j, Y', $activityDate));
    }
    
    // Parse activity details
    $parsed = parseActivity($row['description']);
    
    // Format time
    $time = date('h:i A', $activityDate);
    $relative_time = getRelativeTime($activityDate);
    
    // Create activity array
    $activity = [
        'id' => (int)$row['id'],
        'user' => htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'),
        'action' => $parsed['action'],
        'type' => $parsed['type'],
        'description' => htmlspecialchars($row['description'] ?? '', ENT_QUOTES, 'UTF-8'),
        'details' => $parsed['details'],
        'time' => $time,
        'relative_time' => $relative_time,
        'icon' => $parsed['icon']
    ];
    
    // Group by date
    if (!isset($activities_grouped[$dateLabel])) {
        $activities_grouped[$dateLabel] = [];
    }
    $activities_grouped[$dateLabel][] = $activity;
}

$stmt->close();

// Calculate total pages
$totalPages = ceil($total_activities / $activitiesPerPage);

// Function to get relative time
function getRelativeTime($timestamp) {
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 172800) {
        return 'Yesterday';
    } else {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    }
}
?>

<main class="main-content">
    <!-- Stats Cards -->
    <div class="activity-stats">
        <div class="activity-stat-card">
            <div class="stat-icon-wrapper activities">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $total_activities; ?></div>
                <div class="stat-label">Total Activities</div>
                <div class="stat-change <?php echo $total_change >= 0 ? 'positive' : 'negative'; ?>">
                    <i class="fas fa-arrow-<?php echo $total_change >= 0 ? 'up' : 'down'; ?>"></i> 
                    <?php echo abs($total_change); ?>%
                </div>
            </div>
        </div>
        
        <div class="activity-stat-card">
            <div class="stat-icon-wrapper today">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $today_actions; ?></div>
                <div class="stat-label">Today's Actions</div>
            </div>
        </div>
        
        <div class="activity-stat-card">
            <div class="stat-icon-wrapper week">
                <i class="fas fa-calendar-week"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $this_week; ?></div>
                <div class="stat-label">This Week</div>
            </div>
        </div>
    </div>

    <!-- Activity Log Card -->
    <div class="card activity-card" style="margin: 20px;">
        <div class="card-header" style="flex-direction: row; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; margin-bottom: 24px;">
            <div class="card-title-section">
                <h3 style="font-size: 22px; font-weight: 700; margin: 0 0 4px 0; color: #1f2937;">Activity Log</h3>
                <p style="font-size: 13px; color: #6b7280; margin: 0;"><?php echo $isAdmin ? 'Monitor all admin actions and system changes' : 'View your activities'; ?></p>
            </div>
            <div class="header-actions" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                <?php if ($isAdmin): ?>
                <button class="btn btn-danger btn-delete-selected" id="deleteSelectedBtn" style="display: none; background: #ef4444; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 13px; transition: all 0.2s;">
                    <i class="fas fa-trash"></i> Delete Selected (<span id="selectedCount">0</span>)
                </button>
                <?php endif; ?>
                <button class="btn btn-refresh" onclick="location.reload()" style="background: #6366f1; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 13px; transition: all 0.2s; display: flex; align-items: center; gap: 6px;">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
        </div>

        <!-- Enhanced Filters -->
        <div class="activity-filters" style="margin-bottom: 24px;">
            <div class="filter-tabs" style="display: flex; gap: 8px; margin-bottom: 16px; flex-wrap: wrap; border-bottom: 2px solid #e5e7eb; padding-bottom: 12px;">
                <button class="filter-tab <?php echo $filterAction === 'all' ? 'active' : ''; ?>" data-filter="all" style="padding: 6px 12px; border: none; background: <?php echo $filterAction === 'all' ? '#6366f1' : 'transparent'; ?>; color: <?php echo $filterAction === 'all' ? 'white' : '#6b7280'; ?>; border-radius: 4px; cursor: pointer; font-weight: 500; font-size: 13px; transition: all 0.2s;">All</button>
                <?php if ($isAdmin): ?>
                <button class="filter-tab <?php echo $filterAction === 'created' ? 'active' : ''; ?>" data-filter="created" style="padding: 6px 12px; border: none; background: <?php echo $filterAction === 'created' ? '#10b981' : 'transparent'; ?>; color: <?php echo $filterAction === 'created' ? 'white' : '#6b7280'; ?>; border-radius: 4px; cursor: pointer; font-weight: 500; font-size: 13px; transition: all 0.2s;">Created</button>
                <button class="filter-tab <?php echo $filterAction === 'updated' ? 'active' : ''; ?>" data-filter="updated" style="padding: 6px 12px; border: none; background: <?php echo $filterAction === 'updated' ? '#f59e0b' : 'transparent'; ?>; color: <?php echo $filterAction === 'updated' ? 'white' : '#6b7280'; ?>; border-radius: 4px; cursor: pointer; font-weight: 500; font-size: 13px; transition: all 0.2s;">Updated</button>
                <button class="filter-tab <?php echo $filterAction === 'deleted' ? 'active' : ''; ?>" data-filter="deleted" style="padding: 6px 12px; border: none; background: <?php echo $filterAction === 'deleted' ? '#ef4444' : 'transparent'; ?>; color: <?php echo $filterAction === 'deleted' ? 'white' : '#6b7280'; ?>; border-radius: 4px; cursor: pointer; font-weight: 500; font-size: 13px; transition: all 0.2s;">Deleted</button>
                <button class="filter-tab <?php echo $filterAction === 'violations' ? 'active' : ''; ?>" data-filter="violations" style="padding: 6px 12px; border: none; background: <?php echo $filterAction === 'violations' ? '#a855f7' : 'transparent'; ?>; color: <?php echo $filterAction === 'violations' ? 'white' : '#6b7280'; ?>; border-radius: 4px; cursor: pointer; font-weight: 500; font-size: 13px; transition: all 0.2s;">Violations</button>
                <?php endif; ?>
            </div>
            
            <div class="filter-right" style="display: flex; gap: 12px; flex-wrap: wrap;">
                <div class="search-box" style="position: relative; flex: 1; min-width: 200px;">
                    <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #9ca3af; font-size: 14px;"></i>
                    <input type="text" id="activitySearch" placeholder="Search activities..." value="<?php echo htmlspecialchars($searchTerm); ?>" style="width: 100%; padding: 8px 12px 8px 36px; border: 2px solid #e5e7eb; border-radius: 6px; font-size: 13px; transition: all 0.2s; box-sizing: border-box;">
                </div>
                
                <div class="date-range-picker" style="position: relative;">
                    <button class="date-range-btn" id="dateRangeBtn" style="padding: 8px 14px; border: 2px solid #e5e7eb; background: white; border-radius: 6px; cursor: pointer; font-size: 13px; font-weight: 500; color: #6b7280; display: flex; align-items: center; gap: 6px; transition: all 0.2s; white-space: nowrap;">
                        <i class="fas fa-calendar-alt" style="font-size: 13px;"></i>
                        <span id="dateRangeText">Last 30 days</span>
                        <i class="fas fa-chevron-down" style="font-size: 11px;"></i>
                    </button>
                    
                    <div class="date-range-dropdown" id="dateRangeDropdown" style="display: none; position: absolute; top: 100%; right: 0; background: white; border: 2px solid #e5e7eb; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.12); z-index: 1000; min-width: 280px; margin-top: 4px;">
                        <div class="dropdown-header" style="padding: 12px 16px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; font-weight: 600; color: #1f2937; font-size: 13px;">
                            Select Date Range
                            <button class="close-dropdown" onclick="closeDateRangePicker()" style="background: none; border: none; cursor: pointer; color: #9ca3af; font-size: 16px; padding: 0;">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        
                        <div class="date-presets" style="padding: 12px; display: flex; flex-direction: column; gap: 6px; border-bottom: 1px solid #e5e7eb;">
                            <button class="preset-btn" data-preset="today" style="padding: 8px 12px; border: 1px solid #d1d5db; background: white; border-radius: 4px; cursor: pointer; font-size: 13px; text-align: left; transition: all 0.2s;">Today</button>
                            <button class="preset-btn" data-preset="7days" style="padding: 8px 12px; border: 1px solid #d1d5db; background: white; border-radius: 4px; cursor: pointer; font-size: 13px; text-align: left; transition: all 0.2s;">Last 7 days</button>
                            <button class="preset-btn active" data-preset="30days" style="padding: 8px 12px; border: 2px solid #6366f1; background: #f0f4ff; border-radius: 4px; cursor: pointer; font-size: 13px; text-align: left; transition: all 0.2s; color: #6366f1; font-weight: 600;">Last 30 days</button>
                        </div>
                        
                        <div class="custom-range-section" style="padding: 12px 16px;">
                            <div class="custom-range-header" style="font-weight: 600; color: #1f2937; font-size: 13px; margin-bottom: 10px;">Custom Range</div>
                            <div class="date-inputs" style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 12px;">
                                <div class="date-input-group" style="display: flex; flex-direction: column; gap: 4px;">
                                    <label class="date-input-label" style="font-size: 12px; font-weight: 500; color: #6b7280;">Start Date</label>
                                    <input type="date" class="date-input" id="startDate" value="<?php echo $startDate; ?>" style="padding: 6px 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 13px;">
                                </div>
                                <div class="date-input-group" style="display: flex; flex-direction: column; gap: 4px;">
                                    <label class="date-input-label" style="font-size: 12px; font-weight: 500; color: #6b7280;">End Date</label>
                                    <input type="date" class="date-input" id="endDate" value="<?php echo $endDate; ?>" style="padding: 6px 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 13px;">
                                </div>
                            </div>
                            <button class="apply-filter-btn" onclick="applyDateRange()" style="width: 100%; padding: 8px 12px; background: #6366f1; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 13px; transition: all 0.2s;">
                                Apply Filter
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Activity Timeline -->
        <div class="activity-timeline" style="max-height: 600px; overflow-y: auto;">
            <?php if (empty($activities_grouped)): ?>
                <div class="no-activities" style="text-align: center; padding: 60px 20px;">
                    <i class="fas fa-inbox" style="font-size: 48px; color: #cbd5e1; display: block; margin-bottom: 16px;"></i>
                    <p style="margin: 0; color: #9ca3af; font-size: 15px; font-weight: 500;">No activities found</p>
                </div>
            <?php else: ?>
                <?php foreach ($activities_grouped as $dateLabel => $activities): ?>
                <div class="timeline-day" style="margin-bottom: 20px;">
                    <div class="timeline-day-header" style="font-weight: 700; color: #1f2937; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px; padding-bottom: 8px; border-bottom: 2px solid #e5e7eb;"><?php echo $dateLabel; ?></div>
                    <div class="activity-list" style="display: flex; flex-direction: column; gap: 8px;">
                        <?php foreach ($activities as $activity): ?>
                        <div class="activity-item" data-action="<?php echo strtolower($activity['action']); ?>" data-activity-id="<?php echo $activity['id']; ?>" style="display: flex; gap: 12px; padding: 12px; background: #f9fafb; border-radius: 8px; border: 1px solid #e5e7eb; transition: all 0.2s; align-items: flex-start;">
                            <input type="checkbox" class="activity-checkbox" value="<?php echo $activity['id']; ?>" style="width: 16px; height: 16px; cursor: pointer; flex-shrink: 0; margin-top: 2px;">
                            
                            <div class="activity-icon <?php echo $activity['icon']; ?>" style="width: 32px; height: 32px; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 14px; flex-shrink: 0; background: #f0f4ff; color: #6366f1;">
                                <?php
                                $icons = [
                                    'created' => 'fa-plus',
                                    'updated' => 'fa-pen',
                                    'deleted' => 'fa-trash',
                                    'login' => 'fa-sign-in-alt',
                                    'viewed' => 'fa-eye',
                                    'info' => 'fa-info-circle'
                                ];
                                $iconClass = $icons[$activity['icon']] ?? 'fa-info-circle';
                                ?>
                                <i class="fas <?php echo $iconClass; ?>"></i>
                            </div>
                            
                            <div class="activity-content" style="flex: 1; min-width: 0;">
                                <div class="activity-header" style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 6px;">
                                    <div class="activity-user" style="flex: 1;">
                                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                                            <span class="activity-name" style="font-weight: 600; color: #1f2937; font-size: 13px;"><?php echo $activity['user']; ?></span>
                                            <span class="activity-badge <?php echo $activity['icon']; ?>" style="padding: 2px 6px; background: #f0f4ff; color: #6366f1; border-radius: 3px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.3px;"><?php echo $activity['action']; ?></span>
                                        </div>
                                        <span class="activity-type" style="font-size: 12px; color: #9ca3af;"><?php echo $activity['type']; ?></span>
                                    </div>
                                    <span class="activity-time" style="font-size: 12px; color: #9ca3af; white-space: nowrap;"><?php echo $activity['time']; ?></span>
                                </div>
                                
                                <div class="activity-description" style="font-size: 13px; color: #6b7280; margin-bottom: 6px;">
                                    <?php echo $activity['description']; ?>
                                </div>
                                
                                <?php if (!empty($activity['details'])): ?>
                                <div class="activity-details" style="font-size: 12px; color: #059669; background: #f0fdf4; padding: 6px 8px; border-radius: 4px; display: inline-flex; align-items: center; gap: 4px;">
                                    <i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($activity['details'], ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                                <?php endif; ?>
                                
                                <div class="activity-meta" style="margin-top: 6px; font-size: 12px; color: #9ca3af; display: flex; align-items: center; gap: 6px;">
                                    <i class="far fa-clock"></i> <?php echo $activity['relative_time']; ?>
                                </div>
                            </div>
                            
                            <div class="activity-actions" style="flex-shrink: 0;">
                                <button class="activity-action-btn" onclick="showActivityMenu(event, <?php echo $activity['id']; ?>)" title="More options" style="background: none; border: none; cursor: pointer; color: #9ca3af; font-size: 16px; padding: 4px; border-radius: 4px; transition: all 0.2s;">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <div class="activity-pagination" style="display: flex; justify-content: space-between; align-items: center; margin-top: 24px; padding-top: 16px; border-top: 2px solid #e5e7eb; flex-wrap: wrap; gap: 16px;">
            <span class="showing-text" style="font-size: 13px; color: #6b7280;">
                Showing <strong><?php echo min($offset + 1, $total_activities); ?>-<?php echo min($offset + $activitiesPerPage, $total_activities); ?></strong> 
                of <strong><?php echo $total_activities; ?></strong> activities
            </span>
            <div class="pagination" style="display: flex; gap: 4px; align-items: center;">
                <button class="page-btn" onclick="goToPage(<?php echo max(1, $page - 1); ?>)" <?php if ($page <= 1) echo 'disabled'; ?> style="padding: 6px 10px; border: 1px solid #d1d5db; background: white; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: 500; transition: all 0.2s; <?php if ($page <= 1) echo 'opacity: 0.5; cursor: not-allowed;'; ?>">&lt;</button>
                
                <?php
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);
                for ($i = $startPage; $i <= $endPage; $i++):
                ?>
                    <button class="page-btn <?php if ($i === $page) echo 'active'; ?>" onclick="goToPage(<?php echo $i; ?>)" style="padding: 6px 10px; border: 1px solid <?php echo $i === $page ? '#6366f1' : '#d1d5db'; ?>; background: <?php echo $i === $page ? '#6366f1' : 'white'; ?>; color: <?php echo $i === $page ? 'white' : '#1f2937'; ?>; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: 600; transition: all 0.2s;">
                        <?php echo $i; ?>
                    </button>
                <?php endfor; ?>
                
                <button class="page-btn" onclick="goToPage(<?php echo min($totalPages, $page + 1); ?>)" <?php if ($page >= $totalPages) echo 'disabled'; ?> style="padding: 6px 10px; border: 1px solid #d1d5db; background: white; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: 500; transition: all 0.2s; <?php if ($page >= $totalPages) echo 'opacity: 0.5; cursor: not-allowed;'; ?>">&gt;</button>
            </div>
        </div>
    </div>
</main>

<!-- Activity Action Menu -->
<div class="activity-menu" id="activityMenu" style="display: none;">
    <button class="activity-menu-item" onclick="viewActivity()">
        <i class="fas fa-eye"></i> View Details
    </button>
                    
</div>

<script src="js/activity.js"></script>

<?php include 'footer.php'; ?>