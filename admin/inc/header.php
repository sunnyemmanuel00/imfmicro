<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo $_settings->info('title') != false ? $_settings->info('title').' | ' : '' ?><?php echo $_settings->info('name') ?></title>
  <link rel="icon" href="<?php echo validate_image($_settings->info('logo')) ?>" />
  
  <link rel="stylesheet" href="<?php echo base_url ?>plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="<?php echo base_url ?>plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
  <link rel="stylesheet" href="<?php echo base_url ?>plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="<?php echo base_url ?>plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
  <link rel="stylesheet" href="<?php echo base_url ?>plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
  <link rel="stylesheet" href="<?php echo base_url ?>plugins/datatables-select/css/select.bootstrap4.min.css">
  <link rel="stylesheet" href="<?php echo base_url ?>plugins/select2/css/select2.min.css">
  <link rel="stylesheet" href="<?php echo base_url ?>plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
  <link rel="stylesheet" href="<?php echo base_url ?>plugins/icheck-bootstrap/icheck-bootstrap.min.css">
  <link rel="stylesheet" href="<?php echo base_url ?>plugins/jqvmap/jqvmap.min.css">
  <link rel="stylesheet" href="<?php echo base_url ?>dist/css/adminlte.css">
  <link rel="stylesheet" href="<?php echo base_url ?>dist/css/custom.css">
  <link rel="stylesheet" href="<?php echo base_url ?>plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
  <link rel="stylesheet" href="<?php echo base_url ?>plugins/daterangepicker/daterangepicker.css">
  <link rel="stylesheet" href="<?php echo base_url ?>plugins/summernote/summernote-bs4.min.css">
  <link rel="stylesheet" href="<?php echo base_url ?>plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css">
  <link rel="stylesheet" href="<?php echo base_url ?>plugins/fullcalendar/main.css">
  
  <script src="<?php echo base_url ?>plugins/jquery/jquery.min.js"></script>
  <script src="<?php echo base_url ?>plugins/jquery-ui/jquery-ui.min.js"></script>
  <script src="<?php echo base_url ?>plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="<?php echo base_url ?>plugins/sweetalert2/sweetalert2.min.js"></script>
  <script src="<?php echo base_url ?>plugins/toastr/toastr.min.js"></script>
  <script src="<?php echo base_url ?>plugins/moment/moment.min.js"></script>
  <script src="<?php echo base_url ?>plugins/fullcalendar/main.js"></script>
  <script src="<?php echo base_url ?>dist/js/adminlte.js"></script>
  <script>
    // Defines the base URL for all scripts on the page
    window._base_url_ = '<?php echo base_url ?>';
  </script>
  <script src="<?php echo base_url ?>dist/js/script.js"></script>
  <script>
      // Admin Login Form Handler
      $(function(){
        $('#login-frm').submit(function(e){
          e.preventDefault();
          start_loader();
          if($('.err_msg').length > 0)
            $('.err_msg').remove();
          $.ajax({
            url: window._base_url_ + "classes/Login.php?f=login",
            method: "POST",
            data: $(this).serialize(),
            dataType: "json",
            error: err => {
              console.log(err);
              alert_toast("An error occurred", 'error');
              end_loader();
            },
            success: function(resp){
              if(typeof resp == 'object' && resp.status == 'success'){
                alert_toast("Login successful", 'success');
                setTimeout(function(){
                  location.href = window._base_url_ + "admin";
                }, 500);
              } else if(resp.status == 'incorrect'){
                var _err_el = $('<div>').addClass("alert alert-danger err_msg").text("Incorrect Username or Password.");
                $('#login-frm').prepend(_err_el);
                end_loader();
              } else {
                console.log(resp);
                alert_toast("An unknown error occurred", 'error');
                end_loader();
              }
            }
          })
        })
      })
  </script>
</head>