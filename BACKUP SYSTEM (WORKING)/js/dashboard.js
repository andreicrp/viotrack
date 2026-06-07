// dashboard.js - COMPLETE VERSION WITH REPORT INTEGRATION

// ==================== GLOBAL STATE ====================
let currentMonth = new Date().getMonth();
let currentYear = new Date().getFullYear();
let selectedDate = null;
let editingMeetingId = null;
let meetings = {};

// Chart instances
let trendChartInstance = null;
let donutChartInstance = null;

const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'];

// ==================== INITIALIZATION ====================
document.addEventListener('DOMContentLoaded', function() {
    console.log('=== Dashboard Initialization Started ===');
    console.log('Chart.js loaded:', typeof Chart !== 'undefined');
    console.log('Trend data available:', typeof window.trendData !== 'undefined');
    console.log('Violation percentage available:', typeof window.violationPercentage !== 'undefined');
    
    // Log the actual data
    if (window.trendData) {
        console.log('Trend Data:', window.trendData);
    }
    if (window.violationPercentage) {
        console.log('Violation Percentage:', window.violationPercentage);
    }
    
    initializeCalendar();
    handlePaginationScroll();
    
    // Initialize charts after a small delay to ensure DOM is ready
    setTimeout(function() {
        if (typeof Chart === 'undefined') {
            console.error('Chart.js is not loaded!');
            return;
        }
        
        console.log('Starting chart initialization...');
        initializeCharts();
    }, 300);
});

// ==================== PAGINATION SCROLL FIX ====================
function handlePaginationScroll() {
    if (window.location.hash) {
        setTimeout(function() {
            const element = document.querySelector(window.location.hash);
            if (element) {
                element.scrollIntoView({ behavior: 'smooth', block: 'start' });
                window.scrollBy(0, -20);
            }
        }, 100);
    }
}

// ==================== CHART FUNCTIONS ====================
function initializeCharts() {
    console.log('=== Initializing Charts ===');
    
    // Check if Chart.js is available
    if (typeof Chart === 'undefined') {
        console.error('Chart.js is not available!');
        return;
    }
    
    // Initialize both charts
    const trendSuccess = createTrendChart();
    const donutSuccess = createDonutChart();
    
    console.log('Trend chart created:', trendSuccess);
    console.log('Donut chart created:', donutSuccess);
}

function createTrendChart() {
    const canvas = document.getElementById('trendChart');
    if (!canvas) {
        console.error('Trend chart canvas element not found!');
        return false;
    }
    
    console.log('Creating trend chart...');
    const ctx = canvas.getContext('2d');
    
    // Get data from PHP
    const trendData = window.trendData || [];
    console.log('Processing trend data:', trendData);
    
    // Extract and validate data
    const labels = [];
    const minorData = [];
    const seriousData = [];
    const majorData = [];
    
    if (trendData.length === 0) {
        console.warn('No trend data available, using empty dataset');
        labels.push('No Data');
        minorData.push(0);
        seriousData.push(0);
        majorData.push(0);
    } else {
        trendData.forEach(item => {
            labels.push(item.date || 'N/A');
            minorData.push(parseInt(item.minor) || 0);
            seriousData.push(parseInt(item.serious) || 0);
            majorData.push(parseInt(item.major) || 0);
        });
    }
    
    console.log('Chart Labels:', labels);
    console.log('Minor Data:', minorData);
    console.log('Serious Data:', seriousData);
    console.log('Major Data:', majorData);
    
    // Destroy existing chart
    if (trendChartInstance) {
        console.log('Destroying existing trend chart');
        trendChartInstance.destroy();
    }
    
    try {
        // Create chart with explicit configuration
        trendChartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Minor',
                        data: minorData,
                        borderColor: '#22c55e',
                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        pointBackgroundColor: '#22c55e',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointHoverBackgroundColor: '#22c55e',
                        pointHoverBorderColor: '#fff'
                    },
                    {
                        label: 'Serious',
                        data: seriousData,
                        borderColor: '#f59e0b',
                        backgroundColor: 'rgba(245, 158, 11, 0.1)',
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        pointBackgroundColor: '#f59e0b',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointHoverBackgroundColor: '#f59e0b',
                        pointHoverBorderColor: '#fff'
                    },
                    {
                        label: 'Major',
                        data: majorData,
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        pointBackgroundColor: '#ef4444',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointHoverBackgroundColor: '#ef4444',
                        pointHoverBorderColor: '#fff'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        align: 'end',
                        labels: {
                            boxWidth: 10,
                            padding: 15,
                            font: {
                                size: 13,
                                weight: '600',
                                family: "'Inter', 'Segoe UI', sans-serif"
                            },
                            color: '#6b7280',
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: {
                        enabled: true,
                        backgroundColor: 'rgba(31, 41, 55, 0.95)',
                        padding: 12,
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderColor: 'rgba(255, 255, 255, 0.1)',
                        borderWidth: 1,
                        displayColors: true,
                        titleFont: { size: 14, weight: 'bold' },
                        bodyFont: { size: 13 },
                        callbacks: {
                            title: function(context) {
                                return context[0].label;
                            },
                            label: function(context) {
                                return context.dataset.label + ': ' + context.parsed.y + ' violations';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: true,
                            color: 'rgba(0, 0, 0, 0.05)',
                            drawBorder: false
                        },
                        ticks: {
                            font: {
                                size: 12,
                                family: "'Inter', 'Segoe UI', sans-serif"
                            },
                            color: '#9ca3af',
                            maxRotation: 0,
                            padding: 8
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)',
                            drawBorder: false
                        },
                        ticks: {
                            font: {
                                size: 12,
                                family: "'Inter', 'Segoe UI', sans-serif"
                            },
                            color: '#9ca3af',
                            stepSize: 1,
                            padding: 8,
                            callback: function(value) {
                                return Number.isInteger(value) ? value : '';
                            }
                        }
                    }
                }
            }
        });
        
        console.log('✓ Trend chart created successfully');
        return true;
    } catch (error) {
        console.error('✗ Error creating trend chart:', error);
        return false;
    }
}

