<?php
// The require_once('../config.php') line is removed because index.php now loads it for us.
?>
<!DOCTYPE html>
<html lang="en" class="" style="height: auto;">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Login | <?php echo $_settings->info('name') ?></title>
  <link rel="icon" href="<?php echo validate_image($_settings->info('logo')) ?>" />
  <link rel="stylesheet" href="<?php echo base_url ?>plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="<?php echo base_url ?>plugins/icheck-bootstrap/icheck-bootstrap.min.css">
  <link rel="stylesheet" href="<?php echo base_url ?>dist/css/adminlte.min.css">
  <link rel="stylesheet" href="<?php echo base_url ?>plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css">
</head>
<body class="hold-transition login-page">
  <script>
    window.start_loader = function(){ document.body.insertAdjacentHTML('beforeend', '<div id="loader-wrapper"><div class="loader"></div></div>'); }
    window.end_loader = function(){ const loader = document.getElementById('loader-wrapper'); if(loader) loader.remove(); }
    window.alert_toast= function(msg = 'TEST', bg = 'success' ,pos=''){
        var Toast = Swal.mixin({ toast: true, position: pos || 'top-end', showConfirmButton: false, timer: 3000 });
        Toast.fire({ icon: bg, title: msg })
    }
    start_loader();
  </script>
<div class="login-box">
  <div class="card card-outline card-primary">
    <div class="card-header text-center">
      <a href="<?php echo base_url ?>" class="h1"><b>Admin Login</b></a>
    </div>
    <div class="card-body">
      <p class="login-box-msg">Sign in to start your session</p>
      <form id="login-frm" action="" method="post">
        <div class="input-group mb-3">
          <input type="text" class="form-control" name="username" placeholder="Username" autofocus>
          <div class="input-group-append"><div class="input-group-text"><span class="fas fa-user"></span></div></div>
        </div>
        <div class="input-group mb-3">
          <input type="password" class="form-control" name="password" placeholder="Password">
          <div class="input-group-append"><div class="input-group-text"><span class="fas fa-lock"></span></div></div>
        </div>
        <div class="row">
          <div class="col-8"><a href="<?php echo base_url ?>">Go to Website</a></div>
          <div class="col-4"><button type="submit" class="btn btn-primary btn-block">Sign In</button></div>
        </div>
      </form>
    </div>
  </div>
</div>
<script src="<?php echo base_url ?>plugins/jquery/jquery.min.js"></script>
<script src="<?php echo base_url ?>plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo base_url ?>dist/js/adminlte.min.js"></script>
<script src="<?php echo base_url ?>plugins/sweetalert2/sweetalert2.min.js"></script>
<script>
  $(document).ready(function(){
    $('#login-frm').submit(function(e){
      e.preventDefault();
      start_loader();
      $('.err-msg').remove();
      $.ajax({
        url: "<?php echo base_url ?>?f=login",
        method: "POST",
        data: $(this).serialize(),
        dataType: "json",
        error: err => { console.log(err); alert_toast("An error occurred", 'error'); end_loader(); },
        success: function(resp){
          if(typeof resp == 'object' && resp.status == 'success'){
            alert_toast("Login successful", 'success');
            setTimeout(function(){ location.href = "<?php echo base_url ?>admin"; }, 500);
          } else {
            $('#login-frm').prepend($('<div>').addClass("alert alert-danger err-msg").text("Incorrect Username or Password."));
            end_loader();
          }
        }
      })
    })
    end_loader();
  })
</script>
</body>
</html>