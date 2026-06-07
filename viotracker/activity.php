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
    $whereConditions[] = "(a.description LIKE ? OR t.fname LIKE ? OR t.lname LIKE ?)";
    $params[] = '%' . $searchTerm . '%';
    $params[] = '%' . $searchTerm . '%';
    $params[] = '%' . $searchTerm . '%';
    $types .= 'sss';
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Fetch total activities count
$countQuery = "SELECT COUNT(*) AS total FROM activity a LEFT JOIN teacher t ON a.aid = t.id $whereClause";
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
$query = "SELECT a.id, a.description, a.date, a.aid, t.fname, t.lname, t.mname
          FROM activity a
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

// Calculate percentage changes
$thirtyDaysAgo = date('Y-m-d 00:00:00', strtotime('-30 days'));
$sixtyDaysAgo = date('Y-m-d 00:00:00', strtotime('-60 days'));

$prevQuery = "SELECT COUNT(*) AS prev_count FROM activity WHERE date >= ? AND date < ?";
$prevStmt = $conn->prepare($prevQuery);
$prevStmt->bind_param('ss', $sixtyDaysAgo, $thirtyDaysAgo);
$prevStmt->execute();
$prevRow = $prevStmt->get_result()->fetch_assoc();
$prev_total_activities = $prevRow['prev_count'] ?? 1;
$prevStmt->close();

