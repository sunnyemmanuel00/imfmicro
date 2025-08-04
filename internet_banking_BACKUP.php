<header class="py-5" id="main-header">
    <div class="container px-4 px-lg-5 my-5">
        <div class="text-center text-white">
            <h1 class="display-4 fw-bolder">Internet Banking</h1>
        </div>
    </div>
</header>
<section class="py-5">
    <div class="container px-4 px-lg-5 mt-5">
        <div class="row">
            <div class="col-md-6 offset-md-3">
                <div class="card rounded-0 shadow">
                    <div class="card-header">
                        <div class="card-title text-center">Login</div>
                    </div>
                    <div class="card-body">
                        <form id="login-frm" action="">
                            <div class="form-group">
                                <label for="email" class="control-label">Email</label>
                                <input type="email" id="email" name="email" class="form-control rounded-0" required>
                            </div>
                            <div class="form-group">
                                <label for="password" class="control-label">Password</label>
                                <div class="input-group">
                                    <input type="password" id="password" name="password" class="form-control rounded-0" required>
                                    <div class="input-group-append">
                                        <span class="input-group-text toggle-password" style="cursor: pointer;">
                                            <i class="fa fa-eye-slash"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group d-flex justify-content-end">
                                <button class="btn btn-sm btn-primary col-4 rounded-0">Login</button>
                            </div>
                            <div class="form-group">
                                <p class="text-center">Don't have an account? <a href="./?p=open_account">Open Account</a></p>
                            </div>
                        </form>

                        <div id="loginProgressContainer" class="mb-3" style="display: none;">
                            <p class="text-center text-muted mb-2">Authenticating and securing your session...</p>
                            <div class="progress" style="height: 20px;">
                                <div id="loginProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: 0%;">0%</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Define _base_url_ in a regular script block for robust global availability -->


<script type="module">
    // Import the functions you need from the SDKs
  import { initializeApp, getApps, getApp } from "https://www.gstatic.com/firebasejs/9.6.1/firebase-app.js";
import { getAuth, signInWithEmailAndPassword } from "https://www.gstatic.com/firebasejs/9.6.1/firebase-auth.js";


    // NOTE: It is highly recommended to update your Firebase Web SDK to the latest v9.x, v10.x, or v11.x
    // e.g., import { initializeApp } from "https://www.gstatic.com/firebasejs/10.0.0/firebase-app.js";

    // Your web app's Firebase configuration
    // These values are obtained directly from your Firebase Project Settings
    const firebaseConfig = {
      apiKey: "AIzaSyAkHD7A-HnZYakoiV5YxIVJamEwMe2r86w",
      authDomain: "usbmicro-ca116.firebaseapp.com",
      projectId: "usbmicro-ca116",
      storageBucket: "usbmicro-ca116.firebasestorage.app",
      messagingSenderId: "774331717251",
      appId: "1:774331717251:web:986a6350209aea275bedb6"
    };

    // Initialize Firebase
   let app;
