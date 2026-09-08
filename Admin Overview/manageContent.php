<?php

include "Error404Connection.php";

/* SQL query to retrieve all incident tickets */

$sql = "SELECT notice_id,
               username,
               notice_description,
               date_posted
        FROM   notices
        ORDER BY date_posted DESC";

/* Execute the SQL query */

$result = $conn->query($sql);

/* Check for SQL errors */

if (!$result) {
    die("Error retrieving tickets: " . $conn->error);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Content Management</title>
    <link rel="stylesheet" href="manageContent.css">
    <link rel="stylesheet" href="dashboardSideBar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="appContainer dashboardContainer">
        
        <!--###### Navidation/SideBar Bar ##############-->
        
        <input type="checkbox" id="sidebarToggle">
        <label for="sidebarToggle" class="hamburger">
            <span></span><span></span><span></span>
        </label>
        <aside class="sidebar">
            
            <nav class="navigation">
                <img src="MakanaLogo.svg" height="150" width="150" alt="M-link logo">
                
                <ul>
                    <li><a href="admindashboard.php">Dashboard</a></li>
                    <li><a href="#appointments-section">Appointments</a></li>
                    <li><a href="#patient-details-section">Patients</a></li>
                    <li><a href="#schedule-section">Schedule</a></li>
                </ul>
            </nav>
            <div class="sidebar-footer">
                <button class="logout-btn"><a href="DoctorLoginPage.html">Logout</a></button>
            </div>
        </aside>

        <!-- ################################################### -->

        <!-- Main Content Area -->
        <main class="mainContent">
            <!-- Header -->
            <header class="topHeader">
                <h1>Notice Management</h1>
                <div class="userProfile">
                    <i class="fa-solid fa-bell"></i>
                    <div class="avatar"><i class="fa-solid fa-user-shield"></i></div>
                    <span>Admin Portal</span>
                </div>
            </header>

            <!-- Content Overview -->
            <section class="metricsGrid">
                <div class="metricCard">
                    <div class="metricIcon blue"><i class="fa-solid fa-paper-plane"></i></div>
                    <div>
                        <h3 id="countActive">0</h3>
                        <p>Active Notices</p>
                    </div>
                </div>
                <div class="metricCard">
                    <div class="metricIcon orange"><i class="fa-solid fa-clock"></i></div>
                    <div>
                        <h3 id="countPending">0</h3>
                        <p>Pending Approvals</p>
                    </div>
                </div>
                <div class="metricCard">
                    <div class="metricIcon red"><i class="fa-solid fa-box-archive"></i></div>
                    <div>
                        <h3 id="countArchived">0</h3>
                        <p>Archived</p>
                    </div>
                </div>
                <button class="btn btnPrimary btnLg" id="btnOpenModal">
                    <i class="fa-solid fa-plus"></i> Create New Notice
                </button>
            </section>

            <!-- Layout Grid: Table + Side Drawer -->
            <div class="contentWorkspace">
                <!-- Table View -->
                <section class="tableContainer">
                    <div class="filterBar">
                        <div class="searchBox">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" id="searchInput" placeholder="Search notices by title...">
                        </div>
                        <select id="filterCategory">
                            <option value="">All Categories</option>
                            <option value="Water">Water</option>
                            <option value="Electricity">Electricity</option>
                            <option value="Roads">Roads</option>
                            <option value="General">General</option>
                        </select>
                        <select id="filterStatus">
                            <option value="">All Statuses</option>
                            <option value="Active">Active</option>
                            <option value="Pending">Pending</option>
                            <option value="Draft">Draft</option>
                            <option value="Archived">Archived</option>
                        </select>
                    </div>

                    <table class="dataTable">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Scope</th>
                                <th>Publish Date</th>
                                <th>Expiry Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="noticesTableBody">
                            <!-- Injected dynamically via script.js -->
                        </tbody>
                    </table>
                </section>

                <!-- Details / Inspector Panel -->
                <aside class="detailsPanel" id="inspectorPanel">
                    <h3><i class="fa-solid fa-circle-info"></i> Notice Details</h3>
                    <div id="inspectorContent" class="inspectorEmpty">
                        <p>Select a notice from the table to view details, manage approval workflow, or execute soft deletes.</p>
                    </div>
                </aside>
            </div>
        </main>
    </div>

    <!-- Modal for Notice Creation/Editing -->
    <div class="modalBackdrop" id="noticeModal">
        <div class="modal">
            <div class="modalHeader">
                <h3 id="modalTitle">Create New Municipal Notice</h3>
                <button class="closeBtn" id="btnCloseModal">&times;</button>
            </div>
            <form id="noticeForm">
                <div class="formGroup">
                    <label for="noticeTitle">Notice Title</label>
                    <input type="text" id="noticeTitle" required placeholder="e.g., Scheduled Water Interruption">
                </div>
                <div class="formRow">
                    <div class="formGroup">
                        <label for="noticeCategory">Category</label>
                        <select id="noticeCategory" required>
                            <option value="Water">Water</option>
                            <option value="Electricity">Electricity</option>
                            <option value="Roads">Roads</option>
                            <option value="General">General</option>
                        </select>
                    </div>
                    <div class="formGroup">
                        <label for="noticeScope">Target Scope</label>
                        <select id="noticeScope" required>
                            <option value="Global">Community-Wide (Global)</option>
                            <option value="Ward 1">Ward 1</option>
                            <option value="Ward 2">Ward 2</option>
                            <option value="Ward 3">Ward 3</option>
                            <option value="Ward 4">Ward 4</option>
                        </select>
                    </div>
                </div>
                <div class="formRow">
                    <div class="formGroup">
                        <label for="noticePublish">Publish Date/Time</label>
                        <input type="datetime-local" id="noticePublish" required>
                    </div>
                    <div class="formGroup">
                        <label for="noticeExpiry">Expiration Date/Time</label>
                        <input type="datetime-local" id="noticeExpiry" required>
                    </div>
                </div>
                <div class="formGroup">
                    <label for="noticeBody">Notice Body</label>
                    <textarea id="noticeBody" rows="4" required placeholder="Enter full announcement details..."></textarea>
                </div>
                <div class="modalFooter">
                    <button type="button" class="btn btnSecondary" id="btnCancelModal">Cancel</button>
                    <button type="submit" class="btnBtnPrimary">Save & Submit</button>
                </div>
            </form>
        </div>
    </div>

    <script src="manageContent.js"></script>
</body>
</html>