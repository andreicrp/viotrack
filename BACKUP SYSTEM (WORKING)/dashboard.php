<?php
// dashboard.php - PART 1 OF 2 - ORDER 1: RECENT VIOLATIONS PAGINATION FIX
date_default_timezone_set('Asia/Manila');

// Authentication check
require_once 'auth_check.php';
requireLogin();

include 'header.php';
include 'sidebar.php';

// Check user type (Admin or Teacher)
$isAdmin = (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin');
$isTeacher = (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'teacher');

// Database connection
require_once('connect.php');
if (!$conn) {
    die("Connection failed");
}

// Get date range from filters (default to today in Manila timezone)
$today_manila = date('Y-m-d');
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : $today_manila;
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : $today_manila;
$filter_type = isset($_GET['filter']) ? $_GET['filter'] : 'today';

// Stats for selected date range
$rangeMinorQuery = "SELECT COUNT(*) as count FROM record r 
                    INNER JOIN violation v ON CAST(r.vid AS UNSIGNED) = v.id 
                    WHERE DATE(r.date) BETWEEN '$start_date' AND '$end_date' AND v.type = 'Minor'";
$rangeMinorResult = $conn->query($rangeMinorQuery);
$rangeMinor = $rangeMinorResult ? $rangeMinorResult->fetch_assoc()['count'] : 0;

$rangeSeriousQuery = "SELECT COUNT(*) as count FROM record r 
                      INNER JOIN violation v ON CAST(r.vid AS UNSIGNED) = v.id 
                      WHERE DATE(r.date) BETWEEN '$start_date' AND '$end_date' AND v.type = 'Serious'";
$rangeSeriousResult = $conn->query($rangeSeriousQuery);
$rangeSerious = $rangeSeriousResult ? $rangeSeriousResult->fetch_assoc()['count'] : 0;

$rangeMajorQuery = "SELECT COUNT(*) as count FROM record r 
                    INNER JOIN violation v ON CAST(r.vid AS UNSIGNED) = v.id 
                    WHERE DATE(r.date) BETWEEN '$start_date' AND '$end_date' AND v.type = 'Major'";
$rangeMajorResult = $conn->query($rangeMajorQuery);
$rangeMajor = $rangeMajorResult ? $rangeMajorResult->fetch_assoc()['count'] : 0;

// Total students
$totalStudentsQuery = "SELECT COUNT(*) as count FROM student";
$totalStudentsResult = $conn->query($totalStudentsQuery);
$totalStudents = $totalStudentsResult ? $totalStudentsResult->fetch_assoc()['count'] : 0;

// Total count for selected range
$rangeTotalQuery = "SELECT COUNT(*) as count FROM record 
                    WHERE DATE(date) BETWEEN '$start_date' AND '$end_date'";
$rangeTotalResult = $conn->query($rangeTotalQuery);
$rangeTotal = $rangeTotalResult ? $rangeTotalResult->fetch_assoc()['count'] : 0;

$stats = [
    'daily_minor' => $rangeMinor,
    'daily_serious' => $rangeSerious,
    'daily_major' => $rangeMajor,
    'total_students' => $totalStudents,
    'weekly_count' => $rangeTotal
];

// Violation percentage for selected date range
$percentageQuery = "SELECT v.type, COUNT(*) as count 
                    FROM record r 
                    INNER JOIN violation v ON CAST(r.vid AS UNSIGNED) = v.id 
                    WHERE DATE(r.date) BETWEEN '$start_date' AND '$end_date'
                    GROUP BY v.type";
$percentageResult = $conn->query($percentageQuery);
$violationPercentage = [
    'minor' => ['count' => 0, 'percent' => 0],
    'serious' => ['count' => 0, 'percent' => 0],
    'major' => ['count' => 0, 'percent' => 0]
];
$totalViolations = 0;
if ($percentageResult) {
    while ($row = $percentageResult->fetch_assoc()) {
        $type = strtolower($row['type']);
        if (isset($violationPercentage[$type])) {
            $violationPercentage[$type]['count'] = $row['count'];
            $totalViolations += $row['count'];
        }
    }
}
foreach ($violationPercentage as $type => &$data) {
    if ($totalViolations > 0) {
        $data['percent'] = round(($data['count'] / $totalViolations) * 100, 1);
    }
}

// Trend data for selected date range - ensure we have data for all dates in range
// If viewing "Today", show hourly breakdown; otherwise show daily breakdown
$is_today = ($start_date === $today_manila && $end_date === $today_manila);

if ($is_today) {
    // HOURLY breakdown for today
    $trendQuery = "SELECT HOUR(r.date) as hour, v.type, COUNT(*) as count 
                   FROM record r 
                   INNER JOIN violation v ON CAST(r.vid AS UNSIGNED) = v.id 
                   WHERE DATE(r.date) = '$start_date'
                   GROUP BY HOUR(r.date), v.type 
                   ORDER BY HOUR(r.date)";
    $trendResult = $conn->query($trendQuery);
    $trendDataRaw = [];
    
    // Create array with all hours in the day (0-23)
    for ($hour = 0; $hour < 24; $hour++) {
        $hourLabel = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00';
        $trendDataRaw[$hourLabel] = ['date' => $hourLabel, 'minor' => 0, 'serious' => 0, 'major' => 0];
    }
    
    // Fill in data from query
    if ($trendResult) {
        while ($row = $trendResult->fetch_assoc()) {
            $hour = (int)$row['hour'];
            $type = strtolower($row['type']);
            $hourLabel = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00';
            
            if (isset($trendDataRaw[$hourLabel]) && in_array($type, ['minor', 'serious', 'major'])) {
                $trendDataRaw[$hourLabel][$type] = $row['count'];
            }
        }
    }
} else {
    // DAILY breakdown for date ranges
    $trendQuery = "SELECT DATE(r.date) as date, v.type, COUNT(*) as count 
                   FROM record r 
                   INNER JOIN violation v ON CAST(r.vid AS UNSIGNED) = v.id 
                   WHERE DATE(r.date) BETWEEN '$start_date' AND '$end_date'
                   GROUP BY DATE(r.date), v.type 
                   ORDER BY DATE(r.date)";
    $trendResult = $conn->query($trendQuery);
    $trendDataRaw = [];
    
    // Create array with all dates in range initialized to 0
    $current = strtotime($start_date);
    $end = strtotime($end_date);
    while ($current <= $end) {
        $date = date('M d', $current);
        $trendDataRaw[$date] = ['date' => $date, 'minor' => 0, 'serious' => 0, 'major' => 0];
        $current = strtotime('+1 day', $current);
    }
    
    // Fill in actual data
    if ($trendResult) {
        while ($row = $trendResult->fetch_assoc()) {
            $date = date('M d', strtotime($row['date']));
            $type = strtolower($row['type']);
            if (isset($trendDataRaw[$date]) && in_array($type, ['minor', 'serious', 'major'])) {
                $trendDataRaw[$date][$type] = $row['count'];
            }
        }
    }
}
$trendData = array_values($trendDataRaw);

// Repeat offenders for selected date range with pagination
$offenders_per_page = 5;
$offenders_page = isset($_GET['offenders_page']) ? max(1, intval($_GET['offenders_page'])) : 1;
$offenders_offset = ($offenders_page - 1) * $offenders_per_page;

$offendersTotalQuery = "SELECT COUNT(DISTINCT s.id) as count 
                        FROM student s 
                        INNER JOIN record r ON CAST(s.id AS UNSIGNED) = CAST(r.sid AS UNSIGNED)
                        WHERE DATE(r.date) BETWEEN '$start_date' AND '$end_date'";
$offendersTotalResult = $conn->query($offendersTotalQuery);
$totalOffenders = $offendersTotalResult ? $offendersTotalResult->fetch_assoc()['count'] : 0;

$offendersQuery = "SELECT s.id, CONCAT(s.fname, ' ', s.lname) as name, s.grade, COUNT(r.id) as count 
                   FROM student s 
                   INNER JOIN record r ON CAST(s.id AS UNSIGNED) = CAST(r.sid AS UNSIGNED)
                   WHERE DATE(r.date) BETWEEN '$start_date' AND '$end_date'
                   GROUP BY s.id 
                   ORDER BY count DESC 
                   LIMIT $offenders_per_page OFFSET $offenders_offset";
$offendersResult = $conn->query($offendersQuery);
$repeatOffenders = [];
$colors = ['#ef4444', '#f97316', '#f59e0b', '#f59e0b', '#22c55e'];
$i = 0;
if ($offendersResult) {
    while ($row = $offendersResult->fetch_assoc()) {
        $row['color'] = $colors[$i % 5];
        $i++;
        $repeatOffenders[] = $row;
    }
}

// Violations by grade level for selected date range
$gradeQuery = "SELECT s.grade, COUNT(r.id) as count 
               FROM student s 
               INNER JOIN record r ON CAST(s.id AS UNSIGNED) = CAST(r.sid AS UNSIGNED)
               WHERE DATE(r.date) BETWEEN '$start_date' AND '$end_date'
               GROUP BY s.grade 
               ORDER BY s.grade DESC";
$gradeResult = $conn->query($gradeQuery);
$gradeViolations = [];
if ($gradeResult) {
    while ($row = $gradeResult->fetch_assoc()) {
        $gradeViolations[] = $row;
    }
}

// Calculate previous period for comparison
$date_diff = (strtotime($end_date) - strtotime($start_date)) / 86400;
$prev_start = date('Y-m-d', strtotime($start_date . " -" . ceil($date_diff + 1) . " days"));
$prev_end = date('Y-m-d', strtotime($end_date . " -" . ceil($date_diff + 1) . " days"));

// Violations by category (selected date range)
$categoryQuery = "SELECT v.title as name, v.type, COUNT(r.id) as count
                  FROM violation v 
                  LEFT JOIN record r ON CAST(v.id AS UNSIGNED) = CAST(r.vid AS UNSIGNED)
                  AND DATE(r.date) BETWEEN '$start_date' AND '$end_date'
                  GROUP BY v.id 
                  HAVING count > 0
                  ORDER BY count DESC 
                  LIMIT 6";
$categoryResult = $conn->query($categoryQuery);
$categoryViolations = [];
$totalCategoryCount = 0;

if ($categoryResult) {
    // First pass: collect all data and get total
    $tempData = [];
    while ($row = $categoryResult->fetch_assoc()) {
        $tempData[] = $row;
        $totalCategoryCount += $row['count'];
    }
    
    // Second pass: calculate percentages
    foreach ($tempData as $row) {
        $percentage = $totalCategoryCount > 0 ? round(($row['count'] / $totalCategoryCount) * 100) : 0;
        $row['percentage'] = $percentage;
        $row['type'] = strtolower($row['type']);
        $categoryViolations[] = $row;
    }
}

// Recent violations for selected date range with pagination
$records_per_page = 10;
$current_page = isset($_GET['recent_page']) ? max(1, intval($_GET['recent_page'])) : 1;
$offset = ($current_page - 1) * $records_per_page;

$recentQuery = "SELECT r.id, CONCAT(s.fname, ' ', s.lname) as name, v.title as violation, 
                r.date, v.type
                FROM record r 
                INNER JOIN student s ON CAST(s.id AS UNSIGNED) = CAST(r.sid AS UNSIGNED)
                INNER JOIN violation v ON CAST(r.vid AS UNSIGNED) = v.id 
                WHERE DATE(r.date) BETWEEN '$start_date' AND '$end_date'
                ORDER BY r.date DESC 
                LIMIT $records_per_page OFFSET $offset";
$recentResult = $conn->query($recentQuery);
$recentViolations = [];
if ($recentResult) {
    while ($row = $recentResult->fetch_assoc()) {
        $row['type'] = strtolower($row['type']);
        $datetime = strtotime($row['date']);
        $row['formatted_date'] = date('M d, Y', $datetime);
        $row['time'] = date('g:i A', $datetime);
        $recentViolations[] = $row;
    }
}

// Get total count for recent violations in date range
$totalRecentQuery = "SELECT COUNT(*) as count FROM record 
                     WHERE DATE(date) BETWEEN '$start_date' AND '$end_date'";
$totalRecentResult = $conn->query($totalRecentQuery);
$totalRecent = $totalRecentResult ? $totalRecentResult->fetch_assoc()['count'] : 0;

$conn->close();
?>
<main class="main-content">
    <div class="dashboard-header">
        <div class="welcome-section">
            <h1>Hi, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?> <span style="font-size: 0.8em; color: #9ca3af;">(<?php echo ucfirst($_SESSION['user_type'] ?? 'Admin'); ?>)</span></h1>
            <p>Here's what's happening with your school today</p>
        </div>
        <div class="date-filter">
            <span>Date Range:</span>
            <button class="filter-btn <?php echo $filter_type == 'today' ? 'active' : ''; ?>" onclick="setDateFilter('today')">Today</button>
            <button class="filter-btn <?php echo $filter_type == 'week' ? 'active' : ''; ?>" onclick="setDateFilter('week')">This Week</button>
            <button class="filter-btn <?php echo $filter_type == 'month' ? 'active' : ''; ?>" onclick="setDateFilter('month')">This Month</button>
            <button class="filter-btn <?php echo $filter_type == 'custom' ? 'active' : ''; ?>">Custom Range</button>
            <input type="date" class="date-input" id="start_date" value="<?php echo $start_date; ?>" onchange="setCustomFilter()">
            <span>to</span>
            <input type="date" class="date-input" id="end_date" value="<?php echo $end_date; ?>" onchange="setCustomFilter()">
            <button class="btn btn-primary" onclick="applyDateFilter()">Apply</button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-info">
                <span class="stat-label">Minor Offense</span>
                <span class="stat-value"><?php echo $stats['daily_minor']; ?></span>
            </div>
            <div class="stat-icon minor">
                <img src="images/minor-icon.png" alt="Minor Offense">
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-info">
                <span class="stat-label">Serious Offense</span>
                <span class="stat-value"><?php echo $stats['daily_serious']; ?></span>
            </div>
            <div class="stat-icon serious">
                <img src="images/serious-icon.png" alt="Serious Offense">
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-info">
                <span class="stat-label">Major Offense</span>
                <span class="stat-value"><?php echo $stats['daily_major']; ?></span>
            </div>
            <div class="stat-icon major">
                <img src="images/major-icon.png" alt="Major Offense">
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-info">
                <span class="stat-label">Total Students</span>
                <span class="stat-value"><?php echo $stats['total_students']; ?></span>
            </div>
            <div class="stat-icon students">
                <img src="images/students-icon.png" alt="Total Students">
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-info">
                <span class="stat-label">Total Violations</span>
                <span class="stat-value"><?php echo $stats['weekly_count']; ?></span>
            </div>
            <div class="stat-icon weekly">
                <img src="images/weekly-icon.png" alt="Weekly Count">
            </div>
        </div>
    </div>

    <!-- Violation Trends Chart -->
    <div class="card chart-card">
        <div class="card-header">
            <h3>Violation Trends (<?php echo date('M d', strtotime($start_date)); ?> - <?php echo date('M d, Y', strtotime($end_date)); ?>)</h3>
        </div>
        <div class="chart-container">
            <canvas id="trendChart"></canvas>
        </div>
    </div>

    <!-- Two Column Layout -->
    <div class="two-column-grid">
        <!-- Repeat Offenders -->
        <div class="card">
            <div class="card-header">
                <h3>Repeat Offenders</h3>
                <?php if (count($repeatOffenders) > 0): ?>
                <span class="badge action-needed">Action Needed</span>
                <?php endif; ?>
            </div>
            <div class="offenders-list">
                <?php if (count($repeatOffenders) > 0): ?>
                    <?php foreach ($repeatOffenders as $offender): ?>
                    <div class="offender-item">
                        <div class="offender-count" style="background-color: <?php echo $offender['color']; ?>">
                            <?php echo $offender['count']; ?>
                        </div>
                        <div class="offender-info">
                            <span class="offender-name"><?php echo $offender['name']; ?></span>
                            <span class="offender-grade"><?php echo $offender['grade']; ?></span>
                        </div>
                        <div class="offender-actions">
                            <button class="btn btn-secondary" onclick="window.location.href='adminstudentviolation.php?id=<?php echo $offender['id']; ?>'">View Profile</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="no-data">No repeat offenders found</p>
                <?php endif; ?>
            </div>
            
            <!-- Repeat Offenders Pagination -->
            <?php if ($totalOffenders > $offenders_per_page): 
            $totalOffendersPages = ceil($totalOffenders / $offenders_per_page);
            ?>
            <div class="pagination">
                <span>Showing <?php echo $offenders_offset + 1; ?>-<?php echo min($offenders_offset + $offenders_per_page, $totalOffenders); ?> of <?php echo $totalOffenders; ?></span>
                <div class="pagination-buttons">
                    <?php if ($offenders_page > 1): ?>
                        <button class="page-btn" onclick="window.location.href='?<?php echo http_build_query(array_merge($_GET, ['offenders_page' => $offenders_page - 1])); ?>'">Previous</button>
                    <?php else: ?>
                        <button class="page-btn" disabled>Previous</button>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $totalOffendersPages; $i++): ?>
                        <button class="page-btn <?php echo $i == $offenders_page ? 'active' : ''; ?>" 
                                onclick="window.location.href='?<?php echo http_build_query(array_merge($_GET, ['offenders_page' => $i])); ?>'">
                            <?php echo $i; ?>
                        </button>
                    <?php endfor; ?>
                    
                    <?php if ($offenders_page < $totalOffendersPages): ?>
                        <button class="page-btn" onclick="window.location.href='?<?php echo http_build_query(array_merge($_GET, ['offenders_page' => $offenders_page + 1])); ?>'">Next</button>
                    <?php else: ?>
                        <button class="page-btn" disabled>Next</button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Violations by Grade Level -->
        <div class="card">
            <div class="card-header">
                <h3>Violations by Grade Level</h3>
            </div>
            <div class="grade-violations">
                <?php 
                if (count($gradeViolations) > 0) {
                    $maxCount = max(array_column($gradeViolations, 'count'));
                    foreach ($gradeViolations as $grade): 
                        $percentage = ($grade['count'] / $maxCount) * 100;
                    ?>
                    <div class="grade-item">
                        <span class="grade-label"><?php echo $grade['grade']; ?></span>
                        <div class="grade-bar-container">
                            <div class="grade-bar" style="width: <?php echo $percentage; ?>%"></div>
                        </div>
                        <span class="grade-count"><?php echo $grade['count']; ?></span>
                    </div>
                    <?php endforeach;
                } else {
                    echo '<p class="no-data">No violations found</p>';
                }
                ?>
            </div>
            <?php if (count($gradeViolations) > 0): 
                $highestGrade = $gradeViolations[0]['grade'];
            ?>
            <div class="insight-box">
                <strong>Insight:</strong> <?php echo $highestGrade; ?> has the highest violation rate. Consider targeted intervention programs.
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Second Two Column Layout -->
    <div class="two-column-grid">
        <!-- Violation Percentage -->
        <div class="card">
            <div class="card-header">
                <h3>Violation Percentage</h3>
            </div>
            <div class="donut-section">
                <p class="total-label">Total Violations (<?php echo date('M d', strtotime($start_date)); ?> - <?php echo date('M d, Y', strtotime($end_date)); ?>)</p>
                <p class="total-count"><?php echo $totalViolations; ?></p>
                <div class="donut-chart">
                    <canvas id="donutChart"></canvas>
                </div>
                <div class="donut-legend">
                    <div class="legend-row">
                        <span class="dot minor"></span>
                        <span>Minor</span>
                        <span class="legend-value"><?php echo $violationPercentage['minor']['count']; ?> (<?php echo $violationPercentage['minor']['percent']; ?>%)</span>
                    </div>
                    <div class="legend-row">
                        <span class="dot serious"></span>
                        <span>Serious</span>
                        <span class="legend-value"><?php echo $violationPercentage['serious']['count']; ?> (<?php echo $violationPercentage['serious']['percent']; ?>%)</span>
                    </div>
                    <div class="legend-row">
                        <span class="dot major"></span>
                        <span>Major</span>
                        <span class="legend-value"><?php echo $violationPercentage['major']['count']; ?> (<?php echo $violationPercentage['major']['percent']; ?>%)</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Violations by Category -->
        <div class="card">
            <div class="card-header">
                <h3>Violations by Category</h3>
                <span class="text-muted"><?php echo date('M d', strtotime($start_date)); ?> - <?php echo date('M d', strtotime($end_date)); ?></span>
            </div>
            <div class="category-list">
                <?php 
                if (count($categoryViolations) > 0) {
                    $maxCat = max(array_column($categoryViolations, 'count'));
                    foreach ($categoryViolations as $category): 
                        $barColor = $category['type'] === 'minor' ? '#22c55e' : ($category['type'] === 'serious' ? '#f59e0b' : '#ef4444');
                        $barWidth = ($category['count'] / $maxCat) * 100;
                    ?>
                    <div class="category-item">
                        <span class="category-name"><?php echo $category['name']; ?></span>
                        <div class="category-bar-container">
                            <div class="category-bar" style="width: <?php echo $barWidth; ?>%; background-color: <?php echo $barColor; ?>"></div>
                        </div>
                        <span class="category-count"><?php echo $category['count']; ?></span>
                        <span class="category-change" style="color: #ef4444;">
                            <i class="fas fa-arrow-up"></i> +<?php echo $category['percentage']; ?>%
                        </span>
                    </div>
                    <?php endforeach; ?>
                <?php } else {
                    echo '<p class="no-data">No violations this week</p>';
                }
                ?>
            </div>
            <div class="category-footer">
                <div class="legend-inline">
                    <span><span class="dot major"></span> Major</span>
                    <span><span class="dot serious"></span> Serious</span>
                    <span><span class="dot minor"></span> Minor</span>
                </div>
                <span>Total: <?php echo $totalCategoryCount; ?> violations</span>
            </div>
        </div>
    </div>

    <!-- Third Two Column Layout -->
    <div class="two-column-grid">
        <!-- Calendar -->
        <?php if ($isAdmin): ?>
        <div class="card">
            <div class="card-header">
                <h3>Scheduled Parent Meetings</h3>
            </div>
            <div class="calendar-navigation">
                <button class="calendar-nav-btn" onclick="changeMonth(-1)">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <span id="calendarMonthYear">November 2025</span>
                <button class="calendar-nav-btn" onclick="changeMonth(1)">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
            <div class="calendar-grid">
                <div class="calendar-header">
                    <span>Sun</span><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span>
                </div>
                <div class="calendar-days" id="calendarDays">
                    <!-- Calendar days will be generated by JavaScript -->
                </div>
            </div>
            
            <!-- Meeting Panel -->
            <div class="meeting-panel" id="meetingPanel" style="display: none;">
                <div class="meeting-panel-header">
                    <h4 id="meetingPanelTitle">Meetings on Nov 15, 2025</h4>
                    <div class="meeting-actions">
                        <button class="icon-btn" onclick="showMeetingForm()" id="addMeetingBtn">
                            <i class="fas fa-plus"></i>
                        </button>
                        <button class="icon-btn" onclick="closeMeetingPanel()"><i class="fas fa-times"></i></button>
                    </div>
                </div>
                
                <!-- Add/Edit Meeting Form (Hidden by default) -->
                <div class="meeting-form" id="meetingForm" style="display: none;">
                    <input type="text" id="meetingName" class="meeting-input" placeholder="Meeting name">
                    <input type="text" id="meetingTime" class="meeting-input" placeholder="Time (e.g., 3:00 PM)">
                    <input type="text" id="meetingReason" class="meeting-input" placeholder="Reason (optional)">
                    <div class="form-actions">
                        <button class="btn btn-cancel" onclick="cancelForm()">Cancel</button>
                        <button class="btn btn-save" onclick="addOrUpdateMeeting()">
                            <span id="formBtnText">Save Meeting</span>
                        </button>
                    </div>
                </div>
                
                <!-- Meetings List -->
                <div class="meeting-list" id="meetingList">
                    <!-- Meetings will be populated here -->
                </div>
            </div>
            
            <div class="meeting-legend">
                <span class="meeting-dot"></span> Parent meetings scheduled
            </div>
        </div>
        <?php endif; ?>

        <!-- Recent Violations -->
        <div class="card" id="recent-violations">
            <div class="card-header">
                <h3>Recent Violations</h3>
                <span class="text-muted"><?php echo $totalRecent; ?> total</span>
            </div>
            <div class="violations-list" id="recentViolationsList">
                <?php if (count($recentViolations) > 0): ?>
                    <?php foreach ($recentViolations as $violation): ?>
                    <div class="violation-item" data-violation-id="<?php echo $violation['id']; ?>">
                        <span class="violation-dot <?php echo $violation['type']; ?>"></span>
                        <div class="violation-info">
                            <span class="violation-name"><?php echo $violation['name']; ?></span>
                            <span class="violation-desc"><?php echo $violation['violation']; ?></span>
                            <span class="violation-date"><?php echo $violation['formatted_date']; ?> • <?php echo $violation['time']; ?></span>
                        </div>
                        <span class="violation-badge <?php echo $violation['type']; ?>">
                            <?php echo ucfirst($violation['type']); ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="no-data">No recent violations</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<!-- Load Chart.js First -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Pass PHP data to JavaScript -->