if (!getApps().length) {
    app = initializeApp(firebaseConfig);
} else {
    app = getApp(); // Get the already initialized app
}
    const auth = getAuth(app); // Get the authentication instance

    $(function() {
        // Consolidated console logs to avoid duplicates and clarify flow
        console.log("JavaScript is running from internet_banking.php."); 
        console.log("Firebase initialized and Auth service available on client-side."); 

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

        $('#login-frm').submit(function(e) {
            e.preventDefault();
            var _this = $(this);
            _this.hide();
            $('#loginProgressContainer').show();
            $('.err-msg').remove();

            var progress = 0;
            var ajax_call_done = false;
            var ajax_response;

            const email = $('#email').val();
            const password = $('#password').val();

            // Ensure auth is defined and Firebase is properly initialized
            if (!auth) {
                ajax_response = { status: 'failed', msg: 'Firebase authentication not initialized. Please refresh.' };
                ajax_call_done = true;
            } else {
                signInWithEmailAndPassword(auth, email, password)
                    .then(userCredential => userCredential.user.getIdToken())
                    .then(idToken => {
                        // Firebase client-side authentication successful, now send token to PHP backend
                        return $.ajax({
                            url: _base_url_ + 'classes/Login.php?f=firebase_login_session',
                            method: 'POST',
                            data: { idToken: idToken },
                            dataType: 'json'
                        });
                    })
                    .then(resp => {
                        // Response from your PHP backend
                        ajax_response = resp;
                    })
                    .catch(error => {
                        // Handle Firebase client-side errors (from signInWithEmailAndPassword)
                        let msg = "An unexpected authentication error occurred.";
                        console.error("Firebase Auth Client Error:", error); // Log full error object for debugging

                        if (error.code) {
                            switch (error.code) {
                                case 'auth/invalid-email':
                                    msg = "Invalid email format.";
                                    break;
                                case 'auth/user-disabled':
                                    msg = "Your account has been disabled.";
                                    break;
                                case 'auth/user-not-found':
                                case 'auth/wrong-password':
                                    msg = "Incorrect Email or Password.";
                                    break;
                                case 'auth/too-many-requests':
                                    msg = "Too many failed login attempts. Please try again later.";
                                    break;
                                default:
                                    // If `error` is an XHR object (from jQuery's $.ajax fail),
                                    // it will have properties like `status` (HTTP status code) and `responseText`.
                                    if (error.readyState === 4 && error.status !== 200) {
                                        // This means the AJAX call to PHP failed (e.g., 500 Internal Server Error, or network error)
                                        msg = `Server Error: Please check Cloud Logs. Status: ${error.status || 'N/A'}`; // Added status for debugging
                                        if (error.responseText) {
                                            console.error("Server Response Text:", error.responseText);
                                        }
                                    } else {
                                        msg = `Firebase Error: ${error.message}`; // Fallback to Firebase's message
                                    }
                                    break;
                            }
                        }
                        ajax_response = { status: 'failed', msg: msg };
                    })
                    .finally(() => {
                        ajax_call_done = true;
                    });
            }

            var interval = setInterval(function() {
                progress++;
                let percentage = Math.min(Math.floor((progress / 150) * 100), 100);
                $('#loginProgressBar').css('width', percentage + '%').text(percentage + '%');

                if (progress >= 150) {
                    clearInterval(interval);
                    
                    var check_ajax = setInterval(function() {
                        if (ajax_call_done) {
                            clearInterval(check_ajax);
                            
                            if (ajax_response && ajax_response.status == 'success') {
                                alert_toast("Login successful!", "success");
                                $('#loginProgressContainer').hide();

                                if (ajax_response.first_login_done == 0) {
                                    const transactionPin = ajax_response.transaction_pin || 'Not found';
                                    const modalHTML = `
<div class="modal fade" id="pinReminderModal" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
        <div class="modal-content rounded-0">
            <div class="modal-header">
                <h5 class="modal-title">Important: Your Transaction PIN</h5>
            </div>
            <div class="modal-body text-center">
                <p>Please save this PIN carefully. It is required for all transactions and will <strong>not</strong> be shown again.</p>
                <h3 class="my-3">Your PIN: <strong class="text-primary">${transactionPin}</strong></h3>
                <button type="button" class="btn btn-primary btn-flat" id="acknowledgePinBtn">I Understand, Continue to Dashboard</button>
            </div>
        </div>
    </div>
</div>`;
                                    
                                    $('body').append(modalHTML);
                                    $('#pinReminderModal').modal('show');

                                    $('#acknowledgePinBtn').on('click', function() {
                                        $('#pinReminderModal').modal('hide');
                                        start_loader();
                                        $.ajax({
                                            url: _base_url_ + 'classes/Login.php?f=update_first_login_status',
                                            method: 'POST',
                                            dataType: 'json',
                                            complete: function() { 
                                                location.href = _base_url_ + 'client/';
                                            }
                                        });
                                    });

                                    $('#pinReminderModal').on('hidden.bs.modal', function () {
                                        $(this).remove();
                                    });

                                } else {
                                    // Normal login redirect
                                    setTimeout(function(){
                                        location.href = _base_url_ + 'client/';
                                    }, 1000); 
                                }
                            } else {
                                $('#loginProgressContainer').hide();
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
