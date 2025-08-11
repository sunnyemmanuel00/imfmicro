<script>
  $(document).ready(function(){
    window.viewer_modal = function($src = ''){
      start_loader()
      var t = $src.split('.')
      t = t[1]
      if(t =='mp4'){
        var view = $("<video src='"+$src+"' controls autoplay></video>")
      }else{
        var view = $("<img src='"+$src+"' />")
      }
      $('#viewer_modal .modal-content video,#viewer_modal .modal-content img').remove()
      $('#viewer_modal .modal-content').append(view)
      $('#viewer_modal').modal({
            show:true,
            backdrop:'static',
            keyboard:false,
            focus:true
      })
      end_loader()  

    }
    window.uni_modal = function($title = '' , $url='',$size=""){
        start_loader()
        $.ajax({
            url:$url,
            error:err=>{
                console.log(err)
                alert("An error occured")
            },
            success:function(resp){
                if(resp){
                    $('#uni_modal .modal-title').html($title)
                    $('#uni_modal .modal-body').html(resp)
                    if($size != ''){
                        $('#uni_modal .modal-dialog').addClass($size+'  modal-dialog-centered')
                    }else{
                        $('#uni_modal .modal-dialog').removeAttr("class").addClass("modal-dialog modal-md modal-dialog-centered")
                    }
                    $('#uni_modal').modal({
                      show:true,
                      backdrop:'static',
                      keyboard:false,
                      focus:true
                    })
                    end_loader()
                }
            }
        })
    }
    window._conf = function($msg='',$func='',$params = []){
       $('#confirm_modal #confirm').attr('onclick',$func+"("+$params.join(',')+")")
       $('#confirm_modal .modal-body').html($msg)
       $('#confirm_modal').modal('show')
    }
  })
</script>
<footer class="main-footer text-sm">
  <strong>Copyright  IMF 2016 - <?php echo date('Y') ?>. 
  </strong>
  All rights reserved.
  <div class="float-right d-none d-sm-inline-block">
    <b><?php echo $_settings->info('short_name') ?> v1.0</b>
  </div>
</footer>
</div>
  <script>
 
    $.widget.bridge('uibutton', $.ui.button)
  </script>
  <script src="<?php echo base_url ?>plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="<?php echo base_url ?>plugins/chart.js/Chart.min.js"></script>
  <script src="<?php echo base_url ?>plugins/sparklines/sparkline.js"></script>
  <script src="<?php echo base_url ?>plugins/select2/js/select2.full.min.js"></script>
  <script src="<?php echo base_url ?>plugins/jqvmap/jquery.vmap.min.js"></script>
  <script src="<?php echo base_url ?>plugins/jqvmap/maps/jquery.vmap.usa.js"></script>
  <script src="<?php echo base_url ?>plugins/jquery-knob/jquery.knob.min.js"></script>
  <script src="<?php echo base_url ?>plugins/moment/moment.min.js"></script>
  <script src="<?php echo base_url ?>plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js"></script>
  <script src="<?php echo base_url ?>plugins/summernote/summernote-bs4.min.js"></script>
  <script src="<?php echo base_url ?>plugins/datatables/jquery.dataTables.min.js"></script>
  <script src="<?php echo base_url ?>plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
  <script src="<?php echo base_url ?>plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
  <script src="<?php echo base_url ?>plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
  <script src="<?php echo base_url ?>plugins/datatables-select/js/select.bootstrap4.min.js"></script>
  <script src="<?php echo base_url ?>dist/js/adminlte.js"></script>

  <div class="jqvmap-label" style="display: none; left: 1093.83px; top: 394.361px;">Idaho</div>


<div id="inactivity-modal" class="modal" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Session Timeout Warning</h5>
      </div>
      <div class="modal-body">
        <p>You will be logged out in <span id="countdown-timer" class="font-weight-bold">30</span> seconds due to inactivity.</p>
        <p>Click the button below to stay logged in.</p>
      </div>
      <div class="modal-footer">
        <button id="stay-logged-in-btn" type="button" class="btn btn-primary">Stay Logged In</button>
      </div>
    </div>
  </div>
</div>

<script type="text/javascript">
$(document).ready(function() {
  // --- CONFIGURATION ---
  const INACTIVITY_TIMEOUT = 6 * 60 * 1000; // 6 minutes
  const WARNING_TIME = 30 * 1000;      // 30 second warning
  const LOGOUT_URL = '<?php echo base_url ?>?f=clogout'; // Corrected to use smart router

  // --- SCRIPT LOGIC ---
  let inactivityTimer, warningTimer, countdownInterval;

  function performLogout() {
    console.log("Inactivity timeout. Logging out...");
    window.location.href = LOGOUT_URL;
  }

  function showWarningModal() {
    let countdown = WARNING_TIME / 1000;
    $('#countdown-timer').text(countdown);
    $('#inactivity-modal').modal('show');
    countdownInterval = setInterval(function() {
      countdown--;
      $('#countdown-timer').text(Math.max(0, countdown));
    }, 1000);
  }

  function resetInactivityTimer() {
    clearTimeout(inactivityTimer);
    clearTimeout(warningTimer);
    clearInterval(countdownInterval);
    $('#inactivity-modal').modal('hide');
    warningTimer = setTimeout(showWarningModal, INACTIVITY_TIMEOUT - WARNING_TIME);
    inactivityTimer = setTimeout(performLogout, INACTIVITY_TIMEOUT);
  }

  // --- EVENT LISTENERS ---
  const activityEvents = 'mousemove keypress scroll click touchstart touchend';
  $(document).on(activityEvents, resetInactivityTimer);
  $('#stay-logged-in-btn').on('click', resetInactivityTimer);

  // --- START TIMER ---
  resetInactivityTimer();
  console.log("Inactivity timer started.");
});
</script>