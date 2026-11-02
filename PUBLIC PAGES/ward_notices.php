<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ward councilor's Update</title>
    <link rel="stylesheet" href="publicfaultBoard.css">
</head>
<body>
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
        
    <div>
        <h2>Create Notice</h2>
        <form action="submit.php" method="POST">
            <label for="ward" id="ward" required>
                <select name="ward" id="ward" required>
                    <option value="">Select Ward</option>
                    <option value="1">Ward 1</option>
                    <option value="2">Ward 2</option>
                    <option value="3">Ward 3</option>
                    <option value="4">Ward 4</option>
                    <option value="5">Ward 5</option>
                    <option value="6">Ward 6</option>
                    <option value="7">Ward 7</option>
                    <option value="8">Ward 8</option>
                    <option value="9">Ward 9</option>
                    <option value="10">Ward 10</option>
                    <option value="11">Ward 11</option>
                    <option value="12">Ward 12</option>
                    <option value="13">Ward 13</option>
                    <option value="14">Ward 14</option>
                </select> 
                <br><br>   

                <label for="title">Notice Title:</label><br>
                <input type="text" id="title" name="title" required><br><br>

                <label class="notice-date">Date: </label><br>
                <input type="date" id ="notice-date" name="notice_date" required><br><br> 

                <label class="message">Message:</label><br> 
                <textarea id="message" name="message" placeholder="Type the notice for the ward here" required></textarea><br><br> 

                <div class="button-group">
                    <button type="submit" class="btn-submit">Submit Notice</button>
                    <button type="reset" class="btn-reset">Reset</button>
                </div>    


        </form>
    </div>

</body>
</html>