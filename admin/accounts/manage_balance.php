<?php
require_once(__DIR__ . '/../../config.php');
if(isset($_GET['id']) && $_GET['id'] > 0){
    try {
        // Using a PDO prepared statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT * FROM `accounts` WHERE id = :id");
        $stmt->bindParam(':id', $_GET['id'], PDO::PARAM_INT);
        $stmt->execute();
        if($stmt->rowCount() > 0){
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            foreach($row as $k => $v){
                $$k=stripslashes($v);
            }
            // Concatenate name in PHP for display
            $name = trim($firstname . ' ' . $middlename . ' ' . $lastname);
        }
    } catch (PDOException $e) {
        // In a production environment, you would log this error and show a generic message.
        // For development, it's useful to see the error.
        die("Database error: " . $e->getMessage());
    }
}
?>
<div class="container-fluid">
    <div class="callout callout-info">
        <dl>
            <dt>Account Holder:</dt>
            <dd><?php echo isset($name) ? htmlspecialchars($name) : "" ?></dd>
            <dt>Account Number:</dt>
            <dd><?php echo isset($account_number) ? htmlspecialchars($account_number) : "" ?></dd>
        </dl>
    </div>
    <form id="balance-modal-form">
        <input type="hidden" name="account_id" value="<?php echo isset($id) ? htmlspecialchars($id) : '' ?>">
        <div class="form-group">
            <label for="transaction_type" class="control-label">Transaction Type</label>
            <select name="transaction_type" id="transaction_type" class="form-control form-control-sm" required>
                <option value="1">Credit (Deposit)</option>
                <option value="2">Debit (Transfer)</option>
            </select>
        </div>
        <div class="form-group">
            <label for="amount" class="control-label">Amount</label>
            <input type="number" step="any" min="0" name="amount" id="amount" class="form-control form-control-sm text-right" required>
        </div>
        <div class="form-group">
            <label for="remarks" class="control-label">Remarks/Notes</label>
            <input type="text" name="remarks" id="remarks" class="form-control form-control-sm" placeholder="e.g., Admin Deposit, Correction, etc." required>
        </div>
    </form>
</div>
<script>
    $(function(){
        $('#balance-modal-form').submit(function(e){
            e.preventDefault();
            start_loader();
            $('.err-msg').remove();
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
                        $('#balance-modal-form').prepend('<div class="alert alert-danger err-msg">'+resp.msg+'</div>')
                    } else {
                        $('#balance-modal-form').prepend('<div class="alert alert-danger err-msg">An unknown error occurred.</div>')
                    }
                    end_loader();
                }
            })
        })
    })
</script>
