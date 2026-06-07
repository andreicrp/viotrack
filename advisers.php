<?php
// advisers.php
include 'auth_check.php';
include 'connect.php';

// Require login - only admin can access
requireAdmin();

include 'header.php';
include 'sidebar.php';

// Fetch all advisers with their student counts
$advisersSQL = "
    SELECT 
        a.id,
        a.tid,
        a.fname,
        a.mname,
        a.lname,
        a.email,
        a.grade_level,
        a.class_section,
        a.image,
        (SELECT COUNT(*) FROM student s WHERE (TRIM(s.grade) = TRIM(a.grade_level) OR TRIM(s.grade) = CONCAT('Grade ', TRIM(a.grade_level)) OR TRIM(REPLACE(s.grade, 'Grade ', '')) = TRIM(REPLACE(a.grade_level, 'Grade ', ''))) AND (TRIM(s.section) = TRIM(a.class_section) OR LEFT(TRIM(s.section), LENGTH(TRIM(a.class_section))) = TRIM(a.class_section) OR LEFT(TRIM(a.class_section), LENGTH(TRIM(s.section))) = TRIM(s.section))) as student_count
    FROM adviser a
    ORDER BY a.grade_level ASC, a.class_section ASC
";

$advisersResult = $conn->query($advisersSQL);
if (!$advisersResult) {
    die("Error fetching advisers: " . $conn->error);
}

$advisers = [];
if ($advisersResult->num_rows > 0) {
    while($row = $advisersResult->fetch_assoc()) {
        $advisers[] = $row;
    }
}

$totalAdvisers = count($advisers);

// Define color palette for advisers (light pastel gradients)
$colors = [
    ['bg' => '#fffbeb', 'text' => '#78350f', 'border' => '#fed7aa', 'shadow' => 'rgba(245, 158, 11, 0.1)', 'gradient1' => '#fffbeb', 'gradient2' => '#fef3c7'],   // Amber
    ['bg' => '#fef2f2', 'text' => '#7f1d1d', 'border' => '#fecaca', 'shadow' => 'rgba(239, 68, 68, 0.1)', 'gradient1' => '#fef2f2', 'gradient2' => '#fee2e2'],       // Red
    ['bg' => '#eff6ff', 'text' => '#082f49', 'border' => '#bfdbfe', 'shadow' => 'rgba(59, 130, 246, 0.1)', 'gradient1' => '#eff6ff', 'gradient2' => '#dbeafe'],     // Blue
    ['bg' => '#f0fdf4', 'text' => '#166534', 'border' => '#bbf7d0', 'shadow' => 'rgba(34, 197, 94, 0.1)', 'gradient1' => '#f0fdf4', 'gradient2' => '#dcfce7'],       // Green
    ['bg' => '#faf5ff', 'text' => '#6b21a8', 'border' => '#e9d5ff', 'shadow' => 'rgba(168, 85, 247, 0.1)', 'gradient1' => '#faf5ff', 'gradient2' => '#f3e8ff'],     // Purple
    ['bg' => '#fdf2f8', 'text' => '#be185d', 'border' => '#fbcfe8', 'shadow' => 'rgba(236, 72, 153, 0.1)', 'gradient1' => '#fdf2f8', 'gradient2' => '#fce7f3'],     // Pink
    ['bg' => '#f5f3ff', 'text' => '#3730a3', 'border' => '#ddd6fe', 'shadow' => 'rgba(99, 102, 241, 0.1)', 'gradient1' => '#f5f3ff', 'gradient2' => '#ede9fe'],     // Indigo
    ['bg' => '#f9fafb', 'text' => '#374151', 'border' => '#e5e7eb', 'shadow' => 'rgba(156, 163, 175, 0.1)', 'gradient1' => '#f9fafb', 'gradient2' => '#f3f4f6'],    // Gray
];

?>

