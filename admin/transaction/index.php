<?php 
// C:\xampp\htdocs\banking\admin\transaction\index.php
// Corrected path to include config.php.
require_once(__DIR__ . '/../../config.php'); 

if ($_settings->chk_flashdata('success')): ?>
<script>
  alert_toast("<?php echo $_settings->flashdata('success'); ?>", 'success');
</script>
<?php endif; ?>

<div class="card card-outline card-primary">
  <div class="card-header">
    <h3 class="card-title">List of All Transactions</h3>
  </div>
  <div class="card-body">
    <div class="container-fluid">
      <table class="table table-bordered table-stripped table-hover" id="transaction-list">
        <thead>
          <tr>
            <th class="text-center">#</th>
            <th>Date & Time</th>
            <th>Transaction Code</th>
            <th>Account Holder</th>
            <th>Transaction Type</th>
            <th>Status</th>
            <th class="text-right">Amount</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php 
          $i = 1;
          $combined_data = [];
          try {
            // Query for COMPLETED transactions from the 'transactions' table
            $query_completed = "
              SELECT 
                t.id,
                t.date_created,
                t.transaction_code,
                t.transaction_type,
                t.amount,
                t.status,
                a.account_number,
                CONCAT(a.firstname, ' ', a.lastname) as account_holder_name 
              FROM 
                transactions t
              INNER JOIN 
                accounts a ON t.account_id = a.id
            ";
            $stmt_completed = $conn->prepare($query_completed);
            $stmt_completed->execute();

            while($row = $stmt_completed->fetch(PDO::FETCH_ASSOC)){
              $row['is_pending'] = false;
              $combined_data[] = $row;
            }

            // Query for PENDING transactions from the 'pending_transactions' table
            $query_pending = "
              SELECT 
                pt.id,
                pt.timestamp as date_created,
                'N/A' as transaction_code,
                'transfer_external_debit' as transaction_type,
                pt.amount,
                pt.status,
                pt.sender_id as account_id,
                a.account_number,
                CONCAT(a.firstname, ' ', a.lastname) as account_holder_name 
              FROM 
                pending_transactions pt
              INNER JOIN 
                accounts a ON pt.sender_id = a.id
            ";
            $stmt_pending = $conn->prepare($query_pending);
            $stmt_pending->execute();

            while($row = $stmt_pending->fetch(PDO::FETCH_ASSOC)){
              $row['is_pending'] = true;
              $combined_data[] = $row;
            }

            // Sort the combined data by date in descending order
            usort($combined_data, function($a, $b) {
              return strtotime($b['date_created']) - strtotime($a['date_created']);
            });

            foreach($combined_data as $row):
              $is_pending = $row['is_pending'];
              
              // Determine status badge
              $status_badge = '';
              switch (strtolower($row['status'] ?? '')) {
                case 'pending':
                  $status_badge = '<span class="badge badge-warning">Pending</span>';
                  break;
                case 'completed':
                  $status_badge = '<span class="badge badge-success">Completed</span>';
                  break;
                case 'declined':
                  $status_badge = '<span class="badge badge-danger">Declined</span>';
                  break;
                default:
                  $status_badge = '<span class="badge badge-secondary">' . htmlspecialchars($row['status']) . '</span>';
              }
              
              // Determine transaction type display
              $type_display = '';
              $amount_class = '';
              $ttype = $row['transaction_type'] ?? 'N/A';
              if (strpos($ttype, 'deposit') !== false) {
                $type_display = '<i class="fas fa-arrow-down text-success"></i> Deposit';
                $amount_class = 'text-success';
              } elseif (strpos($ttype, 'withdraw') !== false) {
                $type_display = '<i class="fas fa-arrow-up text-danger"></i> Withdrawal';
                $amount_class = 'text-danger';
              } elseif (strpos($ttype, 'transfer') !== false) {
                if (strpos($ttype, 'debit') !== false || strpos($ttype, 'external') !== false) {
                  $type_display = '<i class="fas fa-exchange-alt text-danger"></i> Transfer (Out)';
                  $amount_class = 'text-danger';
                } else {
                  $type_display = '<i class="fas fa-exchange-alt text-success"></i> Transfer (In)';
                  $amount_class = 'text-success';
                }
              }
          ?>
            <tr>
              <td class="text-center"><?php echo $i++; ?></td>
              <td><?php echo date("M d, Y h:i A", strtotime($row['date_created'])); ?></td>
              <td><?php echo htmlspecialchars($row['transaction_code'] ?? 'N/A'); ?></td>
              <td><?php echo htmlspecialchars($row['account_holder_name'] . ' (' . $row['account_number'] . ')'); ?></td>
              <td><?php echo $type_display; ?></td>
              <td class="text-center"><?php echo $status_badge; ?></td>
              <td class="text-right <?php echo $amount_class; ?>"><?php echo number_format($row['amount'], 2); ?></td>
              <td class="text-center">
                <button type="button" class="btn btn-flat btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">
                  Action
                  <span class="sr-only">Toggle Dropdown</span>
                </button>
                <div class="dropdown-menu" role="menu">
                  <a class="dropdown-item view_transaction" href="javascript:void(0)" data-id="<?php echo $row['id']; ?>" data-is_pending="<?php echo $is_pending ? 'true' : 'false'; ?>"><span class="fa fa-eye text-dark"></span> View</a>
                  <div class="dropdown-divider"></div>
                  <a class="dropdown-item edit_transaction" href="javascript:void(0)" data-id="<?php echo $row['id']; ?>" data-is_pending="<?php echo $is_pending ? 'true' : 'false'; ?>"><span class="fa fa-edit text-primary"></span> Edit</a>
                  <div class="dropdown-divider"></div>
                  <?php if ($is_pending): ?>
                    <a class="dropdown-item approve_transaction" href="javascript:void(0)" data-id="<?php echo $row['id']; ?>"><span class="fa fa-check text-success"></span> Approve</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item decline_transaction" href="javascript:void(0)" data-id="<?php echo $row['id']; ?>"><span class="fa fa-times text-danger"></span> Decline</a>
                    <div class="dropdown-divider"></div>
                  <?php endif; ?>
                  <a class="dropdown-item delete_transaction" href="javascript:void(0)" data-id="<?php echo $row['id']; ?>"><span class="fa fa-trash text-danger"></span> Delete</a>
                </div>
              </td>
            </tr>
          <?php 
            endforeach;
          } catch (PDOException $e) {
            echo "<tr><td colspan='8' class='text-center'>Error fetching data: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
          }
          ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<script>
