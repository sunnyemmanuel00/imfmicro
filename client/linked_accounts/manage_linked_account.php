<?php
// Check if $_settings and $conn are already defined by the parent page.
// This prevents re-including config.php if it's already set up globally.
if (!isset($_settings) || !isset($conn)) {
    // If not defined, then include config.php to make them available.
    require_once(__DIR__ . '/../../config.php');
}

// PHP Logic for fetching data
if(isset($_GET['id'])){
    $id = $_GET['id'];
    if (isset($_settings)) {
        $user_id = $_settings->userdata('id');
    } else {
        echo "<script> alert_toast('System settings not available. Please refresh.','error'); </script>";
        echo "<script> $('.modal').modal('hide'); </script>";
        exit;
    }
    
    if (isset($conn)) {
        // Corrected SQL query for PostgreSQL by replacing backticks with double quotes
        $qry = $conn->query("SELECT * FROM \"user_linked_accounts\" WHERE id = '{$id}' AND user_id = '{$user_id}'");
    } else {
        echo "<script> alert_toast('Database connection not available. Please refresh.','error'); </script>";
        echo "<script> $('.modal').modal('hide'); </script>";
        exit;
    }

    // Corrected logic: Use rowCount() for PDOStatement instead of num_rows
    if($qry->rowCount() > 0){
        $res = $qry->fetch(PDO::FETCH_ASSOC); // Use PDO's fetch method
        foreach($res as $k => $v){
            if(!is_numeric($k)){
                $$k = $v;
            }
        }
    } else {
        echo "<script> alert_toast('Linked Account not found or unauthorized access.','error'); </script>";
        echo "<script> $('.modal').modal('hide'); </script>";
        exit;
    }
}
?>

<div class="container-fluid">
    <form action="" id="linked-account-form">
        <input type="hidden" name="id" value="<?php echo isset($id) ? $id : ''; ?>">

        <div class="form-group">
            <label for="account_label" class="control-label">Account Label</label>
            <input type="text" name="account_label" id="account_label" class="form-control form-control-sm rounded-0" value="<?php echo isset($account_label) ? $account_label : ''; ?>" required>
        </div>
        <div class="form-group">
            <label for="account_number" class="control-label">Account Number</label>
            <input type="text" name="account_number" id="account_number" class="form-control form-control-sm rounded-0" value="<?php echo isset($account_number) ? $account_number : ''; ?>" required>
        </div>

        <div class="form-group">
            <label class="control-label">Is this an internal bank account?</label><br>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="is_internal_bank" id="is_internal_bank_yes" value="1" <?php echo (isset($is_internal_bank) && $is_internal_bank === '1') ? 'checked' : ''; ?> required>
                <label class="form-check-label" for="is_internal_bank_yes">Yes</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="is_internal_bank" id="is_internal_bank_no" value="0" <?php echo (isset($is_internal_bank) && $is_internal_bank == 0) ? 'checked' : ''; ?> required>
                <label class="form-check-label" for="is_internal_bank_no">No</label>
            </div>
            <small id="account_number_feedback" class="form-text text-muted"></small>
        </div>
        <div class="form-group">
            <label for="account_holder_name" class="control-label">Account Holder Name</label>
            <input type="text" name="account_holder_name" id="account_holder_name" class="form-control form-control-sm rounded-0" value="<?php echo isset($account_holder_name) ? $account_holder_name : ''; ?>" required>
        </div>
        <div class="form-group">
            <label for="bank_name" class="control-label">Bank Name</label>
            <input type="text" name="bank_name" id="bank_name" class="form-control form-control-sm rounded-0" value="<?php echo isset($bank_name) ? $bank_name : ''; ?>" required>
        </div>

        <div class="form-group" id="swift_bic_field" style="display: <?php echo (isset($is_internal_bank) && $is_internal_bank == 1) ? 'none' : 'block'; ?>;">
            <label for="swift_bic" class="control-label">SWIFT/BIC Code (Optional)</label>
            <input type="text" name="swift_bic" id="swift_bic" class="form-control form-control-sm rounded-0" value="<?php echo isset($swift_bic) ? $swift_bic : ''; ?>">
        </div>
        <div class="form-group" id="routing_number_field" style="display: <?php echo (isset($is_internal_bank) && $is_internal_bank == 1) ? 'none' : 'block'; ?>;">
            <label for="routing_number" class="control-label">Routing Number (Optional)</label>
            <input type="text" name="routing_number" id="routing_number" class="form-control form-control-sm rounded-0" value="<?php echo isset($routing_number) ? $routing_number : ''; ?>">
        </div>
        <div class="form-group" id="iban_field" style="display: <?php echo (isset($is_internal_bank) && $is_internal_bank == 1) ? 'none' : 'block'; ?>;">
            <label for="iban" class="control-label">IBAN (Optional)</label>
            <input type="text" name="iban" id="iban" class="form-control form-control-sm rounded-0" value="<?php echo isset($iban) ? $iban : ''; ?>">
        </div>
        <div class="form-group" id="beneficiary_address_field" style="display: <?php echo (isset($is_internal_bank) && $is_internal_bank == 1) ? 'none' : 'block'; ?>;">
            <label for="beneficiary_address" class="control-label">Beneficiary Address (Optional)</label>
            <textarea name="beneficiary_address" id="beneficiary_address" rows="3" class="form-control form-control-sm rounded-0"><?php echo isset($beneficiary_address) ? $beneficiary_address : ''; ?></textarea>
        </div>
        <div class="form-group" id="beneficiary_phone_field" style="display: <?php echo (isset($is_internal_bank) && $is_internal_bank == 1) ? 'none' : 'block'; ?>;">
            <label for="beneficiary_phone" class="control-label">Beneficiary Phone (Optional)</label>
            <input type="text" name="beneficiary_phone" id="beneficiary_phone" class="form-control form-control-sm rounded-0" value="<?php echo isset($beneficiary_phone) ? $beneficiary_phone : ''; ?>">
        </div>
        <div class="form-group">
            <label for="account_type" class="control-label">Account Type</label>
            <select name="account_type" id="account_type" class="form-control form-control-sm rounded-0" required>
                <option value="Savings" <?php echo (isset($account_type) && $account_type == 'Savings') ? 'selected' : ''; ?>>Savings</option>
                <option value="Current" <?php echo (isset($account_type) && $account_type == 'Current') ? 'selected' : ''; ?>>Current</option>
                <option value="Checking" <?php echo (isset($account_type) && $account_type == 'Checking') ? 'selected' : ''; ?>>Checking</option>
                <option value="Loan" <?php echo (isset($account_type) && $account_type == 'Loan') ? 'selected' : ''; ?>>Loan</option>
                <option value="Investment" <?php echo (isset($account_type) && $account_type == 'Investment') ? 'selected' : ''; ?>>Investment</option>
            </select>
        </div>
        <div class="form-group">
            <label for="link_type" class="control-label">Link Type</label>
            <select name="link_type" id="link_type" class="form-control form-control-sm rounded-0" required>
                <option value="source" <?php echo (isset($link_type) && $link_type == 'source') ? 'selected' : ''; ?>>Source (for deposits to you)</option>
                <option value="beneficiary" <?php echo (isset($link_type) && $link_type == 'beneficiary') ? 'selected' : ''; ?>>Beneficiary (for transfers from you)</option>
            </select>
        </div>
       <div class="form-group">
    <label for="transaction_pin" id="pin_label" class="control-label">Your Transaction PIN</label>
    <input type="password" name="transaction_pin" id="transaction_pin" class="form-control form-control-sm rounded-0" maxlength="5" pattern="\d{5}" title="Please enter a 5-digit transaction PIN" required>
