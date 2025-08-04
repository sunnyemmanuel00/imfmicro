<header class="bg-dark py-5" id="main-header">
    <div class="container px-4 px-lg-5 my-5">
        <div class="text-center text-white">
            <h1 class="display-4 fw-bolder">Open a New Account</h1>
        </div>
    </div>
</header>
<section class="py-5">
    <div class="container d-flex justify-content-center">
        <div class="card col-md-8 p-0">
            <div class="card-header">
                <div class="card-title text-center w-100">Account Information</div>
            </div>
            <div class="card-body">
                <form action="" id="open-account-form">
                    <div class="row">
                        <div class="form-group col-md-4">
                            <label for="firstname" class='control-label'>First Name</label>
                            <input type="text" class="form-control" name="firstname" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="middlename" class='control-label'>Middle Name (Optional)</label>
                            <input type="text" class="form-control" name="middlename">
                        </div>
                        <div class="form-group col-md-4">
                            <label for="lastname" class='control-label'>Last Name</label>
                            <input type="text" class="form-control" name="lastname" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="address" class='control-label'>Address</label>
                        <textarea class="form-control" name="address" rows="2" required></textarea>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-4">
                            <label for="marital_status" class='control-label'>Marital Status</label>
                            <select class="form-control" name="marital_status" required>
                                <option value="">Select</option>
                                <option value="Single">Single</option>
                                <option value="Married">Married</option>
                                <option value="Divorced">Divorced</option>
                                <option value="Widowed">Widowed</option>
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="gender" class='control-label'>Gender</label>
                            <select class="form-control" name="gender" required>
                                <option value="">Select</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="phone_number" class='control-label'>Phone Number</label>
                            <input type="text" class="form-control" name="phone_number" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="date_of_birth" class='control-label'>Date of Birth</label>
                        <input type="date" class="form-control" name="date_of_birth" required>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="id_type" class='control-label'>Type of Identity</label>
                            <select class="form-control" name="id_type" required>
                                <option value="">Select</option>
                                <option value="Passport">Passport</option>
                                <option value="National ID">National ID</option>
                                <option value="Drivers License">Driver's License</option>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="id_number" class='control-label'>ID Card Number</label>
                            <input type="text" class="form-control" name="id_number" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="email" class='control-label'>Email</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="password" class='control-label'>Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" name="password" id="password" required>
                                <div class="input-group-append">
                                    <span class="input-group-text toggle-password" style="cursor: pointer;">
                                        <i class="fa fa-eye-slash"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="confirm_password" class='control-label'>Confirm Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" name="confirm_password" id="confirm_password" required>
                                <div class="input-group-append">
                                    <span class="input-group-text toggle-password" style="cursor: pointer;">
                                        <i class="fa fa-eye-slash"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group d-flex justify-content-end mt-3">
                        <button class="btn btn-sm btn-primary btn-flat">Submit Application</button>
                    </div>
                </form>

                <!-- NEW PROGRESS BAR ELEMENT FOR SIGN UP -->
                <div id="signupProgressContainer" class="mb-3" style="display: none;">
                    <p class="text-center text-muted mb-2">Creating your account securely...</p>
                    <div class="progress" style="height: 20px;">
                        <div id="signupProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                    </div>
                </div>
                <!-- END OF NEW ELEMENT -->

                <div class="text-center mt-3">
                    <p>If you have an account, <a href="./?p=internet_banking">Login here</a></p>
                </div>
            </div>
        </div>
    </div>
</section>

