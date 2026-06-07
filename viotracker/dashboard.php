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
            <?php if ($isAdmin): ?>
            <div class="export-section">
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


    </main>

    <!-- Load Chart.js and Export Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

    <!-- Pass PHP data to JavaScript -->
    <script>
        
        // Pass data from PHP to JavaScript
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
                padding: 30px;
                width: 90%;
                max-width: 1200px;
                max-height: 85vh;
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
                .preview-two-column { display: flex; gap: 30px; margin-bottom: 30px; }
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
            `;
            previewContent.appendChild(style);
            
            // Add trend chart as image (full-width)
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
            
            // Create two-column container for middle section
            const twoColumnContainer = document.createElement('div');
            twoColumnContainer.className = 'preview-two-column';
            
            // Clone other cards for preview
            const gradeCard = document.querySelectorAll('.two-column-grid')[0]?.querySelector('.card:nth-child(2)');
            const violationPercentageCard = document.querySelectorAll('.two-column-grid')[1]?.querySelector('.card:first-child');
            const categoryCard = document.querySelectorAll('.two-column-grid')[1]?.querySelector('.card:last-child');
            
            // Add violation percentage card with donut chart (left column)
            if (violationPercentageCard) {
                const clone = violationPercentageCard.cloneNode(true);
                clone.classList.add('preview-card', 'half-width');
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
                twoColumnContainer.appendChild(clone);
            }
            
            // Add grade violations by sections (right column)
            if (gradeCard) {
                const clone = gradeCard.cloneNode(true);
                clone.classList.add('preview-card', 'half-width');
                twoColumnContainer.appendChild(clone);
            }
            
            previewContent.appendChild(twoColumnContainer);
            
            // Add violations by category (full-width)
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
                
                // Add trend chart as image (full-width)
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
                
                // Create two-column container
                const twoColumnContainer = document.createElement('div');
                twoColumnContainer.className = 'export-two-column';
                
                // Add violation percentage card with donut chart (left column)
                if (typeof donutChartInstance !== 'undefined' && donutChartInstance) {
                    const donutImg = convertChartToImage(donutChartInstance);
                    if (donutImg) {
                        const percentageCard = document.createElement('div');
                        percentageCard.className = 'export-card half-width';
                        percentageCard.innerHTML = `
                            <div class="export-card-header">
                                <h3>Violation Percentage</h3>
                            </div>
                            <div class="export-chart-container" style="display: flex; justify-content: center;">
                                <img src="${donutImg}" class="export-chart-img" alt="Donut Chart" style="max-width: 250px; height: auto;">
                            </div>
                            <div style="margin-top: 8px;">
                                <div class="legend-row"><span class="dot minor"></span> <span>Minor</span> <span style="margin-left: auto;">${window.violationPercentage[0]?.count || 0} (${window.violationPercentage[0]?.percent || 0}%)</span></div>
                                <div class="legend-row"><span class="dot serious"></span> <span>Serious</span> <span style="margin-left: auto;">${window.violationPercentage[1]?.count || 0} (${window.violationPercentage[1]?.percent || 0}%)</span></div>
                                <div class="legend-row"><span class="dot major"></span> <span>Major</span> <span style="margin-left: auto;">${window.violationPercentage[2]?.count || 0} (${window.violationPercentage[2]?.percent || 0}%)</span></div>
                            </div>
                        `;
                        twoColumnContainer.appendChild(percentageCard);
                    }
                }
                
                // Add grade violations (right column)
                if (window.gradeViolations.length > 0) {
                    const maxCount = Math.max(...window.gradeViolations.map(g => g.count));
                    const gradeCard = document.createElement('div');
                    gradeCard.className = 'export-card half-width';
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
                    twoColumnContainer.appendChild(gradeCard);
                }
                
                chartsContainer.appendChild(twoColumnContainer);
                
                // Add violations by category (full-width)
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
                        .page { padding: 20px; background: #ffffff; }
                        h1 { color: #000000; margin: 0 0 5px 0; font-size: 24px; font-weight: 900; }
                        .period-info { color: #000000; margin: 0 0 20px 0; font-size: 12px; border-bottom: 2px solid #000000; padding-bottom: 10px; font-weight: 700; }
                        .export-card { background: #ffffff; border: 2px solid #000000; border-radius: 8px; padding: 12px; margin-bottom: 15px; page-break-inside: avoid; }
                        .export-card.full-width { width: 100%; }
                        .export-card.half-width { width: calc(50% - 8px); display: inline-block; vertical-align: top; }
                        .export-card.half-width:first-child { margin-right: 16px; }
                        .export-two-column { display: flex; gap: 16px; margin-bottom: 15px; }
                        .export-two-column .export-card { margin-bottom: 0; }
                        .export-card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; padding-bottom: 8px; border-bottom: 2px solid #000000; }
                        .export-card-header h3 { margin: 0; font-size: 14px; font-weight: 900; color: #000000; }
                        .export-chart-container { position: relative; width: 100%; margin: 10px 0; background: #ffffff; padding: 5px; border-radius: 4px; }
                        .export-chart-img { max-width: 100%; height: auto; display: block; }
                        .grade-item, .category-item { display: flex; align-items: center; gap: 8px; margin-bottom: 10px; padding: 6px 0; border-bottom: 1px solid #cccccc; font-size: 12px; }
                        .grade-label, .category-name { min-width: 120px; font-size: 11px; font-weight: 700; color: #000000; }
                        .grade-bar-container, .category-bar-container { flex: 1; height: 12px; background: #e0e0e0; border-radius: 6px; overflow: hidden; border: 1px solid #999999; }
                        .grade-bar, .category-bar { height: 100%; border-radius: 4px; }
                        .grade-count, .category-count { min-width: 30px; text-align: right; font-size: 11px; font-weight: 700; color: #000000; }
                        .legend-row { display: flex; align-items: center; gap: 8px; font-size: 11px; color: #000000; font-weight: 700; margin-bottom: 6px; }
                        .dot { width: 14px; height: 14px; border-radius: 50%; border: 2px solid #000000; }
                        .dot.minor { background: #22c55e; }
                        .dot.serious { background: #f59e0b; }
                        .dot.major { background: #ef4444; }
                        @media print { body { margin: 0; padding: 0; } .page { padding: 15px; margin: 0; } }
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
                                <img src="${trendImg}" class="export-chart-img" alt="Trend Chart" style="max-height: 120px;">
                            </div>
                        </div>
                    `;
                }
            }
            
            // Start two-column section
            htmlContent += `<div class="export-two-column">`;
            
            // Add violation percentage chart (left column)
            if (typeof donutChartInstance !== 'undefined' && donutChartInstance) {
                const donutImg = convertChartToImage(donutChartInstance);
                if (donutImg) {
                    htmlContent += `
                        <div class="export-card half-width">
                            <div class="export-card-header">
                                <h3>Violation Percentage</h3>
                            </div>
                            <div class="export-chart-container" style="display: flex; justify-content: center;">
                                <img src="${donutImg}" class="export-chart-img" alt="Donut Chart" style="max-width: 150px; max-height: 150px;">
                            </div>
                            <div style="margin-top: 8px;">
                                <div class="legend-row"><span class="dot minor"></span> <span>Minor</span> <span style="margin-left: auto;">${window.violationPercentage[0]?.count || 0} (${window.violationPercentage[0]?.percent || 0}%)</span></div>
                                <div class="legend-row"><span class="dot serious"></span> <span>Serious</span> <span style="margin-left: auto;">${window.violationPercentage[1]?.count || 0} (${window.violationPercentage[1]?.percent || 0}%)</span></div>
                                <div class="legend-row"><span class="dot major"></span> <span>Major</span> <span style="margin-left: auto;">${window.violationPercentage[2]?.count || 0} (${window.violationPercentage[2]?.percent || 0}%)</span></div>
                            </div>
                        </div>
                    `;
                }
            }
            
            // Add grade violations (right column)
            if (window.gradeViolations.length > 0) {
                const maxCount = Math.max(...window.gradeViolations.map(g => g.count));
                htmlContent += `
                    <div class="export-card half-width">
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
            
            // End two-column section
            htmlContent += `</div>`;
            
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

    <!-- Load Dashboard Script -->
    <script src="js/dashboard.js"></script>

    <?php include 'footer.php'; ?>