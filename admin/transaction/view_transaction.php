<?php
// C:\xampp\htdocs\banking\admin\transaction\view_transaction.php
require_once('../../config.php');

$transaction_data = null;

if (isset($_GET['id'])) {
    try {
        // Check if the transaction is pending based on the 'type' parameter
        $is_pending = isset($_GET['type']) && $_GET['type'] === 'pending';
        
        $id = $_GET['id'];
        $query = "";
        
        if ($is_pending) {
            // Query for pending transactions from the 'pending_transactions' table
            // We use aliases to make the column names consistent with the 'transactions' table query
            $query = "
                SELECT 
                    pt.id,
                    pt.amount,
                    pt.status,
                    pt.timestamp as date_created,
                    'N/A' as transaction_code,
                    'transfer_external_debit' as transaction_type,
                    pt.sender_id as account_id,
                    a.account_number as sender_account_number,
                    CONCAT(a.firstname, ' ', a.lastname) as sender_name,
                    pt.recipient_id as receiver_account_id,
                    r.account_number as receiver_account_number,
                    CONCAT(r.firstname, ' ', r.lastname) as receiver_name,
                    pt.description as remarks
                FROM 
                    pending_transactions pt
                INNER JOIN 
                    accounts a ON pt.sender_id = a.id
                LEFT JOIN 
                    accounts r ON pt.recipient_id = r.id
                WHERE 
                    pt.id = ?
            ";
        } else {
            // Original query for completed transactions from the 'transactions' table
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
                    accounts r ON t.linked_account_id = r.id
                WHERE 
                    t.id = ?
            ";
        }

        $stmt = $conn->prepare($query);
        $stmt->execute([$id]);
        $transaction_data = $stmt->fetch(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        die("Error fetching transaction details: " . $e->getMessage());
    }
}
?>
<div class="container-fluid">
    <?php if ($transaction_data): ?>
        <style>
            .transaction-details-row {
                display: flex;
                flex-wrap: wrap;
                margin-bottom: 10px;
            }
            .detail-item {
                flex: 1 1 50%;
                padding: 5px;
                box-sizing: border-box;
            }
            .detail-item dt {
                font-weight: bold;
                color: #555;
            }
            .detail-item dd {
                margin-left: 0;
                padding-left: 10px;
                border-left: 2px solid #007bff;
            }
        </style>
        <div class="card p-3">
            <div class="transaction-details-row">
                <div class="detail-item">
                    <dt class="text-muted">Transaction Code</dt>
                    <dd class="pl-4"><?php echo htmlspecialchars($transaction_data['transaction_code'] ?? 'N/A'); ?></dd>
                </div>
                <div class="detail-item">
                    <dt class="text-muted">Date & Time</dt>
                    <dd class="pl-4"><?php echo date("F d, Y h:i A", strtotime($transaction_data['date_created'])); ?></dd>
                </div>
            </div>
            <hr>
            <div class="transaction-details-row">
                <div class="detail-item">
                    <dt class="text-muted">Amount</dt>
                    <dd class="pl-4"><?php echo number_format($transaction_data['amount'], 2); ?></dd>
                </div>
                <div class="detail-item">
                    <dt class="text-muted">Type</dt>
                    <dd class="pl-4"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $transaction_data['transaction_type']))); ?></dd>
                </div>
            </div>
            <div class="transaction-details-row">
                <div class="detail-item">
                    <dt class="text-muted">Status</dt>
                    <dd class="pl-4"><?php echo htmlspecialchars(ucwords($transaction_data['status'])); ?></dd>
                </div>
                <div class="detail-item">
                    <dt class="text-muted">Sender</dt>
                    <dd class="pl-4"><?php echo htmlspecialchars($transaction_data['sender_name'] . ' - ' . $transaction_data['sender_account_number']); ?></dd>
                </div>
            </div>
            <hr>
            <div class="transaction-details-row">
                <div class="detail-item">
                    <dt class="text-muted">Receiver</dt>
                    <dd class="pl-4">
                        <?php 
                        if (!empty($transaction_data['receiver_name'])) {
                            echo htmlspecialchars($transaction_data['receiver_name'] . ' - ' . $transaction_data['receiver_account_number']);
                        } else {
                            echo "External/Bank-level Transaction";
                        }
                        ?>
                    </dd>
                </div>
                <div class="detail-item">
                    <dt class="text-muted">Remarks</dt>
                    <dd class="pl-4"><?php echo htmlspecialchars($transaction_data['remarks'] ?? ''); ?></dd>
                </div>
            </div>
        </div>
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
