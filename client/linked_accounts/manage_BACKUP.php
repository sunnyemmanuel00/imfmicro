<?php
// This block is restored to its original, correct state.
// It is required for the page to work when included by index.php.
if(!isset($Master)){
  require_once('../../classes/Master.php');
  $Master = new Master();
}

// Flashdata display logic
if($_settings->flashdata('success')):
?>
<script>
  $(function(){
    _alert_success("<?php echo $_settings->flashdata('success') ?>");
  })
</script>
<?php
endif;
if($_settings->flashdata('error')):
?>
<script>
  $(function(){
    _alert_danger("<?php echo $_settings->flashdata('error') ?>");
  })
</script>
<?php
endif;
// End Flashdata display logic
?>

<?php if($_settings->userdata('login_type') == 1): // Admin View ?>
<div class="card card-outline rounded-0 card-navy">
  <div class="card-header">
    <h3 class="card-title">List of Linked Accounts (Admin View)</h3>
    <div class="card-tools">
      <a href="javascript:void(0)" id="create_new" class="btn btn-flat btn-primary"><span class="fas fa-plus"></span> Create New</a>
    </div>
  </div>
  <div class="card-body">
    <div class="container-fluid">
      <table class="table table-hover table-striped table-bordered" id="list">
        <colgroup>
          <col width="5%">
          <col width="10%">
          <col width="10%">
          <col width="10%">
          <col width="10%">
          <col width="10%">
          <col width="8%">
          <col width="8%">
          <col width="8%">
          <col width="8%">
          <col width="8%">
          <col width="8%">
          <col width="5%">
          <col width="5%">
        </colgroup>
        <thead>
          <tr>
            <th>#</th>
            <th>Date Added</th>
            <th>User (Primary Account Holder)</th>
            <th>Linked Account Number</th>
            <th>Account Label</th>
            <th>Bank Name</th>
            <th>SWIFT/BIC</th>
            <th>Routing No.</th>
            <th>IBAN</th>
            <th>Beneficiary Address</th>
            <th>Beneficiary Phone</th>
            <th>Internal Bank?</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php 
          $i = 1;
          $qry = $conn->query("SELECT ula.*, 
                    CONCAT(a.lastname, ', ', a.firstname, ' ', COALESCE(a.middlename,'')) as user_fullname, 
                    a.account_number as primary_account_number
                    FROM `user_linked_accounts` ula
                    INNER JOIN `accounts` a ON ula.user_id = a.id
                    ORDER BY ula.date_added DESC");
     while($row = $qry->fetch_assoc()):
     ?>
      <tr>
       <td class="text-center"><?php echo $i++; ?></td>
       <td><?php echo date("Y-m-d H:i",strtotime($row['date_added'])) ?></td>
       <td><?php echo $row['user_fullname'] . " (" . $row['primary_account_number'] . ")"; ?></td>
       <td><?php echo $row['account_number'] ?></td>
       <td><?php echo $row['account_label'] ?></td>
       <td><?php echo $row['bank_name'] ?></td>
       <td><?php echo !empty($row['swift_bic']) ? $row['swift_bic'] : 'N/A'; ?></td>
       <td><?php echo !empty($row['routing_number']) ? $row['routing_number'] : 'N/A'; ?></td>
       <td><?php echo !empty($row['iban']) ? $row['iban'] : 'N/A'; ?></td>
       <td><?php echo !empty($row['beneficiary_address']) ? $row['beneficiary_address'] : 'N/A'; ?></td>
       <td><?php echo !empty($row['beneficiary_phone']) ? $row['beneficiary_phone'] : 'N/A'; ?></td>
       <td class="text-center">
        <?php if($row['is_internal_bank'] == 1): ?>
         <span class="badge badge-success px-3 rounded-pill">Yes</span>
        <?php else: ?>
         <span class="badge badge-secondary px-3 rounded-pill">No</span>
        <?php endif; ?>
       </td>
       <td align="center">
        <button type="button" class="btn btn-flat p-1 btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">
         Action
         <span class="sr-only">Toggle Dropdown</span>
        </button>
        <div class="dropdown-menu" role="menu">
         <a class="dropdown-item edit_data" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>"><span class="fa fa-edit text-primary"></span> Edit</a>
         <div class="dropdown-divider"></div>
         <a class="dropdown-item delete_data" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>"><span class="fa fa-trash text-danger"></span> Delete</a>
        </div>
       </td>
      </tr>
     <?php endwhile; ?>
    </tbody>
   </table>
  </div>
  </div>
</div>
<?php else: // ================== START: NEW CLIENT VIEW ================== ?>
<style>
  /* Custom styles for the new responsive card layout */
  .linked-account-card {
    border: 1px solid #dee2e6;
    border-radius: .35rem;
    margin-bottom: 1.5rem;
    background-color: #fff;
    box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,.075);
  }
  .linked-account-header {
    background-color: #f8f9fa;
    padding: .75rem 1.25rem;
    border-bottom: 1px solid #dee2e6;
    display: flex;
    flex-wrap: wrap; /* Allows items to wrap on small screens */
    justify-content: space-between;
    align-items: center;
    gap: .5rem; /* Adds space between items when they wrap */
  }
  .account-label {
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 0;
  }
  .linked-account-body {
    padding: 1.25rem;
  }
  .detail-row {
    display: flex;
    flex-wrap: wrap; /* Allow wrapping on very narrow screens */
    border-bottom: 1px solid #f1f1f1;
    padding-top: .6rem;
    padding-bottom: .6rem;
  }
  .detail-row:last-child {
    border-bottom: none;
  }
  .detail-label {
    font-weight: bold;
    color: #6c757d;
    flex-basis: 40%; /* Label takes 40% of the space */
    padding-right: 10px;
  }
  .detail-value {
    flex-basis: 60%; /* Value takes 60% of the space */
    word-wrap: break-word; /* Ensure long values wrap */
  }
  /* On mobile, stack label and value */
  @media (max-width: 576px) {
    .detail-label, .detail-value {
      flex-basis: 100%; /* Make them full width */
    }
    .detail-label {
      margin-bottom: .25rem; /* Add a little space below the label */
    }
  }
