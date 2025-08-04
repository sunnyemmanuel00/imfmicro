<?php if($_settings->chk_flashdata('success')): ?>
<script>
    alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')
</script>
<?php endif;?>
<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title">All Transactions</h3>
    </div>
    <div class="card-body">
        <div class="container-fluid">
            <table class="table table-bordered table-stripped table-hover" id="transaction-list">
                <thead>
                    <tr>
                        <th class="text-center">#</th>
                        <th class="text-nowrap">Date & Time</th>
                        <th>Transaction Code</th>
                        <th>Account #</th>
                        <th>Account Holder</th>
                        <th>Type</th>
                        <th>Sender Account #</th>
                        <th>Receiver Account #</th>
                        <th class="text-nowrap">Amount</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $i = 1;
                    // Updated query to select all new columns (t.*) and include account details (a.*)
                    $qry = $conn->query("SELECT t.*, a.account_number AS primary_account_number, concat(a.lastname,', ',a.firstname,' ',a.middlename) as account_holder_name FROM `transactions` t INNER JOIN `accounts` a ON a.id = t.account_id ORDER BY unix_timestamp(t.date_created) DESC ");
                    
                    while($row = $qry->fetch_assoc()):
                        $amount = $row['amount'];
                        $db_type = $row['type']; // Numeric type from DB: 1=Cash in, 2=Withdraw, 3=transfer
                        $remarks = $row['remarks'];
                        $transaction_code = $row['transaction_code'] ?? 'N/A';
                        $sender_acc_num = $row['sender_account_number'] ?? 'N/A';
                        $receiver_acc_num = $row['receiver_account_number'] ?? 'N/A';

                        $amount_class = ''; 
                        $transaction_display_type = ''; // Textual type
                        $transaction_details = ''; 
                        $icon = ''; 

                        // Determine display values based on transaction type
                        if($db_type == 1){ // 1 = Cash in (Deposit)
                            $amount_class = 'text-success'; 
                            $icon = '<i class="fas fa-arrow-alt-circle-down text-success"></i> '; 
                            $transaction_display_type = "Deposit";
                            $transaction_details = "Deposit";
                            if (!empty($sender_acc_num) && $sender_acc_num != "CASH" && $sender_acc_num != "N/A") { 
                                $transaction_details .= " from Account: " . $sender_acc_num;
                            }
                            if(!empty($remarks) && $remarks != 'Deposit'){ 
                                $transaction_details .= " (" . $remarks . ")";
                            }
                        } elseif($db_type == 2){ // 2 = Withdraw
                            $amount_class = 'text-danger'; 
                            $icon = '<i class="fas fa-arrow-alt-circle-up text-danger"></i> '; 
                            $transaction_display_type = "Withdrawal";
                            $transaction_details = "Withdrawal";
                            if(!empty($remarks) && $remarks != 'Withdraw'){
                                $transaction_details .= " (" . $remarks . ")";
                            }
                        } elseif($db_type == 3){ // 3 = Transfer
                            $icon = '<i class="fas fa-exchange-alt text-warning"></i> '; 
                            $transaction_display_type = "Transfer";
                            $transaction_details = "Transfer";

                            // Admin view shows both sides clearly
                            $current_user_acc = $row['primary_account_number']; // The account_id column is now `primary_account_number` from the join

                            if ($sender_acc_num == $current_user_acc) { // If the joined account is the sender
                                $amount_class = 'text-danger'; // Outgoing from this account
                                $transaction_details = "Outgoing Transfer to " . $receiver_acc_num;
                            } elseif ($receiver_acc_num == $current_user_acc) { // If the joined account is the receiver
                                $amount_class = 'text-success'; // Incoming to this account
                                $icon = '<i class="fas fa-arrow-alt-circle-down text-success"></i> '; 
                                $transaction_details = "Incoming Transfer from " . $sender_acc_num;
                            } else {
                                // Fallback for transfers where neither sender/receiver is the primary account_id (shouldn't happen with correct logic)
                                $amount_class = '';
                                $transaction_details = "Transfer (Internal)";
                            }
                            
                            if(!empty($remarks) && $remarks != 'Transfer'){
                                $transaction_details .= " (" . $remarks . ")";
                            }
                        } else {
                            // Default fallback for unknown types
                            $transaction_display_type = "N/A";
                            $transaction_details = $remarks;
                        }
                    ?>
                        <tr>
                            <td class="text-center"><?php echo $i++; ?></td>
                            <td class="text-nowrap"><?php echo date("M d, Y h:i A", strtotime($row['date_created'])) ?></td>
                            <td><?php echo $transaction_code ?></td>
                            <td><?php echo $row['primary_account_number'] ?></td>
                            <td><?php echo $row['account_holder_name'] ?></td>
                            <td class="text-nowrap"><?php echo $icon . $transaction_display_type ?></td>
                            <td><?php echo $sender_acc_num ?></td>
                            <td><?php echo $receiver_acc_num ?></td>
                            <td class='text-right <?php echo $amount_class ?>'>
                                <?php 
                                    // Display amount with +/- sign for clarity based on transaction type for the primary account
                                    if(($db_type == 1) || ($db_type == 3 && $receiver_acc_num == $row['primary_account_number'])){ // Deposit or Incoming Transfer
                                        echo '+ ' . number_format($amount, 2);
                                    } else { // Withdrawal or Outgoing Transfer
                                        echo '- ' . number_format($amount, 2);
                                    }
                                ?>
                            </td>
                            <td><?php echo $transaction_details ?></td>
                        </tr>
                    <?php endwhile; ?>
                    <?php if($qry->num_rows <= 0): ?>
                        <tr>
                            <td colspan="10" class="text-center">No transactions found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
    var indiList; // This variable name might be a leftover from previous code. Consider renaming to `transactionTable` for clarity.
    $(document).ready(function(){
        $('#transaction-list').dataTable(); // Changed ID to transaction-list for clarity
    })
</script>