<?php
// violation.php
include 'auth_check.php';
requireAdminOrTeacher();

include 'header.php';
include 'sidebar.php';

// Pagination settings
$violationsPerPage = 25;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $violationsPerPage;

// Fetch violations from the database with pagination and order by id in descending order
$query = "SELECT * FROM violation ORDER BY id DESC LIMIT $offset, $violationsPerPage";
$result = mysqli_query($conn, $query);
$violations = [];
while ($row = mysqli_fetch_assoc($result)) {
    $violations[] = $row;
}

// Fetch total number of violations for pagination
$totalViolationsQuery = "SELECT COUNT(*) AS total FROM violation";
$totalViolationsResult = mysqli_query($conn, $totalViolationsQuery);
$totalViolationsRow = mysqli_fetch_assoc($totalViolationsResult);
$totalViolations = $totalViolationsRow['total'];

// Calculate stats
$stats = [
    'total' => $totalViolations,
    'major' => 0,
    'serious' => 0,
    'minor' => 0
];

// Fetch all violations for stats calculation
$allViolationsQuery = "SELECT * FROM violation";
$allViolationsResult = mysqli_query($conn, $allViolationsQuery);
while ($row = mysqli_fetch_assoc($allViolationsResult)) {
    if ($row['type'] === 'Major') {
        $stats['major']++;
    } elseif ($row['type'] === 'Serious') {
        $stats['serious']++;
    } elseif ($row['type'] === 'Minor') {
        $stats['minor']++;
    }
}

// Calculate total pages
$totalPages = ceil($totalViolations / $violationsPerPage);
?>
<!-- Include CSS Files -->
<link rel="stylesheet" href="css/violation.css">
<link rel="stylesheet" href="css/violation-modal.css">
<link rel="stylesheet" href="css/importviolation-modal.css">
<main class="main-content">
    <!-- Violation List Card -->
    <div class="violation-card">
        <div class="violation-card-header">
            <div class="violation-title-section">
                <h3>Violation List</h3>
                <p class="subtitle"><?php echo $stats['total']; ?> violations configured</p>
            </div>
            <div class="violation-header-actions">
                <button class="btn-delete-selected" id="deleteSelectedBtn" style="display: none;">
                    <i class="fas fa-trash"></i> Delete Selected (<span id="selectedCount">0</span>)
                </button>

                </button>
                <button class="btn-import" onclick="openImportViolationModal()">
                    <i class="fas fa-file-import"></i> Import
                </button>
                <button class="btn-export" onclick="exportViolations()">
                    <i class="fas fa-file-export"></i> Export
                </button>
                <button class="btn-add-violation" onclick="openAddViolationModal()">
                    <i class="fas fa-plus"></i> Add Violation
                </button>
            </div>
        </div>
        <!-- Stats Cards -->
        <div class="violation-stats">
            <div class="stat-card-violation total">
                <div class="stat-icon">
                    <i class="fas fa-list"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-value"><?php echo $stats['total']; ?></span>
                    <span class="stat-label">Total</span>
                </div>
            </div>
            <div class="stat-card-violation major">
                <div class="stat-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-value"><?php echo $stats['major']; ?></span>
                    <span class="stat-label">Major</span>
                </div>
            </div>
            <div class="stat-card-violation serious">
                <div class="stat-icon">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-value"><?php echo $stats['serious']; ?></span>
                    <span class="stat-label">Serious</span>
                </div>
            </div>
            <div class="stat-card-violation minor">
                <div class="stat-icon">
                    <i class="fas fa-info-circle"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-value"><?php echo $stats['minor']; ?></span>
                    <span class="stat-label">Minor</span>
                </div>
            </div>
        </div>
        <!-- Search Box -->
        <div class="search-box-container">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="violationSearch" placeholder="Search...">
            </div>
        </div>
        <!-- Violation Table -->
        <div class="table-container">
            <table class="violation-table" id="violationTable">
                <thead>
                    <tr>
                        <th class="checkbox-col">
                            <input type="checkbox" id="selectAll">
                        </th>
                        <th>VIOLATION</th>
                        <th>TYPE</th>
                        <th>ACTION</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($violations as $violation): ?>
                    <tr data-violation-id="<?php echo $violation['id']; ?>" data-type="<?php echo $violation['type']; ?>">
                        <td class="checkbox-col">
                            <input type="checkbox" class="violation-checkbox" value="<?php echo $violation['id']; ?>">
                        </td>
                        <td>
                            <div class="violation-content">
                                <span class="violation-title"><?php echo $violation['title']; ?></span>
                                <span class="violation-desc"><?php echo $violation['description']; ?></span>
                            </div>
                        </td>
                        <td>
                            <span class="violation-type <?php echo strtolower($violation['type']); ?>">
                                <i class="fas fa-circle"></i> <?php echo $violation['type']; ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-action btn-edit" onclick="openEditViolationModal(<?php echo $violation['id']; ?>)">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="btn-action btn-delete" onclick="deleteViolation(<?php echo $violation['id']; ?>)">
                                    <i class="fas fa-trash"></i> Remove
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <!-- Pagination -->
        <div class="table-footer">
            <span class="showing-text">
                Showing <?php echo count($violations); ?> of <?php echo $stats['total']; ?>
            </span>
            <div class="pagination">
                <button class="page-btn" onclick="goToPage(1)" <?php if ($page <= 1) echo 'disabled'; ?>>
                    «
                </button>
                <button class="page-btn" onclick="goToPage(<?php echo max(1, $page - 1); ?>)" <?php if ($page <= 1) echo 'disabled'; ?>>
                    ‹
                </button>
                <?php
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);
                for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <button class="page-btn <?php if ($i === $page) echo 'active'; ?>" onclick="goToPage(<?php echo $i; ?>)">
                        <?php echo $i; ?>
                    </button>
                <?php endfor; ?>
                <button class="page-btn" onclick="goToPage(<?php echo min($totalPages, $page + 1); ?>)" <?php if ($page >= $totalPages) echo 'disabled'; ?>>
                    ›
                </button>
                <button class="page-btn" onclick="goToPage(<?php echo $totalPages; ?>)" <?php if ($page >= $totalPages) echo 'disabled'; ?>>
                    »
                </button>
            </div>
        </div>
    </div>
