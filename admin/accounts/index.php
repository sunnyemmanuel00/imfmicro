<?php 
// Check if the flash message exists and display an alert
if($_settings->chk_flashdata('success')): 
?>
<script>
    alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')
</script>
<?php 
endif;
?>

<style>
    /* Custom styles for the table */
    .account-table td, .account-table th{
        vertical-align: middle !important;
    }
</style>
<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title">List of Accounts</h3>
        <div class="card-tools">
            <a href="?page=accounts/manage_account" class="btn btn-flat btn-primary"><span class="fas fa-plus"></span> Create New</a>
        </div>
    </div>
    <div class="card-body">
        <div class="container-fluid">
            <div class="container-fluid">
                <table class="table table-bordered table-stripped account-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Account #</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Balance</th>
                            <th>Status</th>
                            <th>Role</th> <!-- New 'Role' column -->
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $i = 1;
                        // Start a try-catch block to handle potential PDO exceptions
                        try {
                            // The `$conn` variable is assumed to be a PDO connection object from your config file.
                            
                            // Corrected query for PostgreSQL: aliases columns to match for the UNION ALL
                            $stmt = $conn->prepare("
                                SELECT 
                                    id, 
                                    CAST(id AS TEXT) as id_number, 
                                    firstname, 
                                    lastname, 
                                    username as email, 
                                    NULL as middlename, 
                                    CAST(0.00 AS DECIMAL) as balance, 
                                    'System' as status, 
                                    'Admin' as role 
                                FROM users 
                                WHERE type = 1 
                                UNION ALL 
                                SELECT 
                                    id, 
                                    CAST(id_number AS TEXT) as id_number, 
                                    firstname, 
                                    lastname, 
                                    email, 
                                    middlename, 
                                    balance, 
                                    status, 
                                    'Client' as role 
                                FROM accounts 
                                ORDER BY id ASC
                            ");
                            
                            // Execute the prepared statement
                            $stmt->execute();

                            // Use a while loop to fetch each row as an associative array
                            while($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                        ?>
                                <tr>
                                    <td class="text-center"><?php echo $i++; ?></td>
                                    <td><?php echo htmlspecialchars($row['id_number']) ?></td>
                                    <td><?php echo htmlspecialchars($row['firstname'] . ' ' . (isset($row['middlename']) ? $row['middlename'] . ' ' : '') . $row['lastname']) ?></td>
                                    <td><?php echo htmlspecialchars($row['email']) ?></td>
                                    <td class="text-right"><?php echo isset($row['balance']) ? number_format($row['balance'], 2) : 'N/A'; ?></td>
                                    <td class="text-center">
                                        <!-- UPDATED LOGIC: First check the role, then determine the status display -->
                                        <?php 
                                            $badge_class = 'badge-secondary'; // Default badge color
                                            $status_text = 'N/A'; // Default status text
                                            
                                            if (strtolower($row['role']) == 'client') {
                                                $status = isset($row['status']) ? strtolower($row['status']) : '';
                                                
                                                if ($status == 'active') {
                                                    $badge_class = 'badge-success';
                                                } elseif ($status == 'pending') {
                                                    $badge_class = 'badge-warning';
                                                } elseif ($status == 'on hold') {
                                                    $badge_class = 'badge-info';
                                                } elseif ($status == 'suspended') {
                                                    $badge_class = 'badge-danger';
                                                }
                                                $status_text = htmlspecialchars($row['status']);
                                            } else { // This handles the 'Admin' role
                                                $badge_class = 'badge-primary';
                                                $status_text = 'System';
                                            }
                                        ?>
                                        <span class="badge <?php echo $badge_class; ?>"><?php echo $status_text; ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge <?php echo ($row['role'] == 'Admin') ? 'badge-dark' : 'badge-light'; ?>"><?php echo htmlspecialchars($row['role']); ?></span>
                                    </td>
                                    <td align="center">
                                        <button type="button" class="btn btn-flat btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">
                                            Action
                                            <span class="sr-only">Toggle Dropdown</span>
                                        </button>
                                        <div class="dropdown-menu" role="menu">
                                            <a class="dropdown-item" href="?page=accounts/view_account&id=<?php echo $row['id'] ?>&role=<?php echo strtolower($row['role']) ?>"><span class="fa fa-eye text-primary"></span> View</a>
                                            <div class="dropdown-divider"></div>
                                            <a class="dropdown-item" href="?page=accounts/manage_account&id=<?php echo $row['id'] ?>&role=<?php echo strtolower($row['role']) ?>"><span class="fa fa-edit text-primary"></span> Edit</a>
                                            <div class="dropdown-divider"></div>
                                            <?php if(strtolower($row['role']) == 'client' && strtolower($row['status']) != 'active'): ?>
                                                <a class="dropdown-item activate_data" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>"><span class="fa fa-check text-success"></span> Activate</a>
                                                <div class="dropdown-divider"></div>
                                            <?php endif; ?>
                                            <a class="dropdown-item delete_data" href="javascript:void(0)" data-id="id=<?php echo $row['id'] ?>&role=<?php echo strtolower($row['role']) ?>"><span class="fa fa-trash text-danger"></span> Delete</a>
                                        </div>
                                    </td>
                                </tr>
                        <?php 
                            endwhile;
                        } catch (PDOException $e) {
                            // Display a user-friendly error message if the query fails
                            echo "<tr><td colspan='8'>Error fetching data: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script>
    // Delete account logic
    $(document).ready(function(){
        $('.delete_data').click(function(){
            var data_id = $(this).attr('data-id');
            var params = new URLSearchParams(data_id);
            var id = params.get('id');
            var role = params.get('role');
            if (id && role) {
                _conf("Are you sure to delete this " + role + " permanently?","delete_user",[id, role])
            } else {
                alert_toast("An error occurred with the data-id attribute.","error");
            }
        })
        $('.activate_data').click(function(){
            _conf("Are you sure you want to activate this Account?","activate_account",[$(this).attr('data-id')])
        })
        $('.table').dataTable({
            columnDefs: [
                { orderable: false, targets: [7] }
            ]
        });
    })

    function delete_user($id, $role){
        start_loader();
        var url = _base_url_ + "classes/Master.php?f=delete_account";
        if($role === 'admin') {
            url = _base_url_ + "classes/Users.php?f=delete";
        }
        $.ajax({
            url: url,
            method:"POST",
            data:{id: $id},
            dataType:"json",
            error:err=>{
                console.log(err)
                alert_toast("An error occured.","error");
                end_loader();
            },
            success:function(resp){
                if(typeof resp== 'object' && resp.status == 'success'){
                    location.reload();
                }else{
                    alert_toast("An error occured.","error");
                    end_loader();
                }
            }
        })
    }
    
    function activate_account($id){
        start_loader();
        $.ajax({
            url:_base_url_+"classes/Master.php?f=activate_account",
            method:"POST",
            data:{id: $id},
            dataType:"json",
            error:err=>{
                console.log(err)
                alert_toast("An error occured.","error");
                end_loader();
            },
            success:function(resp){
                if(typeof resp== 'object' && resp.status == 'success'){
                    location.reload();
                }else{
                    alert_toast("An error occured.","error");
                    end_loader();
                }
            }
        })
    }
</script>
