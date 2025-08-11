<?php
// This check is added to ensure that if this file is accessed directly,
// it doesn't cause errors because of missing variables.
if(!isset($conn) || !isset($_settings)){
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
    /* Custom styles for the new progress bar modal */
    #progress-modal .progress-bar {
        transition: width 0.5s ease-in-out !important;
    }
    .modal-title-centered {
        margin: 0 auto;
    }
</style>
<div class="content py-3">
    <div class="col-lg-12">
        <div class="card card-outline card-dark shadow rounded-0">
            <div class="card-header">
                <h3 class="card-title">Funds Transfer/Deposit</h3>
            </div>
            <div class="card-body">
                <ul class="nav nav-tabs" id="myTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link active" id="deposit-tab-nav" data-toggle="tab" href="#deposit-tab-content" role="tab" aria-controls="deposit-tab-content" aria-selected="true">Deposit (from Linked Account)</a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" id="transfer-tab-nav" data-toggle="tab" href="#transfer-tab-content" role="tab" aria-controls="transfer-tab-content" aria-selected="false">Transfer (to Linked Account)</a>
                    </li>
                </ul>
                <div class="tab-content" id="myTabContent">
                    <div class="tab-pane fade show active" id="deposit-tab-content" role="tabpanel" aria-labelledby="deposit-tab-nav">
                        <form action="" id="deposit-form" class="py-3">
                            <div class="form-group row">
                                <label for="source_linked_account_id" class="col-lg-3 col-md-4 col-sm-12 col-form-label">Source Linked Account</label>
                                <div class="col-lg-5 col-md-6 col-sm-12">
                                    <select name="source_linked_account_id" id="source_linked_account_id" class="form-control form-control-sm rounded-0 select2" required>
                                        <option value="" disabled selected>-- Select Linked Account --</option>
                                        <?php
                                        // Use PDO for fetching linked accounts
                                        $linked_accounts = $conn->prepare("SELECT * FROM \"user_linked_accounts\" WHERE user_id = :user_id ORDER BY \"account_label\" ASC");
                                        $linked_accounts->bindValue(':user_id', $_settings->userdata('id'), PDO::PARAM_INT);
                                        $linked_accounts->execute();
                                        while($row = $linked_accounts->fetch(PDO::FETCH_ASSOC)):
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
                            <div class="form-group row">
                                <label for="deposit_transaction_pin" class="col-lg-3 col-md-4 col-sm-12 col-form-label">Transaction PIN</label>
                                <div class="col-lg-5 col-md-6 col-sm-12">
                                    <div class="input-group">
                                        <input type="password" class="form-control form-control-sm rounded-0" id="deposit_transaction_pin" name="transaction_pin" maxlength="5" pattern="\d{5}" title="Please enter a 5-digit transaction PIN" required>
                                        <div class="input-group-append">
                                            <span class="input-group-text toggle-pin" style="cursor: pointer;"><i class="fa fa-eye-slash"></i></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="tab-pane fade" id="transfer-tab-content" role="tabpanel" aria-labelledby="transfer-tab-nav">
                        <form action="" id="transfer-form" class="py-3">
                               <div class="form-group row">
                                 <label for="destination_linked_account_id" class="col-lg-3 col-md-4 col-sm-12 col-form-label">Destination Linked Account</label>
                                 <div class="col-lg-5 col-md-6 col-sm-12">
                                     <select name="destination_linked_account_id" id="destination_linked_account_id" class="form-control form-control-sm rounded-0 select2" required>
                                         <option value="" disabled selected>-- Select Linked Account --</option>
                                          <?php
                                              // Re-fetch linked accounts for the second dropdown using PDO
                                              $linked_accounts_transfer = $conn->prepare("SELECT * FROM \"user_linked_accounts\" WHERE user_id = :user_id ORDER BY \"account_label\" ASC");
                                              $linked_accounts_transfer->bindValue(':user_id', $_settings->userdata('id'), PDO::PARAM_INT);
                                              $linked_accounts_transfer->execute();
                                              while($row = $linked_accounts_transfer->fetch(PDO::FETCH_ASSOC)):
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
                              <div class="form-group row">
                                 <label for="transfer_transaction_pin" class="col-lg-3 col-md-4 col-sm-12 col-form-label">Transaction PIN</label>
                                 <div class="col-lg-5 col-md-6 col-sm-12">
                                     <div class="input-group">
                                         <input type="password" class="form-control form-control-sm rounded-0" id="transfer_transaction_pin" name="transaction_pin" maxlength="5" pattern="\d{5}" title="Please enter a 5-digit transaction PIN" required>
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
    $(function(){
        // Initialize select2 elements
        $('#source_linked_account_id, #destination_linked_account_id').select2({
            placeholder: "Select Linked Account Here",
            width: '100%'
        });

        // Bootstrap tab activation logic
        $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
          // You can add logic here to run when a tab is shown
          console.log('Tab shown:', $(e.target).text());
        });

        // Universal PIN toggle logic for any .toggle-pin button
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

        // Main button click handler
        $('#process-transaction-btn').click(function(){
            var activeTab = $('.tab-pane.active').attr('id');
            if(activeTab === 'deposit-tab-content'){
                $('#deposit-form').submit();
            } else if (activeTab === 'transfer-tab-content'){
                $('#transfer-form').submit();
            }
        });
        
        // AJAX submission handlers for each form
        $('#deposit-form').submit(function(e){
            e.preventDefault();
            handleFormSubmit(this, _base_url_+"classes/Master.php?f=deposit_from_linked_account", "Depositing funds, please wait...");
        });

        $('#transfer-form').submit(function(e){
            e.preventDefault();
            handleFormSubmit(this, _base_url_+"classes/Master.php?f=transfer_to_linked_account", "Transferring funds, please wait...");
        });

        function handleFormSubmit(form, url, progress_msg){
            var _this = $(form);
            _this.find('.pop-msg').remove(); // Clear previous messages
            var _el = $('<div>').addClass('pop-msg alert').hide();
            
            // Disable button to prevent multiple submissions
            $('#process-transaction-btn').prop('disabled', true).text('Processing...');

            var duration = 35000;

            // Show progress modal and start animation
            $('#progress-modal #progress-message').text(progress_msg);
            $('#progress-modal').modal('show');
            var progressBar = $('#progress-modal .progress-bar');
            progressBar.css('width', '0%').text('0%');
            progressBar.animate({ width: "100%" }, {
                duration: duration,
                step: function(now) {
                    progressBar.text(Math.round(now) + '%');
                }
            });

            $.ajax({
                url: url,
                data: new FormData(_this[0]),
                cache: false,
                contentType: false,
                processData: false,
                method: 'POST',
                type: 'POST',
                dataType: 'json',
                error:err=>{
                    console.log(err);
                    setTimeout(function(){
                        $('#progress-modal').modal('hide');
                        _el.addClass('alert-danger').text("An error occurred during AJAX request. Check console for details.");
                        _this.prepend(_el);
                        _el.show('slow');
                        $('#process-transaction-btn').prop('disabled', false).text('Process Transaction');
                    }, duration);
                },
                success:function(resp){
                    setTimeout(function(){
                        $('#progress-modal').modal('hide');
                        if(resp.status == 'success'){
                            _el.addClass('alert-success').text(resp.msg);
                            _this.prepend(_el);
                            _el.show('slow');
                            setTimeout(() => { location.reload() }, 2000);
                        }else if(!!resp.msg){
                            _el.addClass('alert-danger').text(resp.msg);
                             _this.prepend(_el);
                            _el.show('slow');
                            $('#process-transaction-btn').prop('disabled',false).text('Process Transaction');
                        }else{
                            _el.addClass('alert-danger').text("An unknown error occurred.");
                             _this.prepend(_el);
                            _el.show('slow');
                            $('#process-transaction-btn').prop('disabled',false).text('Process Transaction');
                        }
                    }, duration);
                }
            })
        }
    })
</script>