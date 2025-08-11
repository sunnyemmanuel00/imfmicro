<?php
require_once('../../config.php');

$transaction_data = [];
if (isset($_GET['id'])) {
    try {
        $stmt = $conn->prepare("SELECT * FROM transactions WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $transaction_data = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Handle error, maybe log it or display a message
        die("Error fetching transaction details: " . $e->getMessage());
    }
}
?>

<div class="container-fluid">
    <form action="" id="manage-transaction-form">
        <input type="hidden" name="id" value="<?php echo isset($transaction_data['id']) ? htmlspecialchars($transaction_data['id']) : ''; ?>">
        
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