<main class="main-content">
    <!-- Advisers Card -->
    <div class="card adviser-card" style="margin: 16px;">
        <div class="card-header" style="flex-direction: column; align-items: flex-start; gap: 12px;">
            <div class="card-title-section">
                <h3 style="font-size: 20px; margin-bottom: 4px;">Adviser Management</h3>
                <p class="subtitle">You have <?php echo $totalAdvisers; ?> adviser(s)</p>
            </div>
            <div class="header-actions" style="width: 100%; display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
                <div style="flex: 1; min-width: 200px; position: relative; display: flex; align-items: center;">
                    <i class="fas fa-search" style="position: absolute; left: 12px; color: #9ca3af; font-size: 14px;"></i>
                    <input type="text" id="adviserSearch" placeholder="Search by name, email, grade, or section..." style="flex: 1; padding: 10px 14px 10px 38px; border: 2px solid #d1d5db; border-radius: 6px; font-size: 13px; transition: all 0.2s; width: 100%;" onkeyup="filterAdvisers()" onfocus="this.style.borderColor='#3b82f6'; this.style.boxShadow='0 0 0 3px rgba(59, 130, 246, 0.1)'" onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none'">
                    <button id="clearSearchBtn" onclick="clearSearch()" style="position: absolute; right: 10px; background: none; border: none; color: #9ca3af; cursor: pointer; font-size: 16px; padding: 4px 6px; display: none; transition: all 0.2s;" onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#9ca3af'" title="Clear search">
                        <i class="fas fa-times-circle"></i>
                    </button>
                </div>
                <button class="btn btn-primary" style="background: #27367f; color: #fff; border: none; font-weight: 600; padding: 10px 18px; border-radius: 6px; cursor: pointer; transition: all 0.2s; box-shadow: 0 2px 8px rgba(39, 54, 127, 0.2); white-space: nowrap;" onclick="goBackToTeachers()">
                    <i class="fas fa-arrow-left"></i> Back to Teachers
                </button>
            </div>
        </div>

        <!-- Advisers Grid View -->
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(400px, 1fr)); gap: 16px; padding: 16px;">
            <?php if(count($advisers) > 0): ?>
                <?php foreach ($advisers as $adviser): ?>
                    <?php 
                    // Get color for this adviser based on ID
                    $colorIndex = $adviser['id'] % count($colors);
                    $adviserColor = $colors[$colorIndex];
                    
                    // Prepare adviser data BEFORE using in data attributes
                    $avatar = !empty($adviser['image']) ? htmlspecialchars($adviser['image'], ENT_QUOTES, 'UTF-8') : 'image/default.jpg';
                    $initials = substr($adviser['fname'], 0, 1) . substr($adviser['lname'], 0, 1);
                    $fullName = htmlspecialchars(trim($adviser['fname'] . ' ' . (!empty($adviser['mname']) ? $adviser['mname'] . ' ' : '') . $adviser['lname']), ENT_QUOTES, 'UTF-8');
                    ?>
                <div style="background: white; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08); transition: all 0.3s; display: flex; flex-direction: column; min-width: 0;" onmouseover="this.style.boxShadow='0 4px 16px rgba(0,0,0,0.12)'; this.style.transform='translateY(-2px)'" onmouseout="this.style.boxShadow='0 2px 8px rgba(0,0,0,0.08)'; this.style.transform='translateY(0)'" data-adviser-name="<?php echo strtolower($fullName); ?>" data-adviser-email="<?php echo strtolower(htmlspecialchars($adviser['email'], ENT_QUOTES, 'UTF-8')); ?>" data-adviser-grade="<?php echo strtolower(htmlspecialchars($adviser['grade_level'], ENT_QUOTES, 'UTF-8')); ?>" data-adviser-section="<?php echo strtolower(htmlspecialchars($adviser['class_section'], ENT_QUOTES, 'UTF-8')); ?>">
                    <!-- Adviser Header -->
                    <div style="background: linear-gradient(135deg, <?php echo $adviserColor['gradient1']; ?> 0%, <?php echo $adviserColor['gradient2']; ?> 100%); padding: 16px; color: <?php echo $adviserColor['text']; ?>; border-bottom: 2px solid <?php echo $adviserColor['border']; ?>;">
                        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px; flex-wrap: wrap;">
                            <?php 
                            ?>
                            <img src="<?php echo $avatar; ?>" 
                                 alt="<?php echo $fullName; ?>" 
                                 style="width: 48px; height: 48px; border-radius: 50%; object-fit: cover; border: 3px solid white; flex-shrink: 0;"
                                 onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($initials); ?>&background=<?php echo substr($adviserColor['border'], 1); ?>&color=fff'">
                            <div style="flex: 1; min-width: 120px;">
                                <h4 style="margin: 0 0 2px 0; font-size: 14px; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo $fullName; ?></h4>
                                <p style="margin: 0; font-size: 12px; opacity: 0.8; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($adviser['email'], ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Adviser Info -->
                    <div style="padding: 16px; border-bottom: 1px solid #e5e7eb; min-width: 0;">
                        <!-- Grade and Section with Colors -->
                        <div style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 12px; min-width: 0;">
                            <div style="background: <?php echo $adviserColor['bg']; ?>; padding: 10px; border-radius: 8px; border: 2px solid <?php echo $adviserColor['border']; ?>; box-shadow: 0 2px 8px <?php echo $adviserColor['shadow']; ?>; transition: all 0.3s ease; min-width: 0; width: 100%;">
                                <p style="margin: 0; font-size: 11px; color: <?php echo $adviserColor['text']; ?>; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">Grade</p>
                                <p style="margin: 4px 0 0 0; font-size: 14px; color: <?php echo $adviserColor['text']; ?>; font-weight: 700; width: 100%; max-width: none;"><?php echo htmlspecialchars($adviser['grade_level'], ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>
                            <div style="background: <?php echo $adviserColor['bg']; ?>; padding: 10px; border-radius: 8px; border: 2px solid <?php echo $adviserColor['border']; ?>; box-shadow: 0 2px 8px <?php echo $adviserColor['shadow']; ?>; transition: all 0.3s ease; min-width: 0; width: 100%;">
                                <p style="margin: 0; font-size: 11px; color: <?php echo $adviserColor['text']; ?>; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">Section</p>
                                <p style="margin: 4px 0 0 0; font-size: 14px; color: <?php echo $adviserColor['text']; ?>; font-weight: 700; white-space: normal; word-break: break-word; overflow-wrap: break-word; width: 100%; max-width: none;" title="<?php echo htmlspecialchars($adviser['class_section'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($adviser['class_section'], ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>
                        </div>

                        <!-- Student Count Badge -->
                        <div style="background: <?php echo $adviserColor['bg']; ?>; padding: 10px; border-radius: 8px; border: 2px solid <?php echo $adviserColor['border']; ?>; box-shadow: 0 2px 8px <?php echo $adviserColor['shadow']; ?>; display: flex; align-items: center; gap: 6px; justify-content: center; transition: all 0.3s ease;">
                            <i class="fas fa-users" style="color: <?php echo $adviserColor['text']; ?>; font-size: 14px;"></i>
                            <span style="font-weight: 600; color: <?php echo $adviserColor['text']; ?>; font-size: 13px;">
                                <?php echo (int)$adviser['student_count']; ?> student<?php echo $adviser['student_count'] != 1 ? 's' : ''; ?>
                            </span>
                        </div>
                    </div>

                    <!-- Students List -->
                    <div style="padding: 12px; background: #f9fafb; max-height: 280px; overflow-y: auto;">
                        <?php
                        // Fetch students for this adviser
                        $gradeLevel = trim($adviser['grade_level']);
                        $classSection = trim($adviser['class_section']);
                        
                        // Use prepared statement with exact grade and section matching
                        $gradeNum = trim(str_replace('Grade ', '', $gradeLevel));
                        $studentsSQL = "SELECT 
                                            id,
                                            lrn,
                                            fname,
                                            mname,
                                            lname,
                                            email,
                                            gender,
                                            image
                                        FROM student 
                                        WHERE (TRIM(grade) = ? OR TRIM(grade) = CONCAT('Grade ', ?) OR TRIM(REPLACE(grade, 'Grade ', '')) = ?)
                                        AND TRIM(section) = ?
                                        ORDER BY lname ASC, fname ASC";
                        
                        $studentStmt = $conn->prepare($studentsSQL);
                        $studentStmt->bind_param('ssss', $gradeLevel, $gradeNum, $gradeLevel, $classSection);
                        $studentStmt->execute();
                        $studentsResult = $studentStmt->get_result();
                        
                        if (!$studentsResult) {
                            echo '<p style="color: #ef4444; font-size: 12px; padding: 12px; text-align: center;"><i class="fas fa-exclamation-circle"></i> Error loading students: ' . htmlspecialchars($conn->error) . '</p>';
                            $studentStmt->close();
                        } else {
                            
                            if ($studentsResult->num_rows > 0) {
                                echo '<div style="font-size: 11px; color: #6b7280; margin-bottom: 8px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
                                        <i class="fas fa-book" style="margin-right: 4px;"></i>Students (' . $studentsResult->num_rows . ')
                                      </div>';
                                
                                $count = 0;
                                while($student = $studentsResult->fetch_assoc()) {
                                    $count++;
                                    $studentName = htmlspecialchars(trim($student['fname'] . ' ' . (!empty($student['mname']) ? $student['mname'] . ' ' : '') . $student['lname']), ENT_QUOTES, 'UTF-8');
                                    $studentInitials = substr($student['fname'], 0, 1) . substr($student['lname'], 0, 1);
                                    $studentLRN = htmlspecialchars($student['lrn'], ENT_QUOTES, 'UTF-8');
                                    $studentEmail = htmlspecialchars($student['email'], ENT_QUOTES, 'UTF-8');
                                    $studentGender = htmlspecialchars($student['gender'], ENT_QUOTES, 'UTF-8');
                                    
                                    // Determine avatar - Check if custom image exists, otherwise use generated avatar
                                    $studentAvatar = 'image/default.jpg';
                                    if (!empty($student['image']) && $student['image'] != 'image/default.jpg' && $student['image'] != 'image/default.png') {
                                        // If it's a URL or file exists, use it
                                        if (strpos($student['image'], 'http') === 0 || file_exists($student['image'])) {
                                            $studentAvatar = htmlspecialchars($student['image'], ENT_QUOTES, 'UTF-8');
                                        } else {
                                            // File doesn't exist, generate avatar with initials
                                            $studentAvatar = 'https://ui-avatars.com/api/?name=' . urlencode($studentInitials) . '&background=10b981&color=fff&size=32';
                                        }
                                    } else {
                                        // No custom image, generate avatar with initials
                                        $studentAvatar = 'https://ui-avatars.com/api/?name=' . urlencode($studentInitials) . '&background=10b981&color=fff&size=32';
                                    }
                                    
                                    echo '
                                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 6px; padding: 8px 10px; background: white; border-radius: 6px; margin-bottom: 6px; border: 1px solid #e5e7eb; transition: all 0.2s;" onmouseover="this.style.background=\'#f0f9ff\'; this.style.borderColor=\'#3b82f6\'; this.style.transform=\'translateX(2px)\'" onmouseout="this.style.background=\'white\'; this.style.borderColor=\'#e5e7eb\'; this.style.transform=\'translateX(0)\'">
                                        <div style="display: flex; align-items: center; gap: 6px; flex: 1; min-width: 0;">
                                            <img src="' . $studentAvatar . '" 
                                                 alt="' . $studentName . '" 
                                                 style="width: 28px; height: 28px; border-radius: 50%; object-fit: cover; flex-shrink: 0; border: 1px solid #e5e7eb;">
                                            <div style="flex: 1; min-width: 0;">
                                                <p style="margin: 0; font-size: 12px; font-weight: 600; color: #1f2937; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">' . $studentName . '</p>
                                                <p style="margin: 1px 0 0 0; font-size: 10px; color: #9ca3af;">ID: ' . $studentLRN . '</p>
                                            </div>
                                        </div>
                                        <button onclick="addViolation(' . (int)$student['id'] . ', \'' . htmlspecialchars($studentName, ENT_QUOTES) . '\')" style="padding: 5px 10px; background: #ef4444; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; transition: all 0.2s; white-space: nowrap; flex-shrink: 0;" onmouseover="this.style.background=\'#dc2626\'" onmouseout="this.style.background=\'#ef4444\'">
                                            <i class="fas fa-exclamation-circle" style="margin-right: 2px;"></i>Violation
                                        </button>
                                    </div>';
                                    
                                    if ($count >= 6) {
                                        if ($studentsResult->num_rows > 6) {
                                            echo '<div style="text-align: center; padding: 6px; color: #6b7280; font-size: 11px; font-weight: 600; background: white; border-radius: 4px; border: 1px solid #e5e7eb;">
                                                    +' . ($studentsResult->num_rows - 6) . ' more
                                                  </div>';
                                        }
                                        break;
                                    }
                                }
                            } else {
                                echo '<div style="text-align: center; padding: 20px 12px;">
                                        <i class="fas fa-inbox" style="font-size: 24px; color: #cbd5e1; display: block; margin-bottom: 8px;"></i>
                                        <p style="margin: 0; color: #9ca3af; font-size: 12px;">No students</p>
                                      </div>';
                            }
                            $studentStmt->close();
                        }
                        ?>
                    </div>

                    <!-- Actions -->
                    <div style="padding: 12px; background: white; border-top: 1px solid #e5e7eb; display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                        <button onclick="editAdviser(<?php echo (int)$adviser['id']; ?>)" style="padding: 8px 12px; background: #3b82f6; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 12px; transition: all 0.2s;" onmouseover="this.style.background='#2563eb'" onmouseout="this.style.background='#3b82f6'">
                            <i class="far fa-eye" style="margin-right: 2px;"></i> View
                        </button>
                        <button onclick="removeAdviser(<?php echo (int)$adviser['id']; ?>, '<?php echo htmlspecialchars($adviser['fname'] . ' ' . $adviser['lname'], ENT_QUOTES); ?>')" style="padding: 8px 12px; background: #ef4444; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 12px; transition: all 0.2s;" onmouseover="this.style.background='#dc2626'" onmouseout="this.style.background='#ef4444'">
                            <i class="far fa-trash-alt" style="margin-right: 2px;"></i> Remove
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px;">
                    <i class="fas fa-inbox" style="font-size: 64px; color: #cbd5e1; display: block; margin-bottom: 16px;"></i>
                    <h3 style="margin: 0 0 8px 0; color: #6b7280; font-size: 18px; font-weight: 600;">No Advisers Yet</h3>
                    <p style="margin: 0 0 24px 0; color: #9ca3af; font-size: 14px;">Go to the Teachers page and appoint teachers as advisers.</p>
                    <button onclick="goBackToTeachers()" style="padding: 10px 20px; background: #27367f; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; transition: all 0.2s; box-shadow: 0 2px 8px rgba(39, 54, 127, 0.2);" onmouseover="this.style.boxShadow='0 4px 12px rgba(39, 54, 127, 0.3)'; this.style.transform='translateY(-2px)'" onmouseout="this.style.boxShadow='0 2px 8px rgba(39, 54, 127, 0.2)'; this.style.transform='translateY(0)'">
                        <i class="fas fa-arrow-left" style="margin-right: 4px;"></i> Back to Teachers
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
function goBackToTeachers() {
    window.location.href = 'teacher.php';
}

function clearSearch() {
    const searchInput = document.getElementById('adviserSearch');
    searchInput.value = '';
    searchInput.focus();
    filterAdvisers();
}

// Show/hide clear button based on input
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('adviserSearch');
    const clearBtn = document.getElementById('clearSearchBtn');
    
    searchInput.addEventListener('input', function() {
        clearBtn.style.display = this.value.length > 0 ? 'block' : 'none';
    });
});

