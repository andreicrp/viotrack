<!-- filter-modal.php -->
<?php
// Fetch filter data from database
// Connection already included in parent file

// Get unique academic years from students table
$academicYearsQuery = "SELECT DISTINCT academicyear FROM student WHERE academicyear IS NOT NULL AND academicyear != '' ORDER BY academicyear DESC";
$academicYearsResult = $conn->query($academicYearsQuery);
$academicYears = [];
if ($academicYearsResult) {
    while ($row = $academicYearsResult->fetch_assoc()) {
        $academicYears[] = $row['academicyear'];
    }
}

// Get unique grades from students table
$gradesQuery = "SELECT DISTINCT grade FROM student WHERE grade IS NOT NULL AND grade != '' ORDER BY grade ASC";
$gradesResult = $conn->query($gradesQuery);
$grades = [];
if ($gradesResult) {
    while ($row = $gradesResult->fetch_assoc()) {
        $grades[] = $row['grade'];
    }
}

// Get unique sections from students table
$sectionsQuery = "SELECT DISTINCT section FROM student WHERE section IS NOT NULL AND section != '' ORDER BY section ASC";
$sectionsResult = $conn->query($sectionsQuery);
$sections = [];
if ($sectionsResult) {
    while ($row = $sectionsResult->fetch_assoc()) {
        $sections[] = $row['section'];
    }
}

$conn->close();
?>

<!-- Filter Schedule Modal -->
<div class="filter-modal-overlay" id="filterModal">
    <div class="filter-modal">
        <div class="filter-modal-header">
            <h2>Filter Schedule</h2>
            <button class="filter-modal-close" onclick="closeFilterModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="filter-modal-body">
            <!-- Academic Year -->
            <div class="filter-group">
                <label class="filter-label">Academic Year</label>
                <select class="filter-select" id="filterAcademicYear" onchange="onAcademicYearChange()">
                    <option value="">Select academic year...</option>
                    <?php 
                    foreach ($academicYears as $year): 
                    ?>
                    <option value="<?php echo htmlspecialchars($year, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($year, ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Grade Level -->
            <div class="filter-group">
                <label class="filter-label">Grade Level</label>
                <select class="filter-select" id="filterGradeLevel" onchange="onGradeLevelChange()">
                    <option value="">Select grade level...</option>
                    <?php 
                    foreach ($grades as $grade): 
                    ?>
                    <option value="<?php echo htmlspecialchars($grade, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($grade, ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Section -->
            <div class="filter-group">
                <label class="filter-label">Section</label>
                <select class="filter-select" id="filterSection">
                    <option value="">Select section...</option>
                    <?php 
                    foreach ($sections as $section): 
                    ?>
                    <option value="<?php echo htmlspecialchars($section, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($section, ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <div class="filter-modal-footer">
            <button class="btn-filter" onclick="applyFilter()">
                <i class="fas fa-check"></i> Apply Filter
            </button>
            <button class="btn-close-filter" onclick="resetFilter()">
                <i class="fas fa-redo"></i> Reset
            </button>
            <button class="btn-close-filter" onclick="closeFilterModal()">
                <i class="fas fa-times"></i> Close
            </button>
        </div>
    </div>
</div>

<script>
// Current filter state
let currentFilter = {
    academicYear: '',
    gradeLevel: '',
    section: ''
};

// Open Filter Modal
function openFilterModal() {
    console.log('Opening filter modal');
    const modal = document.getElementById('filterModal');
    if (modal) {
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
        console.log('Modal opened, class added');
    } else {
        console.error('ERROR: filterModal element not found!');
    }
}

// Close Filter Modal
function closeFilterModal() {
    console.log('Closing filter modal');
    const modal = document.getElementById('filterModal');
    if (modal) {
        modal.classList.remove('show');
        document.body.style.overflow = '';
    }
}

// Handle Academic Year Change - dynamically fetch grades for that year
function onAcademicYearChange() {
    const yearSelect = document.getElementById('filterAcademicYear');
    const gradeSelect = document.getElementById('filterGradeLevel');
    const sectionSelect = document.getElementById('filterSection');
    
    const selectedYear = yearSelect.value;
    
    // Reset dependent selects
    gradeSelect.value = '';
    sectionSelect.value = '';
    
    if (selectedYear) {
        // Fetch grades for selected academic year via AJAX
        fetch(`get_filter_data.php?type=grades&academicyear=${encodeURIComponent(selectedYear)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    gradeSelect.innerHTML = '<option value="">Select grade level...</option>';
                    data.grades.forEach(grade => {
                        const option = document.createElement('option');
                        option.value = grade;
                        option.textContent = grade;
                        gradeSelect.appendChild(option);
                    });
                } else {
                    console.error('Error fetching grades:', data.message);
                }
            })
            .catch(error => console.error('Fetch error:', error));
    }
}

// Handle Grade Level Change - dynamically fetch sections for that grade
function onGradeLevelChange() {
    const gradeSelect = document.getElementById('filterGradeLevel');
    const sectionSelect = document.getElementById('filterSection');
    
    const selectedGrade = gradeSelect.value;
    
    // Reset section
    sectionSelect.value = '';
    
    if (selectedGrade) {
        // Fetch sections for selected grade via AJAX
        fetch(`get_filter_data.php?type=sections&grade=${encodeURIComponent(selectedGrade)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    sectionSelect.innerHTML = '<option value="">Select section...</option>';
                    data.sections.forEach(section => {
                        const option = document.createElement('option');
                        option.value = section;
                        option.textContent = section;
                        sectionSelect.appendChild(option);
                    });
                } else {
                    console.error('Error fetching sections:', data.message);
                }
            })
            .catch(error => console.error('Fetch error:', error));
    }
}

