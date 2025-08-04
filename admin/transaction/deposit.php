<?php
// This check is added to ensure that if this file is accessed directly,
// it doesn't cause errors because of missing variables.
if(!isset($conn) || !isset($_settings)){
    // This assumes your config.php is two directories up from the current file.
    // This path is relative to /client/transaction/deposit.php
    require_once(__DIR__ . '/../../config.php');
}
?>
<style>
    .form-group.row {
        align-items: center;
    }
    .form-group.row label {
        text-align: right;
        padding-right: 15px;
    }
</style>
<div class="content py-3">
    <div class="col-lg-12">
        <div class="card card-outline card-dark shadow rounded-0">
            <div class="card-header">
                <h3 class="card-title">Funds Transfer/Deposit</h3>
            </div>
            <div class="card-body">
                <form action="" id="deposit-form">
                    <input type="hidden" name="id">
                    <div class="form-group row">
                        <label for="account_number" class="col-lg-3 col-md-4 col-sm-12 col-form-label">Your Primary Account Number</label>
                        <div class="col-lg-5 col-md-6 col-sm-12">
                            <input type="text" class="form-control form-control-sm rounded-0" id="account_number" value="<?= $_settings->userdata('account_number') ?>" readonly>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="current_balance" class="col-lg-3 col-md-4 col-sm-12 col-form-label">Current Primary Account Balance</label>
                        <div class="col-lg-5 col-md-6 col-sm-12">
                            <input type="text" class="form-control form-control-sm rounded-0 text-right" id="current_balance" value="<?= number_format($_settings->userdata('balance'), 2) ?>" readonly>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="transaction_type_radio" class="col-lg-3 col-md-4 col-sm-12 col-form-label">Select Transaction Type</label>
                        <div class="col-lg-8 col-md-8 col-sm-12">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="transaction_type_radio" id="deposit_from_linked" value="deposit_from_linked_account" checked>
                                <label class="form-check-label" for="deposit_from_linked">Deposit to My Primary Account (from Linked)</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="transaction_type_radio" id="transfer_to_linked" value="transfer_to_linked_account">
                                <label class="form-check-label" for="transfer_to_linked">Transfer from My Primary Account (to Linked)</label>
                            </div>
                        </div>
                    </div>

                    <div id="deposit_fields">
                        <div class="form-group row">
                            <label for="source_linked_account_id" class="col-lg-3 col-md-4 col-sm-12 col-form-label">Source Linked Account (Pull From)</label>
                            <div class="col-lg-5 col-md-6 col-sm-12">
                                <select name="source_linked_account_id" id="source_linked_account_id" class="form-control form-control-sm rounded-0 select2" required>
                                    <option value="" disabled selected>-- Select Linked Account --</option>
                                    <?php
                                    $linked_accounts = $conn->query("SELECT * FROM `user_linked_accounts` WHERE user_id = '{$_settings->userdata('id')}' ORDER BY `account_label` ASC");
                                    while($row = $linked_accounts->fetch_assoc()):
                                        $display_name = $row['account_label'] . " (" . $row['account_number'] . " -- " . $row['bank_name'] . ")";
                                    ?>
                                    <option value="<?= $row['id'] ?>"><?= $display_name ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="amount_deposit" class="col-lg-3 col-md-4 col-sm-12 col-form-label">Deposit Amount</label>
                            <div class="col-lg-5 col-md-6 col-sm-12">
                                <input type="number" step="any" class="form-control form-control-sm rounded-0 text-right" id="amount_deposit" name="amount_deposit" required>
                            </div>
                        </div>
                    </div>

                    <div id="transfer_fields" style="display:none;">
                        <div class="form-group row">
                            <label for="destination_linked_account_id" class="col-lg-3 col-md-4 col-sm-12 col-form-label">Destination Linked Account (Push To)</label>
                            <div class="col-lg-5 col-md-6 col-sm-12">
                                <select name="destination_linked_account_id" id="destination_linked_account_id" class="form-control form-control-sm rounded-0 select2" required>
                                    <option value="" disabled selected>-- Select Linked Account --</option>
                                    <?php
                                    $linked_accounts->data_seek(0); // Reset pointer for second loop
                                    while($row = $linked_accounts->fetch_assoc()):
                                        $display_name = $row['account_label'] . " (" . $row['account_number'] . " -- " . $row['bank_name'] . ")";
                                    ?>
                                    <option value="<?= $row['id'] ?>"><?= $display_name ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="amount_transfer" class="col-lg-3 col-md-4 col-sm-12 col-form-label">Transfer Amount</label>
                            <div class="col-lg-5 col-md-6 col-sm-12">
                                <input type="number" step="any" class="form-control form-control-sm rounded-0 text-right" id="amount_transfer" name="amount_transfer" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label for="transaction_pin" class="col-lg-3 col-md-4 col-sm-12 col-form-label">Transaction PIN</label>
                        <div class="col-lg-5 col-md-6 col-sm-12">
                            <div class="input-group">
                                <input type="password" class="form-control form-control-sm rounded-0" id="transaction_pin" name="transaction_pin" maxlength="5" pattern="\d{5}" title="Please enter a 5-digit transaction PIN" required>
                                <div class="input-group-append">
                                    <span class="input-group-text toggle-pin" style="cursor: pointer;">
                                        <i class="fa fa-eye-slash"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="card-footer">
                <div class="col-md-12">
                    <div class="row">
                        <button class="btn btn-flat btn-primary" form="deposit-form">Process Transaction</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    $(function(){
        $('#source_linked_account_id, #destination_linked_account_id').select2({
            placeholder: "Select Linked Account Here",
            width: '100%'
        });

        $('input[name="transaction_type_radio"]').change(function(){
            if($(this).val() == 'deposit_from_linked_account'){
                $('#deposit_fields').show();
                $('#transfer_fields').hide();
                $('#destination_linked_account_id').val('').trigger('change');
                $('#amount_transfer').val('');
                $('#source_linked_account_id').prop('required', true);
                $('#amount_deposit').prop('required', true);
                $('#destination_linked_account_id').prop('required', false);
                $('#amount_transfer').prop('required', false);
            } else {
                $('#deposit_fields').hide();
                $('#transfer_fields').show();
                $('#source_linked_account_id').val('').trigger('change');
                $('#amount_deposit').val('');
                $('#destination_linked_account_id').prop('required', true);
                $('#amount_transfer').prop('required', true);
                $('#source_linked_account_id').prop('required', false);
                $('#amount_deposit').prop('required', false);
            }
        });

        $('.toggle-pin').on('click', function() {
            var pinInput = $('#transaction_pin');
            var icon = $(this).find('i');
            if (pinInput.attr('type') === 'password') {
                pinInput.attr('type', 'text');
                icon.removeClass('fa-eye-slash').addClass('fa-eye');
            } else {
                pinInput.attr('type', 'password');
                icon.removeClass('fa-eye').addClass('fa-eye-slash');
            }
        });

        $('#deposit-form').submit(function(e){
            e.preventDefault();
            var _this = $(this)
            $('.pop-msg').remove();
            var _el = $('<div>')
                _el.addClass('pop-msg alert')
                _el.hide()
            _this.find('button').attr('disabled',true)
            _this.find('button').text('Processing...')
            $.ajax({
                url:_base_url_+"classes/Master.php?f=" + $('input[name="transaction_type_radio"]:checked').val(),
                data: new FormData($(this)[0]),
                cache: false,
                contentType: false,
                processData: false,
                method: 'POST',
                type: 'POST',
                dataType: 'json',
                error:err=>{
                    console.log(err)
                    _el.addClass('alert-danger')
                    _el.text("An error occurred during AJAX request. Check console for details.")
                    _this.prepend(_el)
                    _el.show('slow')
                    _this.find('button').attr('disabled',false)
                    _this.find('button').text('Process Transaction')
                },
                success:function(resp){
                    if(resp.status == 'success'){
                        _el.addClass('alert-success')
                        _el.text(resp.msg);
                        setTimeout(() => {
                           location.reload()
                        }, 2000);
                    }else if(!!resp.msg){
                        _el.addClass('alert-danger')
                        _el.text(resp.msg)
                    }else{
                        _el.addClass('alert-danger')
                        _el.text("An error occurred due to an unknown reason.")
                    }
                    _el.show('slow');
                    _this.prepend(_el)
                    _this.find('button').attr('disabled',false)
                    _this.find('button').text('Process Transaction')
                }
            })
        })
    })
</script>