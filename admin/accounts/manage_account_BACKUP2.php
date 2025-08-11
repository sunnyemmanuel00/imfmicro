<?php 
// Corrected query for PostgreSQL: uses prepared statements and double quotes
if(isset($_GET['id']) && $_GET['id'] > 0){
    try {
        $stmt = $conn->prepare('SELECT * FROM "accounts" WHERE id = :id');
        $stmt->bindParam(':id', $_GET['id'], PDO::PARAM_INT);
        $stmt->execute();
        
        if($stmt->rowCount() > 0){
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            foreach($row as $k => $v){
                $$k = stripslashes($v);
            }
        }
    } catch (PDOException $e) {
        // Display a user-friendly error message
        echo "Fatal error: Uncaught PDOException: " . htmlspecialchars($e->getMessage());
    }
}
?>
<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title"><?php echo isset($id) ? "Update Account Details & Status" : "Create New Client Account"; ?></h3>
    </div>
    <div class="card-body">
        <form id="account-form">
            <input type="hidden" name="id" value='<?php echo isset($id)? $id : '' ?>'>
            
            <h5 class="text-primary">Personal Information</h5>
            <hr>
            <div class="row">
                <div class="form-group col-md-4">
                    <label for="firstname" class='control-label'>First Name</label>
                    <input type="text" class="form-control form-control-sm" name="firstname" value="<?php echo isset($firstname)? $firstname : '' ?>" required>
                </div>
                <div class="form-group col-md-4">
                    <label for="middlename" class='control-label'>Middle Name (Optional)</label>
                    <input type="text" class="form-control form-control-sm" name="middlename" value="<?php echo isset($middlename)? $middlename : '' ?>">
                </div>
                <div class="form-group col-md-4">
                    <label for="lastname" class='control-label'>Last Name</label>
                    <input type="text" class="form-control form-control-sm" name="lastname" value="<?php echo isset($lastname)? $lastname : '' ?>" required>
                </div>
            </div>

            <div class="row">
                <div class="form-group col-md-4">
                    <label for="gender" class='control-label'>Gender</label>
                    <select class="form-control form-control-sm" name="gender" required>
                        <option <?php echo (isset($gender) && $gender == 'Male') ? 'selected' : '' ?>>Male</option>
                        <option <?php echo (isset($gender) && $gender == 'Female') ? 'selected' : '' ?>>Female</option>
                    </select>
                </div>
                <div class="form-group col-md-4">
                    <label for="phone_number" class='control-label'>Phone Number</label>
                    <input type="text" class="form-control form-control-sm" name="phone_number" value="<?php echo isset($phone_number)? $phone_number : '' ?>" required>
                </div>
                <div class="form-group col-md-4">
                    <label for="date_of_birth" class='control-label'>Date of Birth</label>
                    <input type="date" class="form-control form-control-sm" name="date_of_birth" value="<?php echo isset($date_of_birth)? $date_of_birth : '' ?>" required>
                </div>
            </div>

            <h5 class="text-primary mt-4">Account & Login Details</h5>
            <hr>
            <div class="row">
                <div class="form-group col-md-6">
                    <label class="control-label">Account Number</label>
                    <input type="text" class="form-control form-control-sm" name="account_number" value="<?php echo isset($account_number)? $account_number : '' ?>" required>
                </div>
                <div class="form-group col-md-6">
                    <label class="control-label">Email (Used for Client Login)</label>
                    <input type="email" class="form-control form-control-sm" name="email" value="<?php echo isset($email)? $email : '' ?>" required>
                </div>
            </div>
            
            <div class="row">
                <div class="form-group col-md-4">
                    <label class="control-label">Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control form-control-sm" name="password" value="" autocomplete="new-password">
                        <div class="input-group-append">
                            <button class="btn btn-outline-primary btn-sm" type="button" id="generate_pass">Generate</button>
                        </div>
                    </div>
                    <small class="text-info"><i>Leave blank to keep current password.</i></small>
                </div>
                <div class="form-group col-md-4">
                    <label class="control-label">Transaction PIN</label>
                    <input type="text" class="form-control form-control-sm" name="transaction_pin" value="<?php echo isset($transaction_pin) ? $transaction_pin : '' ?>" required>
                    <small class="text-info"><i>Client's 5-digit PIN for transactions.</i></small>
                </div>
                <div class="form-group col-md-4">
                    <label for="status" class="control-label">Account Status</label>
                    <input type="text" name="status" id="status" class="form-control form-control-sm" value="<?php echo isset($status) ? htmlspecialchars($status) : 'Pending'; ?>" required>
                    <small class="text-info"><i>e.g., Active, Pending, On Hold</i></small>
                </div>
            </div>

        </form>
    </div>
    <div class="card-footer">
        <div class="d-flex w-100">
            <button form="account-form" class="btn btn-primary mr-2">Save</button>
            <a href="./?page=accounts" class="btn btn-default">Cancel</a>
        </div>
    </div>