$total_change = round((($total_activities - $prev_total_activities) / max($prev_total_activities, 1)) * 100);

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
    <div class="card activity-card">
        <div class="card-header">
            <div class="card-title-section">
                <h3>Activity Log</h3>
                <p class="subtitle"><?php echo $isAdmin ? 'Monitor all admin actions and system changes' : 'View your activities'; ?></p>
            </div>
            <div class="header-actions">
                <?php if ($isAdmin): ?>
                <button class="btn btn-danger btn-delete-selected" id="deleteSelectedBtn" style="display: none;">
                    <i class="fas fa-trash"></i> Delete Selected (<span id="selectedCount">0</span>)
                </button>
                <?php endif; ?>
                <button class="btn btn-refresh" onclick="location.reload()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
        </div>

        <!-- Enhanced Filters -->
        <div class="activity-filters">
            <div class="filter-tabs">
                <button class="filter-tab <?php echo $filterAction === 'all' ? 'active' : ''; ?>" data-filter="all">All</button>
                <?php if ($isAdmin): ?>
                <button class="filter-tab <?php echo $filterAction === 'created' ? 'active' : ''; ?>" data-filter="created">Created</button>
                <button class="filter-tab <?php echo $filterAction === 'updated' ? 'active' : ''; ?>" data-filter="updated">Updated</button>
                <button class="filter-tab <?php echo $filterAction === 'deleted' ? 'active' : ''; ?>" data-filter="deleted">Deleted</button>
                <button class="filter-tab <?php echo $filterAction === 'records' ? 'active' : ''; ?>" data-filter="records">Records</button>
                <button class="filter-tab <?php echo $filterAction === 'violations' ? 'active' : ''; ?>" data-filter="violations">Violations</button>
                <button class="filter-tab <?php echo $filterAction === 'students' ? 'active' : ''; ?>" data-filter="students">Students</button>
                <button class="filter-tab <?php echo $filterAction === 'teachers' ? 'active' : ''; ?>" data-filter="teachers">Teachers</button>
                <button class="filter-tab <?php echo $filterAction === 'sms' ? 'active' : ''; ?>" data-filter="sms">SMS</button>
                <?php endif; ?>
            </div>
            
            <div class="filter-right">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="activitySearch" placeholder="Search activities..." value="<?php echo htmlspecialchars($searchTerm); ?>">
                </div>
                
                <div class="date-range-picker">
                    <button class="date-range-btn" id="dateRangeBtn">
                        <i class="fas fa-calendar-alt"></i>
                        <span id="dateRangeText">Last 30 days</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    
                    <div class="date-range-dropdown" id="dateRangeDropdown">
                        <div class="dropdown-header">
                            Select Date Range
                            <button class="close-dropdown" onclick="closeDateRangePicker()">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        
                        <div class="date-presets">
                            <button class="preset-btn" data-preset="today">Today</button>
                            <button class="preset-btn" data-preset="7days">Last 7 days</button>
                            <button class="preset-btn active" data-preset="30days">Last 30 days</button>
                        </div>
                        
                        <div class="custom-range-section">
                            <div class="custom-range-header">Custom Range</div>
                            <div class="date-inputs">
                                <div class="date-input-group">
                                    <label class="date-input-label">Start Date</label>
                                    <input type="date" class="date-input" id="startDate" value="<?php echo $startDate; ?>">
                                </div>
                                <div class="date-input-group">
                                    <label class="date-input-label">End Date</label>
                                    <input type="date" class="date-input" id="endDate" value="<?php echo $endDate; ?>">
                                </div>
                            </div>
                            <button class="apply-filter-btn" onclick="applyDateRange()">
                                Apply Filter
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Activity Timeline -->
        <div class="activity-timeline">
            <?php if (empty($activities_grouped)): ?>
                <div class="no-activities">
                    <i class="fas fa-inbox"></i>
                    <p>No activities found</p>
                </div>
            <?php else: ?>
                <?php foreach ($activities_grouped as $dateLabel => $activities): ?>
                <div class="timeline-day">
                    <div class="timeline-day-header"><?php echo $dateLabel; ?></div>
                    <div class="activity-list">
                        <?php foreach ($activities as $activity): ?>
                        <div class="activity-item" data-action="<?php echo strtolower($activity['action']); ?>" data-activity-id="<?php echo $activity['id']; ?>">
                            <input type="checkbox" class="activity-checkbox" value="<?php echo $activity['id']; ?>">
                            
                            <div class="activity-icon <?php echo $activity['icon']; ?>">
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
                            
                            <div class="activity-content">
                                <div class="activity-header">
                                    <div class="activity-user">
                                        <div>
                                            <span class="activity-name"><?php echo $activity['user']; ?></span>
                                            <span class="activity-badge <?php echo $activity['icon']; ?>"><?php echo $activity['action']; ?></span>
                                        </div>
                                        <span class="activity-type"><?php echo $activity['type']; ?></span>
                                    </div>
                                    <span class="activity-time"><?php echo $activity['time']; ?></span>
                                </div>
                                
                                <div class="activity-description">
                                    <?php echo $activity['description']; ?>
                                </div>
                                
                                <?php if (!empty($activity['details'])): ?>
                                <div class="activity-details">
                                    <i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($activity['details'], ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                                <?php endif; ?>
                                
                                <div class="activity-meta">
                                    <span><i class="far fa-clock"></i> <?php echo $activity['relative_time']; ?></span>
                                </div>
                            </div>
                            
                            <div class="activity-actions">
                                <button class="activity-action-btn" onclick="showActivityMenu(event, <?php echo $activity['id']; ?>)" title="More options">
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
        <div class="activity-pagination">
            <span class="showing-text">
                Showing <strong><?php echo min($offset + 1, $total_activities); ?>-<?php echo min($offset + $activitiesPerPage, $total_activities); ?></strong> 
                of <strong><?php echo $total_activities; ?></strong> activities
            </span>
            <div class="pagination">
                <button class="page-btn" onclick="goToPage(<?php echo max(1, $page - 1); ?>)" <?php if ($page <= 1) echo 'disabled'; ?>>&lt;</button>
                
                <?php
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);
                for ($i = $startPage; $i <= $endPage; $i++):
                ?>
                    <button class="page-btn <?php if ($i === $page) echo 'active'; ?>" onclick="goToPage(<?php echo $i; ?>)">
                        <?php echo $i; ?>
                    </button>
                <?php endfor; ?>
                
                <button class="page-btn" onclick="goToPage(<?php echo min($totalPages, $page + 1); ?>)" <?php if ($page >= $totalPages) echo 'disabled'; ?>>&gt;</button>
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