</style>
<div class="card card-outline rounded-0 card-navy">
  <div class="card-header">
    <h3 class="card-title">Manage Your Linked Accounts</h3>
    <div class="card-tools">
      <a href="javascript:void(0)" id="create_new" class="btn btn-flat btn-primary" onclick="uni_modal('Link New Account','?page=linked_accounts/manage_linked_account','mid-large')"><span class="fas fa-plus"></span> Link New Account</a>
    </div>
  </div>
  <div class="card-body">
    <div class="container-fluid px-0">
      <?php 
      $user_id = $_settings->userdata('id');
      $linked_accounts_json = $Master->get_linked_accounts($user_id); 
      $linked_accounts_data = json_decode($linked_accounts_json, true);

      if ($linked_accounts_data['status'] == 'success' && !empty($linked_accounts_data['data'])):
        foreach($linked_accounts_data['data'] as $row):
      ?>
        <div class="linked-account-card">
          <div class="linked-account-header">
            <h5 class="account-label"><?php echo htmlspecialchars($row['account_label']); ?></h5>
            <div class="card-tools">
              <button type="button" class="btn btn-sm btn-outline-primary edit_data" data-id="<?php echo $row['id'] ?>">Edit</button>
              <button type="button" class="btn btn-sm btn-outline-danger delete_data" data-id="<?php echo $row['id'] ?>">Delete</button>
            </div>
          </div>
          <div class="linked-account-body">
            <div class="detail-row">
              <div class="detail-label">Account Holder:</div>
              <div class="detail-value"><?php echo htmlspecialchars($row['account_holder_name']); ?></div>
            </div>
            <div class="detail-row">
              <div class="detail-label">Account Number:</div>
              <div class="detail-value"><?php echo htmlspecialchars($row['account_number']); ?></div>
            </div>
            <div class="detail-row">
              <div class="detail-label">Bank Name:</div>
              <div class="detail-value"><?php echo htmlspecialchars($row['bank_name']); ?></div>
            </div>
            <div class="detail-row">
              <div class="detail-label">Account Type:</div>
              <div class="detail-value"><?php echo htmlspecialchars($row['account_type']); ?></div>
            </div>
            <div class="detail-row">
              <div class="detail-label">Internal Account:</div>
              <div class="detail-value">
                <?php if($row['is_internal_bank'] == 1): ?>
                  <span class="badge badge-success px-2 rounded-pill">Yes</span>
                <?php else: ?>
                  <span class="badge badge-secondary px-2 rounded-pill">No</span>
                <?php endif; ?>
              </div>
            </div>
            
            <?php if(!empty($row['swift_bic'])): ?>
              <div class="detail-row">
                <div class="detail-label">SWIFT/BIC:</div>
                <div class="detail-value"><?php echo htmlspecialchars($row['swift_bic']); ?></div>
              </div>
            <?php endif; ?>

          </div>
        </div> <?php 
        endforeach;
      else:
      ?>
        <div class="alert alert-info text-center">
          No linked accounts found. Click "Link New Account" to add one.
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<script>
  // MODIFIED: A diagnostic log to confirm the script is loaded and running.
  console.log("manage_linked_account.php script loaded and executing.");

  $(function(){
    // MODIFIED: Corrected function parameter syntax
    function delete_linked_account(id, transaction_pin){
      start_loader();
      $.ajax({
        url:_base_url_+"classes/Master.php?f=delete_linked_account",
        method:"POST",
        data:{id: id, transaction_pin: transaction_pin},
        dataType:"json",
        error:err=>{
          console.log("AJAX Error (delete_linked_account):", err);
          alert_toast("An error occurred.",'error');
          end_loader(); 
        },
        success:function(resp){
          if(typeof resp == 'object' && resp.status == 'success'){
            location.reload();
          } else if(resp.status == 'failed' && resp.msg){
            alert_toast(resp.msg,'error');
            end_loader();
          } else {
            alert_toast("An error occurred.",'error');
            end_loader();
          }
        }
      })
    }

    // MODIFIED: Corrected function parameter syntax and added a fix for the modal link
    function uni_modal_prompt(title, message, callback, input_type = 'text') {
      var modal = `
        <div class="modal fade" id="prompt_modal" tabindex="-1" role="dialog" aria-labelledby="prompt_modal_label" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="prompt_modal_label">${title}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <p>${message}</p>
              <input type="${input_type}" class="form-control" id="prompt_input" autocomplete="off" />
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
              <button type="button" class="btn btn-primary" id="prompt_confirm_btn">Confirm</button>
            </div>
          </div>
        </div>
      </div>
    `;
    $('body').append(modal);
    $('#prompt_modal').modal('show');

    $('#prompt_confirm_btn').click(function() {
      var input_value = $('#prompt_input').val();
      $('#prompt_modal').modal('hide');
      $('#prompt_modal').on('hidden.bs.modal', function (e) {
        $(this).remove();
        callback(input_value);
      });
    });

    $('#prompt_modal').on('hidden.bs.modal', function (e) {
      $(this).remove();
    });
  }
</script>