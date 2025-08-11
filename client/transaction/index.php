<?php 
// Corrected path to config.php by going up two directories from the 'transaction' folder.
require_once(__DIR__ . '/../../config.php'); 

// This block was removed to stop the top-right alert message when navigating to this page.
/*
if($_settings->chk_flashdata('success')): ?>
<script>
    alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')
</script>
<?php endif;
*/
?>
<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title">Transaction History</h3>
    </div>
    <div class="card-body">
        <div class="container-fluid">
            <div class="table-responsive">
                <table class="table table-bordered table-stripped table-hover" id="indi-list">
                    <colgroup>
                        <col width="3%"> <col width="15%"> <col width="12%"> <col width="8%"> <col width="30%"> <col width="12%"> <col width="20%">
                    </colgroup>
                    <thead>
                        <tr>
                            <th class="text-center">#</th>
                            <th class="text-nowrap">Date & Time</th>
                            <th class="text-nowrap">Type</th>
                            <th class="text-nowrap">Status</th>
                            <th>Details</th>
                            <th class="text-nowrap">Amount</th>
                            <th class="text-nowrap">Balance After</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 1;
                        $combined_data = [];
                        $current_user_account_id = $_settings->userdata('account_id');
                        $current_user_acc_num = $_settings->userdata('account_number');

                        // Fetch all transactions from the 'transactions' table for the current user
                        try {
                            $qry_stmt = $conn->prepare('SELECT * FROM "transactions" WHERE account_id = ?');
                            $qry_stmt->execute([$current_user_account_id]);
                            while($row = $qry_stmt->fetch(PDO::FETCH_ASSOC)){
                                $combined_data[] = $row;
                            }
                            $qry_stmt->closeCursor();

                            // Fetch PENDING transfers from the 'pending_transactions' table for the current user
                            $qry_pending_stmt = $conn->prepare('
                                SELECT
                                    pt.id,
                                    pt.sender_id AS account_id,
                                    3 AS type,
                                    pt.amount,
                                    pt.description AS remarks,
                                    pt.timestamp AS date_created,
                                    pt.status,
                                    :sender_account_number AS sender_account_number,
                                    r.account_number AS receiver_account_number,
                                    \'transfer_external_pending\' AS transaction_type,
                                    json_build_object(
                                        \'recipient_bank_name\', r.bank_name,
                                        \'recipient_account_name\', r.account_name,
                                        \'recipient_account_number\', r.account_number
                                    ) AS meta_data
                                FROM
                                    "pending_transactions" pt
                                JOIN
                                    "recipients" r ON pt.recipient_id = r.id
                                WHERE
                                    pt.sender_id = :sender_id
                            ');

                            $qry_pending_stmt->bindValue(':sender_id', $current_user_account_id, PDO::PARAM_INT);
                            $qry_pending_stmt->bindValue(':sender_account_number', $current_user_acc_num);
                            $qry_pending_stmt->execute();

                            while($row = $qry_pending_stmt->fetch(PDO::FETCH_ASSOC)){
                                $combined_data[] = $row;
                            }
                            $qry_pending_stmt->closeCursor();
                        } catch (PDOException $e) {
                            echo "<tr><td colspan='7' class='text-center text-danger'>Database Error: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
                            $combined_data = [];
                        }

                        if (!empty($combined_data)):
                            // Sort the combined array by date in ASCENDING order (oldest first) to calculate balance
                            usort($combined_data, function($a, $b) {
                                return strtotime($a['date_created']) - strtotime($b['date_created']);
                            });

                            // Get the current actual balance of the user to start the calculation from
                            $account_qry = $conn->prepare('SELECT balance FROM accounts WHERE id = ?');
                            $account_qry->execute([$current_user_account_id]);
                            $current_account_balance = $account_qry->fetch(PDO::FETCH_ASSOC)['balance'];
                            
                            // Initialize balance tracking for the calculation loop
                            $calculated_balance = $current_account_balance;
                            $history_to_display = [];

                            // Iterate through the sorted data in reverse to calculate balance after each transaction
                            for ($j = count($combined_data) - 1; $j >= 0; $j--) {
                                $row = $combined_data[$j];
                                
                                $db_type = $row['type'];
                                $amount = $row['amount'];
                                $remarks = $row['remarks'];
                                $status_db = $row['status'] ?? 'completed';
                                $transaction_type_specific = $row['transaction_type'] ?? '';

                                // Calculate the balance that existed BEFORE this transaction
                                $balance_before_this_transaction = $calculated_balance;
                                if ($db_type == 1) { // Credit
                                    $balance_before_this_transaction -= $amount;
                                } else { // Debit or Transfer
                                    $balance_before_this_transaction += $amount;
                                }

                                // Store the transaction with its calculated balance
                                $row['balance_before'] = $balance_before_this_transaction;
                                $row['balance_after'] = $calculated_balance;
                                $history_to_display[] = $row;

                                // Update the running balance for the next (older) transaction in the loop
                                $calculated_balance = $balance_before_this_transaction;
                            }

                            // Now, display the history_to_display array which is already in reverse chronological order
                            foreach(array_reverse($history_to_display) as $row):
                                $db_type = $row['type'];
                                $amount = $row['amount'];
                                $remarks = $row['remarks'];
                                $status_db = $row['status'] ?? 'completed';
                                $balance_after = $row['balance_after'];

                                $transaction_status_badge_html = '';
                                switch ($status_db) {
                                    case 'completed':
                                        $transaction_status_badge_html = '<span class="badge badge-success px-2 rounded-pill">Completed</span>';
                                        break;
                                    case 'pending':
                                        $transaction_status_badge_html = '<span class="badge badge-warning px-2 rounded-pill">Pending</span>';
                                        break;
                                    case 'declined':
                                        $transaction_status_badge_html = '<span class="badge badge-danger px-2 rounded-pill">Declined</span>';
                                        break;
                                    default:
                                        $transaction_status_badge_html = '<span class="badge badge-secondary px-2 rounded-pill">Unknown</span>';
                                        break;
                                }
                        ?>
                        <tr>
                            <td class="text-center"><?php echo $i++; ?></td>
                            <td class="text-nowrap"><?php echo date("M d, Y h:i A", strtotime($row['date_created'])) ?></td>
                            <td class="text-nowrap">
                                <?php
                                    switch($row['type']){
                                        case 1:
                                            echo '<span class="badge badge-success"><i class="fa fa-arrow-down"></i> Credit</span>';
                                            break;
                                        case 2:
                                            echo '<span class="badge badge-danger"><i class="fa fa-arrow-up"></i> Debit</span>';
                                            break;
                                        case 3:
                                            echo '<span class="badge badge-warning"><i class="fa fa-exchange-alt"></i> Transfer</span>';
                                            break;
                                        default:
                                            echo '<span class="badge badge-secondary">Other</span>';
                                            break;
                                    }
                                ?>
                            </td>
                            <td class="text-center">
                                <?php echo $transaction_status_badge_html; ?>
                            </td>
                            <td><?php echo htmlspecialchars($remarks) ?></td>
                            <td class='text-right'>
                                <?php
                                    // Display amount based on transaction type and status
                                    if ($db_type == 1) { // Credit (Deposit/Incoming Transfer)
                                        echo '<span class="text-success font-weight-bold">+ $' . number_format($amount, 2) . '</span>';
                                    } else { // Debit (Withdrawal/Outgoing Transfer)
                                        echo '<span class="text-danger font-weight-bold">- $' . number_format($amount, 2) . '</span>';
                                    }
                                ?>
                            </td>
                            <td class='text-right text-nowrap'>
                                <?php echo '$' . number_format($balance_after, 2); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">No transactions found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script>
    $(document).ready(function(){
        $('#indi-list').dataTable({
            "order": [[1, "desc"]] // Order by Date & Time (column 1) in descending order
        });
        $('.dataTable td,.dataTable th').addClass('py-1 px-2 align-middle')
    })
</script>