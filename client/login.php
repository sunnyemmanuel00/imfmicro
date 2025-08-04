<?php 
require_once('../config.php'); // Assuming config.php is in the parent directory
require_once('../classes/SystemSettings.php'); // Include SystemSettings to use $_settings
?>
<!DOCTYPE html>
<html lang="en" class="" style="height: auto;">
<?php require_once('inc/header.php') ?>
<body class="hold-transition login-page">
    <script>
        start_loader()
    </script>
    <div class="login-box">
        <div class="card card-outline card-primary">
            <div class="card-header text-center">
                <a href="./" class="h1"><b>Admin Login</b></a>
            </div>
            <div class="card-body">
                <p class="login-box-msg">Sign in to start your session</p>

                <form id="login-frm" action="" method="post">
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" name="username" placeholder="Username">
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-user"></span>
                            </div>
                        </div>
                    </div>
                    <div class="input-group mb-3">
                        <input type="password" class="form-control" name="password" placeholder="Password">
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-lock"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-8">
                            <a href="<?php echo base_url ?>">Go to Website</a>
                        </div>
                        <div class="col-4">
                            <button type="submit" class="btn btn-primary btn-block">Sign In</button>
                        </div>
                        </div>
                </form>
                </div>
            </div>
        </div>
    <script src="plugins/jquery/jquery.min.js"></script>
    <script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="dist/js/adminlte.min.js"></script>

    <script>
        $(document).ready(function(){
            end_loader();

            // Handle login form submission
            $('#login-frm').submit(function(e){
                e.preventDefault();
                var _this = $(this);
                $('.err-msg').remove();
                $.ajax({
                    url:_base_url_+"classes/Login.php?f=login", // Assuming a login handling script exists
                    data: new FormData($(this)[0]),
                    cache: false,
                    contentType: false,
                    processData: false,
                    method: 'POST',
                    type: 'POST',
                    dataType: 'json',
                    error:err=>{
                        console.log(err);
                        alert_toast("An error occurred",'error');
                        end_loader();
                    },
                    success:function(resp){
                        if(resp.status == 'success'){
                            location.href = _base_url_+'admin'; // Redirect to admin dashboard on success
                        }else if(resp.status == 'incorrect'){
                            var _frm = $('#login-frm');
                            _frm.prepend('<div class="alert alert-danger _err_msg"><i class="fa fa-exclamation-triangle"></i> Incorrect Username or Password</div>');
                            _frm.find('input').addClass('is-invalid');
                            $('[name="username"]').focus();
                            end_loader();
                        } else {
                            alert_toast("An error occurred",'error');
                            end_loader();
                        }
                    }
                });
            });
        });
    </script>
</body>
</html>