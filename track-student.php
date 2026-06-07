  <?php
  require_once('connect.php');
  date_default_timezone_set('Asia/Manila');
  $DATE = date("Y-m-d H:i:s");
  session_start();

  // Check authentication - use user_id from new session
  if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
  }

  // Get student ID from URL parameter
  $student_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

  if ($student_id <= 0) {
    header('Location: students.php');
    exit;
  }

  // Fetch student information
  $stmt = $conn->prepare("SELECT id, fname, mname, lname, lrn, grade, section, academicyear, image FROM student WHERE id=?");
  $stmt->bind_param("i", $student_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $student = $result->fetch_array(MYSQLI_ASSOC);
  $stmt->close();

  if (!$student) {
    header('Location: students.php');
    exit;
  }

  // Get current user info for header
  $stmt = $conn->prepare("SELECT fname, lname, position FROM teacher WHERE id=?");
  $stmt->bind_param("i", $_SESSION['user_id']);
  $stmt->execute();
  $user_result = $stmt->get_result();
  $profile = $user_result->fetch_array(MYSQLI_ASSOC);
  $stmt->close();
  ?>
  <!DOCTYPE html>
  <html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?=$profile['position']?> - Track Student Location</title>
    <link rel="icon" type="image/x-icon" href="images/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Leaflet CSS (FREE - No API Key) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" />
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/footer.css">
    <style>
      /* Main content wrapper */
      .main-panel {
        width: calc(100% - 250px);
        margin-left: 250px;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
      }
      
      .content-wrapper {
        flex: 1;
        padding: 30px;
        background-color: #f5f5f5;
      }
      
      #map {
        border: 1px solid #e0e0e0;
        height: 600px;
        width: 100%;
        border-radius: 15px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
      }
      
      .student-info-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 20px;
        margin-bottom: 20px;
        border-radius: 10px;
      }
      
      .student-info-header h3 {
        margin: 0;
        font-size: 24px;
        font-weight: 600;
      }
      
      .student-info-header p {
        margin: 5px 0 0 0;
        font-size: 14px;
        opacity: 0.9;
      }
      
      .location-info {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        border-left: 4px solid #667eea;
      }
      
      .location-info h5 {
        margin-top: 0;
        color: #333;
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 1px;
      }
      
      .location-item {
        display: flex;
        align-items: center;
        margin: 10px 0;
        padding: 8px 0;
      }
      
      .location-item i {
        color: #667eea;
        width: 20px;
        text-align: center;
        margin-right: 10px;
      }
      
      .location-item span {
        font-size: 14px;
        color: #555;
      }
      
      .btn-back {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        background: #667eea;
        color: white;
        text-decoration: none;
        border-radius: 8px;
        font-size: 14px;
        margin-bottom: 20px;
        transition: background 0.3s ease;
      }
      
      .btn-back:hover {
        background: #764ba2;
        color: white;
        text-decoration: none;
      }
      
      .card {
        background: white;
        border: none;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
      }
      
      .card-rounded {
        border-radius: 15px;
      }
      
      .card-body {
        padding: 20px;
      }
      
      .violation-marker-list {
        margin-top: 20px;
      }
      
      .violation-item {
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 12px;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 12px;
      }
      
      .violation-item-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        overflow: hidden;
        flex-shrink: 0;
      }
      
      .violation-item-icon img {
        width: 100%;
        height: 100%;
        object-fit: cover;
      }
      
      .violation-item-content {
        flex: 1;
      }
      
      .violation-item-title {
        font-weight: 600;
        color: #333;
        margin: 0;
        font-size: 14px;
      }
      
      .violation-item-date {
        font-size: 12px;
        color: #999;
        margin: 4px 0 0 0;
      }
      
      /* Responsive adjustments */
      @media (max-width: 768px) {
        .main-panel {
          width: 100%;
          margin-left: 0;
        }
        
        .content-wrapper {
          padding: 15px;
        }
      }
    </style>
  </head>
  <body>
    <div class="container-scroller"> 
      <!-- partial:partials/_navbar.html -->
      <?php include('header.php');?>
      <!-- partial -->
      <div class="container-fluid page-body-wrapper">
        <!-- partial:partials/_sidebar.html -->
        <?php include('sidebar.php');?>
        <!-- partial -->
        <div class="main-panel">
          <div class="content-wrapper">
            <!-- Back Button -->
            <a href="adminstudentviolation.php?id=<?php echo $student_id; ?>" class="btn-back">
              <i class="fas fa-arrow-left"></i> Back to Student Profile
            </a>
            
            <!-- Student Information Header -->
            <div class="student-info-header">
              <h3><i class="fas fa-map-marker-alt"></i> Track Student Location</h3>
              <p><?php echo htmlspecialchars($student['fname'] . ' ' . $student['mname'] . ' ' . $student['lname']); ?> (<?php echo htmlspecialchars($student['lrn']); ?>)</p>
            </div>
            
            <!-- Location Information -->
            <div class="location-info">
              <h5><i class="fas fa-info-circle"></i> Information</h5>
              <div class="location-item">
                <i class="fas fa-id-card"></i>
                <span><strong>Student ID:</strong> <?php echo htmlspecialchars($student['lrn']); ?></span>
              </div>
              <div class="location-item">
                <i class="fas fa-graduation-cap"></i>
                <span><strong>Grade & Section:</strong> <?php echo htmlspecialchars($student['grade'] . ' - ' . $student['section']); ?></span>
              </div>
              <div class="location-item">
                <i class="fas fa-calendar"></i>
                <span><strong>Academic Year:</strong> <?php echo htmlspecialchars($student['academicyear']); ?></span>
              </div>
            </div>
            
            <!-- Map Card -->
            <div class="card card-rounded">
              <div class="card-body">
                <div id="map"></div>
              </div>
            </div>
            
            <!-- Violation Markers List -->
            <div class="card card-rounded" style="margin-top: 20px;">
              <div class="card-body">
                <h5><i class="fas fa-list"></i> Tracked Violations</h5>
                <div class="violation-marker-list" id="violationList">
                  <!-- Populated by JavaScript -->
                </div>
              </div>
            </div>
          </div>

          <?php
          // Fetch all violation locations for this student
          $stmt = $conn->prepare("SELECT r.lat, r.lng, v.title, r.date, s.image 
                                  FROM record r 
                                  LEFT JOIN violation v ON r.vid=v.id 
                                  LEFT JOIN student s ON r.sid=s.id 
                                  WHERE r.lat IS NOT NULL AND r.lat != '' AND r.lng IS NOT NULL AND r.lng != '' AND r.sid=? 
                                  ORDER BY r.date DESC");
          $stmt->bind_param("i", $student_id);
          $stmt->execute();
          $res = $stmt->get_result();
          
          $positions = array();
          $count = 0;
          
          while($row = $res->fetch_assoc()){
              $positions[] = array(
                  'lat' =>   $row['lat'],
                  'lng' =>   $row['lng'],
                  'title' => $row['title']. ' | ' . date_format(date_create($row['date']),'M d, Y h:i A'),
                  'icon' => $row['image'],
                  'date' => $row['date'],
                  'violation' => $row['title']
              );
              $count++;
          }
          $stmt->close();
          ?>
          
          <!-- Leaflet JS (FREE - No API Key) -->
          <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
          
          <script type="text/javascript">
          var positions = <?php echo json_encode($positions);?>;
          var markerCount = <?php echo $count; ?>;
          var map;
          var markers = [];
          
          function initMap() {
              // Default center (Metro Manila)
              var defaultCenter = [14.6124466, 120.9879835];
              var mapZoom = 17;
              
              // If there are markers, center on the first one
              if (positions.length > 0) {
                  defaultCenter = [parseFloat(positions[0].lat), parseFloat(positions[0].lng)];
              }
              
              // Initialize map with Leaflet (FREE - No API Key)
              map = L.map('map').setView(defaultCenter, mapZoom);
              
              // Add OpenStreetMap tiles (FREE)
              L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                  attribution: '© OpenStreetMap contributors',
                  maxZoom: 19
              }).addTo(map);
              
              // Add markers for each tracked location
              positions.forEach(function(position, index){
                  var lat = parseFloat(position.lat);
                  var lng = parseFloat(position.lng);
                  
                  // Create custom marker with student image
                  var markerHtml = `
                      <div style="width: 45px; height: 45px; border-radius: 50%; border: 3px solid #667eea; overflow: hidden; background: white; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(0,0,0,0.2);">
                          <img src="${position.icon}" alt="Student" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.src='https://via.placeholder.com/45'">
                      </div>
                  `;
                  
                  var customIcon = L.divIcon({
                      html: markerHtml,
                      iconSize: [45, 45],
                      iconAnchor: [22, 45],
                      popupAnchor: [0, -45],
                      className: 'custom-marker'
                  });
                  
                  var marker = L.marker([lat, lng], {
                      icon: customIcon,
                      title: position.violation
                  }).addTo(map);
                  
                  // Bind popup with violation details
                  var popupContent = `
                      <div style="min-width: 220px;">
                          <strong style="color: #667eea; display: block; margin-bottom: 8px; font-size: 14px;">
                              <i class="fas fa-exclamation-circle"></i> ${position.violation}
                          </strong>
                          <div style="color: #666; line-height: 1.8; font-size: 12px;">
                              <div><i class="fas fa-calendar" style="color: #667eea; width: 14px;"></i> ${position.title}</div>
                              <div><i class="fas fa-map-marker-alt" style="color: #667eea; width: 14px;"></i> Lat: ${lat.toFixed(6)}</div>
                              <div><i class="fas fa-map-marker-alt" style="color: #667eea; width: 14px;"></i> Lng: ${lng.toFixed(6)}</div>
                          </div>
                      </div>
                  `;
                  
                  marker.bindPopup(popupContent);
                  markers.push({marker: marker, data: position, index: index});
              });
              
              // Auto-fit all markers in view
              if (positions.length > 1) {
                  var group = new L.featureGroup(markers.map(m => m.marker));
                  map.fitBounds(group.getBounds().pad(0.1));
              }
          }
          
          // Populate violation list
          document.addEventListener('DOMContentLoaded', function() {
              var violationList = document.getElementById('violationList');
              
              if (positions.length === 0) {
                  violationList.innerHTML = '<div style="text-align: center; padding: 40px 20px; color: #999;"><i class="fas fa-inbox" style="font-size: 32px; display: block; margin-bottom: 10px;"></i>No tracked violations yet.</div>';
              } else {
                  positions.forEach(function(position, index) {
                      var violationItem = document.createElement('div');
                      violationItem.className = 'violation-item';
                      violationItem.style.cursor = 'pointer';
                      violationItem.style.transition = 'all 0.3s ease';
                      
                      var dateObj = new Date(position.date);
                      var formattedDate = dateObj.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
                      
                      violationItem.innerHTML = `
                          <div class="violation-item-icon">
                              <img src="${position.icon}" alt="Student" onerror="this.src='https://via.placeholder.com/40'">
                          </div>
                          <div class="violation-item-content">
                              <p class="violation-item-title"><i class="fas fa-exclamation-circle" style="color: #ef4444; margin-right: 6px;"></i>${position.violation}</p>
                              <p class="violation-item-date"><i class="fas fa-clock"></i> ${formattedDate}</p>
                          </div>
                      `;
                      
                      // Click on violation item to center map on that marker
                      violationItem.addEventListener('click', function() {
                          var lat = parseFloat(position.lat);
                          var lng = parseFloat(position.lng);
                          map.setView([lat, lng], 19);
                          markers[index].marker.openPopup();
                      });
                      
                      violationItem.addEventListener('mouseenter', function() {
                          this.style.background = '#f0f4ff';
                          this.style.borderColor = '#667eea';
                          this.style.boxShadow = '0 2px 8px rgba(102, 126, 234, 0.2)';
                          this.style.transform = 'translateX(4px)';
                      });
                      
                      violationItem.addEventListener('mouseleave', function() {
                          this.style.background = 'white';
                          this.style.borderColor = '#e0e0e0';
                          this.style.boxShadow = 'none';
                          this.style.transform = 'translateX(0)';
                      });
                      
                      violationList.appendChild(violationItem);
                  });
              }
              
              // Initialize map after DOM is ready
              initMap();
          });
          </script>

          <!-- content-wrapper ends -->
          <!-- partial:partials/_footer.html -->
          <?php include('footer.php');?>
          <!-- partial -->
        </div>
        <!-- main-panel ends -->
      </div>
      <!-- page-body-wrapper ends -->
    </div>
    <!-- container-scroller -->
    <!-- Google Maps API removed - Using FREE Leaflet.js instead (no API key needed) -->
  </body>
  </html>

  <?php 
  $conn->close();
  ?>