<script type="module">
    import { createUserWithEmailAndPassword } from "https://www.gstatic.com/firebasejs/9.6.1/firebase-auth.js";

    $(function(){
        // Password toggle functionality
        $(document).on('click', '.toggle-password', function() {
            var input = $(this).closest('.input-group').find('input');
            var icon = $(this).find('i');
            if (input.attr('type') === 'password') {
                input.attr('type', 'text');
                icon.removeClass('fa-eye-slash').addClass('fa-eye');
            } else {
                input.attr('type', 'password');
                icon.removeClass('fa-eye').addClass('fa-eye-slash');
            }
        });

        $('#open-account-form').submit(function(e){
            e.preventDefault();
            var _this = $(this);
            $('.err-msg').remove();

            var password = $('[name="password"]').val();
            var confirmPassword = $('[name="confirm_password"]').val();

            if(password !== confirmPassword){
                var el = $('<div class="alert alert-danger err-msg"><i class="fa fa-exclamation-triangle"></i> Passwords do not match.</div>');
                _this.prepend(el);
                el.show('slow');
                return false;
            }

            // --- NEW SIGN-UP UX ---
            // 1. Hide the form and show the progress bar.
            _this.hide();
            $('#signupProgressContainer').show();

            var progress = 0;
            var ajax_call_done = false;
            var ajax_response;

            // 2. Start the sign-up process in the background
            const auth = window.auth;
            const email = $('[name="email"]').val();
            var formData = new FormData(_this[0]);

            if (!auth) {
                ajax_response = { status: 'failed', msg: 'Authentication system failed to load. Please refresh the page.' };
                ajax_call_done = true;
                return;
            }

            createUserWithEmailAndPassword(auth, email, password)
                .then(userCredential => userCredential.user)
                .then(user => {
                    formData.append('firebase_uid', user.uid);
                    formData.delete('password');
                    formData.delete('confirm_password');
                    
                    return $.ajax({
                        url: _base_url_ + "classes/Account.php?f=save_account",
                        data: formData,
                        cache: false,
                        contentType: false,
                        processData: false,
                        method: 'POST',
                        type: 'POST',
                        dataType: 'json'
                    });
                })
                .then(resp => {
                    ajax_response = resp;
                })
                .catch(error => {
                    let msg = "An unknown error occurred during registration.";
                    if (error.code) { // Firebase specific errors
                        if (error.code === 'auth/email-already-in-use') {
                            msg = "This email address is already registered.";
                        } else if (error.code === 'auth/weak-password') {
                            msg = "The password is too weak (must be at least 6 characters).";
                        }
                    } else if (error.responseText) { // AJAX error
                        msg = "A server error occurred saving your details.";
                    }
                    ajax_response = { status: 'failed', msg: msg };
                })
                .finally(() => {
                    ajax_call_done = true;
                });

            // 3. Start the 15-second progress bar animation
            var interval = setInterval(function() {
                progress++;
                let percentage = Math.min(Math.floor((progress / 150) * 100), 100);
                $('#signupProgressBar').css('width', percentage + '%').text(percentage + '%');

                // If 15 seconds have passed
                if (progress >= 150) {
                    clearInterval(interval);
                    
                    var check_ajax = setInterval(function() {
                        if (ajax_call_done) {
                            clearInterval(check_ajax);
                            
                            // 4. Check the server response
                            if (ajax_response && ajax_response.status == 'success') {
                                // Show success message
                                $('#signupProgressContainer').hide();
                                var el = $('<div class="alert alert-success"></div>').text(ajax_response.msg);
                                _this.before(el);
                                _this[0].reset();
                                _this.hide();

                                // Hide message and redirect after 1 minute
                                setTimeout(() => {
                                    location.href = './?p=internet_banking';
                                }, 60000);

                            } else {
                                // If sign-up failed, show error message and restore the form
                                $('#signupProgressContainer').hide();
                                var error_msg = (ajax_response && ajax_response.msg) ? ajax_response.msg : 'An unexpected error occurred.';
                                var _err_el = $('<div class="alert alert-danger err-msg"><i class="fa fa-exclamation-triangle"></i> ' + error_msg + '</div>');
                                _this.prepend(_err_el).show();
                            }
                        }
                    }, 50);
                }
            }, 100);
        });
    });
</script>