</div>
    </form>
</div>

<script>
    console.log("manage_linked_account.php script loaded and executing.");

    $(function(){
        function updatePinLabel() {
        var isInternalBank = $('input[name="is_internal_bank"]:checked').val();
        var pinLabel = $('#pin_label');
        if (isInternalBank == '1') {
            pinLabel.text("Account Owner's Transaction PIN");
        } else {
            pinLabel.text("Your Transaction PIN (to confirm)");
        }
    }
        // Function to toggle display of external account fields
        function toggleExternalAccountFields() {
            var isInternalBank = $('input[name="is_internal_bank"]:checked').val();
            var fields = [
                $('#swift_bic_field'), 
                $('#routing_number_field'), 
                $('#iban_field'), 
                $('#beneficiary_address_field'), 
                $('#beneficiary_phone_field')
            ];
            
            if (isInternalBank == '1') { // Yes, it's an internal bank account
                fields.forEach(function(field) {
                    field.hide();
                    field.find('input, textarea').removeAttr('required'); // Remove required if hidden
                });
            } else { // No, it's an external account
                fields.forEach(function(field) {
                    field.show();
                    // All new fields are optional, so no 'required' attribute is added here.
                });
            }
        }

        // Variable to hold the debounce timer for account lookup
        var accountLookupTimer;

        function handleAccountLookup() {
            var accountNumber = $('#account_number').val();
            var isInternalBank = $('input[name="is_internal_bank"]:checked').val();
            var accountHolderNameField = $('#account_holder_name');
            var bankNameField = $('#bank_name');
            var feedbackDiv = $('#account_number_feedback');

            // Clear previous feedback and reset fields initially
            accountHolderNameField.val('').prop('readonly', false).removeClass('is-invalid is-valid');
            bankNameField.val('').prop('readonly', false).removeClass('is-invalid is-valid');
            feedbackDiv.html('').removeClass('text-danger text-success text-muted');

            clearTimeout(accountLookupTimer); // Clear any existing timer

            if (isInternalBank == '1' && accountNumber.length > 0) {
                // Display checking message
                feedbackDiv.html('Checking availablity...').addClass('text-muted');
                //start_loader(); // Re-introducing start_loader here, as uni_modal doesn't dismiss it implicitly.

                accountLookupTimer = setTimeout(function() {
                    $.ajax({
                        url: _base_url_ + "classes/Master.php?f=get_account_details_by_number",
                        method: 'POST',
                        data: { account_number: accountNumber },
                        dataType: 'json',
                        error: function(err) {
                            console.error("AJAX Error (get_account_details_by_number):", err);
                            feedbackDiv.html('Error checking account.').addClass('text-danger');
                            accountHolderNameField.val('').prop('readonly', false).addClass('is-invalid');
                            bankNameField.val('').prop('readonly', false).addClass('is-invalid');
                            end_loader(); 
                        },
                        success: function(resp) {
                            if (resp.status == 'success') {
                                accountHolderNameField.val(resp.account_holder_name).prop('readonly', true).addClass('is-valid');
                                bankNameField.val(resp.bank_name).prop('readonly', true).addClass('is-valid');
                                feedbackDiv.html('Account found.').addClass('text-success');
                            } else {
                                accountHolderNameField.val('').prop('readonly', false).addClass('is-invalid');
                                bankNameField.val('').prop('readonly', false).addClass('is-invalid');
                                feedbackDiv.html(resp.msg || 'Internal Account Number does not exist.').addClass('text-danger');
                            }
                            end_loader(); 
                        }
                    });
                }, 500); // Debounce delay
            } else {
                // If "No" is selected or account number is empty, ensure fields are editable
                // And re-populate original values if in edit mode (isset($account_holder_name), etc.)
                accountHolderNameField.prop('readonly', false);
                bankNameField.prop('readonly', false);

                // If editing, make sure current values are restored when switching from internal (lookup) to external (manual)
                if ($('[name="id"]').val() > 0 && isInternalBank == '0') {
                    accountHolderNameField.val('<?php echo isset($account_holder_name) ? $account_holder_name : ''; ?>');
                    bankNameField.val('<?php echo isset($bank_name) ? $bank_name : ''; ?>');
                }
                end_loader(); // Dismiss loader if it was started before this path.
            }
        }

        // Attach event listeners
        $('#account_number').on('input', handleAccountLookup); 
        $('input[name="is_internal_bank"]').on('change', function() {
            handleAccountLookup(); 
            toggleExternalAccountFields(); // Toggle new fields visibility
            updatePinLabel();
        });

        // Initial checks on page load
        // Only trigger initial lookup if in edit mode and it's an internal bank
        if ($('[name="id"]').val() > 0) { 
            if ($('input[name="is_internal_bank"]:checked').val() == '1') {
                handleAccountLookup(); // Perform lookup for existing internal account
            } else {
                // For existing external account, ensure fields are editable
                $('#account_holder_name').prop('readonly', false);
                $('#bank_name').prop('readonly', false);
            }
        } else { 
            // For creating a new record, set initial readonly state
            if ($('input[name="is_internal_bank"]:checked').val() == '1') {
                $('#account_holder_name').prop('readonly', true);
                $('#bank_name').prop('readonly', true);
            } else {
                $('#account_holder_name').prop('readonly', false);
                $('#bank_name').prop('readonly', false);
            }
        }
        toggleExternalAccountFields(); // Initial call to set visibility based on loaded data
        updatePinLabel();


        $('#linked-account-form').submit(function(e){
            e.preventDefault();
            var _this = $(this);
            $('.pop_msg').remove();
            var el = $('<div>');
            el.addClass("pop_msg alert");
            el.hide();
            start_loader(); // Ensure loader starts for form submission AJAX
            $.ajax({
                url:_base_url_+"classes/Master.php?f=save_linked_account",
                data: new FormData($(this)[0]),
                cache: false,
                contentType: false,
                processData: false,
                method: 'POST',
                type: 'POST',
                dataType: 'json',
                error:err=>{
                    console.log("AJAX Error (save_linked_account):", err);
                    alert_toast("An error occurred.",'error'); // FIXED: More specific error message if available
                    end_loader(); 
                },
                success:function(resp){
                    if(resp.status == 'success'){
                        alert_toast(resp.msg,'success'); // Use resp.msg for success
                        // Delay reload to allow user to see the message
                        setTimeout(function(){
                            location.reload(); 
                        }, 1500); // 1.5 seconds delay
                    } else if(!!resp.msg){
                        el.addClass("alert-danger");
                        el.text(resp.msg); // FIXED: Display exact error message from backend
                        _this.prepend(el);
                    } else {
                        el.addClass("alert-danger");
                        el.text("An error occurred due to unknown reason.");
                        _this.prepend(el);
                    }
                    el.show('slow');
                    $('html,body,.modal').animate({scrollTop:0},'fast');
                    end_loader(); 
                }
            })
        })
    })

    // This is the absolute final attempt to dismiss the loader for the *initial* modal load.
    // Ensure end_loader is defined or remove this if not needed for initial load.
    // setTimeout(function() {
    //     console.log("Forcing #preloader hide from manage_linked_account.php after delay.");
    //     if (typeof end_loader === 'function') {
    //         end_loader(); 
    //     }
    //     $('#preloader').hide(); 
    // }, 200); 
</script>