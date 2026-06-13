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

    // Violations by grade section for selected date range
    $gradeQuery = "SELECT s.grade, s.section, CONCAT(s.grade, ' - ', s.section) as grade_section, COUNT(r.id) as count 
                FROM student s 
                INNER JOIN record r ON CAST(s.id AS UNSIGNED) = CAST(r.sid AS UNSIGNED)
                WHERE DATE(r.date) BETWEEN '$start_date' AND '$end_date'
                GROUP BY s.grade, s.section 
                ORDER BY s.grade ASC, s.section ASC";
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
                <?php if ($isAdmin): ?>
                <div class="export-section" style="margin-left: 12px;">
                    <button class="btn-export" onclick="toggleExportMenu(event)">
                        <i class="fas fa-download"></i> Export Data
                    </button>
                    <div class="export-menu" id="exportMenu" style="display: none;">
                        <button class="export-menu-item" onclick="previewExport()">
                            <i class="fas fa-eye"></i> Preview Export
                        </button>
                        <button class="export-menu-item" onclick="exportChartData('png')">
                            <i class="fas fa-image"></i> Export as PNG
                        </button>
                        <button class="export-menu-item" onclick="exportChartData('pdf')">
                            <i class="fas fa-file-pdf"></i> Export as PDF
                        </button>
                    </div>
                </div>
                <?php endif; ?>
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
                <canvas id="trendChart" width="800" height="320"></canvas>
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
                // Build query string preserving all filters
                $queryParams = array_merge($_GET, ['offenders_page' => 1]);
                $baseUrl = '?' . http_build_query($queryParams);
                ?>
                <div class="pagination">
                    <span>Showing <?php echo $offenders_offset + 1; ?>-<?php echo min($offenders_offset + $offenders_per_page, $totalOffenders); ?> of <?php echo $totalOffenders; ?></span>
                    <div class="pagination-buttons">
                        <?php if ($offenders_page > 1): 
                            $prevParams = array_merge($_GET, ['offenders_page' => $offenders_page - 1]);
                            $prevUrl = '?' . http_build_query($prevParams);
                        ?>
                            <button class="page-btn" onclick="window.location.href='<?php echo htmlspecialchars($prevUrl, ENT_QUOTES); ?>'"><</button>
                        <?php else: ?>
                            <button class="page-btn" disabled><</button>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $totalOffendersPages; $i++): 
                            $pageParams = array_merge($_GET, ['offenders_page' => $i]);
                            $pageUrl = '?' . http_build_query($pageParams);
                        ?>
                            <button class="page-btn <?php echo $i == $offenders_page ? 'active' : ''; ?>" 
                                    onclick="window.location.href='<?php echo htmlspecialchars($pageUrl, ENT_QUOTES); ?>'">
                                <?php echo $i; ?>
                            </button>
                        <?php endfor; ?>
                        
                        <?php if ($offenders_page < $totalOffendersPages): 
                            $nextParams = array_merge($_GET, ['offenders_page' => $offenders_page + 1]);
                            $nextUrl = '?' . http_build_query($nextParams);
                        ?>
                            <button class="page-btn" onclick="window.location.href='<?php echo htmlspecialchars($nextUrl, ENT_QUOTES); ?>'">></button>
                        <?php else: ?>
                            <button class="page-btn" disabled>></button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Violations by Grade Level & Sections -->
            <div class="card">
                <div class="card-header">
                    <h3>Violations by Grade Level & Sections</h3>
                </div>
                <div class="grade-violations">
                    <?php 
                    if (count($gradeViolations) > 0) {
                        $maxCount = max(array_column($gradeViolations, 'count'));
                        foreach ($gradeViolations as $grade): 
                            $percentage = ($grade['count'] / $maxCount) * 100;
                        ?>
                        <div class="grade-item">
                            <span class="grade-label"><?php echo $grade['grade_section']; ?></span>
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
                    $highestGrade = $gradeViolations[0]['grade_section'];
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

        <!-- CALENDAR SECTION - ADMIN ONLY -->
        <?php if ($isAdmin): ?>
        <div class="card" style="margin-top: 30px;">
            <div class="card-header">
                <h3>Meeting Calendar</h3>
            </div>
            
            <div style="display: flex; gap: 20px; padding: 20px; flex-wrap: wrap;">
                <!-- Calendar -->
                <div style="flex: 1; min-width: 300px;">
                    <div class="calendar-navigation">
                        <button class="calendar-nav-btn" onclick="changeMonth(-1)"><i class="fas fa-chevron-left"></i></button>
                        <span id="calendarMonthYear" style="flex: 1; text-align: center; font-weight: 600; font-size: 16px;">January 2024</span>
                        <button class="calendar-nav-btn" onclick="changeMonth(1)"><i class="fas fa-chevron-right"></i></button>
                        <button class="calendar-nav-btn" onclick="goToToday()" title="Go to today" style="margin-left: 10px;"><i class="fas fa-calendar-check"></i></button>
                    </div>
                    
                    <div class="calendar-grid">
                        <div class="calendar-header">
                            <span>Sun</span>
                            <span>Mon</span>
                            <span>Tue</span>
                            <span>Wed</span>
                            <span>Thu</span>
                            <span>Fri</span>
                            <span>Sat</span>
                        </div>
                        <div id="calendarDays" class="calendar-days"></div>
                    </div>
                </div>
                
                <!-- Meeting Panel -->
                <div id="meetingPanel" style="flex: 1; min-width: 300px; display: none; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; background: #fafafa; max-height: 600px; overflow-y: auto;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #e5e7eb;">
                        <h4 id="meetingPanelTitle" style="margin: 0; font-size: 15px; font-weight: 600;">Meetings</h4>
                        <button onclick="closeMeetingPanel()" style="background: none; border: none; font-size: 18px; color: #6b7280; cursor: pointer;"><i class="fas fa-times"></i></button>
                    </div>
                    
                    <button id="addMeetingBtn" onclick="showMeetingForm()" style="width: 100%; padding: 10px; background: #6366f1; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; margin-bottom: 16px; display: none;">
                        <i class="fas fa-plus"></i> Add Meeting
                    </button>
                    
                    <!-- Meeting Form -->
                    <form id="meetingForm" style="display: none; flex-direction: column; gap: 12px; margin-bottom: 20px; padding: 16px; background: white; border-radius: 6px; border: 1px solid #e5e7eb;">
                        <div>
                            <label style="font-size: 12px; font-weight: 600; color: #374151; display: block; margin-bottom: 4px;">Name/Student</label>
                            <input id="meetingName" type="text" placeholder="Student name or meeting title" style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 13px;">
                        </div>
                        <div>
                            <label style="font-size: 12px; font-weight: 600; color: #374151; display: block; margin-bottom: 4px;">Time</label>
                            <input id="meetingTime" type="time" style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 13px;">
                        </div>
                        <div>
                            <label style="font-size: 12px; font-weight: 600; color: #374151; display: block; margin-bottom: 4px;">Reason/Notes</label>
                            <textarea id="meetingReason" placeholder="Meeting reason or notes" style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 13px; resize: vertical; min-height: 60px;"></textarea>
                        </div>
                        <div style="display: flex; gap: 8px;">
                            <button type="button" onclick="addOrUpdateMeeting()" style="flex: 1; padding: 8px; background: #10b981; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 13px;">
                                <i class="fas fa-check"></i> <span id="formBtnText">Save Meeting</span>
                            </button>
                            <button type="button" onclick="cancelForm()" style="flex: 1; padding: 8px; background: #9ca3af; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 13px;">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                        </div>
                    </form>
                    
                    <!-- Meeting List -->
                    <div id="meetingList" style="display: flex; flex-direction: column; gap: 12px;"></div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- ===== VIOLATION HOTSPOT MAP (Admin Only) ===== -->
        <?php if ($isAdmin): ?>
        <div class="card map-card" style="margin-top: 30px;">
            <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#6366f1,#8b5cf6);display:flex;align-items:center;justify-content:center;color:#fff;font-size:15px;">
                        <i class="fas fa-map-marked-alt"></i>
                    </div>
                    <div>
                        <h3 style="margin:0;">Violation Hotspot Map</h3>
                        <p style="margin:0;font-size:12px;color:var(--text-muted);">Live campus violation locations</p>
                    </div>
                </div>
                <div style="display:flex;gap:8px;align-items:center;">
                    <span id="mapViolationCount" class="badge" style="background:rgba(99,102,241,0.1);color:#6366f1;padding:4px 12px;border-radius:999px;font-size:12px;font-weight:600;">Loading...</span>
                    <button onclick="refreshViolationMap()" style="background:none;border:1px solid var(--surface-border,#e2e8f0);color:var(--text-secondary);padding:6px 12px;border-radius:8px;cursor:pointer;font-size:12px;font-weight:600;display:flex;align-items:center;gap:5px;transition:all 0.2s;" onmouseover="this.style.background='rgba(99,102,241,0.08)'" onmouseout="this.style.background='none'">
                        <i class="fas fa-sync-alt" id="mapRefreshIcon"></i> Refresh
                    </button>
                </div>
            </div>

            <!-- Skeleton Loader for Map -->
            <div id="mapSkeleton" style="padding:20px;">
                <div class="skeleton" style="height:420px;border-radius:12px;"></div>
                <div style="display:flex;gap:12px;margin-top:14px;">
                    <div class="skeleton" style="height:32px;width:120px;border-radius:8px;"></div>
                    <div class="skeleton" style="height:32px;width:120px;border-radius:8px;"></div>
                    <div class="skeleton" style="height:32px;width:120px;border-radius:8px;"></div>
                </div>
            </div>

            <!-- Map Container (hidden until loaded) -->
            <div id="mapContainer" style="display:none;padding:20px 20px 10px;">
                <div id="violationMap" style="height:420px;border-radius:12px;overflow:hidden;border:1px solid var(--surface-border,#e2e8f0);"></div>

                <!-- Map Legend -->
                <div style="display:flex;gap:20px;flex-wrap:wrap;margin-top:14px;padding:12px 16px;background:var(--surface-raised,#f8fafc);border-radius:10px;border:1px solid var(--surface-border,#e2e8f0);">
                    <span style="font-size:12px;font-weight:600;color:var(--text-secondary);margin-right:4px;">Legend:</span>
                    <span style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--text-secondary);">
                        <span style="width:12px;height:12px;background:#ef4444;border-radius:50%;display:inline-block;border:2px solid #fff;box-shadow:0 0 0 1px #ef4444;"></span> Major
                    </span>
                    <span style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--text-secondary);">
                        <span style="width:12px;height:12px;background:#f59e0b;border-radius:50%;display:inline-block;border:2px solid #fff;box-shadow:0 0 0 1px #f59e0b;"></span> Serious
                    </span>
                    <span style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--text-secondary);">
                        <span style="width:12px;height:12px;background:#22c55e;border-radius:50%;display:inline-block;border:2px solid #fff;box-shadow:0 0 0 1px #22c55e;"></span> Minor
                    </span>
                    <span id="mapNoDataMsg" style="display:none;font-size:12px;color:var(--text-muted);font-style:italic;">No location data recorded yet. Violations with GPS enabled will appear here.</span>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </main>

    <!-- Leaflet.js for Hotspot Map -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>

    <!-- Load Chart.js and Export Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>


    <!-- Pass PHP data to JavaScript -->
    <script>
        
        // Pass data from PHP to JavaScript
        window.isAdmin = <?php echo json_encode($isAdmin); ?>;
        window.isTeacher = <?php echo json_encode($isTeacher); ?>;
        window.trendData = <?php echo json_encode($trendData); ?>;
        window.violationPercentage = <?php echo json_encode(array_values($violationPercentage)); ?>;
        window.gradeViolations = <?php echo json_encode($gradeViolations); ?>;
        window.categoryViolations = <?php echo json_encode($categoryViolations); ?>;
        window.exportDateRange = {
            start_date: '<?php echo $start_date; ?>',
            end_date: '<?php echo $end_date; ?>',
            filter_type: '<?php echo $filter_type; ?>'
        };
        
        // Debug: Log the data
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
        
        /**
         * Toggle export menu visibility
         */
        function toggleExportMenu(event) {
            event.stopPropagation();
            const menu = document.getElementById('exportMenu');
            if (menu) {
                menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
            }
        }
        
        /**
         * Convert Chart.js instance to image
         */
        function convertChartToImage(chartInstance) {
            if (!chartInstance || !chartInstance.canvas) {
                console.warn('Chart instance not available');
                return null;
            }
            try {
                return chartInstance.canvas.toDataURL('image/png');
            } catch (error) {
                console.error('Error converting chart to image:', error);
                return null;
            }
        }

        /**
         * Preview the export content in a modal
         */
        function previewExport() {
            // Close export menu
            document.getElementById('exportMenu').style.display = 'none';
            
            // Create modal overlay
            const modalOverlay = document.createElement('div');
            modalOverlay.id = 'previewModal';
            modalOverlay.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.7);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 10000;
            `;
            
            // Create modal content
            const modalContent = document.createElement('div');
            modalContent.style.cssText = `
                background: #ffffff;
                border-radius: 12px;
                padding: 20px;
                width: 95%;
                max-width: 1200px;
                max-height: 90vh;
                overflow-y: auto;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            `;
            
            // Modal header
            const header = document.createElement('div');
            header.style.cssText = `
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 30px;
                padding-bottom: 20px;
                border-bottom: 3px solid #000000;
            `;
            
            const title = document.createElement('h2');
            title.style.cssText = 'margin: 0; color: #000000; font-size: 24px; font-weight: 900;';
            title.textContent = 'Export Preview';
            header.appendChild(title);
            
            const closeBtn = document.createElement('button');
            closeBtn.style.cssText = `
                background: #ef4444;
                color: white;
                border: none;
                padding: 10px 20px;
                border-radius: 8px;
                cursor: pointer;
                font-size: 14px;
                font-weight: 700;
            `;
            closeBtn.textContent = 'Close';
            closeBtn.onclick = () => {
                modalOverlay.remove();
            };
            header.appendChild(closeBtn);
            
            modalContent.appendChild(header);
            
            // Create preview content
            const previewContent = document.createElement('div');
            previewContent.style.cssText = `
                background: #ffffff;
                padding: 20px;
                border: 3px solid #000000;
                border-radius: 12px;
            `;
            
            // Add title and period info
            const previewTitle = document.createElement('h1');
            previewTitle.style.cssText = 'color: #000000; margin: 0 0 10px 0; font-size: 32px; font-weight: 900;';
            previewTitle.textContent = 'VIOTRACK Dashboard Report';
            previewContent.appendChild(previewTitle);
            
            const periodInfo = document.createElement('p');
            periodInfo.style.cssText = 'color: #000000; margin: 0 0 30px 0; font-size: 14px; border-bottom: 3px solid #000000; padding-bottom: 20px; font-weight: 700;';
            periodInfo.textContent = `Period: ${window.exportDateRange.start_date} to ${window.exportDateRange.end_date} | Generated: ${new Date().toLocaleString()}`;
            previewContent.appendChild(periodInfo);
            
            // Add inline styles to preview
            const style = document.createElement('style');
            style.textContent = `
                .preview-card { background: #ffffff !important; border: 3px solid #000000 !important; border-radius: 12px; padding: 24px; margin-bottom: 30px; }
                .preview-card.full-width { width: 100%; }
                .preview-card.half-width { width: calc(50% - 15px); display: inline-block; }
                .preview-card.half-width:first-of-type { margin-right: 30px; }
                .preview-two-column { display: flex; gap: 30px; margin-bottom: 30px; flex-wrap: wrap; }
                .preview-two-column .preview-card { margin-bottom: 0; }
                .preview-card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 3px solid #000000; }
                .preview-card-header h3 { margin: 0; font-size: 20px; font-weight: 900 !important; color: #000000 !important; }
                .preview-chart-container { position: relative; width: 100%; margin: 20px 0; background: #ffffff; padding: 10px; border-radius: 6px; }
                .preview-chart-img { max-width: 100%; height: auto; display: block; }
                .preview-grade-item { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; padding: 10px 0; border-bottom: 2px solid #000000; }
                .preview-grade-label { min-width: 120px; font-size: 14px; font-weight: 900 !important; color: #000000 !important; }
                .preview-grade-bar-container { flex: 1; height: 14px; background: #cccccc; border-radius: 8px; overflow: hidden; border: 2px solid #000000; }
                .preview-grade-bar { height: 100%; background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); border-radius: 4px; }
                .preview-grade-count { min-width: 40px; text-align: right; font-size: 14px; font-weight: 900 !important; color: #000000 !important; }
                .preview-donut-section { display: flex; flex-direction: column; align-items: center; gap: 25px; padding: 20px; }
                .preview-total-label { font-size: 14px; color: #000000 !important; margin: 0; font-weight: 900 !important; }
                .preview-total-count { font-size: 64px; font-weight: 900 !important; color: #10b981 !important; margin: 0; }
                .preview-donut-chart { width: 220px; height: 220px; }
                .preview-category-item { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; padding: 10px 0; border-bottom: 2px solid #000000; }
                .preview-category-name { min-width: 180px; font-size: 14px; font-weight: 900 !important; color: #000000 !important; }
                .preview-category-bar-container { flex: 1; height: 14px; background: #cccccc; border-radius: 8px; overflow: hidden; border: 2px solid #000000; }
                .preview-category-bar { height: 100%; border-radius: 6px; }
                .preview-category-count { min-width: 40px; text-align: right; font-size: 14px; font-weight: 900 !important; color: #000000 !important; }
                .preview-legend-row { display: flex; align-items: center; gap: 10px; font-size: 14px; color: #000000 !important; font-weight: 900 !important; margin-bottom: 10px; }
                .preview-dot { width: 18px; height: 18px; border-radius: 50%; border: 3px solid #000000 !important; }
                .preview-dot.minor { background: #22c55e !important; }
                .preview-dot.serious { background: #f59e0b !important; }
                .preview-dot.major { background: #ef4444 !important; }
                
                /* === PC MODE (Desktop >= 769px) === */
                @media (min-width: 769px) {
                    .preview-card { padding: 24px; margin-bottom: 30px; }
                    .preview-card-header { margin-bottom: 20px; padding-bottom: 16px; }
                    .preview-card-header h3 { font-size: 20px; }
                    .preview-two-column { gap: 30px; }
                    .preview-card.half-width { width: calc(50% - 15px); display: inline-block; }
                    .preview-card.half-width:first-of-type { margin-right: 30px; }
                    .preview-grade-item, .preview-category-item { gap: 12px; margin-bottom: 16px; }
                    .preview-grade-label, .preview-category-name { min-width: 120px; font-size: 14px; }
                    .preview-grade-count, .preview-category-count { min-width: 40px; font-size: 14px; }
                    .preview-legend-row { font-size: 14px; }
                    .preview-dot { width: 18px; height: 18px; }
                }
                
                /* === MOBILE MODE (Tablet & Phone <= 768px) === */
                @media (max-width: 768px) {
                    .preview-card { padding: 16px; margin-bottom: 20px; }
                    .preview-card-header { margin-bottom: 15px; padding-bottom: 12px; }
                    .preview-card-header h3 { font-size: 16px; }
                    .preview-two-column { gap: 15px; flex-wrap: wrap; }
                    .preview-card.half-width { width: 100%; display: block !important; }
                    .preview-card.half-width:first-of-type { margin-right: 0; }
                    .preview-grade-item, .preview-category-item { gap: 8px; margin-bottom: 12px; }
                    .preview-grade-label, .preview-category-name { min-width: 100px; font-size: 12px; }
                    .preview-grade-count, .preview-category-count { min-width: 35px; font-size: 12px; }
                    .preview-legend-row { font-size: 12px; }
                    .preview-dot { width: 14px; height: 14px; }
                }
            `;
            previewContent.appendChild(style);
            
            // Add trend chart as image (Section 1)
            if (typeof trendChartInstance !== 'undefined' && trendChartInstance) {
                const trendImg = convertChartToImage(trendChartInstance);
                if (trendImg) {
                    const trendCard = document.createElement('div');
                    trendCard.className = 'preview-card full-width';
                    trendCard.innerHTML = `
                        <div class="preview-card-header">
                            <h3>Violation Trends (${window.exportDateRange.start_date} - ${window.exportDateRange.end_date})</h3>
                        </div>
                        <div class="preview-chart-container">
                            <img src="${trendImg}" class="preview-chart-img" alt="Trend Chart">
                        </div>
                    `;
                    previewContent.appendChild(trendCard);
                }
            }
            
            // Add violation percentage with donut chart (Section 2)
            const violationPercentageCard = document.querySelectorAll('.two-column-grid')[1]?.querySelector('.card:first-child');
            if (violationPercentageCard) {
                const clone = violationPercentageCard.cloneNode(true);
                clone.classList.add('preview-card', 'full-width');
                // Replace canvas with image
                const canvas = clone.querySelector('canvas');
                if (canvas && typeof donutChartInstance !== 'undefined' && donutChartInstance) {
                    const donutImg = convertChartToImage(donutChartInstance);
                    if (donutImg) {
                        const img = document.createElement('img');
                        img.src = donutImg;
                        img.className = 'preview-chart-img';
                        img.style.cssText = 'max-width: 300px; height: auto; display: block; margin: 0 auto;';
                        canvas.parentNode.replaceChild(img, canvas);
                    }
                }
                previewContent.appendChild(clone);
            }
            
            // Add grade violations (Section 3)
            const gradeCard = document.querySelectorAll('.two-column-grid')[0]?.querySelector('.card:nth-child(2)');
            if (gradeCard) {
                const clone = gradeCard.cloneNode(true);
                clone.classList.add('preview-card', 'full-width');
                previewContent.appendChild(clone);
            }
            
            // Add violations by category (Section 4)
            const categoryCard = document.querySelectorAll('.two-column-grid')[1]?.querySelector('.card:last-child');
            if (categoryCard) {
                const clone = categoryCard.cloneNode(true);
                clone.classList.add('preview-card', 'full-width');
                previewContent.appendChild(clone);
            }
            
            // Add preview info notice
            const notice = document.createElement('div');
            notice.style.cssText = `
                background: #e0f2fe;
                border-left: 6px solid #0284c7;
                padding: 16px;
                border-radius: 8px;
                margin-top: 20px;
                font-size: 13px;
                color: #000000;
                font-weight: 700;
                border: 2px solid #0284c7;
            `;
            notice.innerHTML = '<strong>ℹ️ Note:</strong> This preview shows how your export will look with all charts included.';
            previewContent.appendChild(notice);
            
            modalContent.appendChild(previewContent);
            
            // Add export buttons in modal footer
            const footer = document.createElement('div');
            footer.style.cssText = `
                display: flex;
                gap: 10px;
                margin-top: 30px;
                padding-top: 20px;
                border-top: 2px solid #cccccc;
                justify-content: center;
            `;
            
            const pngBtn = document.createElement('button');
            pngBtn.style.cssText = `
                background: #3b82f6;
                color: white;
                border: none;
                padding: 12px 24px;
                border-radius: 8px;
                cursor: pointer;
                font-size: 14px;
                font-weight: 700;
            `;
            pngBtn.innerHTML = '<i class="fas fa-image"></i> Export as PNG';
            pngBtn.onclick = () => {
                modalOverlay.remove();
                exportChartData('png');
            };
            footer.appendChild(pngBtn);
            
            const pdfBtn = document.createElement('button');
            pdfBtn.style.cssText = `
                background: #ef4444;
                color: white;
                border: none;
                padding: 12px 24px;
                border-radius: 8px;
                cursor: pointer;
                font-size: 14px;
                font-weight: 700;
            `;
            pdfBtn.innerHTML = '<i class="fas fa-file-pdf"></i> Export as PDF';
            pdfBtn.onclick = () => {
                modalOverlay.remove();
                exportChartData('pdf');
            };
            footer.appendChild(pdfBtn);
            
            modalContent.appendChild(footer);
            
            // Append modal to page
            modalOverlay.appendChild(modalContent);
            document.body.appendChild(modalOverlay);
            
            // Close on background click
            modalOverlay.addEventListener('click', function(event) {
                if (event.target === modalOverlay) {
                    modalOverlay.remove();
                }
            });
            
            // Show notification
            showNotification('Preview loaded successfully', 'info');
        }
        
        /**
         * Close export menu when clicking outside
         */
        document.addEventListener('click', function(event) {
            const exportMenu = document.getElementById('exportMenu');
            if (exportMenu && !event.target.closest('.export-section')) {
                exportMenu.style.display = 'none';
            }
        });
        
        /**
         * Export chart data in various formats
         */
        function exportChartData(format) {
            const dateRange = window.exportDateRange;
            const filename = `VIOTRACK Dashboard Report ${dateRange.start_date} to ${dateRange.end_date}`;
            
            if (format === 'png') {
                exportAsPNG(filename);
            } else if (format === 'pdf') {
                exportAsPDF(filename);
            }
            
            // Close menu after export
            document.getElementById('exportMenu').style.display = 'none';
        }
        

        
        /**
         * Export all charts as single PNG
         */
        function exportAsPNG(filename) {
            if (typeof html2canvas === 'undefined') {
                alert('PNG export requires html2canvas library.');
                return;
            }
            
            // Wait for charts to be ready
            setTimeout(() => {
                // Create a container that stays visible during rendering
                const chartsContainer = document.createElement('div');
                chartsContainer.style.cssText = `
                    background: #ffffff;
                    padding: 40px;
                    width: 1400px;
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                    position: absolute;
                    left: -9999px;
                    top: -9999px;
                    z-index: 99999;
                `;
                
                // Add title
                const title = document.createElement('h1');
                title.style.cssText = 'color: #000000; margin: 0 0 5px 0; font-size: 32px; font-weight: 900;';
                title.textContent = 'VIOTRACK Dashboard Report';
                chartsContainer.appendChild(title);
                
                // Add period info
                const periodInfo = document.createElement('p');
                periodInfo.style.cssText = 'color: #000000; margin: 0 0 40px 0; font-size: 14px; border-bottom: 3px solid #000000; padding-bottom: 20px; font-weight: 700;';
                periodInfo.textContent = `Period: ${window.exportDateRange.start_date} to ${window.exportDateRange.end_date} | Generated: ${new Date().toLocaleString()}`;
                chartsContainer.appendChild(periodInfo);
                
                // Inject stylesheet
                const style = document.createElement('style');
                style.textContent = `
                    * { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important; }
                    .export-card { background: #ffffff !important; border: 3px solid #000000 !important; border-radius: 12px; padding: 24px; margin-bottom: 30px; }
                    .export-card.full-width { width: 100%; }
                    .export-card.half-width { width: calc(50% - 15px); display: inline-block; }
                    .export-card.half-width:first-of-type { margin-right: 30px; }
                    .export-two-column { display: flex; gap: 30px; margin-bottom: 30px; }
                    .export-two-column .export-card { margin-bottom: 0; }
                    .export-card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 3px solid #000000; }
                    .export-card-header h3 { margin: 0; font-size: 20px; font-weight: 900 !important; color: #000000 !important; }
                    .export-chart-container { position: relative; width: 100%; margin: 20px 0; background: #ffffff; padding: 10px; border-radius: 6px; }
                    .export-chart-img { max-width: 100%; height: auto; display: block; }
                    .grade-item { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; padding: 10px 0; border-bottom: 2px solid #000000; }
                    .grade-label { min-width: 120px; font-size: 14px; font-weight: 900 !important; color: #000000 !important; }
                    .grade-bar-container { flex: 1; height: 14px; background: #cccccc; border-radius: 8px; overflow: hidden; border: 2px solid #000000; }
                    .grade-bar { height: 100%; background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); border-radius: 4px; }
                    .grade-count { min-width: 40px; text-align: right; font-size: 14px; font-weight: 900 !important; color: #000000 !important; }
                    .category-item { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; padding: 10px 0; border-bottom: 2px solid #000000; }
                    .category-name { min-width: 180px; font-size: 14px; font-weight: 900 !important; color: #000000 !important; }
                    .category-bar-container { flex: 1; height: 14px; background: #cccccc; border-radius: 8px; overflow: hidden; border: 2px solid #000000; }
                    .category-bar { height: 100%; border-radius: 6px; }
                    .category-count { min-width: 40px; text-align: right; font-size: 14px; font-weight: 900 !important; color: #000000 !important; }
                    .legend-row { display: flex; align-items: center; gap: 10px; font-size: 14px; color: #000000 !important; font-weight: 900 !important; margin-bottom: 10px; }
                    .dot { width: 18px; height: 18px; border-radius: 50%; border: 3px solid #000000 !important; }
                    .dot.minor { background: #22c55e !important; }
                    .dot.serious { background: #f59e0b !important; }
                    .dot.major { background: #ef4444 !important; }
                    img { max-width: 100% !important; height: auto !important; }
                    h1, h2, h3, h4, p { color: #000000 !important; }
                `;
                chartsContainer.appendChild(style);
                
                // Add trend chart as image (Section 1)
                if (typeof trendChartInstance !== 'undefined' && trendChartInstance) {
                    const trendImg = convertChartToImage(trendChartInstance);
                    if (trendImg) {
                        const trendCard = document.createElement('div');
                        trendCard.className = 'export-card full-width';
                        trendCard.innerHTML = `
                            <div class="export-card-header">
                                <h3>Violation Trends (${window.exportDateRange.start_date} - ${window.exportDateRange.end_date})</h3>
                            </div>
                            <div class="export-chart-container">
                                <img src="${trendImg}" class="export-chart-img" alt="Trend Chart">
                            </div>
                        `;
                        chartsContainer.appendChild(trendCard);
                    }
                }
                
                // Add violation percentage with donut chart (Section 2)
                if (typeof donutChartInstance !== 'undefined' && donutChartInstance) {
                    const donutImg = convertChartToImage(donutChartInstance);
                    if (donutImg) {
                        const percentageCard = document.createElement('div');
                        percentageCard.className = 'export-card full-width';
                        percentageCard.innerHTML = `
                            <div class="export-card-header">
                                <h3>Violation Percentage</h3>
                            </div>
                            <div class="export-chart-container" style="display: flex; justify-content: center;">
                                <img src="${donutImg}" class="export-chart-img" alt="Donut Chart" style="max-width: 300px; height: auto;">
                            </div>
                            <div style="margin-top: 8px; text-align: center;">
                                <div class="legend-row"><span class="dot minor"></span> <span>Minor</span> <span style="margin-left: auto;">${window.violationPercentage[0]?.count || 0} (${window.violationPercentage[0]?.percent || 0}%)</span></div>
                                <div class="legend-row"><span class="dot serious"></span> <span>Serious</span> <span style="margin-left: auto;">${window.violationPercentage[1]?.count || 0} (${window.violationPercentage[1]?.percent || 0}%)</span></div>
                                <div class="legend-row"><span class="dot major"></span> <span>Major</span> <span style="margin-left: auto;">${window.violationPercentage[2]?.count || 0} (${window.violationPercentage[2]?.percent || 0}%)</span></div>
                            </div>
                        `;
                        chartsContainer.appendChild(percentageCard);
                    }
                }
                
                // Add grade violations (Section 3)
                if (window.gradeViolations.length > 0) {
                    const maxCount = Math.max(...window.gradeViolations.map(g => g.count));
                    const gradeCard = document.createElement('div');
                    gradeCard.className = 'export-card full-width';
                    let gradeHTML = `
                        <div class="export-card-header">
                            <h3>Grade Level & Sections</h3>
                        </div>
                    `;
                    window.gradeViolations.forEach(grade => {
                        const percentage = (grade.count / maxCount) * 100;
                        gradeHTML += `
                            <div class="grade-item">
                                <span class="grade-label">${grade.grade_section}</span>
                                <div class="grade-bar-container">
                                    <div class="grade-bar" style="width: ${percentage}%;"></div>
                                </div>
                                <span class="grade-count">${grade.count}</span>
                            </div>
                        `;
                    });
                    gradeCard.innerHTML = gradeHTML;
                    chartsContainer.appendChild(gradeCard);
                }
                
                // Add violations by category (Section 4)
                if (window.categoryViolations.length > 0) {
                    const maxCat = Math.max(...window.categoryViolations.map(c => c.count));
                    const categoryCard = document.createElement('div');
                    categoryCard.className = 'export-card full-width';
                    let categoryHTML = `
                        <div class="export-card-header">
                            <h3>Violations by Category</h3>
                        </div>
                    `;
                    window.categoryViolations.forEach(cat => {
                        const barColor = cat.type === 'minor' ? '#22c55e' : (cat.type === 'serious' ? '#f59e0b' : '#ef4444');
                        const barWidth = (cat.count / maxCat) * 100;
                        categoryHTML += `
                            <div class="category-item">
                                <span class="category-name">${cat.name}</span>
                                <div class="category-bar-container">
                                    <div class="category-bar" style="width: ${barWidth}%; background-color: ${barColor};"></div>
                                </div>
                                <span class="category-count">${cat.count}</span>
                            </div>
                        `;
                    });
                    categoryCard.innerHTML = categoryHTML;
                    chartsContainer.appendChild(categoryCard);
                }
                
                document.body.appendChild(chartsContainer);
                
                // Render after insertion
                setTimeout(() => {
                    html2canvas(chartsContainer, {
                        allowTaint: true,
                        useCORS: true,
                        scale: 2,
                        backgroundColor: '#ffffff',
                        logging: false,
                        onclone: function(clonedDocument) {
                            console.log('Canvas cloned for PNG export');
                        }
                    }).then(canvas => {
                        const link = document.createElement('a');
                        link.href = canvas.toDataURL('image/png', 1.0);
                        link.download = filename + '.png';
                        link.click();
                        showNotification('Dashboard exported as PNG!', 'success');
                        document.body.removeChild(chartsContainer);
                    }).catch(error => {
                        console.error('PNG export error:', error);
                        alert('Error exporting PNG.');
                        if (chartsContainer.parentNode) {
                            document.body.removeChild(chartsContainer);
                        }
                    });
                }, 300);
            }, 500);
        }
        
        /**
         * Export all charts as PDF using browser print dialog
         */
        function exportAsPDF(filename) {
            // Create a new window for printing
            const printWindow = window.open('', '', 'width=1400,height=900');
            
            // Add HTML structure
            let htmlContent = `
                <!DOCTYPE html>
                <html>
                <head>
                    <title>${filename}</title>
                    <style>
                        * { margin: 0; padding: 0; box-sizing: border-box; }
                        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; background: #ffffff; }
                        .page { padding: 12px; background: #ffffff; }
                        h1 { color: #000000; margin: 0 0 3px 0; font-size: 20px; font-weight: 900; }
                        .period-info { color: #000000; margin: 0 0 12px 0; font-size: 10px; border-bottom: 1px solid #000000; padding-bottom: 6px; font-weight: 700; }
                        .export-card { background: #ffffff; border: 1px solid #000000; border-radius: 4px; padding: 8px; margin-bottom: 10px; page-break-inside: avoid; }
                        .export-card.full-width { width: 100%; }
                        .export-card.half-width { width: calc(50% - 5px); display: inline-block; vertical-align: top; }
                        .export-card.half-width:first-child { margin-right: 10px; }
                        .export-two-column { display: flex; gap: 10px; margin-bottom: 10px; }
                        .export-two-column .export-card { margin-bottom: 0; }
                        .export-card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; padding-bottom: 4px; border-bottom: 1px solid #000000; }
                        .export-card-header h3 { margin: 0; font-size: 11px; font-weight: 900; color: #000000; }
                        .export-chart-container { position: relative; width: 100%; margin: 6px 0; background: #ffffff; padding: 3px; border-radius: 2px; }
                        .export-chart-img { max-width: 100%; height: auto; display: block; max-height: 100px; }
                        .grade-item, .category-item { display: flex; align-items: center; gap: 6px; margin-bottom: 5px; padding: 3px 0; border-bottom: 0.5px solid #cccccc; font-size: 9px; }
                        .grade-label, .category-name { min-width: 90px; font-size: 9px; font-weight: 700; color: #000000; }
                        .grade-bar-container, .category-bar-container { flex: 1; height: 10px; background: #e0e0e0; border-radius: 2px; overflow: hidden; border: 0.5px solid #999999; }
                        .grade-bar, .category-bar { height: 100%; border-radius: 2px; }
                        .grade-count, .category-count { min-width: 20px; text-align: right; font-size: 9px; font-weight: 700; color: #000000; }
                        .legend-row { display: flex; align-items: center; gap: 5px; font-size: 9px; color: #000000; font-weight: 700; margin-bottom: 3px; }
                        .dot { width: 10px; height: 10px; border-radius: 50%; border: 1px solid #000000; }
                        .dot.minor { background: #22c55e; }
                        .dot.serious { background: #f59e0b; }
                        .dot.major { background: #ef4444; }
                        @media print { body { margin: 0; padding: 0; } .page { padding: 10px; margin: 0; } }
                    </style>
                </head>
                <body>
                    <div class="page">
                        <h1>VIOTRACK Dashboard Report</h1>
                        <p class="period-info">Period: ${window.exportDateRange.start_date} to ${window.exportDateRange.end_date} | Generated: ${new Date().toLocaleString()}</p>
            `;
            
            // Add trend chart as image (full-width)
            if (typeof trendChartInstance !== 'undefined' && trendChartInstance) {
                const trendImg = convertChartToImage(trendChartInstance);
                if (trendImg) {
                    htmlContent += `
                        <div class="export-card full-width">
                            <div class="export-card-header">
                                <h3>Violation Trends (${window.exportDateRange.start_date} - ${window.exportDateRange.end_date})</h3>
                            </div>
                            <div class="export-chart-container">
                                <img src="${trendImg}" class="export-chart-img" alt="Trend Chart" style="max-height: 70px;">
                            </div>
                        </div>
                    `;
                }
            }
            
            // Add violation percentage with donut chart (Section 2)
            if (typeof donutChartInstance !== 'undefined' && donutChartInstance) {
                const donutImg = convertChartToImage(donutChartInstance);
                if (donutImg) {
                    htmlContent += `
                        <div class="export-card full-width">
                            <div class="export-card-header">
                                <h3>Violation Percentage</h3>
                            </div>
                            <div class="export-chart-container" style="display: flex; justify-content: center;">
                                <img src="${donutImg}" class="export-chart-img" alt="Donut Chart" style="max-width: 140px; max-height: 140px;">
                            </div>
                            <div style="margin-top: 4px;">
                                <div class="legend-row"><span class="dot minor"></span> <span>Minor</span> <span style="margin-left: auto;">${window.violationPercentage[0]?.count || 0} (${window.violationPercentage[0]?.percent || 0}%)</span></div>
                                <div class="legend-row"><span class="dot serious"></span> <span>Serious</span> <span style="margin-left: auto;">${window.violationPercentage[1]?.count || 0} (${window.violationPercentage[1]?.percent || 0}%)</span></div>
                                <div class="legend-row"><span class="dot major"></span> <span>Major</span> <span style="margin-left: auto;">${window.violationPercentage[2]?.count || 0} (${window.violationPercentage[2]?.percent || 0}%)</span></div>
                            </div>
                        </div>
                    `;
                }
            }
            
            // Add grade violations (Section 3)
            if (window.gradeViolations.length > 0) {
                const maxCount = Math.max(...window.gradeViolations.map(g => g.count));
                htmlContent += `
                    <div class="export-card full-width">
                        <div class="export-card-header">
                            <h3>Grade Level & Sections</h3>
                        </div>
                `;
                window.gradeViolations.forEach(grade => {
                    const percentage = (grade.count / maxCount) * 100;
                    htmlContent += `
                        <div class="grade-item">
                            <span class="grade-label">${grade.grade_section}</span>
                            <div class="grade-bar-container">
                                <div class="grade-bar" style="width: ${percentage}%; background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);"></div>
                            </div>
                            <span class="grade-count">${grade.count}</span>
                        </div>
                    `;
                });
                htmlContent += `</div>`;
            }
            
            // Add category violations (full-width)
            if (window.categoryViolations.length > 0) {
                const maxCat = Math.max(...window.categoryViolations.map(c => c.count));
                htmlContent += `
                    <div class="export-card full-width">
                        <div class="export-card-header">
                            <h3>Violations by Category</h3>
                        </div>
                `;
                window.categoryViolations.forEach(cat => {
                    const barColor = cat.type === 'minor' ? '#22c55e' : (cat.type === 'serious' ? '#f59e0b' : '#ef4444');
                    const barWidth = (cat.count / maxCat) * 100;
                    htmlContent += `
                        <div class="category-item">
                            <span class="category-name">${cat.name}</span>
                            <div class="category-bar-container">
                                <div class="category-bar" style="width: ${barWidth}%; background-color: ${barColor};"></div>
                            </div>
                            <span class="category-count">${cat.count}</span>
                        </div>
                    `;
                });
                htmlContent += `</div>`;
            }
            
            htmlContent += `
                    </div>
                </body>
                </html>
            `;
            
            // Write content to print window
            printWindow.document.write(htmlContent);
            printWindow.document.close();
            
            // Wait for content to load, then print
            setTimeout(() => {
                printWindow.print();
                showNotification('Opening print dialog...', 'info');
            }, 500);
        }
        
        /**
         * Download CSV file
         */
        function downloadCSV(content, filename) {
            const link = document.createElement('a');
            link.setAttribute('href', 'data:text/csv;charset=utf-8,' + encodeURIComponent(content));
            link.setAttribute('download', filename);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
        
        /**
         * Show notification message
         */
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                background: ${type === 'success' ? '#10b981' : '#3b82f6'};
                color: white;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                z-index: 10000;
                animation: slideInRight 0.3s ease-out;
                font-size: 14px;
                font-weight: 500;
            `;
            notification.textContent = message;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOutRight 0.3s ease-out';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
    </script>

    <!-- Skeleton Screen + Map CSS -->
    <style>
        /* ── Skeleton Loader ── */
        .skeleton {
            background: linear-gradient(90deg, var(--surface-raised,#f1f5f9) 25%, var(--surface-border,#e2e8f0) 50%, var(--surface-raised,#f1f5f9) 75%);
            background-size: 200% auto;
            animation: skeleton-shimmer 1.5s linear infinite;
            border-radius: 8px;
        }
        @keyframes skeleton-shimmer {
            from { background-position: 200% center; }
            to   { background-position: -200% center; }
        }
        [data-theme="dark"] .skeleton {
            background: linear-gradient(90deg, #1e293b 25%, #253247 50%, #1e293b 75%);
            background-size: 200% auto;
            animation: skeleton-shimmer 1.5s linear infinite;
        }

        /* Stat card skeleton */
        .stat-card-skeleton {
            background: var(--surface, #fff);
            border-radius: 16px;
            padding: 24px;
            border: 1px solid var(--surface-border, #e2e8f0);
            box-shadow: var(--shadow-sm);
        }

        /* ── Map Card Tweaks ── */
        .map-card { overflow: hidden; }
        .leaflet-popup-content-wrapper {
            border-radius: 12px !important;
            box-shadow: 0 8px 32px rgba(0,0,0,0.15) !important;
            font-family: 'Inter', sans-serif !important;
        }
        .leaflet-popup-content { margin: 12px 16px !important; font-size: 13px; line-height: 1.6; }
        .map-popup-type {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 2px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }
        .map-popup-type.Major   { background: #ffe4e6; color: #ef4444; }
        .map-popup-type.Serious { background: #fef3c7; color: #f59e0b; }
        .map-popup-type.Minor   { background: #dcfce7; color: #22c55e; }
    </style>

    <!-- Violation Hotspot Map Script -->
    <script>
    let violationMapInstance = null;
    let mapMarkersLayer = null;

    function initViolationMap() {
        if (!document.getElementById('violationMap')) return;

        // Show skeleton
        document.getElementById('mapSkeleton').style.display = 'block';
        document.getElementById('mapContainer').style.display = 'none';

        fetch('php/get_location.php')
            .then(r => r.json())
            .then(data => {
                // Hide skeleton, show map
                document.getElementById('mapSkeleton').style.display = 'none';
                document.getElementById('mapContainer').style.display = 'block';

                const count = data.locations ? data.locations.length : 0;
                document.getElementById('mapViolationCount').textContent = count + ' location' + (count !== 1 ? 's' : '');

                // Initialize map centered on PHCM campus (Manila)
                if (!violationMapInstance) {
                    violationMapInstance = L.map('violationMap', {
                        center: [14.6124466, 120.9879835],
                        zoom: 15,
                        zoomControl: true,
                        scrollWheelZoom: true
                    });

                    // Premium tile layer
                    L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
                        attribution: '&copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/">CARTO</a>',
                        subdomains: 'abcd',
                        maxZoom: 20
                    }).addTo(violationMapInstance);

                    mapMarkersLayer = L.layerGroup().addTo(violationMapInstance);
                } else {
                    mapMarkersLayer.clearLayers();
                }

                if (!data.locations || data.locations.length === 0) {
                    document.getElementById('mapNoDataMsg').style.display = 'inline';
                    return;
                }

                document.getElementById('mapNoDataMsg').style.display = 'none';

                const colorMap = {
                    'Major':   { fill: '#ef4444', stroke: '#b91c1c' },
                    'Serious': { fill: '#f59e0b', stroke: '#b45309' },
                    'Minor':   { fill: '#22c55e', stroke: '#15803d' }
                };

                const bounds = [];
                data.locations.forEach(loc => {
                    const c = colorMap[loc.type] || { fill: '#6366f1', stroke: '#4f46e5' };

                    const marker = L.circleMarker([loc.lat, loc.lng], {
                        radius: loc.type === 'Major' ? 10 : (loc.type === 'Serious' ? 8 : 7),
                        fillColor: c.fill,
                        color: c.stroke,
                        weight: 2,
                        opacity: 1,
                        fillOpacity: 0.82
                    });

                    const date = loc.date ? new Date(loc.date).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : 'Unknown';

                    marker.bindPopup(`
                        <span class="map-popup-type ${loc.type}">${loc.type}</span>
                        <div style="font-weight:700;font-size:14px;margin-bottom:4px;">${loc.violation || 'Violation'}</div>
                        <div style="color:#64748b;font-size:12px;">
                            <i class="fas fa-user" style="width:14px;"></i> ${loc.student_name || 'Unknown'}
                        </div>
                        <div style="color:#64748b;font-size:12px;">
                            <i class="fas fa-layer-group" style="width:14px;"></i> Grade ${loc.grade || '-'} - ${loc.section || '-'}
                        </div>
                        <div style="color:#64748b;font-size:12px;">
                            <i class="fas fa-clock" style="width:14px;"></i> ${date}
                        </div>
                        <div style="color:#64748b;font-size:12px;">
                            <i class="fas fa-info-circle" style="width:14px;"></i> ${loc.status || 'Pending'}
                        </div>
                    `, { maxWidth: 260 });

                    marker.addTo(mapMarkersLayer);
                    bounds.push([loc.lat, loc.lng]);
                });

                if (bounds.length > 1) {
                    violationMapInstance.fitBounds(bounds, { padding: [40, 40] });
                }
            })
            .catch(err => {
                document.getElementById('mapSkeleton').style.display = 'none';
                document.getElementById('mapContainer').style.display = 'block';
                document.getElementById('mapViolationCount').textContent = 'Error';
                document.getElementById('mapNoDataMsg').style.display = 'inline';
                document.getElementById('mapNoDataMsg').textContent = 'Could not load location data.';
                console.error('Map load error:', err);
            });
    }

    function refreshViolationMap() {
        const icon = document.getElementById('mapRefreshIcon');
        if (icon) { icon.style.animation = 'spin 0.6s linear infinite'; }
        if (violationMapInstance) {
            mapMarkersLayer.clearLayers();
        }
        initViolationMap();
        setTimeout(() => { if (icon) icon.style.animation = ''; }, 1000);
    }

    // Init map when DOM is ready
    document.addEventListener('DOMContentLoaded', function () {
        if (document.getElementById('violationMap')) {
            initViolationMap();
        }
    });
    </script>

    <!-- Load Dashboard Script -->
    <script src="js/dashboard.js"></script>

    <?php include 'footer.php'; ?>