</main>
<!-- Include Modals -->
<?php include 'php/modals/violation-modal.php'; ?>
<?php include 'php/modals/importviolation-modal.php'; ?>

<!-- Violation Details Modal -->
<div class="violation-details-modal-overlay" id="violationDetailsModal">
    <div class="violation-details-modal">
        <div class="violation-details-modal-header">
            <h2 id="detailsModalTitle">Violation Details</h2>
            <button class="violation-details-modal-close" onclick="closeViolationDetails()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="violation-details-modal-body">
            <div class="detail-row">
                <span class="detail-label">Title:</span>
                <span class="detail-value" id="detailsTitle">-</span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Description:</span>
                <span class="detail-value" id="detailsDescription">-</span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Type:</span>
                <span class="detail-value" id="detailsType">-</span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Type Badge:</span>
                <span class="detail-value" id="detailsTypeBadge">-</span>
            </div>
        </div>
        
        <div class="violation-details-modal-footer">
            <button class="violation-details-modal-btn btn-edit-details" onclick="editCurrentViolation()">
                <i class="fas fa-edit"></i> Edit
            </button>
            <button class="violation-details-modal-btn btn-close-details" onclick="closeViolationDetails()">
                Close
            </button>
        </div>
    </div>
</div>

<script>
// Store current violation for editing
let currentViolationId = null;