</div>

<?php if(isset($id)): ?>
<div class="card card-outline card-success">
    <div class="card-header">
        <h3 class="card-title">Balance Management (Deposit / Transfer)</h3>
    </div>
    <div class="card-body">
        <div class="callout callout-info">
            <h4 class="mb-0">Current Balance: <strong>$<?php echo number_format(isset($balance) ? $balance : 0, 2); ?></strong></h4>
        </div>
        <hr>
        <form id="balance-form">
            <input type="hidden" name="account_id" value="<?php echo $id ?>">
            <div class="row">
                <div class="form-group col-md-4">
                    <label for="transaction_type" class="control-label">Transaction Type</label>
                    <select name="transaction_type" id="transaction_type" class="form-control" required>
                        <option value="1">Credit (Deposit)</option>
                        <option value="2">Debit (Transfer)</option>
                    </select>
                </div>
                <div class="form-group col-md-4">
                    <label for="amount" class="control-label">Amount</label>
                    <input type="number" step="any" min="0" name="amount" id="amount" class="form-control text-right" required>
                </div>
                <div class="form-group col-md-4">
                    <label for="remarks" class="control-label">Remarks/Notes</label>
                    <input type="text" name="remarks" id="remarks" class="form-control" placeholder="e.g., Admin manual deposit" required>
                </div>
            </div>
        </form>
    </div>
    <div class="card-footer">
        <button form="balance-form" class="btn btn-success">Post Transaction</button>
    </div>
</div>
<script>
// AJAX script for the Balance Management form
$(function(){
    $('#balance-form').submit(function(e){
        e.preventDefault();
        start_loader();
        $.ajax({
            url:_base_url_+"classes/Master.php?f=admin_adjust_balance",
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            error: err => {
                console.log(err);
                alert_toast("An error occurred.", "error");
                end_loader();
            },
            success: function(resp){
                if(resp.status == 'success'){
                    alert_toast(resp.msg, 'success');
                    setTimeout(function(){
                        location.reload();
                    }, 1500);
                } else if(!!resp.msg){
                    var msg = $('<div class="err_msg"><div class="alert alert-danger">'+resp.msg+'</div></div>');
                    $('#account-form').prepend(msg); 
                    msg.show('slow');
                    end_loader();
                } else {
                    alert_toast("An unknown error occurred.", "error");
                    end_loader();
                }
            }
        })
    })
})
</script>
<?php endif; ?>

<script>
 $(function(){
  $('#generate_pass').click(function(){
    var pass = Math.random().toString(36).slice(-8);
    $('[name="password"]').val(pass).attr('type','text');
  });
  
  $('#account-form').submit(function(e){
    e.preventDefault();
    start_loader();
    $('.err_msg').remove();
    $.ajax({
    url:_base_url_+'classes/Master.php?f=save_account',
    method:'POST',
    data:$(this).serialize(),
    dataType:'json',
    error:err=>{
      console.log(err); 
          alert_toast("An error occured","error"); 
          end_loader();
    },
    success:function(resp){
      if(resp.status == 'success'){
      location.href="./?page=accounts";
      }else if(!!resp.msg){
      var msg = $('<div class="err_msg"><div class="alert alert-danger">'+resp.msg+'</div></div>');
      $('#account-form').prepend(msg); 
      msg.show('slow');
      }else{
      alert_toast('An error occured',"error");
      console.log(resp);
      }
      end_loader();
    }
    })
  })
 })
</script>
