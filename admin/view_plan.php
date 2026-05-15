<?php
include("./include/connection.php");
include("./include/header.php");
include("./include/sidebar.php");
?>

<div class="container mt-5">
    <h2 class="text-center mb-4">All Insurance Plans</h2>

    <a href="add_plan.php" class="btn btn-primary mb-3">Add New Plan</a>

    <div class="table-responsive">
        <table class="table table-bordered table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Plan Name</th>
                    <th>Status</th>
                    <th>Applicable Crops</th>
                    <th>Premium %</th>
                    <th>Coverage %</th>
                    <th>Deductible %</th>
                    <th>Coverage Type</th>
                    <th>Unit Structure</th>
                    <th>Description</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
        <?php
        $sql = "SELECT * FROM insurance_plan ORDER BY plan_id DESC";
        $result = mysqli_query($conn, $sql);

        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
            ?>
                <tr>
                <td><?php echo $row['plan_id']; ?></td>
                <td><?php echo $row['plan_name']; ?></td>
                <td><?php echo $row['plan_status']; ?></td>
                <td><?php echo $row['applicable_crops']; ?></td>
                <td><?php echo $row['base_premium_rate']; ?>%</td>
                <td><?php echo $row['coverage_level']; ?>%</td>
                <td><?php echo $row['deductible_rate']; ?>%</td>
                <td><?php echo $row['coverage_type']; ?></td>
                <td><?php echo $row['unit_structure']; ?></td>
                <td><?php echo $row['description']; ?></td>

                <td>
                <a href='update_plan.php?pid=<?php echo $row['plan_id'] ?>' 
                   class='btn btn-sm btn-success mb-1'>Update</a>

                <a href='delete_plan.php?pid=<?php echo $row['plan_id'] ?>' 
                   class='btn btn-sm btn-danger'
                   onclick='return confirm("Are you sure you want to delete this plan?")'>Delete</a>
            </td>
        </tr>
    <?php            
            }
        } else {
            ?>
            <tr>
                <td colspan='12' class='text-center text-danger'>No Plans Available</td>
            </tr>
        <?php 
        }
        ?>
        </tbody>
        </table>
    </div>
</div>

<?php include("./include/footer.php"); ?>
