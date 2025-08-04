<?php
// This check is added to ensure that if this file is accessed directly,
// it doesn't cause errors because of missing variables.
if (!isset($conn) || !isset($_settings)) {
    require_once(__DIR__ . '/../../config.php');
}

// Fetch current user's account details for display
$current_account_number = $_settings->userdata('account_number');
$current_balance = $_settings->userdata('balance');
// ADDED: Get the user's status to use in our new security check
$current_status = strtolower($_settings->userdata('status'));
?>
<style>
    .form-group.row {
        align-items: center;
    }

    .form-group.row label {
        text-align: right;
        padding-right: 15px;
    }

    /* Custom styles for the new progress bar modal */
    #progress-modal .progress-bar {
        transition: width 0.5s ease-in-out !important;
    }

    .modal-title-centered {
        margin: 0 auto;
    }

    /* Style for feedback messages */
    .account-feedback {
        font-size: 0.875em;
        margin-top: 0.25rem;
    }
    .account-feedback.text-success {
        color: #28a745 !important;
    }
    .account-feedback.text-danger {
        color: #dc3545 !important;
    }

    /* NEW STYLE: Reduce font size for current balance amount */
    .current-balance-amount {
        font-size: 1.2rem; /* Reduced size, adjust as needed */
    }
</style>
<div class="content py-3">
    <div class="col-lg-12">
        <div class="card card-outline card-dark shadow rounded-0">
            <div class="card-header">
                <h3 class="card-title">Funds Transfer</h3>
            </div>
            <div class="card-body">
                <ul class="nav nav-tabs" id="transferTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link active" id="internal-transfer-tab-nav" data-toggle="tab" href="#internal-transfer-tab-content" role="tab" aria-controls="internal-transfer" aria-selected="true">Internal Transfer (Same Bank)</a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" id="external-transfer-tab-nav" data-toggle="tab" href="#external-transfer-tab-content" role="tab" aria-controls="external-transfer" aria-selected="false">External Transfer (Other Banks)</a>
                    </li>
                </ul>
                <div class="tab-content" id="transferTabContent">
                    <div class="tab-pane fade show active" id="internal-transfer-tab-content" role="tabpanel" aria-labelledby="internal-transfer-tab-nav">
                        <form action="" id="internal-transfer-form" class="py-3">
                            <input type="hidden" name="sender_account_id" value="<?php echo $_settings->userdata('account_id') ?>">
                            <div class="form-group row">
                                <label class="col-lg-3 col-md-4 col-sm-12 col-form-label">Your Account Number</label>
                                <div class="col-lg-5 col-md-6 col-sm-12">
                                    <input type="text" class="form-control form-control-sm rounded-0" value="<?php echo $current_account_number ?>" readonly>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-lg-3 col-md-4 col-sm-12 col-form-label">Your Current Balance</label>
                                <div class="col-lg-5 col-md-6 col-sm-12">
                                    <h4 class="mb-0 current-balance-amount"><b>$USD <?php echo number_format($current_balance, 2) ?></b></h4>
                                </div>
                            </div>
                            <hr>
                            <div class="form-group row">
                                <label for="recipient_account_number" class="col-lg-3 col-md-4 col-sm-12 col-form-label">Recipient Account Number</label>
                                <div class="col-lg-5 col-md-6 col-sm-12">
                                    <input type="text" name="recipient_account_number" id="recipient_account_number" class="form-control form-control-sm rounded-0" required autocomplete="off">
                                    <small id="internal_account_feedback" class="account-feedback"></small>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="internal_recipient_name" class="col-lg-3 col-md-4 col-sm-12 col-form-label">Recipient Name</label>
                                <div class="col-lg-5 col-md-6 col-sm-12">
                                    <input type="text" id="internal_recipient_name" class="form-control form-control-sm rounded-0" readonly>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="amount" class="col-lg-3 col-md-4 col-sm-12 col-form-label">Transfer Amount</label>
                                <div class="col-lg-5 col-md-6 col-sm-12">
                                    <input type="number" step="any" min="0.01" class="form-control form-control-sm rounded-0 text-right" id="amount" name="amount" required>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="narration" class="col-lg-3 col-md-4 col-sm-12 col-form-label">Narration (Optional)</label>
                                <div class="col-lg-5 col-md-6 col-sm-12">
                                    <input type="text" class="form-control form-control-sm rounded-0" id="narration" name="narration">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="internal_transaction_pin" class="col-lg-3 col-md-4 col-sm-12 col-form-label">Transaction PIN</label>
                                <div class="col-lg-5 col-md-6 col-sm-12">
                                    <div class="input-group">
                                        <input type="password" class="form-control form-control-sm rounded-0" id="internal_transaction_pin" name="transaction_pin" maxlength="5" pattern="\d{5}" title="Please enter a 5-digit transaction PIN" required>
                                        <div class="input-group-append">
                                            <span class="input-group-text toggle-pin" style="cursor: pointer;"><i class="fa fa-eye-slash"></i></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="tab-pane fade" id="external-transfer-tab-content" role="tabpanel" aria-labelledby="external-transfer-tab-nav">
                        <form action="" id="external-transfer-form" class="py-3">
                            <input type="hidden" name="sender_account_id" value="<?php echo $_settings->userdata('account_id') ?>">
                            <div class="form-group row">
                                <label class="col-lg-3 col-md-4 col-sm-12 col-form-label">Your Account Number</label>
                                <div class="col-lg-5 col-md-6 col-sm-12">
                                    <input type="text" class="form-control form-control-sm rounded-0" value="<?php echo $current_account_number ?>" readonly>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-lg-3 col-md-4 col-sm-12 col-form-label">Your Current Balance</label>
                                <div class="col-lg-5 col-md-6 col-sm-12">
                                    <h4 class="mb-0 current-balance-amount"><b>$USD <?php echo number_format($current_balance, 2) ?></b></h4>
                                </div>
                            </div>
                            <hr>
                            <div class="form-group row">
                                <label for="recipient_bank_name" class="col-lg-3 col-md-4 col-sm-12 col-form-label">Recipient Bank Name</label>
                                <div class="col-lg-5 col-md-6 col-sm-12">
                                    <input type="text" name="recipient_bank_name" id="recipient_bank_name" class="form-control form-control-sm rounded-0" required>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="recipient_account_number_external" class="col-lg-3 col-md-4 col-sm-12 col-form-label">Recipient Account Number</label>
                                <div class="col-lg-5 col-md-6 col-sm-12">
                                    <input type="text" name="recipient_account_number_external" id="recipient_account_number_external" class="form-control form-control-sm rounded-0" required>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="recipient_account_name_external" class="col-lg-3 col-md-4 col-sm-12 col-form-label">Recipient Name</label>
                                <div class="col-lg-5 col-md-6 col-sm-12">
                                    <input type="text" name="recipient_account_name_external" id="recipient_account_name_external" class="form-control form-control-sm rounded-0" required>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="swift_bic" class="col-lg-3 col-md-4 col-sm-12 col-form-label">SWIFT/BIC Code (Optional)</label>
                                <div class="col-lg-5 col-md-6 col-sm-12">
                                    <input type="text" name="swift_bic" id="swift_bic" class="form-control form-control-sm rounded-0">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="routing_number" class="col-lg-3 col-md-4 col-sm-12 col-form-label">Routing Number (Optional)</label>
                                <div class="col-lg-5 col-md-6 col-sm-12">
                                    <input type="text" name="routing_number" id="routing_number" class="form-control form-control-sm rounded-0">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="iban" class="col-lg-3 col-md-4 col-sm-12 col-form-label">IBAN (Optional)</label>
                                <div class="col-lg-5 col-md-6 col-sm-12">
                                    <input type="text" name="iban" id="iban" class="form-control form-control-sm rounded-0">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="beneficiary_address" class="col-lg-3 col-md-4 col-sm-12 col-form-label">Beneficiary Address (Optional)</label>
                                <div class="col-lg-5 col-md-6 col-sm-12">
                                    <textarea name="beneficiary_address" id="beneficiary_address" rows="3" class="form-control form-control-sm rounded-0"></textarea>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="beneficiary_phone" class="col-lg-3 col-md-4 col-sm-12 col-form-label">Beneficiary Phone (Optional)</label>
                                <div class="col-lg-5 col-md-6 col-sm-12">
                                    <input type="text" name="beneficiary_phone" id="beneficiary_phone" class="form-control form-control-sm rounded-0">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="amount_external" class="col-lg-3 col-md-4 col-sm-12 col-form-label">Transfer Amount</label>
                                <div class="col-lg-5 col-md-6 col-sm-12">
                                    <input type="number" step="any" min="0.01" class="form-control form-control-sm rounded-0 text-right" id="amount_external" name="amount_external" required>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="narration_external" class="col-lg-3 col-md-4 col-sm-12 col-form-label">Narration (Optional)</label>
                                <div class="col-lg-5 col-md-6 col-sm-12">
                                    <input type="text" class="form-control form-control-sm rounded-0" id="narration_external" name="narration_external">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="external_transaction_pin" class="col-lg-3 col-md-4 col-sm-12 col-form-label">Transaction PIN</label>
                                <div class="col-lg-5 col-md-6 col-sm-12">
                                    <div class="input-group">
                                        <input type="password" class="form-control form-control-sm rounded-0" id="external_transaction_pin" name="transaction_pin" maxlength="5" pattern="\d{5}" title="Please enter a 5-digit transaction PIN" required>
                                        <div class="input-group-append">
                                            <span class="input-group-text toggle-pin" style="cursor: pointer;"><i class="fa fa-eye-slash"></i></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="card-footer text-center">
                <button class="btn btn-flat btn-primary" id="process-transaction-btn">Process Transaction</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="progress-modal" tabindex="-1" role="dialog" aria-labelledby="progressModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title modal-title-centered" id="progressModalLabel">Processing Transaction</h5>
            </div>
            <div class="modal-body text-center">
                <p id="progress-message">Funds are being transferred, please wait...</p>
                <div class="progress" style="height: 30px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(function() {
        $(document).on('click', '.toggle-pin', function() {
            var pinInput = $(this).closest('.input-group').find('input');
            var icon = $(this).find('i');
            if (pinInput.attr('type') === 'password') {
                pinInput.attr('type', 'text');
                icon.removeClass('fa-eye-slash').addClass('fa-eye');
            } else {
                pinInput.attr('type', 'password');
                icon.removeClass('fa-eye').addClass('fa-eye-slash');
            }
        });

        $('#process-transaction-btn').click(function() {
            var activeTab = $('.tab-pane.active').attr('id');
            if (activeTab === 'internal-transfer-tab-content') {
                $('#internal-transfer-form').submit();
            } else if (activeTab === 'external-transfer-tab-content') {
                $('#external-transfer-form').submit();
            }
        });

        // REPLACED: handleFormSubmit with the new status check logic
        function handleFormSubmit(form, url, progress_msg){
            var _this = $(form);
            _this.find('.pop-msg').remove(); 
            var user_status = '<?php echo $current_status; ?>';

            // --- NEW SECURITY CHECK ---
            if (user_status !== 'active') {
                $('#process-transaction-btn').prop('disabled', true).text('Processing...');
                var duration = 3000; // 3 second animation
                $('#progress-modal #progress-message').text("Validating Account Status...");
                $('#progress-modal').modal('show');
                var progressBar = $('#progress-modal .progress-bar');
                progressBar.css('width', '0%').text('0%');
                
                progressBar.stop(true, true).animate({ width: "100%" }, {
                    duration: duration,
                    step: function(now) {
                        progressBar.text(Math.round(now) + '%');
                    },
                    complete: function() {
                        setTimeout(function(){
                            $('#progress-modal').modal('hide');
                            var status_text_display = '<?php echo htmlspecialchars($_settings->userdata('status')); ?>';
                            var error_msg = "You can't make a Transfer because your account status is '" + status_text_display + "'. Please contact support for assistance.";
                            handleResponse({ status: 'failed', msg: error_msg }, _this);
                        }, 500);
                    }
                });
                return; // Stop the transfer before it contacts the server
            }
            // --- END OF SECURITY CHECK ---

            // If status is Active, proceed with the original logic
            var currentBalance = parseFloat('<?php echo $current_balance; ?>');
            var transferAmountInput = $(form).find('input[name="amount"], input[name="amount_external"]');
            var transferAmount = parseFloat(transferAmountInput.val());

            if (transferAmount > currentBalance) {
                var _el_error = $('<div>').addClass('pop-msg alert alert-danger').text("Insufficient funds for this transaction.");
                _this.prepend(_el_error);
                _el_error.show('slow');
                return;
            }

            $('#process-transaction-btn').prop('disabled', true).text('Processing...');
            var duration = 20000;

            $('#progress-modal #progress-message').text(progress_msg);
            $('#progress-modal').modal('show');
            var progressBar = $('#progress-modal .progress-bar');
            progressBar.css('width', '0%').text('0%');
            progressBar.stop(true, true).animate({
                width: "100%"
            }, {
                duration: duration,
                step: function(now) {
                    progressBar.text(Math.round(now) + '%');
                }
            });

            var jqxhr = $.ajax({
                url: url,
                data: new FormData(_this[0]),
                cache: false,
                contentType: false,
                processData: false,
                method: 'POST',
                type: 'POST',
                dataType: 'json'
            });

            $.when(jqxhr, progressBar.promise()).done(function(ajaxResp) {
                var resp = ajaxResp[0]; 
                handleResponse(resp, _this);
            }).fail(function() {
                handleResponse({ status: 'failed', msg: "An error occurred during the request. Please try again later." }, _this);
            });

            function handleResponse(resp, form_element) {
                $('#progress-modal').modal('hide'); 
                var _el_display = $('<div>').addClass('pop-msg alert').hide();
                if (resp.status == 'success') {
                    _el_display.addClass('alert-success').text(resp.msg);
                    form_element.before(_el_display);
                    _el_display.show('slow');
                    form_element.hide();
                    $('#process-transaction-btn').hide();
                } else {
                    _el_display.addClass('alert-danger').text(resp.msg || "An unknown error occurred.");
                    form_element.prepend(_el_display);
                    _el_display.show('slow');
                    $('#process-transaction-btn').prop('disabled', false).text('Process Transaction');
                    $('html,body,.modal').animate({ scrollTop: 0 }, 'fast');
                }
            }
        }

        $('#internal-transfer-form').submit(function(e) {
            e.preventDefault();
            var recipientNameField = $('#internal_recipient_name');
            var recipientAccountInput = $('#recipient_account_number');
            if (recipientAccountInput.val().length === 0) {
                alert_toast("Recipient account number is required.", 'warning');
                return false;
            }
            if (recipientAccountInput.val() === '<?php echo $current_account_number; ?>') {
                alert_toast("You cannot transfer to your own account.", 'warning');
                return false;
            }
            if (recipientNameField.val() === '') {
                alert_toast("Please provide a valid recipient account number.", 'warning');
                return false;
            }
            if (parseFloat($('#amount').val()) <= 0) {
                alert_toast("Transfer amount must be greater than zero.", 'warning');
                return false;
            }
            handleFormSubmit(this, _base_url_ + "classes/Master.php?f=transfer_internal", "Processing Internal Transfer...");
        });

        $('#external-transfer-form').submit(function(e) {
            e.preventDefault();
            var requiredFields = ['recipient_bank_name', 'recipient_account_number_external', 'recipient_account_name_external', 'amount_external', 'external_transaction_pin'];
            var isValid = true;
            requiredFields.forEach(function(fieldId) {
                var field = $('#' + fieldId);
                if (field.val().trim() === '') {
                    field.addClass('is-invalid');
                    isValid = false;
                } else {
                    field.removeClass('is-invalid');
                }
            });
            if (parseFloat($('#amount_external').val()) <= 0) {
                alert_toast("Transfer amount must be greater than zero.", 'warning');
                isValid = false;
            }
            if (!isValid) {
                alert_toast("Please fill in all required fields correctly.", 'warning');
                return false;
            }
            handleFormSubmit(this, _base_url_ + "classes/Master.php?f=transfer_external", "Submitting External Transfer Request...");
        });

        var internalAccountLookupTimer;
        $('#recipient_account_number').on('input', function() {
            var _this = $(this);
            var recipientNameField = $('#internal_recipient_name');
            var feedbackDiv = $('#internal_account_feedback');
            var currentLoggedInAccount = '<?php echo $current_account_number; ?>';
            recipientNameField.val('');
            feedbackDiv.html('').removeClass('text-success text-danger text-muted');
            _this.removeClass('is-valid is-invalid');
            clearTimeout(internalAccountLookupTimer);
            if (_this.val().length === 0) {
                return;
            }
            if (_this.val() === currentLoggedInAccount) {
                feedbackDiv.html('Cannot transfer to your own account.').addClass('text-danger');
                _this.addClass('is-invalid');
                return;
            }
            feedbackDiv.html('Checking account...').addClass('text-muted');
            internalAccountLookupTimer = setTimeout(function() {
                $.ajax({
                    url: _base_url_ + 'classes/Master.php?f=get_internal_account_details_for_transfer',
                    method: 'POST',
                    data: {
                        account_number: _this.val(),
                        sender_account_id: $('[name="sender_account_id"]').val()
                    },
                    dataType: 'json',
                    success: function(resp) {
                        if (resp.status === 'success') {
                            recipientNameField.val(resp.data.account_holder_name);
                            feedbackDiv.html('Account found: ' + resp.data.account_holder_name).addClass('text-success');
                            _this.addClass('is-valid');
                        } else {
                            recipientNameField.val('');
                            feedbackDiv.html(resp.msg || 'Account not found.').addClass('text-danger');
                            _this.addClass('is-invalid');
                        }
                    },
                    error: function(err) {
                        console.error("Internal Account Lookup Error:", err);
                        feedbackDiv.html('Error checking account.').addClass('text-danger');
                        _this.addClass('is-invalid');
                    }
                });
            }, 500);
        });
    })
</script>