$(document).ready(function(){
  $('#transaction-list').dataTable({
    "columnDefs": [
      { "orderable": false, "targets": 7 }
    ]
  });

  $('.view_transaction').click(function(){
    var id = $(this).attr('data-id');
    var is_pending = $(this).attr('data-is_pending') === 'true';
    var type_param = is_pending ? '&type=pending' : '';
    uni_modal("<i class='fa fa-eye'></i> Transaction Details", "transaction/view_transaction.php?id=" + id + type_param, "large");
  });

  $('.edit_transaction').click(function(){
    var id = $(this).attr('data-id');
    var is_pending = $(this).attr('data-is_pending') === 'true';
    var type_param = is_pending ? '&type=pending' : '';
    uni_modal("<i class='fa fa-edit'></i> Edit Transaction", "transaction/manage_transaction.php?id=" + id + type_param, "mid-large");
  });

  $('.approve_transaction').click(function(){
    _conf("Are you sure you want to approve this transaction?", "approve_transaction", [$(this).attr('data-id')]);
  });
  
  // UPDATED: Now directly calling _conf and passing the decline function
  $('.decline_transaction').click(function(){
    _conf("Are you sure you want to decline this transaction?", "decline_transaction", [$(this).attr('data-id')]);
  });

  $('.delete_transaction').click(function(){
    _conf("Are you sure to delete this transaction permanently?", "delete_transaction", [$(this).attr('data-id')]);
  });
});

function approve_transaction(id){
  start_loader();
  $.ajax({
    url: _base_url_ + "classes/Master.php?f=approve_transaction",
    method: "POST",
    data: { transaction_id: id },
    dataType: "json",
    error: err => {
      console.log(err);
      alert_toast("An error occurred.", 'error');
      end_loader();
    },
    success: function(resp){
      if(resp.status == 'success'){
        location.reload();
      } else if(!!resp.msg){
        alert_toast(resp.msg, 'error');
      } else {
        alert_toast("An unknown error occurred.", 'error');
      }
      end_loader();
    }
  });
}

// UPDATED: The decline_transaction_prompt function is no longer needed
// UPDATED: The decline_transaction function now expects a single ID and no reason
function decline_transaction(id){
  start_loader();
  $.ajax({
    url: _base_url_ + "classes/Master.php?f=decline_transaction",
    method: "POST",
    data: { transaction_id: id },
    dataType: "json",
    error: err => {
      console.log(err);
      alert_toast("An error occurred.", 'error');
      end_loader();
    },
    success: function(resp){
      if(resp.status == 'success'){
        location.reload();
      } else if(!!resp.msg){
        alert_toast(resp.msg, 'error');
      } else {
        alert_toast("An unknown error occurred.", 'error');
      }
      end_loader();
    }
  });
}

function delete_transaction(id){
  start_loader();
  $.ajax({
    url: _base_url_ + "classes/Master.php?f=delete_transaction",
    method: "POST",
    data: { id: id },
    dataType: "json",
    error: err => {
      console.log(err);
      alert_toast("An error occurred.", 'error');
      end_loader();
    },
    success: function(resp){
      if(resp.status == 'success'){
        location.reload();
      } else if(!!resp.msg){
        alert_toast(resp.msg, 'error');
      } else {
        alert_toast("An unknown error occurred.", 'error');
      }
      end_loader();
    }
  });
}
</script>