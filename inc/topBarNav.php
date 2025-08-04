<nav class="navbar navbar-expand-lg navbar-light bg-primary text-light fixed-top">
    <div class="container px-4 px-lg-5 ">
        <button class="navbar-toggler btn btn-sm d-flex flex-column align-items-center d-lg-none" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="fw-bold text-light mb-1">Menu</span>
            <span class="navbar-toggler-icon" style="width: 1.7em; height: 1.7em;">
                <svg viewBox="0 0 30 30" xmlns="http://www.w3.org/2000/svg" style="stroke: rgba(255, 255, 255, 0.75); stroke-width: 3; stroke-linecap: round; stroke-miterlimit: 10;">
                    <path d="M4 7h22M4 15h22M4 23h22"/>
                </svg>
            </span>
        </button>
        <a class="navbar-brand text-light" href="./">
            <img src="<?php echo validate_image($_settings->info('logo')) ?>" width="100" height="50" class="d-inline-block align-top" alt="" loading="lazy">
            <?php echo $_settings->info('short_name') ?>
        </a>

        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-4">
                <li class="nav-item"><a class="nav-link text-light" aria-current="page" href="./">Home |</a></li>
                <li class="nav-item"><a class="nav-link  text-light" href="./?p=about">About |</a></li>
                <li class="nav-item"><a class="nav-link  text-light" href="./?p=internet_banking">Login Internet Banking |</a></li>
                <li class="nav-item"><a class="nav-link  text-light" href="./?p=open_account">Open Account |</a></li>
                <li class="nav-item"><a class="nav-link  text-light" href="./?p=contact_us">Contact Us |</a></li>
                <li class="nav-item"><a class="nav-link  text-light" href="./?p=trade_finance">Trade Finance |</a></li>
                <li class="nav-item"><a class="nav-link  text-light" href="./?p=loans_credit">Loans & Credit</a></li>
            </ul>
            <div class="d-flex align-items-center">

            </div>
        </div>
    </div>
</nav>
<script>
  $(function(){
    $('#login-btn').click(function(){
      uni_modal("","login.php")
    })
    $('#navbarResponsive').on('show.bs.collapse', function () {
      $('#mainNav').addClass('navbar-shrink')
    })
    $('#navbarResponsive').on('hidden.bs.collapse', function () {
      if($('body').offset.top == 0)
        $('#mainNav').removeClass('navbar-shrink')
    })
  })

  $('#search-form').submit(function(e){
    e.preventDefault()
     var sTxt = $('[name="search"]').val()
     if(sTxt != '')
      location.href = './?p=products&search='+sTxt;
  })
</script>