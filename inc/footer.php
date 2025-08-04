<script>
  $(document).ready(function(){
    $('#p_use').click(function(){
      uni_modal("Privacy Policy","policy.php","mid-large")
    })
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
          alert("An error occurred")
        },
        success:function(resp){
          if(resp){
            $('#uni_modal .modal-title').html($title)
            $('#uni_modal .modal-body').html(resp)
            if($size != ''){
              $('#uni_modal .modal-dialog').addClass($size+' modal-dialog-centered')
            }else{
              $('#uni_modal .modal-dialog').removeAttr("class").addClass("modal-dialog modal-md modal-dialog-centered")
            }
            $('#uni_modal').modal({
              show:true,
              backdrop:'static',
              keyboard:false,
              focus:true
           .modal('show')
    }
  })
</script>
<footer class="py-5 bg-dark">
  <div class="container d-flex justify-content-between align-items-start">
    <div>
      <h5 class="text-white">Quick Links</h5>
      <ul class="list-unstyled">
        <li><i class="bi bi-chevron-right text-light me-2"></i><a href="./" class="text-light" style="text-decoration: none !important;">Home</a></li>
        <li><i class="bi bi-chevron-right text-light me-2"></i><a href="./?p=about" class="text-light" style="text-decoration: none !important;">About</a></li>
        <li><i class="bi bi-chevron-right text-light me-2"></i><a href="./?p=internet_banking" class="text-light" style="text-decoration: none !important;">Login</a></li>
        <li><i class="bi bi-chevron-right text-light me-2"></i><a href="./?p=open_account" class="text-light" style="text-decoration: none !important;">Sign up</a></li>
        <li><i class="bi bi-chevron-right text-light me-2"></i><a href="./?p=contact_us" class="text-light" style="text-decoration: none !important;">Write us</a></li>
        <li><i class="bi bi-chevron-right text-light me-2"></i><a href="./?p=trade_finance" class="text-light" style="text-decoration: none !important;">Trade</a></li>
        <li><i class="bi bi-chevron-right text-light me-2"></i><a href="./?p=loans_credit" class="text-light" style="text-decoration: none !important;">Loans</a></li>
      </ul>
    </div>
    <div class="text-end">
      <img src="<?php echo base_url ?>uploads/fdic.png" alt="FDIC Insured" height="70">
    </div>
  </div>
  <hr class="bg-light my-3">
  <p class="m-0 text-center text-white">Copyright &copy; <?php echo $_settings->info('short_name') ?> 2009 - <?php echo date("Y"); ?> IMF Micro Finance Bank</p>
  <p class="m-0 text-center text-white">All Rights Reserved</p>
</footer>
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
  <script src="<?php echo base_url ?>plugins/daterangepicker/daterangepicker.js"></script>
  <script src="<?php echo base_url ?>plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js"></script>
  <script src="<?php echo base_url ?>plugins/summernote/summernote-bs4.min.js"></script>
  <script src="<?php echo base_url ?>plugins/datatables/jquery.dataTables.min.js"></script>
  <script src="<?php echo base_url ?>plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
  <script src="<?php echo base_url ?>plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
  <script src="<?php echo base_url ?>plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
  <script src="<?php echo base_url ?>dist/js/adminlte.js"></script>
  <?php 
// This is the CORRECT if statement. It uses your website's own function to check for a logged-in user.
if(empty($_settings->userdata('account_id'))): 
?>

<script type="text/javascript">
var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
(function(){
var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
s1.async=true;
s1.src='https://embed.tawk.to/685381dc132bf5191061a1c7/1iu336hsk';
s1.charset='UTF-8';
s1.setAttribute('crossorigin','*');
s0.parentNode.insertBefore(s1,s0);
})();
</script>
<?php endif; ?>

</body>
</html>