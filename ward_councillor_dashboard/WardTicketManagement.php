<?php

include "Error404Connection.php";


$sql = "SELECT incident_id, ward_id, service_id, location_description, incident_description, priority,
               status_id, date_reported, created_at
        FROM incident_ticket
        ORDER BY created_at DESC";



$result = $conn->query($sql);


if (!$result) {
    die("Error retrieving tickets: " . $conn->error);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Ward Ticket Management</title>


    <style>

        body {
            font-family: Arial, sans-serif;
            background-color: #f7f7df;
            margin: 20px;
        }

        h1 {
            text-align: center;
            color: #1b442b;
            margin-bottom: 25px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
        }

        th {
            background-color: #1b442b;
            color: white;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: left;
        }

        tr:hover {
            background-color: #f5f5f5;
        }

        .view-btn {
            display: inline-block;
            background-color: #239e56;
            color: white;
            padding: 7px 12px;
            border-radius: 4px;
            text-decoration: none;
        }

        .view-btn:hover {
            background-color: #236833;
        }

        .no-tickets {
            text-align: center;
            padding: 20px;
            color: #777;
        }

        .navbar{
    display:flex;
    justify-content: space-between;
    align-items: center;
    padding:20px 2%;
    background-color: #334e4a;
    position: sticky;
    top:0;
    z-index: 1000;  /*Keeps keeps the naviagtion bar on top of everything*/
}

.nav-links{
    display: flex;
    flex-direction: row;
    gap: 1.5vw;
    list-style: none;
    flex-wrap: nowrap; /*this makes the links not jump to the nest line, this happend with the about us link*/
}

.nav-links a{
    text-decoration: none;
    color: white;
}

.link{
    text-decoration: none;
    color: white;
    position: relative;
    font-size: 1.5vw;
}


/*underline hover for the links*/
.link::after{
    content: "";
    position: absolute;
    left: 0;
    bottom: -3px;
    width: 0;
    height: 2px;
    background-color: #917e5a;
    transition: width 0.3s ease;
}

.link:hover{
    color: #917e5a;
}


.link:hover::after{
    width: 100%;
}
/*underline hover for thr links end here*/

.logo img{
    width: 10vw;
    min-width: 80px;
    max-width: 150px;
    height: auto;
}

h2{
    text-align: center;
    padding-top: 20px;
}

.btn{
    background-color: #334e4a;
    color:white;
    padding: 10px 20px 10px 20px;
    text-decoration: none;
    border-radius: 20px;
    border: 2px solid #917e5a;
    transition: 0.4s;
    font-size: 16px;

    white-space: nowrap;
}

.btn:hover{
    background-color: #917e5a;
    color: aliceblue;
}
#navbuttons{
    display: flex;
    gap:5px;
    margin-left: 20px;
}
/*######################### RESPONSIVENESS ############### */

@media screen and (max-width: 768px){
    .nav-links{
        display: block;
        gap: 10px;
        padding: 0;
    }

    .nav-links li {
        margin-bottom: 10px; 
    }

    .navbar{
        display: flex;
        flex-direction: column;
        gap: 10px;
        width: 25%;
    }

    #navbuttons{
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin-top: 15px;
    }

    .btn{
        display: inline-block;
        width: max-content;
        text-align: center;
    }
}

.backNavWrapper {
    width: 100%;
    margin: 20px 0;
}

.backNavContainer {
    display: flex;
    align-items: center;
    padding-left: 20px;
}

.backBtn {
    display: inline-flex;
    align-items: center;
    gap: 8px;

    background-color: #334e4a;
    color: white;

    padding: 10px 18px;

    border: 2px solid #917e5a;
    border-radius: 20px;

    text-decoration: none;
    font-size: 16px;

    transition: 0.3s;
}

.backBtn:hover {
    background-color: #917e5a;
    color: white;
}

.backBtn svg {
    width: 18px;
    height: 18px;
}

    </style>

</head>
<nav class="navbar"> 
        <div class="logo">
            <img src="MakanaLogo.svg" alt="Makhanda logo">
        </div> 
        <ul class="nav-links">
            <li><a href="homepage.php" class="link"> Home </a></li>
            <li><a href="PublicFaultBoard.php" class="link"> Fault </a></li>
            <li><a href="ward_notices" class="link"> Notices  </a></li>
            <li><a href="about_us_page.html" class="link"> About us </a></li>
            <li><a href="contactUs.html" class="navItem">Contact us</a></li>
            <div id="navbuttons">
                <li><a href="login.html" class="btn"> Login </a></li>
                <li><a href="register.html" class="btn"> Sign up </a></li>
            </div>
        </ul> 
    </nav>
    <hr>


<body>
    
    
   <div class="backNavWrapper">  
    <div class="backNavContainer">  

        <a href="#" onclick="history.back(); return false;" class="backBtn">  

            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" 
                 stroke="currentColor" stroke-width="2" 
                 stroke-linecap="round" stroke-linejoin="round">  

                <line x1="19" y1="12" x2="5" y2="12"></line> 
                <polyline points="12 19 5 12 12 5"></polyline> 

            </svg>  

            <span>Back</span>  

        </a>  

    </div>  
</div>

    <h1>Ward Ticket Management</h1>

    <table>

        <tr>

            <th>Incident ID</th>

            <th>Ward ID</th>

            <th>Service ID</th>

            <th>Location</th>

            <th>Description</th>

            <th>Priority</th>

            <th>Status</th>

            <th>Date Reported</th>

            <th>Action</th>

        </tr>


        <?php


        if ($result->num_rows > 0) {

            while ($row = $result->fetch_assoc()) {

        ?>

                <tr>

                    <td>
                        <?php echo htmlspecialchars($row['incident_id']); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($row['ward_id']); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($row['service_id']); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($row['location_description']); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($row['incident_description']); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($row['priority']); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($row['status_id']); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($row['date_reported']); ?>
                    </td>

                    <td>

                        <a class="view-btn"
                           href="ticketDetails.php?ticketId=<?php echo urlencode($row['incident_id']); ?>">

                            View

                        </a>

                    </td>

                </tr>

        <?php

            }

        } else {

        ?>

            <tr>

                <td colspan="11" class="no-tickets">

                    No tickets found.

                </td>

            </tr>

        <?php

        }

        $conn->close();

        ?>

    </table>

</body>

</html>