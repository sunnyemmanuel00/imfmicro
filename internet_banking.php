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

<script type="module">
 // Updated Firebase SDK version
 import { initializeApp, getApps, getApp } from "https://www.gstatic.com/firebasejs/10.12.2/firebase-app.js";
 import { getAuth, signInWithEmailAndPassword } from "https://www.gstatic.com/firebasejs/10.12.2/firebase-auth.js";
 
 // Use the same configuration you had before
 const firebaseConfig = {
 apiKey: "AIzaSyAkHD7A-HnZYakoiV5YxIVJamEwMe2r86w",
 authDomain: "usbmicro-ca116.firebaseapp.com",
 projectId: "usbmicro-ca116",
 storageBucket: "usbmicro-ca116.firebasestorage.app",
 messagingSenderId: "774331717251",
 appId: "1:774331717251:web:986a6350209aea275bedb6"
 };

 let app;
 if (!getApps().length) {
  app = initializeApp(firebaseConfig);
 } else {
  app = getApp();
 }
 const auth = getAuth(app);
 window.auth = auth;

 $(function() {
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

   signInWithEmailAndPassword(auth, email, password)
    .then(userCredential => userCredential.user.getIdToken())
    .then(idToken => {
     return $.ajax({
      url: window._base_url_ + '?f=firebase_login_session',
      method: 'POST',
      data: { idToken: idToken },
      dataType: 'json'
     });
    })
    .then(resp => {
     ajax_response = resp;
    })
    .catch(error => {
     let msg = "An unexpected authentication error occurred.";
     // Improved error handling to catch specific Firebase errors
     if (error.code) {
      switch (error.code) {
       case 'auth/user-not-found':
       case 'auth/wrong-password':
       case 'auth/invalid-credential':
        msg = "Incorrect email or password.";
        break;
       default:
        // If the error comes from the backend, we can still get the message from responseJSON
        msg = error.message;
        break;
      }
     }
     ajax_response = { status: 'failed', msg: msg };
    })
    .finally(() => {
     ajax_call_done = true;
    });

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
         const transactionPin = ajax_response.pin || 'Not found';
         const modalHTML = `
         <div class="modal fade" id="pinReminderModal" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
          <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
           <div class="modal-content rounded-0">
            <div class="modal-header"><h5 class="modal-title">Important: Your Transaction PIN</h5></div>
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
           url: window._base_url_ + '?f=update_first_login_status',
           method: 'POST',
           dataType: 'json',
           complete: function() { 
            location.href = window._base_url_ + 'client/';
           }
          });
         });

         $('#pinReminderModal').on('hidden.bs.modal', function () { $(this).remove(); });
        } else {
         setTimeout(function(){
          location.href = window._base_url_ + 'client/';
         }, 1000); 
        }
       } else {
        $('#loginProgressContainer').hide();
        var error_msg = (ajax_response && ajax_response.msg) ? ajax_response.msg : 'An unexpected error occurred.';
        _this.prepend($('<div>').addClass("alert alert-danger err-msg").text(error_msg)).show();
       }
      }
     }, 50);
    }
   }, 100);
  });
 });
</script>
