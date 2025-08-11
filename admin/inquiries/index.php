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

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title">List of Inquiries</h3>
    </div>
    <div class="card-body">
        <div class="container-fluid">
            <table class="table table-bordered table-stripped" id="inquiry-list">
                <colgroup>
                    <col width="5%">
                    <col width="20%">
                    <col width="25%">
                    <col width="35%">
                    <col width="15%">
                </colgroup>
                <thead>
                    <tr>
                        <th class="text-center">#</th>
                        <th>Date Received</th>
                        <th>Sender Details</th>
                        <th>Subject / Type</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $i = 1;
                    // Start a try-catch block to handle potential PDO exceptions
                    try {
                        // The `$conn` variable is assumed to be a PDO connection object from your config file.
                        
                        // Corrected query for PostgreSQL: ordering by a simpler method that avoids `to_timestamp`
                        $inquiries_stmt = $conn->prepare('SELECT * FROM "inquiries" ORDER BY "date_created" DESC');
                        
                        // Execute the prepared statement
                        $inquiries_stmt->execute();

                        // Use a while loop to fetch each row as an associative array
                        while($row = $inquiries_stmt->fetch(PDO::FETCH_ASSOC)):
                    ?>
                        <tr>
                            <td class="text-center"><?php echo $i++; ?></td>
                            <td><?php echo date("Y-m-d H:i",strtotime($row['date_created'])) ?></td>
                            <td>
                                <p class="m-0 lh-1">
                                    <small>
                                        <span class="text-muted">Name: </span><?php echo htmlspecialchars($row['name']) ?><br>
                                        <span class="text-muted">Email: </span><?php echo htmlspecialchars($row['email']) ?><br>
                                        <span class="text-muted">Phone: </span><?php echo htmlspecialchars($row['phone']) ?>
                                    </small>
                                </p>
                            </td>
                            <td>
                                <p class="m-0 lh-1">
                                    <small>
                                        <span class="text-muted">Type: </span><?php echo htmlspecialchars($row['type']) ?><br>
                                        <span class="text-muted">Subject: </span><?php echo htmlspecialchars($row['subject']) ?>
                                    </small>
                                </p>
                            </td>
                            <td align="center">
                                <button type="button" class="btn btn-flat btn-default btn-sm view_data" data-id="<?php echo $row['id'] ?>">
                                    <span class="fa fa-eye text-primary"></span> View
                                </button>
                            </td>
                        </tr>
                    <?php 
                        endwhile;
                    } catch (PDOException $e) {
                        // Display a user-friendly error message if the query fails
                        echo "<tr><td colspan='5'>Error fetching data: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
    $(document).ready(function(){
        $('#inquiry-list').dataTable({
            columnDefs: [
                { orderable: false, targets: [2,4] }
            ],
            order:[0,'asc']
        });
        $('.dataTable td,.dataTable th').addClass('py-1 px-2 align-middle');
        
        $(document).on('click', '.view_data', function(){
            const inquiryId = $(this).data('id');
            const url = _base_url_ + "admin/?page=inquiries/view_inquiry&id=" + inquiryId;
            console.log("AJAX request URL:", url);
            uni_modal("Inquiry Details", url, "large");
        })
    })
</script>