function filterAdvisers() {
    const searchInput = document.getElementById('adviserSearch').value.toLowerCase().trim();
    const cards = document.querySelectorAll('[data-adviser-name]');
    let visibleCount = 0;
    
    // If search is empty, show all cards
    if (searchInput.length === 0) {
        cards.forEach(card => {
            card.style.display = 'block';
            card.style.opacity = '1';
            visibleCount++;
        });
        // Remove no results message
        const noResults = document.querySelector('.no-advisers-found');
        if (noResults) noResults.remove();
        return;
    }
    
    // Split search into keywords for better matching
    const keywords = searchInput.split(/\s+/);
    
    cards.forEach(card => {
        const name = card.getAttribute('data-adviser-name') || '';
        const email = card.getAttribute('data-adviser-email') || '';
        const grade = card.getAttribute('data-adviser-grade') || '';
        const section = card.getAttribute('data-adviser-section') || '';
        
        // Combine all searchable fields
        const searchableText = `${name} ${email} ${grade} ${section}`;
        
        // Check if ALL keywords match (more strict filtering)
        const matchesAllKeywords = keywords.every(keyword => searchableText.includes(keyword));
        
        if (matchesAllKeywords) {
            card.style.display = 'block';
            card.style.opacity = '1';
            card.style.transition = 'all 0.3s ease';
            visibleCount++;
        } else {
            card.style.display = 'none';
            card.style.opacity = '0.5';
        }
    });
    
    // Show/hide no results message
    const container = document.querySelector('[style*="grid"][style*="gap"]');
    let noResults = container ? container.querySelector('.no-advisers-found') : null;
    
    if (visibleCount === 0 && searchInput.length > 0) {
        if (!noResults && container) {
            noResults = document.createElement('div');
            noResults.className = 'no-advisers-found';
            noResults.style.cssText = 'grid-column: 1 / -1; text-align: center; padding: 40px 20px; animation: fadeIn 0.3s ease;';
            noResults.innerHTML = '<i class="fas fa-search" style="font-size: 48px; color: #cbd5e1; display: block; margin-bottom: 16px;"></i><p style="margin: 0; color: #9ca3af; font-size: 16px; font-weight: 500;">No advisers found matching "<strong style="color: #6b7280;">' + searchInput + '</strong>"</p><p style="margin: 8px 0 0 0; color: #cbd5e1; font-size: 14px;">Try searching by name, email, grade, or section</p>';
            container.appendChild(noResults);
        }
    } else if (noResults) {
        noResults.remove();
    }
    
    // Update search input styling
    const searchInput_elem = document.getElementById('adviserSearch');
    if (visibleCount === 0 && searchInput.length > 0) {
        searchInput_elem.style.borderColor = '#ef4444';
        searchInput_elem.style.backgroundColor = '#fef2f2';
    } else {
        searchInput_elem.style.borderColor = '#3b82f6';
        searchInput_elem.style.backgroundColor = 'white';
    }
}

function editAdviser(id) {
    window.location.href = 'adviserview-student.php?id=' + id;
}

function addViolation(studentId, studentName) {
    window.location.href = 'adminstudentviolation.php?id=' + studentId;
}

function removeAdviser(id, name) {
    if (confirm(`Are you sure you want to remove ${name} as an adviser?`)) {
        // Send request to backend to remove adviser
        fetch('delete-adviser.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: parseInt(id) })
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                alert('Adviser removed successfully.');
                location.reload();
            } else {
                alert('Error removing adviser: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while removing the adviser. Please try again.');
        });
    }
}
</script>

<?php
$conn->close();
include 'footer.php';
?>
