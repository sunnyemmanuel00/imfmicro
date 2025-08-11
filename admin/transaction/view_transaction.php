<?php
require_once('../../config.php');

$transaction_data = null;

if (isset($_GET['id'])) {
    try {
        // This single query should handle all transaction types by joining accounts table for sender and optionally for receiver
        $query = "
            SELECT 
                t.*, 
                s.account_number as sender_account_number,
                CONCAT(s.firstname, ' ', s.lastname) as sender_name,
                r.account_number as receiver_account_number,
                CONCAT(r.firstname, ' ', r.lastname) as receiver_name
            FROM 
                transactions t
            INNER JOIN 
                accounts s ON t.account_id = s.id
            LEFT JOIN 
                accounts r ON t.receiver_account_id = r.id
            WHERE 
                t.id = ?
        ";
        $stmt = $conn->prepare($query);
        $stmt->execute([$_GET['id']]);
        $transaction_data = $stmt->fetch(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        die("Error fetching transaction details: " . $e->getMessage());
    }
}
?>
<div class="container-fluid">
    <?php if ($transaction_data): ?>
        <dl>
            <dt class="text-muted">Transaction Code</dt>
            <dd class="pl-4"><?php echo htmlspecialchars($transaction_data['transaction_code']); ?></dd>
            <dt class="text-muted">Date & Time</dt>
            <dd class="pl-4"><?php echo date("F d, Y h:i A", strtotime($transaction_data['date_created'])); ?></dd>
            <dt class="text-muted">Amount</dt>
            <dd class="pl-4"><?php echo number_format($transaction_data['amount'], 2); ?></dd>
            <dt class="text-muted">Type</dt>
            <dd class="pl-4"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $transaction_data['transaction_type']))); ?></dd>
            <dt class="text-muted">Status</dt>
            <dd class="pl-4"><?php echo htmlspecialchars(ucwords($transaction_data['status'])); ?></dd>
            <hr>
            <dt class="text-muted">Sender</dt>
            <dd class="pl-4"><?php echo htmlspecialchars($transaction_data['sender_name'] . ' - ' . $transaction_data['sender_account_number']); ?></dd>
            <dt class="text-muted">Receiver</dt>
            <dd class="pl-4">
                <?php 
                // Display receiver info if available (for internal transfers)
                if (!empty($transaction_data['receiver_name'])) {
                    echo htmlspecialchars($transaction_data['receiver_name'] . ' - ' . $transaction_data['receiver_account_number']);
                } else {
                    echo "External/Bank-level Transaction";
                }
                ?>
            </dd>
            <dt class="text-muted">Remarks</dt>
            <dd class="pl-4"><?php echo htmlspecialchars($transaction_data['remarks']); ?></dd>
        </dl>
    <?php else: ?>
        <p class="text-center text-danger">Transaction details not found.</p>
    <?php endif; ?>
</div>
<div class="modal-footer display p-0 m-0">
    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
</div>
<style>
    #uni_modal .modal-footer { display: none !important; }
    #uni_modal .modal-footer.display { display: flex !important; }
</style>