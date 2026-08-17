<?php
include 'statistics.php';
?>
<div class="dashboard-cards">

    <div class="card">
        <h3>Completed</h3>
        <h1><?php echo $completed['total']; ?></h1>
    </div>

    <div class="card">
        <h3>Pending</h3>
        <h1><?php echo $pending['total']; ?></h1>
    </div>

    <div class="card">
        <h3>In Progress</h3>
        <h1><?php echo $progress['total']; ?></h1>
    </div>

</div>
