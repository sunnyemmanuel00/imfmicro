<?php
// REMOVED: No alert_toast from flashdata on this page as per user request.
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
                        <col width="3%">    <col width="15%">   <col width="12%">   <col width="8%">    <col width="30%">   <col width="12%">   <col width="20%">   </colgroup>
                    <thead>
                        <tr>
                            <th class="text-center">#</th>
                            <th class="text-nowrap">Date & Time</th>
                            <th class="text-nowrap">Type</th>
                            <th class="text-nowrap">Status</th> <th>Details</th>
                            <th class="text-nowrap">Amount</th>
                            <th class="text-nowrap">Balance After</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $i = 1;
                        // Get the current actual balance of the user's account
                        // It's important to fetch the live balance before calculating running balance
                        $current_account_balance_qry = $conn->query("SELECT balance FROM `accounts` WHERE id = '".$_settings->userdata('account_id')."'");
                        $current_account_balance = ($current_account_balance_qry->num_rows > 0) ? $current_account_balance_qry->fetch_assoc()['balance'] : 0;
                        
                        // Fetch all transactions for the current user, ordered by date_created DESC (most recent first)
                        $transactions_data = [];
                        $qry = $conn->query("SELECT t.* FROM `transactions` t WHERE t.account_id = '".$_settings->userdata('account_id')."' ORDER BY unix_timestamp(t.date_created) DESC");
                        while($row = $qry->fetch_assoc()){
                            $transactions_data[] = $row;
                        }

                        // The running balance needs to be calculated by iterating in reverse order (from oldest to newest transaction)
                        // to get correct 'Balance After'. Then reverse the array for display.
                        $transactions_for_calc = array_reverse($transactions_data);
                        $calculated_transactions = [];
                        
                        // Calculate the balance *before* the first transaction in the displayed list.
                        // This assumes the `transactions` table captures all movements from a hypothetical initial state.
                        // If the very first transaction shown is the first ever, this starts from 0.
                        // For display purposes, it's safer to reconstruct.
                        $temp_running_balance_calc = $current_account_balance;
                        $user_account_number_for_calc = $_settings->userdata('account_number');

                        // Loop backwards through the *display-ordered* transactions to calculate the running balance backwards from the current.
                        // This allows us to get the balance *before* each transaction for the "Balance After" column.
                        foreach ($transactions_data as $row_rev) {
                            $amount_rev = $row_rev['amount'];
                            $type_rev = $row_rev['type'];
                            $transaction_type_specific_rev = $row_rev['transaction_type'] ?? '';
                            $sender_acc_num_rev = $row_rev['sender_account_number'] ?? '';
                            $receiver_acc_num_rev = $row_rev['receiver_account_number'] ?? '';

                            if ($type_rev == 1) { // Deposit
                                if ($transaction_type_specific_rev == 'deposit_external_pending' || $transaction_type_specific_rev == 'deposit_external_declined') {
                                    // These types don't affect current balance until 'completed', so they don't affect reverse calculation
                                } else {
                                    $temp_running_balance_calc -= $amount_rev; // If it was a deposit, it means balance was lower before this txn
                                }
                            } elseif ($type_rev == 2) { // Withdrawal
                                $temp_running_balance_calc += $amount_rev; // If it was a withdrawal, balance was higher before this txn
                            } elseif ($type_rev == 3) { // Transfer
                                if ($sender_acc_num_rev == $user_account_number_for_calc) { // Outgoing transfer from current user
                                    if ($transaction_type_specific_rev == 'transfer_external_pending') {
                                        // It was debited, so refund it back to get previous balance
                                        $temp_running_balance_calc += $amount_rev;
                                    } else if ($transaction_type_specific_rev == 'transfer_external_declined') {
                                        // Debited, then refunded. It doesn't affect the final balance.
                                        // The 'refund' is its own entry, so the *debit* itself is the one to reverse.
                                        $temp_running_balance_calc += $amount_rev;
                                    } else { // internal_transfer_outgoing, transfer_to_linked_account (if internal)
                                        $temp_running_balance_calc += $amount_rev;
                                    }
                                } else if ($receiver_acc_num_rev == $user_account_number_for_calc) { // Incoming transfer to current user
                                    $temp_running_balance_calc -= $amount_rev; // If it was an incoming transfer, balance was lower before this txn
                                }
                            }
                        }
                        $running_balance = $temp_running_balance_calc; // This is the calculated balance before the oldest transaction in the fetched set


                        foreach($transactions_for_calc as $row): // Loop through the reversed (chronological) order for correct 'Balance After'
                            $amount = $row['amount'];
                            $db_type = $row['type']; 
                            $remarks = $row['remarks'];
                            $amount_class = ''; 
                            $transaction_display_type = ''; 
                            $transaction_details = ''; 
                            $icon = ''; 
                            $transaction_status_badge_html = ''; // Initialize status badge HTML
                            
                            $transaction_code = $row['transaction_code'] ?? '';
                            $sender_acc_num = $row['sender_account_number'] ?? '';
                            $receiver_acc_num = $row['receiver_account_number'] ?? '';
                            $transaction_type_specific = $row['transaction_type'] ?? '';
                            $current_user_acc_num = $_settings->userdata('account_number');
                            $meta_data = json_decode($row['meta_data'] ?? '{}', true);
                            $original_remarks = $remarks; // Store original remarks to compare against

                            // Determine actual current balance for 'Balance After' for this row
                            // Update running_balance based on the type of transaction
                            if ($db_type == 1) { // Cash In (Deposit)
                                $icon = '<i class="fas fa-arrow-alt-circle-down text-success"></i> '; 
                                $amount_class = 'text-success'; 
                                
                                if ($transaction_type_specific == 'internal_transfer_incoming') {
                                    // FIXED: Prioritized and improved handling for incoming internal transfers, ensuring it's not "Cash Deposit"
                                    $running_balance += $amount;
                                    $transaction_display_type = "Internal Transfer";
                                    $transaction_details = "Incoming Transfer from Account: ";
                                    if (isset($meta_data['sender_account_name']) && !empty($meta_data['sender_account_name'])) {
                                        $transaction_details .= $meta_data['sender_account_name'];
                                    } else if (!empty($sender_acc_num)) {
                                        $transaction_details .= $sender_acc_num;
                                    } else {
                                        $transaction_details .= "Unknown Sender";
                                    }
                                    if (isset($meta_data['narration']) && !empty($meta_data['narration'])) {
                                        $transaction_details .= " (" . $meta_data['narration'] . ")";
                                    } else {
                                        // Attempt to extract narration from original_remarks if it looks like a simple addition
                                        // Ensure it doesn't just re-add the "Transfer from Account:" part
                                        $temp_remark_check = str_ireplace("Incoming Transfer from Account: " . $sender_acc_num, "", $original_remarks);
                                        $temp_remark_check = str_ireplace("Transfer from Account: " . $sender_acc_num, "", $temp_remark_check);
                                        $temp_remark_check = trim(preg_replace('/\s*\([^)]*\)\s*$/', '', $temp_remark_check)); // Remove any remaining (Account: XXX)
                                        
                                        if(!empty($temp_remark_check) && $temp_remark_check !== $sender_acc_num && strpos($temp_remark_check, 'Transfer from') === false) {
                                            $transaction_details .= " (" . $temp_remark_check . ")";
                                        }
                                    }
                                    $transaction_status_badge_html = '<span class="badge badge-success">Completed</span>';
                                } else if ($transaction_type_specific == 'deposit_external_pending') {
                                    $transaction_display_type = "External Deposit";
                                    $transaction_details = "Deposit Request from External Account: ";
                                    if (isset($meta_data['source_account_number_linked'])) $transaction_details .= $meta_data['source_account_number_linked'];
                                    if (isset($meta_data['source_bank_name_linked'])) $transaction_details .= " (" . $meta_data['source_bank_name_linked'] . ")";
                                    $transaction_status_badge_html = '<span class="badge badge-warning">Pending</span>';
                                    // Balance doesn't change for pending external deposit
                                } else if ($transaction_type_specific == 'deposit_external_completed') {
                                    $running_balance += $amount;
                                    $transaction_display_type = "External Deposit";
                                    $transaction_details = "Deposit from External Account: ";
                                    if (isset($meta_data['source_account_number_linked'])) $transaction_details .= $meta_data['source_account_number_linked'];
                                    if (isset($meta_data['source_bank_name_linked'])) $transaction_details .= " (" . $meta_data['source_bank_name_linked'] . ")";
                                    $transaction_status_badge_html = '<span class="badge badge-success">Completed</span>';
                                } else if ($transaction_type_specific == 'deposit_external_declined') {
                                    $transaction_display_type = "External Deposit";
                                    $transaction_details = "External Deposit Request Declined: ";
                                    if (isset($meta_data['source_account_number_linked'])) $transaction_details .= $meta_data['source_account_number_linked'];
                                    if (isset($meta_data['source_bank_name_linked'])) $transaction_details .= " (" . $meta_data['source_bank_name_linked'] . ")";
                                    $transaction_status_badge_html = '<span class="badge badge-danger">Declined</span>';
                                    // Balance doesn't change for declined deposit
                                } else if ($transaction_type_specific == 'cash_deposit') { // Explicitly handle 'cash_deposit'
                                    $running_balance += $amount;
                                    $transaction_display_type = "Deposit";
                                    $transaction_details = "Cash Deposit";
                                    $transaction_status_badge_html = '<span class="badge badge-success">Completed</span>';
                                } else { // Fallback for any other type=1 not specifically handled (could be old data)
                                    $running_balance += $amount;
                                    $transaction_display_type = "Deposit";
                                    $transaction_details = $original_remarks; // Use raw remarks as a fallback
                                    $transaction_status_badge_html = '<span class="badge badge-success">Completed</span>';
                                }
                            } elseif ($db_type == 2) { // Withdraw
                                $running_balance -= $amount;
                                $amount_class = 'text-danger'; 
                                $icon = '<i class="fas fa-arrow-alt-circle-up text-danger"></i> '; 
                                $transaction_display_type = "Withdrawal";
                                $transaction_details = "Cash Withdrawal";
                                $transaction_status_badge_html = '<span class="badge badge-success">Completed</span>';
                            } elseif ($db_type == 3) { // Transfer
                                $icon = '<i class="fas fa-exchange-alt text-warning"></i> '; 
                                
                                if ($transaction_type_specific == 'internal_transfer_outgoing') {
                                    $running_balance -= $amount;
                                    $amount_class = 'text-danger';
                                    $transaction_display_type = "Internal Transfer";
                                    $transaction_details = "Outgoing Transfer to Account: ";
                                    if (isset($meta_data['receiver_account_name']) && !empty($meta_data['receiver_account_name'])) {
                                        $transaction_details .= $meta_data['receiver_account_name'];
                                    } else if (!empty($receiver_acc_num)) {
                                        $transaction_details .= $receiver_acc_num;
                                    } else {
                                        $transaction_details .= "Unknown Recipient";
                                    }
                                    if (isset($meta_data['narration']) && !empty($meta_data['narration'])) {
                                        $transaction_details .= " (" . $meta_data['narration'] . ")";
                                    } else {
                                        // Attempt to extract narration from original_remarks if it looks like a simple addition
                                        $temp_remark_check = str_ireplace("Outgoing Transfer to Account: " . $receiver_acc_num, "", $original_remarks);
                                        $temp_remark_check = str_ireplace("Transfer to Account: " . $receiver_acc_num, "", $temp_remark_check);
                                        $temp_remark_check = trim(preg_replace('/\s*\([^)]*\)\s*$/', '', $temp_remark_check)); // Remove any remaining (Account: XXX)
                                        
                                        if(!empty($temp_remark_check) && $temp_remark_check !== $receiver_acc_num && strpos($temp_remark_check, 'Transfer to') === false) {
                                            $transaction_details .= " (" . $temp_remark_check . ")";
                                        }
                                    }
                                    $transaction_status_badge_html = '<span class="badge badge-success">Completed</span>';
                                } else if ($transaction_type_specific == 'transfer_external_pending') {
                                    $running_balance -= $amount; // Debited immediately
                                    $amount_class = 'text-danger';
                                    $transaction_display_type = "External Transfer";
                                    $transaction_details = "External Transfer Request to: ";
                                    if (isset($meta_data['recipient_account_name'])) $transaction_details .= $meta_data['recipient_account_name'];
                                    if (isset($meta_data['recipient_bank_name'])) $transaction_details .= " (" . $meta_data['recipient_bank_name'] . ")";
                                    if (isset($meta_data['recipient_account_number'])) $transaction_details .= " (Acc: " . $meta_data['recipient_account_number'] . ")";
                                    if (isset($meta_data['narration']) && !empty($meta_data['narration'])) {
                                        $transaction_details .= " [" . $meta_data['narration'] . "]";
                                    }
                                    $transaction_status_badge_html = '<span class="badge badge-warning">Pending</span>';
                                } else if ($transaction_type_specific == 'transfer_external_completed') {
                                    $running_balance -= $amount; // Already debited and completed
                                    $amount_class = 'text-danger';
                                    $transaction_display_type = "External Transfer";
                                    $transaction_details = "External Transfer Completed to: ";
                                    if (isset($meta_data['recipient_account_name'])) $transaction_details .= $meta_data['recipient_account_name'];
                                    if (isset($meta_data['recipient_bank_name'])) $transaction_details .= " (" . $meta_data['recipient_bank_name'] . ")";
                                    if (isset($meta_data['recipient_account_number'])) $transaction_details .= " (Acc: " . $meta_data['recipient_account_number'] . ")";
                                     if (isset($meta_data['narration']) && !empty($meta_data['narration'])) {
                                        $transaction_details .= " [" . $meta_data['narration'] . "]";
                                    }
                                    $transaction_status_badge_html = '<span class="badge badge-success">Completed</span>';
                                } else if ($transaction_type_specific == 'transfer_external_declined') {
                                    // The amount was debited, then refunded. So, effectively no change to final historical balance here.
                                    $amount_class = 'text-secondary'; // Indicate it was a debited amount but then resolved
                                    $transaction_display_type = "External Transfer";
                                    $transaction_details = "External Transfer Declined for: ";
                                    if (isset($meta_data['recipient_account_name'])) $transaction_details .= $meta_data['recipient_account_name'];
                                    if (isset($meta_data['recipient_bank_name'])) $transaction_details .= " (" . $meta_data['recipient_bank_name'] . ")";
                                    if (isset($meta_data['recipient_account_number'])) $transaction_details .= " (Acc: " . $meta_data['recipient_account_number'] . ")";
                                    $transaction_details .= " (Refunded)";
                                     if (isset($meta_data['narration']) && !empty($meta_data['narration'])) {
                                        $transaction_details .= " [" . $meta_data['narration'] . "]";
                                    }
                                    $transaction_status_badge_html = '<span class="badge badge-danger">Declined</span>';
                                } else { // Fallback for old 'type=3' not being explicit (Removed "Legacy" cases)
                                    // Assume these were completed transfers if no other status is available
                                    $transaction_display_type = "Transfer"; 
                                    $transaction_status_badge_html = '<span class="badge badge-success">Completed</span>'; 
                                    
                                    // Check if original remarks start with "Outgoing Transfer to Account:" or "Incoming Transfer from Account:"
                                    // If so, use the full remark, otherwise construct a generic one.
                                    if (strpos($original_remarks, 'Outgoing Transfer to Account:') === 0 || strpos($original_remarks, 'Incoming Transfer from Account:') === 0) {
                                        if ($sender_acc_num == $current_user_acc_num) { // Outgoing
                                            $running_balance -= $amount;
                                            $amount_class = 'text-danger'; 
                                        } else if ($receiver_acc_num == $current_user_acc_num) { // Incoming
                                            $running_balance += $amount;
                                            $amount_class = 'text-success'; 
                                            $icon = '<i class="fas fa-arrow-alt-circle-down text-success"></i> '; 
                                        }
                                        $transaction_details = $original_remarks; // Use the original remark if it's structured
                                    } else {
                                        if ($sender_acc_num == $current_user_acc_num) {
                                            $running_balance -= $amount;
                                            $amount_class = 'text-danger'; 
                                            $transaction_details = "Outgoing Transfer to: " . $receiver_acc_num;
                                        } else if ($receiver_acc_num == $current_user_acc_num) {
                                            $running_balance += $amount;
                                            $amount_class = 'text-success'; 
                                            $icon = '<i class="fas fa-arrow-alt-circle-down text-success"></i> '; 
                                            $transaction_details = "Incoming Transfer from: " . $sender_acc_num;
                                        } else {
                                            $transaction_details = $original_remarks; // As a final fallback
                                        }
                                    }
                                }
                                
                            } else { // Fallback for types not explicitly recognized
                                $transaction_display_type = "Other Transaction";
                                $transaction_details = $remarks; // Use raw remarks as a last resort
                                $transaction_status_badge_html = '<span class="badge badge-secondary">Unknown</span>';
                            }

                            // Final check: If transaction_details is still empty, use original remarks as ultimate fallback
                            if (empty($transaction_details) && !empty($original_remarks)) {
                                $transaction_details = $original_remarks;
                            }
                            
                            $balance_for_this_row = $running_balance; // The balance after this specific transaction.
                        ?>
 <tr>
    <td class="text-center"><?php echo $i++; ?></td>
    <td class="text-nowrap"><?php echo date("M d, Y h:i A", strtotime($row['date_created'])) ?></td>
    <td class="text-nowrap">
        <?php 
            // This new logic correctly identifies the transaction type
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
    <td class="text-center"><span class="badge badge-success px-2 rounded-pill">Completed</span></td>
    
    <td><?php echo htmlspecialchars($row['remarks']) ?></td>
    
    <td class='text-right'>
        <?php 
            if ($row['type'] == 1) { // Type 1 is always a Credit
                echo '<span class="text-success font-weight-bold">+ $' . number_format($row['amount'], 2) . '</span>';
            } else { // All other types are Debit
                echo '<span class="text-danger font-weight-bold">- $' . number_format($row['amount'], 2) . '</span>';
            }
        ?>
    </td>
    <td class='text-right text-nowrap'>
        <?php 
            // The running balance calculation is preserved and used here
            echo '$USD ' . number_format($balance_for_this_row, 2);
        ?>
    </td>
</tr>
                        <?php endforeach; ?>
                        <?php if(empty($transactions_data)): ?>
                            <tr>
                                <td colspan="7" class="text-center">No transactions found.</td> </tr>
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