// Show Violation Details
function showViolationDetails(violationId, title, description, type) {
    currentViolationId = violationId;
    
    document.getElementById('detailsTitle').textContent = title;
    document.getElementById('detailsDescription').textContent = description;
    document.getElementById('detailsType').textContent = type;
    
    // Create type badge with styling
    const badgeClass = type.toLowerCase();
    const badge = `<span class="violation-type ${badgeClass}"><i class="fas fa-circle"></i> ${type}</span>`;
    document.getElementById('detailsTypeBadge').innerHTML = badge;
    
    document.getElementById('violationDetailsModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

// Close Violation Details Modal
function closeViolationDetails() {
    document.getElementById('violationDetailsModal').classList.remove('show');
    document.body.style.overflow = '';
    currentViolationId = null;
}

// Edit Current Violation
function editCurrentViolation() {
    if (currentViolationId) {
        closeViolationDetails();
        openEditViolationModal(currentViolationId);
    }
}

// Close modal when clicking overlay
document.addEventListener('DOMContentLoaded', function() {
    const violationDetailsModal = document.getElementById('violationDetailsModal');
    if (violationDetailsModal) {
        violationDetailsModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeViolationDetails();
            }
        });
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && document.getElementById('violationDetailsModal')?.classList.contains('show')) {
        closeViolationDetails();
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const violationCheckboxes = document.querySelectorAll('.violation-checkbox');
    const searchInput = document.getElementById('violationSearch');
    const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');
    const selectedCountSpan = document.getElementById('selectedCount');
    // Update delete button visibility and count
    function updateDeleteButton() {
        const checkedBoxes = document.querySelectorAll('.violation-checkbox:checked');
        const count = checkedBoxes.length;
        selectedCountSpan.textContent = count;
        deleteSelectedBtn.style.display = count > 0 ? 'inline-flex' : 'none';
    }
    // Select All functionality
    selectAllCheckbox.addEventListener('change', function() {
        violationCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateDeleteButton();
    });
    // Individual checkbox change
    violationCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateSelectAll();
            updateDeleteButton();
        });
    });
    // Delete Selected button click
    deleteSelectedBtn.addEventListener('click', function() {
        const checkedBoxes = document.querySelectorAll('.violation-checkbox:checked');
        const count = checkedBoxes.length;
        if (count === 0) {
            alert('Please select at least one violation to delete.');
            return;
        }
        
        if (confirm(`Are you sure you want to delete ${count} selected violation(s)?`)) {
            const violationIds = [];
            checkedBoxes.forEach(checkbox => {
                violationIds.push(parseInt(checkbox.value));
            });
            
            // Send delete request to server
            fetch('php/delete_violation.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ ids: violationIds })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success && data.data.deleted > 0) {
                    alert(`Successfully deleted ${data.data.deleted} violation(s).`);
                    // Reload page to refresh stats and table
                    window.location.reload();
                } else if (data.success && data.data.deleted === 0) {
                    alert('No violations were deleted. Some violations may be in use.');
                    window.location.reload();
                } else {
                    alert('Error deleting violations: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error deleting violations: ' + error.message);
            });
        }
    });
    // Search functionality
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#violationTable tbody tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
            updateShowingText();
        });
    }
});
// Update Select All checkbox state
function updateSelectAll() {
    const checkboxes = document.querySelectorAll('.violation-checkbox');
    const checkedBoxes = document.querySelectorAll('.violation-checkbox:checked');
    const selectAllCheckbox = document.getElementById('selectAll');
    if (selectAllCheckbox) {
        selectAllCheckbox.checked = checkedBoxes.length === checkboxes.length;
        selectAllCheckbox.indeterminate = checkedBoxes.length > 0 && checkedBoxes.length < checkboxes.length;
    }
}
// Update showing text
function updateShowingText() {
    const visibleRows = document.querySelectorAll('#violationTable tbody tr:not([style*="display: none"])').length;
    const showingText = document.querySelector('.showing-text');
    if (showingText) {
        showingText.textContent = `Showing ${visibleRows} of <?php echo $stats['total']; ?>`;
    }
}
// Export Violations - All violations
function exportViolations() {
    // Fetch all violations from database
    fetch('php/export-violations.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (!data.success || !data.violations || data.violations.length === 0) {
                alert('No violations to export.');
                return;
            }
            
            const violations = data.violations;
            const rows = [["Violation Title", "Description", "Type"]];
            
            // Add all violations to rows
            violations.forEach(violation => {
                rows.push([violation.title || '', violation.description || '', violation.type || '']);
            });
            
            // Convert to CSV
            let csvContent = "data:text/csv;charset=utf-8,"
                + rows.map(e => e.map(cell => `"${cell}"`).join(",")).join("\n");
            
            // Create download link
            const link = document.createElement("a");
            link.setAttribute("href", encodeURI(csvContent));
            link.setAttribute("download", `violations_export_${new Date().toISOString().slice(0,10)}.csv`);
            document.body.appendChild(link);
            link.click();
            link.remove();
            alert(`Violations exported successfully! (${violations.length} records)`);
        })
        .catch(error => {
            console.error('Error exporting violations:', error);
            alert('Error exporting violations: ' + error.message);
        });
}


// Function to navigate to a specific page
function goToPage(page) {
    window.location.href = `violation.php?page=${page}`;
}
</script>
<?php include 'footer.php'; ?>
