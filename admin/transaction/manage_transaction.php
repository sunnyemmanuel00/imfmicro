<?php
// C:\xampp\htdocs\banking\admin\transaction\manage_transaction.php
require_once('../../config.php');

$transaction_data = [];
$is_pending = false;

if (isset($_GET['id'])) {
    try {
        $id = $_GET['id'];
        
        // Query to check if the transaction exists in the pending_transactions table
        $query_pending = "
            SELECT 
                pt.id,
                pt.amount,
                pt.status,
                pt.sender_id as account_id,
                pt.recipient_id as linked_account_id,
                pt.description as remarks,
                'transfer_external_debit' as transaction_type,
                -- Hardcoded value '3' for type, based on the transactions table screenshot
                3 as type,
                -- Generate a temporary transaction code for pending transactions
                'IMF-' || (SELECT TO_CHAR(NOW(), 'YYYYMMDD')) || '-' || MD5(RANDOM()::TEXT) as transaction_code
            FROM 
                pending_transactions pt
            WHERE 
                pt.id = ?
        ";
        
        $stmt_pending = $conn->prepare($query_pending);
        $stmt_pending->execute([$id]);
        $transaction_data = $stmt_pending->fetch(PDO::FETCH_ASSOC);

        if ($transaction_data) {
            $is_pending = true;
        } else {
            // If not found in pending, fetch from the main transactions table for editing completed ones
            $query_transactions = "
                SELECT 
                    t.id,
                    t.amount,
                    t.status,
                    t.account_id,
                    t.linked_account_id,
                    t.remarks,
                    t.transaction_type,
                    t.type,
                    t.transaction_code
                FROM 
                    transactions t
                WHERE 
                    t.id = ?
            ";
            $stmt_transactions = $conn->prepare($query_transactions);
            $stmt_transactions->execute([$id]);
            $transaction_data = $stmt_transactions->fetch(PDO::FETCH_ASSOC);
        }
        
    } catch (PDOException $e) {
        die("Error fetching transaction details: " . $e->getMessage());
    }
}
?>

<div class="container-fluid">
    <form action="" id="manage-transaction-form">
        <input type="hidden" name="id" value="<?php echo isset($transaction_data['id']) ? htmlspecialchars($transaction_data['id']) : ''; ?>">
        
        <!-- These hidden fields are critical for approving a pending transaction -->
        <input type="hidden" name="account_id" value="<?php echo isset($transaction_data['account_id']) ? htmlspecialchars($transaction_data['account_id']) : ''; ?>">
        <input type="hidden" name="linked_account_id" value="<?php echo isset($transaction_data['linked_account_id']) ? htmlspecialchars($transaction_data['linked_account_id']) : ''; ?>">
        <input type="hidden" name="transaction_type" value="<?php echo isset($transaction_data['transaction_type']) ? htmlspecialchars($transaction_data['transaction_type']) : ''; ?>">
        <input type="hidden" name="type" value="<?php echo isset($transaction_data['type']) ? htmlspecialchars($transaction_data['type']) : ''; ?>">
        
        <div class="form-group">
            <label for="transaction_code" class="control-label">Transaction Code</label>
            <input type="text" class="form-control" value="<?php echo isset($transaction_data['transaction_code']) ? htmlspecialchars($transaction_data['transaction_code']) : ''; ?>" readonly>
        </div>
        
        <div class="form-group">
            <label for="amount" class="control-label">Amount</label>
            <input type="number" step="0.01" class="form-control text-right" id="amount" name="amount" value="<?php echo isset($transaction_data['amount']) ? htmlspecialchars($transaction_data['amount']) : ''; ?>" required>
        </div>

        <div class="form-group">
            <label for="remarks" class="control-label">Remarks</label>
            <textarea name="remarks" id="remarks" cols="30" rows="3" class="form-control" required><?php echo isset($transaction_data['remarks']) ? htmlspecialchars($transaction_data['remarks']) : ''; ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="status" class="control-label">Status</label>
            <select name="status" id="status" class="custom-select" required>
                <option value="pending" <?php echo (isset($transaction_data['status']) && $transaction_data['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                <option value="completed" <?php echo (isset($transaction_data['status']) && $transaction_data['status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
                <option value="declined" <?php echo (isset($transaction_data['status']) && $transaction_data['status'] == 'declined') ? 'selected' : ''; ?>>Declined</option>
            </select>
        </div>

    </form>
</div>
<script>
    $(function(){
        $('#manage-transaction-form').submit(function(e){
            e.preventDefault();
            start_loader();
            $.ajax({
                url: _base_url_ + "classes/Master.php?f=save_transaction",
                data: new FormData($(this)[0]),
                cache: false,
                contentType: false,
                processData: false,
                method: 'POST',
                type: 'POST',
                dataType: 'json',
                error: err => {
                    console.log(err);
                    alert_toast("An error occurred.", 'error');
                    end_loader();
                },
                success: function(resp){
                    if(resp.status == 'success'){
                        location.reload();
                    } else if(resp.status == 'failed' && resp.msg){
                        alert_toast(resp.msg, 'error');
                    } else {
                        alert_toast("An error occurred.", 'error');
                    }
                    end_loader();
                }
            });
        });
    });
</script>