// Apply Filter
function applyFilter() {
    alert('Apply Filter button clicked!');
    console.log('=== APPLY FILTER STARTED ===');
    
    const yearInput = document.getElementById('filterAcademicYear');
    const gradeInput = document.getElementById('filterGradeLevel');
    const sectionInput = document.getElementById('filterSection');
    
    if (!yearInput || !gradeInput || !sectionInput) {
        console.error('ERROR: Filter inputs not found!');
        alert('ERROR: Filter inputs not found!');
        return;
    }
    
    const year = yearInput.value;
    const grade = gradeInput.value;
    const section = sectionInput.value;
    
    console.log('Filter selections:', { year, grade, section });
    alert('Filter selections: Year=' + year + ', Grade=' + grade + ', Section=' + section);
    
    const rows = document.querySelectorAll('#studentTableBody tr');
    console.log('Total rows found:', rows.length);
    
    if (rows.length === 0) {
        console.error('ERROR: No student table rows found!');
        alert('ERROR: No student table rows found!');
        return;
    }
    
    let shown = 0;
    let hidden = 0;
    
    rows.forEach((row, idx) => {
        const rowYear = row.getAttribute('data-school-year');
        const rowGrade = row.getAttribute('data-grade');
        const rowSection = row.getAttribute('data-section');
        
        let match = true;
        
        // Only filter if a value is selected
        if (year && rowYear !== year) {
            match = false;
        }
        if (grade && rowGrade !== grade) {
            match = false;
        }
        if (section && rowSection !== section) {
            match = false;
        }
        
        row.style.display = match ? '' : 'none';
        if (match) shown++;
        else hidden++;
    });
    
    console.log(`Results: ${shown} shown, ${hidden} hidden`);
    alert('Filter Results: ' + shown + ' students shown, ' + hidden + ' hidden');
    
    // Update showing text
    const showingText = document.querySelector('.showing-text');
    if (showingText) {
        const total = document.querySelectorAll('#studentTableBody tr').length;
        showingText.innerHTML = `Showing <strong>${shown}</strong> of <strong>${total}</strong> entries`;
        console.log('Updated showing text');
    } else {
        console.warn('WARNING: .showing-text not found');
    }
    
    // Update filter button
    const btn = document.getElementById('openFilterBtn');
    if (btn) {
        const count = [year, grade, section].filter(v => v).length;
        if (count > 0) {
            btn.classList.add('has-filter');
            btn.innerHTML = '<i class="fas fa-filter"></i> Filtered (' + count + ')';
        } else {
            btn.classList.remove('has-filter');
            btn.innerHTML = '<i class="fas fa-filter"></i> Filter';
        }
        console.log('Updated filter button');
    }
    
    console.log('=== APPLY FILTER COMPLETED ===');
    closeFilterModal();
}

// Reset Filter
function resetFilter() {
    document.getElementById('filterAcademicYear').value = '';
    document.getElementById('filterGradeLevel').value = '';
    document.getElementById('filterSection').value = '';
    
    currentFilter = { academicYear: '', gradeLevel: '', section: '' };
    
    // Show all rows
    const rows = document.querySelectorAll('#studentTableBody tr');
    rows.forEach(row => {
        row.style.display = '';
    });
    
    // Update filter button
    const filterBtn = document.getElementById('openFilterBtn');
    if (filterBtn) {
        filterBtn.classList.remove('has-filter');
        filterBtn.innerHTML = '<i class="fas fa-filter"></i> Filter';
    }
    
    // Update showing text
    const showingText = document.querySelector('.showing-text');
    if (showingText) {
        const totalRows = document.querySelectorAll('#studentTableBody tr').length;
        showingText.innerHTML = `Showing <strong>${totalRows}</strong> of <strong>${totalRows}</strong> entries`;
    }
}

// Close modal when clicking overlay
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('filterModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeFilterModal();
            }
        });
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    const modal = document.getElementById('filterModal');
    if (e.key === 'Escape' && modal && modal.classList.contains('show')) {
        closeFilterModal();
    }
});
</script>