<script>
    
    // Pass data from PHP to JavaScript
    window.trendData = <?php echo json_encode($trendData); ?>;
    window.violationPercentage = <?php echo json_encode(array_values($violationPercentage)); ?>;    // Debug: Log the data
    console.log('Trend Data from PHP:', window.trendData);
    console.log('Violation Percentage from PHP:', window.violationPercentage);
    
    // Set timezone to Manila for JavaScript
    const manilaTime = new Date(new Date().toLocaleString("en-US", {timeZone: "Asia/Manila"}));
    
    // Date filter functions
    function setDateFilter(type) {
        // Get current date in Manila timezone
        const now = new Date(new Date().toLocaleString("en-US", {timeZone: "Asia/Manila"}));
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const today = `${year}-${month}-${day}`;
        
        let startDate, endDate;
        
        if (type === 'today') {
            startDate = today;
            endDate = today;
        } else if (type === 'week') {
            endDate = today;
            const weekAgo = new Date(now);
            weekAgo.setDate(now.getDate() - 6);
            const wYear = weekAgo.getFullYear();
            const wMonth = String(weekAgo.getMonth() + 1).padStart(2, '0');
            const wDay = String(weekAgo.getDate()).padStart(2, '0');
            startDate = `${wYear}-${wMonth}-${wDay}`;
        } else if (type === 'month') {
            endDate = today;
            const monthAgo = new Date(now);
            monthAgo.setMonth(now.getMonth() - 1);
            const mYear = monthAgo.getFullYear();
            const mMonth = String(monthAgo.getMonth() + 1).padStart(2, '0');
            const mDay = String(monthAgo.getDate()).padStart(2, '0');
            startDate = `${mYear}-${mMonth}-${mDay}`;
        }
        
        window.location.href = `dashboard.php?filter=${type}&start_date=${startDate}&end_date=${endDate}`;
    }
    
    function setCustomFilter() {
        // Mark as custom when dates are manually changed
        document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.filter-btn')[3].classList.add('active');
    }
    
    function applyDateFilter() {
        const startDate = document.getElementById('start_date').value;
        const endDate = document.getElementById('end_date').value;
        
        if (!startDate || !endDate) {
            alert('Please select both start and end dates');
            return;
        }
        
        if (new Date(startDate) > new Date(endDate)) {
            alert('Start date must be before end date');
            return;
        }
        
        window.location.href = `dashboard.php?filter=custom&start_date=${startDate}&end_date=${endDate}`;
    }
    
    function updatePercentageDate() {
        const date = document.getElementById('percentage_date').value;
        window.location.href = `dashboard.php?filter=custom&start_date=${date}&end_date=${date}`;
    }
</script>

<!-- Load Dashboard Script -->
<script src="js/dashboard.js"></script>

<?php include 'footer.php'; ?>