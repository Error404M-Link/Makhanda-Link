<?php 
    require "generateSummary.php"; 

    if(isset($conn) && $conn){
        // Select the street along with the ward for Auto filling the ward
        $stmtCouncillor = $conn->prepare(" SELECT DISTINCT 
                                            username, 
                                            ward_id, 
                                            date_appointed, 
                                        FROM ward_councillor 
                                        INNER JOIN ward w ON ad.ward_id = w.ward_id
                                        ORDER BY ad.street_name ASC ");

        if($stmtCouncillor){
            $stmtStreets->execute();
            $councillorResult = $stmtCouncillor->get_result();
            $stmtCouncillor->close();
        }

        // Fetching the Categories/Services
        $stmtCategory = $conn->prepare("SELECT DISTINCT service_type 
                                        FROM municipal_service
                                        ORDER BY service_type ASC");
        if($stmtCategory){
            $stmtCategory->execute();
            $categoryResults = $stmtCategory->get_result();
            $stmtCategory->close();
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate A Ward Summary</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="generateSummary.css"/>
    <link rel="stylesheet" href="dashboardSideBar.css"/>
    <style>
        
    </style>
</head>
<body>

    <section class="formContainer">

        <div class="faultsHeader reportFaultHeader">
            <strong>Generate A Ward Summary</strong>
        </div>
        
        <?php echo $message; ?>

        <form action="" method="POST" enctype="multipart/form-data" id="summaryForm">
            
            <div class="formGroup">
                <!-- ###### READ ONLY THE WARD #######--> 
                 
                <label for="wardDisplay">Ward:</label>
                <input type="text" id="wardDisplay" class="placeHolderSelect" placeholder="Select a ward first..." readonly required>
                
                <!-- Hidden input to submit ward_id to POST -->
                <input type="hidden" name="wardId" id="wardId" required>
            </div>

            </div>


            <div class="formGroup">
                <label for="file_path">Upload Ward Summary:</label>
                <input type="file" name="file_path" id="file_path" accept="image/*">
            </div>

            <div class="formActions">
                <div class="leftActions">
                    <button type="reset" class="btn resetBtn">Reset</button>
                    <button type="button" class="btn cancelBtn">Cancel</button>
                </div>
                <button type="submit" class="btn submitBtn">Submit</button>
            </div>
        </form>
    </section>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', ()=>{
    const streetSelect = document.getElementById('streetName');
    const wardDisplay = document.getElementById('wardDisplay');
    const wardIdInput = document.getElementById('wardId');

    if(streetSelect && wardDisplay && wardIdInput){
        streetSelect.addEventListener('change', function(){
            const selectedOption = this.options[this.selectedIndex];

            const linkedWardId = selectedOption.getAttribute('data-ward-id');
            const linkedWardInfo = selectedOption.getAttribute('data-ward-info');

            if(linkedWardId && linkedWardInfo){
                wardDisplay.value = linkedWardInfo;
                wardIdInput.value = linkedWardId;
            }else{
                wardDisplay.value = '';
                wardIdInput.value = '';
            }
        });
    }
});
    </script>
</body>
</html>