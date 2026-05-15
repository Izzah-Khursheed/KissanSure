<?php
include("./include/connection.php");
include("./include/header.php");
include("./include/sidebar.php");
?>
<div class="container">
    <div class="row">
        <div class="col-12 py-5 text-center">
            <h1>Registered Farmers</h1>
        </div>
        <div class="col-12 mb-3">
            <a href="./add_register.php" class="btn btn-primary">Add Farmer</a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-hover table-borderless table-primary align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Farmer ID</th>
                    <th>Full Name</th>
                    <th>Father Name</th>
                    <th>Phone</th>
                    <th>CNIC</th>
                    <th>City</th>
                    <th>Field Size (Acres)</th>
                    <th>Email</th>
                    <th>Delete</th>
                    <th>Update</th>
                </tr>
            </thead>

            <tbody class="table-group-divider">
                <?php
                $sql = "SELECT * FROM `register_farmer`";
                $run_query = mysqli_query($conn, $sql);

                if (mysqli_num_rows($run_query) > 0) {
                    while ($fetch = mysqli_fetch_assoc($run_query)) {
                ?>
                        <tr class="table-primary">
                            <td><?php echo $fetch["farmerid"]; ?></td>
                            <td><?php echo ucwords($fetch["name"]); ?></td>
                            <td><?php echo ucwords($fetch["father_name"]); ?></td>
                            <td><?php echo $fetch["phone"]; ?></td>
                            <td><?php echo $fetch["cnic"]; ?></td>
                            <td><?php echo $fetch["city"]; ?></td>
                            <td><?php echo $fetch["field_size"]; ?></td>
                            <td><?php echo $fetch["email"]; ?></td>

                            <td>
                                <a href="delete_register.php?delid=<?php echo $fetch['farmerid']; ?>" 
                                   class="btn btn-danger del_farmer">
                                   Delete
                                </a>
                            </td>

                            <td>
                                <a href="update_register.php?upid=<?php echo $fetch['farmerid']; ?>" 
                                   class="btn btn-success">
                                   Update
                                </a>
                            </td>
                        </tr>
                <?php
                    }
                } else {
                ?>
                    <tr>
                        <td colspan="8">
                            <h3 class="text-center text-danger">No Farmers Found</h3>
                        </td>
                    </tr>
                <?php
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php
include("./include/footer.php");
?>