function createDonutChart() {
    const canvas = document.getElementById('donutChart');
    if (!canvas) {
        console.error('Donut chart canvas element not found!');
        return false;
    }
    
    console.log('Creating donut chart...');
    const ctx = canvas.getContext('2d');
    
    // Get data from PHP
    const violationPercentage = window.violationPercentage || [
        {count: 0, percent: 0},
        {count: 0, percent: 0},
        {count: 0, percent: 0}
    ];
    
    console.log('Processing violation percentage data:', violationPercentage);
    
    // Extract counts
    const minorCount = parseInt(violationPercentage[0]?.count) || 0;
    const seriousCount = parseInt(violationPercentage[1]?.count) || 0;
    const majorCount = parseInt(violationPercentage[2]?.count) || 0;
    
    const data = [minorCount, seriousCount, majorCount];
    const total = data.reduce((a, b) => a + b, 0);
    
    console.log('Donut chart data:', {
        minor: minorCount,
        serious: seriousCount,
        major: majorCount,
        total: total
    });
    
    // Destroy existing chart
    if (donutChartInstance) {
        console.log('Destroying existing donut chart');
        donutChartInstance.destroy();
    }
    
    try {
        // Create chart
        donutChartInstance = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Minor', 'Serious', 'Major'],
                datasets: [{
                    data: data,
                    backgroundColor: [
                        '#22c55e',  // Green for Minor
                        '#f59e0b',  // Orange for Serious
                        '#ef4444'   // Red for Major
                    ],
                    borderWidth: 0,
                    hoverOffset: 10,
                    hoverBorderColor: '#fff',
                    hoverBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                aspectRatio: 1.1,
                cutout: '65%',
                plugins: {
                    legend: {
                        display: false  // We're using custom legend in HTML
                    },
                    tooltip: {
                        enabled: true,
                        backgroundColor: 'rgba(31, 41, 55, 0.95)',
                        padding: 12,
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderColor: 'rgba(255, 255, 255, 0.1)',
                        borderWidth: 1,
                        titleFont: { size: 14, weight: 'bold' },
                        bodyFont: { size: 13 },
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return label + ': ' + value + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
        
        console.log('✓ Donut chart created successfully');
        return true;
    } catch (error) {
        console.error('✗ Error creating donut chart:', error);
        return false;
    }
}

// ==================== DATE FILTER FUNCTIONS ====================
function setDateFilter(type) {
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

// ==================== CALENDAR FUNCTIONS ====================
function initializeCalendar() {
    console.log('Initializing calendar...');
    // Load meetings from database
    fetch('get-meetings.php')
        .then(response => {
            console.log('Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Raw meetings data:', data);
            if (data.success && data.meetings) {
                meetings = data.meetings;
                console.log('✓ Meetings loaded successfully');
                console.log('Number of dates with meetings:', Object.keys(meetings).length);
                Object.keys(meetings).forEach(dateKey => {
                    console.log(`  ${dateKey}: ${meetings[dateKey].length} meetings`);
                    meetings[dateKey].forEach(m => {
                        console.log(`    - ID: ${m.id}, Name: ${m.name}, Status: ${m.status}`);
                    });
                });
            } else {
                console.warn('No meetings data received or success is false');
                console.warn('Data:', data);
                meetings = {};
            }
            renderCalendar();
        })
        .catch(error => {
            console.error('Error loading meetings:', error);
            meetings = {};
            renderCalendar();
        });
}

function renderCalendar() {
    const firstDay = new Date(currentYear, currentMonth, 1).getDay();
    const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
    const today = new Date();
    
    document.getElementById('calendarMonthYear').textContent = 
        `${monthNames[currentMonth]} ${currentYear}`;
    
    const calendarDays = document.getElementById('calendarDays');
    calendarDays.innerHTML = '';
    
    // Add empty cells for days before the first day of the month
    for (let i = 0; i < firstDay; i++) {
        const emptyDay = document.createElement('span');
        emptyDay.className = 'day empty';
        calendarDays.appendChild(emptyDay);
    }
    
    // Add days of the month
    for (let day = 1; day <= daysInMonth; day++) {
        const dateKey = `${currentYear}-${String(currentMonth + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        const dayMeetings = meetings[dateKey] || [];
        
        const dayEl = document.createElement('span');
        dayEl.className = 'day';
        
        // Highlight today
        if (day === today.getDate() && currentMonth === today.getMonth() && currentYear === today.getFullYear()) {
            dayEl.classList.add('today');
        }
        
        // Mark days with meetings
        if (dayMeetings.length > 0) {
            dayEl.classList.add('has-meeting');
        }
        
        dayEl.textContent = day;
        dayEl.onclick = () => openMeetingPanel(day, dateKey);
        
        // Add meeting dots indicator
        if (dayMeetings.length > 0) {
            const dots = document.createElement('span');
            dots.className = 'meeting-dots';
            for (let i = 0; i < Math.min(dayMeetings.length, 3); i++) {
                const dot = document.createElement('span');
                dot.className = 'meeting-dot';
                dots.appendChild(dot);
            }
            dayEl.appendChild(dots);
        }
        
        calendarDays.appendChild(dayEl);
    }
}

function changeMonth(delta) {
    currentMonth += delta;
    if (currentMonth > 11) {
        currentMonth = 0;
        currentYear++;
    } else if (currentMonth < 0) {
        currentMonth = 11;
        currentYear--;
    }
    closeMeetingPanel();
    renderCalendar();
}

function openMeetingPanel(day, dateKey) {
    selectedDate = { day, dateKey };
    
    // Remove previous selection
    document.querySelectorAll('.day').forEach(d => d.classList.remove('selected'));
    event.target.classList.add('selected');
    
    document.getElementById('meetingPanelTitle').textContent = 
        `Meetings on ${monthNames[currentMonth]} ${day}, ${currentYear}`;
    
    document.getElementById('meetingPanel').style.display = 'block';
    document.getElementById('meetingForm').style.display = 'none';
    document.getElementById('addMeetingBtn').style.display = 'flex';
    
    renderMeetingList();
    clearForm();
}

function renderMeetingList() {
    const meetingList = document.getElementById('meetingList');
    
    // Check if selectedDate is set
    if (!selectedDate || !selectedDate.dateKey) {
        console.error('No selected date for rendering meeting list');
        meetingList.innerHTML = '<div class="no-meetings">Please select a date first</div>';
        return;
    }
    
    const dayMeetings = meetings[selectedDate.dateKey] || [];
    
    console.log('Rendering meetings for', selectedDate.dateKey, ':', dayMeetings);
    
    if (dayMeetings.length === 0) {
        meetingList.innerHTML = '<div class="no-meetings">No meetings scheduled for this day</div>';
        return;
    }
    
    meetingList.innerHTML = dayMeetings.map(meeting => `
        <div class="meeting-item">
            <div class="meeting-info">
                <div class="meeting-indicator"></div>
                <div class="meeting-details">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px; flex-wrap: wrap;">
                        <span class="meeting-name">${escapeHtml(meeting.name)}</span>
                        <span class="meeting-status-badge ${meeting.status.toLowerCase()}">${escapeHtml(meeting.status)}</span>
                    </div>
                    <div style="font-size: 12px; color: #8b5cf6; font-weight: 600; margin-bottom: 6px;">
                        📋 ${escapeHtml(meeting.type)}
                    </div>
                    <span class="meeting-time">${escapeHtml(meeting.time)}</span>
                    ${meeting.phone ? `<span class="meeting-phone">${escapeHtml(meeting.phone)}</span>` : ''}
                    ${meeting.reason ? `<div class="meeting-reason">${escapeHtml(meeting.reason)}</div>` : ''}
                </div>
            </div>
            <div class="meeting-item-actions">
                <button class="icon-btn small" onclick="openMeetingModal(${meeting.id})" title="Options">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
        </div>
    `).join('');
}

function openMeetingModal(id) {
    // Convert id to number for comparison
    const numId = parseInt(id);
    
    console.log('=== Opening meeting modal for ID:', numId, '===');
    console.log('Meetings object keys:', Object.keys(meetings));
    console.log('Total dates with meetings:', Object.keys(meetings).length);
    
    // Find the meeting across all dates
    let meeting = null;
    let foundDateKey = null;
    
    for (const dateKey in meetings) {
        console.log(`Searching in date: ${dateKey}, found ${meetings[dateKey].length} meetings`);
        const found = meetings[dateKey].find(m => {
            const mId = parseInt(m.id);
            console.log(`  Checking meeting ID: ${mId}, looking for: ${numId}`);
            return mId === numId;
        });
        if (found) {
            console.log('✓ Found meeting!', found);
            meeting = found;
            foundDateKey = dateKey;
            break;
        }
    }
    
    if (!meeting) {
        console.error('✗ Meeting not found with ID:', numId);
        console.error('All meetings in system:', meetings);
        alert('Meeting not found');
        return;
    }
    
    console.log('Found meeting:', meeting, 'in date:', foundDateKey);
    
    const html = `
        <div style="padding: 20px;">
            <h3 style="margin: 0 0 16px 0; color: #1f2937; font-weight: 700;">Meeting Details</h3>
            
            <div style="background: #f3f4f6; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div>
                        <div style="font-size: 12px; color: #6b7280; font-weight: 600; margin-bottom: 4px;">Name</div>
                        <div style="font-size: 15px; color: #1f2937; font-weight: 600;">${escapeHtml(meeting.name)}</div>
                    </div>
                    <div>
                        <div style="font-size: 12px; color: #6b7280; font-weight: 600; margin-bottom: 4px;">Type</div>
                        <div style="font-size: 15px; color: #1f2937; font-weight: 600;">${escapeHtml(meeting.type)}</div>
                    </div>
                    <div>
                        <div style="font-size: 12px; color: #6b7280; font-weight: 600; margin-bottom: 4px;">Time</div>
                        <div style="font-size: 15px; color: #1f2937; font-weight: 600;">${escapeHtml(meeting.time)}</div>
                    </div>
                    <div>
                        <div style="font-size: 12px; color: #6b7280; font-weight: 600; margin-bottom: 4px;">Status</div>
                        <select id="statusSelect" style="padding: 8px 12px; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 13px; font-weight: 600; width: 100%;">
                            <option value="Pending" ${meeting.status === 'Pending' ? 'selected' : ''}>Pending</option>
                            <option value="Scheduled" ${meeting.status === 'Scheduled' ? 'selected' : ''}>Scheduled</option>
                            <option value="Completed" ${meeting.status === 'Completed' ? 'selected' : ''}>Completed</option>
                            <option value="Cancelled" ${meeting.status === 'Cancelled' ? 'selected' : ''}>Cancelled</option>
                        </select>
                    </div>
                </div>
                ${meeting.phone ? `<div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #d1d5db;"><div style="font-size: 12px; color: #6b7280; font-weight: 600; margin-bottom: 4px;">Phone</div><div style="font-size: 14px; color: #1f2937;">${escapeHtml(meeting.phone)}</div></div>` : ''}
            </div>
            
            ${meeting.reason ? `<div style="background: #eef2ff; padding: 12px 16px; border-left: 3px solid #6366f1; border-radius: 6px; margin-bottom: 20px;"><div style="font-size: 12px; color: #4338ca; font-weight: 600; margin-bottom: 6px;">Message:</div><div style="font-size: 13px; color: #4338ca; line-height: 1.5;">${escapeHtml(meeting.reason)}</div></div>` : ''}
            
            <div style="display: flex; gap: 8px;">
                <button onclick="updateMeetingStatus(${id}, document.getElementById('statusSelect').value)" style="flex: 1; padding: 10px 16px; background: #6366f1; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.background='#4f46e5'" onmouseout="this.style.background='#6366f1'">
                    <i class="fas fa-check"></i> Update Status
                </button>
                <button onclick="editMeetingFromModal(${id})" style="flex: 1; padding: 10px 16px; background: #6366f1; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.background='#4f46e5'" onmouseout="this.style.background='#6366f1'">
                    <i class="fas fa-edit"></i> Edit
                </button>
                <button onclick="deleteMeetingFromModal(${id})" style="flex: 1; padding: 10px 16px; background: #ef4444; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.background='#dc2626'" onmouseout="this.style.background='#ef4444'">
                    <i class="fas fa-trash"></i> Delete
                </button>
            </div>
        </div>
    `;
    
    // Create and show modal
    const modal = document.createElement('div');
    modal.style.cssText = 'position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 10000;';
    modal.innerHTML = `
        <div style="background: white; border-radius: 12px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); max-width: 500px; width: 90%; max-height: 90vh; overflow-y: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 20px; border-bottom: 1px solid #e5e7eb; sticky; top: 0; background: white; z-index: 1;">
                <h3 style="margin: 0; font-size: 18px; color: #1f2937; font-weight: 700;">Meeting Options</h3>
                <button onclick="this.closest('[style*=position]').remove()" style="width: 32px; height: 32px; background: #f3f4f6; border: none; border-radius: 8px; cursor: pointer; font-size: 16px; color: #6b7280; transition: all 0.2s;" onmouseover="this.style.background='#e5e7eb'" onmouseout="this.style.background='#f3f4f6'">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            ${html}
        </div>
    `;
    
    document.body.appendChild(modal);
    modal.onclick = (e) => {
        if (e.target === modal) modal.remove();
    };
}

function updateMeetingStatus(id, newStatus) {
    // Convert id to number for comparison
    const numId = parseInt(id);
    
    console.log('Updating meeting status:', { id: numId, status: newStatus });
    
    // Find meeting across all dates
    let meeting = null;
    let dateKey = null;
    
    for (const dk in meetings) {
        const found = meetings[dk].find(m => parseInt(m.id) === numId);
        if (found) {
            meeting = found;
            dateKey = dk;
            break;
        }
    }
    
    if (!meeting) {
        alert('Meeting not found');
        return;
    }
    
    fetch('update-meeting-status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            id: numId,
            status: newStatus
        })
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.success) {
            meeting.status = newStatus;
            renderMeetingList();
            document.querySelector('[style*="position: fixed"]')?.remove();
            alert('Status updated successfully!');
        } else {
            console.error('Server error:', data.message);
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        alert('Error updating status: ' + error.message);
    });
}

function editMeetingFromModal(id) {
    // Convert id to number for comparison
    const numId = parseInt(id);
    
    // Find meeting across all dates
    let meeting = null;
    
    for (const dateKey in meetings) {
        const found = meetings[dateKey].find(m => parseInt(m.id) === numId);
        if (found) {
            meeting = found;
            break;
        }
    }
    
    if (!meeting) {
        alert('Meeting not found');
        return;
    }
    
    document.querySelector('[style*="position: fixed"]')?.remove();
    
    document.getElementById('meetingName').value = meeting.name;
    document.getElementById('meetingTime').value = meeting.time;
    document.getElementById('meetingReason').value = meeting.reason;
    document.getElementById('formBtnText').textContent = 'Update Meeting';
    document.getElementById('meetingForm').style.display = 'flex';
    document.getElementById('addMeetingBtn').style.display = 'none';
    editingMeetingId = id;
}

function deleteMeetingFromModal(id) {
    if (!confirm('Are you sure you want to delete this meeting?')) return;
    
    const numId = parseInt(id);
    
    fetch('delete-meeting.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ id: numId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Find and remove from all dates
            for (const dateKey in meetings) {
                meetings[dateKey] = meetings[dateKey].filter(m => parseInt(m.id) !== numId);
                if (meetings[dateKey].length === 0) {
                    delete meetings[dateKey];
                }
            }
            
            renderCalendar();
            renderMeetingList();
            document.querySelector('[style*="position: fixed"]')?.remove();
            alert('Meeting deleted successfully!');
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error deleting meeting');
    });
}

function showMeetingForm() {
    // Check if user is admin (this info should be passed from PHP)
    if (typeof isAdmin !== 'undefined' && !isAdmin) {
        alert('Only admins can add meetings');
        return;
    }
    document.getElementById('meetingForm').style.display = 'flex';
    document.getElementById('addMeetingBtn').style.display = 'none';
    document.getElementById('formBtnText').textContent = 'Save Meeting';
    document.getElementById('meetingName').focus();
    editingMeetingId = null;
}

function cancelForm() {
    document.getElementById('meetingForm').style.display = 'none';
    document.getElementById('addMeetingBtn').style.display = 'flex';
    clearForm();
}

function clearForm() {
    document.getElementById('meetingName').value = '';
    document.getElementById('meetingTime').value = '';
    document.getElementById('meetingReason').value = '';
    document.getElementById('formBtnText').textContent = 'Save Meeting';
    editingMeetingId = null;
}

function addOrUpdateMeeting() {
    const name = document.getElementById('meetingName').value.trim();
    const time = document.getElementById('meetingTime').value.trim();
    const reason = document.getElementById('meetingReason').value.trim();
    
    if (!name || !time) {
        alert('Please fill in meeting name and time');
        return;
    }
    
    // Save to database
    fetch('save-meeting.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            date: selectedDate.dateKey,
            name: name,
            time: time,
            reason: reason
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update local meetings object
            if (editingMeetingId) {
                meetings[selectedDate.dateKey] = meetings[selectedDate.dateKey].map(m => 
                    m.id === editingMeetingId ? { ...m, name, time, reason } : m
                );
            } else {
                const newMeeting = { 
                    id: data.report_id, 
                    name, 
                    time, 
                    reason,
                    type: 'Manual Entry',
                    status: 'Pending',
                    phone: ''
                };
                if (!meetings[selectedDate.dateKey]) {
                    meetings[selectedDate.dateKey] = [];
                }
                meetings[selectedDate.dateKey].push(newMeeting);
            }
            
            renderCalendar();
            renderMeetingList();
            cancelForm();
            alert('Meeting saved successfully!');
        } else {
            alert('Error saving meeting: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error saving meeting');
    });
}

function editMeeting(id) {
    const meeting = meetings[selectedDate.dateKey].find(m => m.id === id);
    if (!meeting) return;
    
    document.getElementById('meetingName').value = meeting.name;
    document.getElementById('meetingTime').value = meeting.time;
    document.getElementById('meetingReason').value = meeting.reason;
    document.getElementById('formBtnText').textContent = 'Update Meeting';
    document.getElementById('meetingForm').style.display = 'flex';
    document.getElementById('addMeetingBtn').style.display = 'none';
    editingMeetingId = id;
}

function deleteMeeting(id) {
    if (!confirm('Are you sure you want to delete this meeting?')) return;
    
    // Delete from database
    fetch('delete-meeting.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ id: id })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove from local meetings object
            meetings[selectedDate.dateKey] = meetings[selectedDate.dateKey].filter(m => m.id !== id);
            
            if (meetings[selectedDate.dateKey].length === 0) {
                delete meetings[selectedDate.dateKey];
            }
            
            renderCalendar();
            renderMeetingList();
            alert('Meeting deleted successfully!');
        } else {
            alert('Error deleting meeting: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error deleting meeting');
    });
}

function closeMeetingPanel() {
    document.getElementById('meetingPanel').style.display = 'none';
    document.getElementById('meetingForm').style.display = 'none';
    document.querySelectorAll('.day').forEach(d => d.classList.remove('selected'));
    selectedDate = null;
    clearForm();
}

// ==================== UTILITY FUNCTIONS ====================
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Handle window resize for charts
window.addEventListener('resize', function() {
    if (trendChartInstance) {
        trendChartInstance.resize();
    }
    if (donutChartInstance) {
        donutChartInstance.resize();
    }
});