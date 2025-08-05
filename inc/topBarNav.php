<?php
// ===========================================================================
// Corrected Top Navigation Bar - `inc/topBarNav.php`
// This file defines the top navigation bar for the public-facing website.
// The logo path has been updated to use a direct, dynamic URL to fix the
// `localhost` issue on live deployments.
// ===========================================================================
?>
<nav class="navbar navbar-expand-lg navbar-light bg-primary text-light fixed-top">
    <div class="container px-4 px-lg-5 ">
        <!-- Navbar Toggler for mobile view -->
        <button class="navbar-toggler btn btn-sm d-flex flex-column align-items-center d-lg-none" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="fw-bold text-light mb-1">Menu</span>
            <span class="navbar-toggler-icon" style="width: 1.7em; height: 1.7em;">
                <!-- SVG for the hamburger icon -->
                <svg viewBox="0 0 30 30" xmlns="http://www.w3.org/2000/svg" style="stroke: rgba(255, 255, 255, 0.75); stroke-width: 3; stroke-linecap: round; stroke-miterlimit: 10;">
                    <path d="M4 7h22M4 15h22M4 23h22"/>
                </svg>
            </span>
        </button>

        <!-- Brand/Logo and Site Name -->
        <a class="navbar-brand text-light" href="./">
            <!--
            The logo path below has been changed.
            It now uses a simple relative path to bypass the problematic
            `validate_image()` function that was causing the `localhost` error.
            This ensures the logo path is correct on both local and live sites.
            -->
            <img src="<?php echo base_url . 'uploads/1626243720_bank.jpg' ?>" width="100" height="50" class="d-inline-block align-top" alt="IMF Micro Finance Bank Logo" loading="lazy">
            <?php echo $_settings->info('short_name') ?>
        </a>

        <!-- Main Navigation Links -->
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
        </div>
    </div>
</nav>

<!-- JavaScript for button functionality -->
<script>
    $(function(){
        // Handles the click event for the login button (if one exists with id="login-btn")
        $('#login-btn').click(function(){
            uni_modal("","login.php")
        })

        // Handles the navbar collapsing/expanding on smaller screens
        $('#navbarSupportedContent').on('show.bs.collapse', function () {
            $('#mainNav').addClass('navbar-shrink')
        })
        $('#navbarSupportedContent').on('hidden.bs.collapse', function () {
            if($('body').offset.top == 0)
                $('#mainNav').removeClass('navbar-shrink')
        })
    })

    // Handles form submission for a search bar (if one exists with id="search-form")
    $('#search-form').submit(function(e){
        e.preventDefault()
        var sTxt = $('[name="search"]').val()
        if(sTxt != '')
            location.href = './?p=products&search='+sTxt;
    })
</script>
