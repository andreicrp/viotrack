<?php
// Quick Fix Script - Direct File Replacement
// Upload to /public_html/ and visit: https://phcm-viotrack.online/fix-now.php?do=fix

if (!isset($_GET['do']) || $_GET['do'] !== 'fix') {
    die("Access denied. Use: ?do=fix");
}

// Try multiple possible paths
$possible_paths = [
    '/home/u396044097/domains/phcm-viotrack.online/public_html/php/addrecord-modal.php',
    '../php/addrecord-modal.php',
    './php/addrecord-modal.php',
    dirname(__FILE__) . '/php/addrecord-modal.php'
];

$fixed_code = '<?php
// Database connection
require_once("connect.php");

try {
    $studentsQuery = $conn->query("
        SELECT id, fname, lname, mname, lrn, grade, section, academicyear, image 
        FROM student 
        ORDER BY lname, fname
    ");
    
    if (!$studentsQuery) {
        throw new Exception("Students query error: " . $conn->error);
    }
    
    $students = [];
    while ($row = $studentsQuery->fetch_assoc()) {
        $students[] = $row;
    }
    
    $violationsQuery = $conn->query("
        SELECT id, title, description, type 
        FROM violation 
        ORDER BY type, title
    ");
    
    if (!$violationsQuery) {
        throw new Exception("Violations query error: " . $conn->error);
    }
    
    $allViolations = [];
    while ($row = $violationsQuery->fetch_assoc()) {
        $allViolations[] = $row;
    }
    
    $minorViolations = array_filter($allViolations, function($v) { return strtolower($v["type"]) == "minor"; });
    $seriousViolations = array_filter($allViolations, function($v) { return strtolower($v["type"]) == "serious"; });
    $majorViolations = array_filter($allViolations, function($v) { return strtolower($v["type"]) == "major"; });
    
} catch(Exception $e) {
    die("Database error: " . $e->getMessage());
}
?>

<link rel="stylesheet" href="css/addrecord-modal.css">

<div class="addrecord-modal-overlay" id="addRecordModal">
    <div class="addrecord-modal">
        <div class="addrecord-modal-header">
            <h2>Add Record</h2>
            <button class="addrecord-modal-close" onclick="closeAddRecordModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form method="POST" id="addRecordForm">
            <div class="addrecord-modal-body">
                <div class="addrecord-form-group">
                    <label class="addrecord-form-label">
                        Student <span class="addrecord-required">*</span>
                    </label>
                    <div class="student-multiselect">
                        <div class="student-select-display" id="studentSelectDisplay" onclick="toggleDropdown(\"student\")">
                            <span class="student-placeholder">Select students...</span>
                        </div>
                        <div class="student-dropdown" id="studentDropdown">
                            <div class="student-dropdown-search">
                                <input type="text" id="studentSearchInput" placeholder="Search students..." onclick="event.stopPropagation()" oninput="searchItems(\"student\", this.value)">
                            </div>
                            <div class="student-options no-scroll" id="studentOptions">
                                <?php foreach ($students as $index => $student): ?>
                                    <div class="student-option" data-index="<?php echo $index; ?>" onclick="toggleSelection(\"student\", <?php echo $index; ?>, event)">
                                        <input type="checkbox" class="student-option-checkbox" name="student_ids[]" value="<?php echo $student["id"]; ?>" id="student-<?php echo $index; ?>" data-index="<?php echo $index; ?>" onclick="toggleSelection(\"student\", <?php echo $index; ?>, event)">
                                        <label for="student-<?php echo $index; ?>" class="student-option-label">
                                            <?php echo htmlspecialchars($student["fname"] . " " . $student["mname"] . " " . $student["lname"]); ?> (<?php echo htmlspecialchars($student["lrn"]); ?>)
                                        </label>
                                        <i class="fas fa-check student-check-icon"></i>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="student-dropdown-footer">
                                <span class="selected-count" id="studentSelectedCount">Nothing selected</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="addrecord-form-group">
                    <label class="addrecord-form-label" style="color: #10b981; font-weight: 600;">Minor Offenses</label>
                    <div class="student-multiselect">
                        <div class="student-select-display" id="minorSelectDisplay" onclick="toggleDropdown(\"minor\")">
                            <span class="student-placeholder">Select minor offenses...</span>
                        </div>
                        <div class="student-dropdown" id="minorDropdown">
                            <div class="student-dropdown-search">
                                <input type="text" id="minorSearchInput" placeholder="Search minor offenses..." onclick="event.stopPropagation()" oninput="searchItems(\"minor\", this.value)">
                            </div>
                            <div class="student-options no-scroll" id="minorOptions">
                                <?php foreach ($minorViolations as $index => $violation): ?>
                                    <div class="student-option" data-index="<?php echo $index; ?>" onclick="toggleSelection(\"minor\", <?php echo $index; ?>, event)">
                                        <input type="checkbox" class="student-option-checkbox" name="minor_offenses[]" value="<?php echo $violation["id"]; ?>" id="minor-<?php echo $index; ?>" data-index="<?php echo $index; ?>" onclick="toggleSelection(\"minor\", <?php echo $index; ?>, event)">
                                        <label for="minor-<?php echo $index; ?>" class="student-option-label"><?php echo htmlspecialchars($violation["title"]); ?></label>
                                        <i class="fas fa-check student-check-icon"></i>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="student-dropdown-footer">
                                <span class="selected-count" id="minorSelectedCount">Nothing selected</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="addrecord-form-group">
                    <label class="addrecord-form-label" style="color: #f59e0b; font-weight: 600;">Serious Offenses</label>
                    <div class="student-multiselect">
                        <div class="student-select-display" id="seriousSelectDisplay" onclick="toggleDropdown(\"serious\")">
                            <span class="student-placeholder">Select serious offenses...</span>
                        </div>
                        <div class="student-dropdown" id="seriousDropdown">
                            <div class="student-dropdown-search">
                                <input type="text" id="seriousSearchInput" placeholder="Search serious offenses..." onclick="event.stopPropagation()" oninput="searchItems(\"serious\", this.value)">
                            </div>
                            <div class="student-options no-scroll" id="seriousOptions">
                                <?php foreach ($seriousViolations as $index => $violation): ?>
                                    <div class="student-option" data-index="<?php echo $index; ?>" onclick="toggleSelection(\"serious\", <?php echo $index; ?>, event)">
                                        <input type="checkbox" class="student-option-checkbox" name="serious_offenses[]" value="<?php echo $violation["id"]; ?>" id="serious-<?php echo $index; ?>" data-index="<?php echo $index; ?>" onclick="toggleSelection(\"serious\", <?php echo $index; ?>, event)">
                                        <label for="serious-<?php echo $index; ?>" class="student-option-label"><?php echo htmlspecialchars($violation["title"]); ?></label>
                                        <i class="fas fa-check student-check-icon"></i>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="student-dropdown-footer">
                                <span class="selected-count" id="seriousSelectedCount">Nothing selected</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="addrecord-form-group">
                    <label class="addrecord-form-label" style="color: #ef4444; font-weight: 600;">Major Offenses</label>
                    <div class="student-multiselect">
                        <div class="student-select-display" id="majorSelectDisplay" onclick="toggleDropdown(\"major\")">
                            <span class="student-placeholder">Select major offenses...</span>
                        </div>
                        <div class="student-dropdown" id="majorDropdown">
                            <div class="student-dropdown-search">
                                <input type="text" id="majorSearchInput" placeholder="Search major offenses..." onclick="event.stopPropagation()" oninput="searchItems(\"major\", this.value)">
                            </div>
                            <div class="student-options" id="majorOptions">
                                <?php foreach ($majorViolations as $index => $violation): ?>
                                    <div class="student-option" data-index="<?php echo $index; ?>" onclick="toggleSelection(\"major\", <?php echo $index; ?>, event)">
                                        <input type="checkbox" class="student-option-checkbox" name="major_offenses[]" value="<?php echo $violation["id"]; ?>" id="major-<?php echo $index; ?>" data-index="<?php echo $index; ?>" onclick="toggleSelection(\"major\", <?php echo $index; ?>, event)">
                                        <label for="major-<?php echo $index; ?>" class="student-option-label"><?php echo htmlspecialchars($violation["title"]); ?></label>
                                        <i class="fas fa-check student-check-icon"></i>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="student-dropdown-footer">
                                <span class="selected-count" id="majorSelectedCount">Nothing selected</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <input type="hidden" id="recordLatitude" name="record_latitude" value="">
            <input type="hidden" id="recordLongitude" name="record_longitude" value="">
            
            <div class="addrecord-form-group" id="locationStatusGroup" style="display: none;">
                <div style="background: #f0fdf4; border: 1px solid #86efac; border-radius: 6px; padding: 12px; text-align: center;">
                    <i class="fas fa-map-pin" style="color: #16a34a; margin-right: 8px;"></i>
                    <span id="locationStatusText" style="color: #16a34a; font-size: 13px; font-weight: 500;">Location captured</span>
                </div>
            </div>
            
            <div class="addrecord-modal-footer">
                <button type="submit" name="submit_record" class="addrecord-modal-btn btn-add-record" id="addRecordBtn" disabled>Add</button>
                <button type="button" class="addrecord-modal-btn btn-cancel-add-record" onclick="closeAddRecordModal()">Close</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleDropdown(type) {
    const dropdown = document.getElementById(type + "Dropdown");
    const display = document.getElementById(type + "SelectDisplay");
    ["student", "minor", "serious", "major"].forEach(t => {
        if (t !== type) {
            const d = document.getElementById(t + "Dropdown");
            const ds = document.getElementById(t + "SelectDisplay");
            if (d) d.classList.remove("show");
            if (ds) ds.classList.remove("active");
        }
    });
    if (dropdown.classList.contains("show")) {
        dropdown.classList.remove("show");
        display.classList.remove("active");
    } else {
        dropdown.classList.add("show");
        display.classList.add("active");
    }
}

document.addEventListener("click", function(e) {
    ["student", "minor", "serious", "major"].forEach(type => {
        const dropdown = document.getElementById(type + "Dropdown");
        const display = document.getElementById(type + "SelectDisplay");
        if (dropdown && display && !dropdown.contains(e.target) && !display.contains(e.target)) {
            dropdown.classList.remove("show");
            display.classList.remove("active");
        }
    });
});

function toggleSelection(type, index, event) {
    event.stopPropagation();
    const checkbox = document.getElementById(type + "-" + index);
    const option = document.querySelector("#" + type + "Options .student-option[data-index=\"" + index + "\"]");
    checkbox.checked = !checkbox.checked;
    if (checkbox.checked) {
        option.classList.add("selected");
    } else {
        option.classList.remove("selected");
    }
    updateSelectedDisplay(type);
    checkFormValidity();
}

function updateSelectedDisplay(type) {
    const display = document.getElementById(type + "SelectDisplay");
    const checkboxes = document.querySelectorAll("#" + type + "Options .student-option-checkbox:checked");
    const count = checkboxes.length;
    if (count === 0) {
        document.getElementById(type + "SelectedCount").textContent = "Nothing selected";
        const placeholderText = type === "student" ? "Select students..." : "Select " + type + " offenses...";
        display.innerHTML = "<span class=\"student-placeholder\">" + placeholderText + "</span>";
    } else {
        document.getElementById(type + "SelectedCount").textContent = count + " selected";
        const names = Array.from(checkboxes).map(cb => cb.parentElement.querySelector("label").textContent.trim()).join(", ");
        display.innerHTML = "<span class=\"student-selected-text\">" + names + "</span>";
    }
}

function searchItems(type, searchTerm) {
    searchTerm = searchTerm.toLowerCase();
    const options = document.querySelectorAll("#" + type + "Options .student-option");
    options.forEach(option => {
        const text = option.textContent.toLowerCase();
        option.style.display = text.includes(searchTerm) ? "flex" : "none";
    });
}

function checkFormValidity() {
    const hasStudents = document.querySelectorAll("#studentOptions .student-option-checkbox:checked").length > 0;
    const minorSelected = document.querySelectorAll("#minorOptions .student-option-checkbox:checked").length > 0;
    const seriousSelected = document.querySelectorAll("#seriousOptions .student-option-checkbox:checked").length > 0;
    const majorSelected = document.querySelectorAll("#majorOptions .student-option-checkbox:checked").length > 0;
    const hasViolation = minorSelected || seriousSelected || majorSelected;
    const isValid = hasStudents && hasViolation;
    const addBtn = document.getElementById("addRecordBtn");
    if (addBtn) addBtn.disabled = !isValid;
}

function getCookie(name) {
    const nameEQ = name + "=";
    const cookies = document.cookie.split(";");
    for (let i = 0; i < cookies.length; i++) {
        let cookie = cookies[i].trim();
        if (cookie.indexOf(nameEQ) === 0) {
            return decodeURIComponent(cookie.substring(nameEQ.length));
        }
    }
    return "";
}

function openAddRecordModal() {
    const modal = document.getElementById("addRecordModal");
    if (modal) {
        modal.classList.add("show");
        document.body.style.overflow = "hidden";
        const cookieLat = getCookie("user_lat");
        const cookieLng = getCookie("user_lng");
        const cookieAccuracy = getCookie("user_accuracy");
        if (cookieLat && cookieLng) {
            console.log("Using location from QR scan cookies:", cookieLat, cookieLng);
            document.getElementById("recordLatitude").value = cookieLat;
            document.getElementById("recordLongitude").value = cookieLng;
            const accuracyText = cookieAccuracy && cookieAccuracy !== "0" ? " (Accuracy: " + cookieAccuracy + "m)" : " (Default Location)";
            alert("Location Loaded from QR scan" + accuracyText);
        } else {
            if (confirm("Location Permission Required - Click OK to allow location access.")) {
                captureLocation();
            } else {
                alert("Location permission was denied. Using default location.");
                document.getElementById("recordLatitude").value = "14.6124466";
                document.getElementById("recordLongitude").value = "120.9879835";
            }
        }
    }
}

function closeAddRecordModal() {
    const modal = document.getElementById("addRecordModal");
    if (modal) {
        modal.classList.remove("show");
        document.body.style.overflow = "";
        resetAddRecordForm();
    }
}

function resetAddRecordForm() {
    const form = document.getElementById("addRecordForm");
    if (form) form.reset();
    ["student", "minor", "serious", "major"].forEach(type => {
        document.querySelectorAll("#" + type + "Options .student-option-checkbox").forEach(cb => cb.checked = false);
        document.querySelectorAll("#" + type + "Options .student-option").forEach(opt => opt.classList.remove("selected"));
        updateSelectedDisplay(type);
    });
    checkFormValidity();
}

document.addEventListener("keydown", function(e) {
    if (e.key === "Escape" && document.getElementById("addRecordModal") && document.getElementById("addRecordModal").classList.contains("show")) {
        closeAddRecordModal();
    }
});

document.addEventListener("DOMContentLoaded", function() {
    ["student", "minor", "serious", "major"].forEach(type => {
        updateSelectedDisplay(type);
    });
    
    const addRecordForm = document.getElementById("addRecordForm");
    if (addRecordForm) {
        addRecordForm.addEventListener("submit", function(e) {
            e.preventDefault();
            
            // CRITICAL FIX: CHECK OFFLINE FIRST
            if (!navigator.onLine) {
                const studentCheckboxes = document.querySelectorAll("#studentOptions .student-option-checkbox:checked");
                const violationCheckboxes = document.querySelectorAll("[id$=\"Options\"] .student-option-checkbox:checked:not(#studentOptions .student-option-checkbox)");
                
                if (studentCheckboxes.length === 0 || violationCheckboxes.length === 0) {
                    alert("Please select at least one student and violation");
                    return;
                }
                
                const lat = document.getElementById("recordLatitude") ? document.getElementById("recordLatitude").value : "0";
                const lng = document.getElementById("recordLongitude") ? document.getElementById("recordLongitude").value : "0";
                
                const records = [];
                studentCheckboxes.forEach(studentCheckbox => {
                    const studentId = studentCheckbox.id.split("-")[1];
                    violationCheckboxes.forEach(violationCheckbox => {
                        const violationId = violationCheckbox.id.split("-")[1];
                        records.push({
                            sid: studentId,
                            vid: violationId,
                            lat: lat,
                            lng: lng,
                            date: new Date().toISOString().split("T")[0],
                            status: "Pending",
                            type: "Violation"
                        });
                    });
                });
                
                if (typeof offlineManager !== "undefined") {
                    records.forEach(record => {
                        offlineManager.saveOffline(record);
                    });
                }
                
                console.log("Saved " + records.length + " violation(s) offline");
                alert("✓ " + records.length + " violation(s) saved offline. Will sync when connected.");
                
                closeAddRecordModal();
                setTimeout(() => location.reload(), 1500);
                return;
            }
            
            // ONLINE MODE
            const lat = document.getElementById("recordLatitude").value;
            const lng = document.getElementById("recordLongitude").value;
            
            if (lat && lng) {
                this.submit();
            } else {
                captureLocationAndSubmit(this);
            }
        });
    }
    
    setTimeout(function() {
        captureLocation();
    }, 500);
});

function captureLocationAndStore() {
    if ("geolocation" in navigator) {
        navigator.geolocation.getCurrentPosition(function(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            const accuracy = position.coords.accuracy;
            const expiryTime = new Date();
            expiryTime.setTime(expiryTime.getTime() + (30 * 60 * 1000));
            document.cookie = "user_lat=" + lat + "; expires=" + expiryTime.toUTCString() + "; path=/";
            document.cookie = "user_lng=" + lng + "; expires=" + expiryTime.toUTCString() + "; path=/";
            document.cookie = "user_accuracy=" + Math.round(accuracy) + "; expires=" + expiryTime.toUTCString() + "; path=/";
            document.getElementById("recordLatitude").value = lat;
            document.getElementById("recordLongitude").value = lng;
            const statusGroup = document.getElementById("locationStatusGroup");
            const statusText = document.getElementById("locationStatusText");
            if (statusGroup && statusText) {
                statusGroup.style.display = "block";
                statusText.innerHTML = "<i class=\"fas fa-map-pin\" style=\"color: #16a34a; margin-right: 8px;\"></i> Location captured (Accuracy: " + Math.round(accuracy) + "m)";
            }
            console.log("Location captured:", lat, lng, "Accuracy:", accuracy);
        }, function(error) {
            console.warn("Geolocation error:", error.message);
            const defaultLat = "14.6124466";
            const defaultLng = "120.9879835";
            const expiryTime = new Date();
            expiryTime.setTime(expiryTime.getTime() + (30 * 60 * 1000));
            document.cookie = "user_lat=" + defaultLat + "; expires=" + expiryTime.toUTCString() + "; path=/";
            document.cookie = "user_lng=" + defaultLng + "; expires=" + expiryTime.toUTCString() + "; path=/";
            document.cookie = "user_accuracy=0; expires=" + expiryTime.toUTCString() + "; path=/";
            document.getElementById("recordLatitude").value = defaultLat;
            document.getElementById("recordLongitude").value = defaultLng;
            const statusGroup = document.getElementById("locationStatusGroup");
            if (statusGroup) {
                statusGroup.style.display = "block";
                statusGroup.querySelector("div").style.background = "#fef2f2";
                statusGroup.querySelector("div").style.borderColor = "#fca5a5";
                const statusText = document.getElementById("locationStatusText");
                if (statusText) {
                    statusText.innerHTML = "<i class=\"fas fa-map-pin\" style=\"color: #dc2626; margin-right: 8px;\"></i> Using default location";
                    statusText.style.color = "#dc2626";
                }
            }
            console.log("Using default location (Manila)");
        }, {enableHighAccuracy: true, timeout: 5000, maximumAge: 0});
    } else {
        console.log("Geolocation not supported - using default location");
        const defaultLat = "14.6124466";
        const defaultLng = "120.9879835";
        const expiryTime = new Date();
        expiryTime.setTime(expiryTime.getTime() + (30 * 60 * 1000));
        document.cookie = "user_lat=" + defaultLat + "; expires=" + expiryTime.toUTCString() + "; path=/";
        document.cookie = "user_lng=" + defaultLng + "; expires=" + expiryTime.toUTCString() + "; path=/";
        document.cookie = "user_accuracy=0; expires=" + expiryTime.toUTCString() + "; path=/";
        document.getElementById("recordLatitude").value = defaultLat;
        document.getElementById("recordLongitude").value = defaultLng;
    }
}

function captureLocation() {
    captureLocationAndStore();
}

function captureLocationAndSubmit(form) {
    if ("geolocation" in navigator) {
        navigator.geolocation.getCurrentPosition(function(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            document.getElementById("recordLatitude").value = lat;
            document.getElementById("recordLongitude").value = lng;
            form.submit();
        }, function(error) {
            console.warn("Geolocation error on submit:", error.message);
            form.submit();
        }, {enableHighAccuracy: true, timeout: 5000, maximumAge: 0});
    } else {
        form.submit();
    }
}
</script>';

echo "<h2>File Replacement Utility</h2>";
echo "<p>Checking file paths...</p>";

$found = false;
foreach ($possible_paths as $path) {
    echo "<p>Trying: $path</p>";
    if (file_exists($path)) {
        echo "<p style='color: green;'>✓ Found at: $path</p>";
        $backup = $path . '.backup-' . date('YmdHis');
        copy($path, $backup);
        echo "<p>✓ Backup created: $backup</p>";
        
        if (file_put_contents($path, $fixed_code) !== false) {
            echo "<p style='color: green; font-size: 18px;'><strong>✅ SUCCESS! File replaced!</strong></p>";
            echo "<p>File size: " . filesize($path) . " bytes</p>";
            echo "<p>Modified: " . date('Y-m-d H:i:s', filemtime($path)) . "</p>";
            echo "<p><a href='https://phcm-viotrack.online/' target='_blank'>Go to VIOTRACK Dashboard</a></p>";
            $found = true;
            break;
        } else {
            echo "<p style='color: red;'>✗ Could not write to file (permissions?)</p>";
        }
    }
}

if (!$found) {
    echo "<p style='color: red;'><strong>✗ Could not find the file in any expected location</strong></p>";
    echo "<p>Debug info: Current dir = " . getcwd() . "</p>";
    echo "<pre>";
    print_r(array_filter(array_map(function($p) { return file_exists($p) ? "EXISTS: $p" : "NOT FOUND: $p"; }, $possible_paths)));
    echo "</pre>";
}
?>
