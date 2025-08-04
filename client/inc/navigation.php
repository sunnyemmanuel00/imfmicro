<style>
  /* Custom CSS to force a black background and ensure content alignment */
  .main-sidebar {
    background-color: #000000 !important; /* Force black background */
    width: 100px; /* Adjusted sidebar width */
    position: fixed; /* Keep it fixed */
    height: 100%; /* Full height */
    left: 0;
    top: 0;
    z-index: 1038; /* Ensure it's above other elements */
    overflow-x: hidden; /* Hide horizontal scrollbar if content is too wide */
  }

  /* Adjust brand link container (now the <a> tag) background for black sidebar */
  /* This targets the 'a' tag directly now that it holds the branding */
  .main-sidebar .brand-link {
    background-color: #000000 !important; /* Ensure brand link also has black background */
    border-bottom: 1px solid #333333; /* Optional: A subtle border */
    /* --- CRITICAL ADJUSTMENTS FOR TOP SECTION WIDTH --- */
    width: 100% !important; /* Force the brand link to be 100% width of the sidebar */
    max-width: 100px !important; /* Explicitly cap its maximum width to 100px */
    box-sizing: border-box; /* Include padding in the element's total width */
    /* --- END CRITICAL ADJUSTMENTS --- */
    overflow: hidden; /* Hide anything that overflows */
    white-space: nowrap; /* Prevent text from wrapping within the brand link */
    text-align: center; /* Center the "MENU" text */
    padding: 15px 0 !important; /* Adjusted vertical padding for aesthetics */
    display: block !important; /* Ensure it behaves as a block for text-align and width */
    color: #ffffff !important; /* Ensure the link text is white */
    text-decoration: none !important; /* Remove underline */
  }

  /* Style for the "MENU" text within the brand link */
  .main-sidebar .brand-link .brand-text {
      font-size: 1.2em; /* Adjust font size for "MENU" */
      font-weight: bold !important; /* Make it bold */
      color: #ffffff; /* Ensure text is visible on black background */
      line-height: normal;
      white-space: nowrap; /* Ensure text stays on one line */
      overflow: hidden; /* Hide text that goes beyond the element's width */
      text-overflow: ellipsis; /* Add ellipsis (...) for hidden text */
  }
  /* Ensure the image (logo) is hidden as per request */
  .main-sidebar .brand-link .brand-image {
      display: none !important; /* Hide the logo image */
  }


  /* Adjust content wrapper to start after the sidebar */
  .content-wrapper, .main-footer {
    margin-left: 100px !important; /* Push content to the right by new sidebar width */
    transition: margin-left .3s ease-in-out; /* Smooth transition if any resizing happens */
  }

  /* --- Styles for OverlayScrollbars and Navigation Items --- */
  /* Force these internal OverlayScrollbars elements to match the parent sidebar's width */
  .sidebar .os-host,
  .sidebar .os-padding,
  .sidebar .os-viewport,
  .sidebar .os-content-glue {
      width: 100% !important; /* Take full width of its parent (.sidebar) */
      margin: 0 !important; /* Remove the -8px margin that might be causing issues */
      padding: 0 !important; /* Ensure no padding is added by these */
  }

  /* Adjust the actual content area inside the scrollbar */
  .sidebar .os-content {
      padding: 0 !important; /* Remove padding here */
      width: 100% !important;
      margin: 0 !important; /* Remove margin here */
  }
  
  /* Make sure the clearfix takes no space if it exists */
  .main-sidebar .sidebar .os-content .clearfix {
      margin: 0 !important;
      padding: 0 !important;
      height: 0 !important;
      display: none !important;
  }

  /* Ensure the nav element has no top margin/padding */
  .main-sidebar .sidebar nav.mt-4 { /* Target the nav specifically if it has mt-4 */
      margin-top: 0 !important;
      padding-top: 0 !important;
  }
  .main-sidebar .sidebar nav ul.nav { /* Target the ul if nav.mt-4 doesn't cover it */
      margin-top: 0 !important;
      padding-top: 0 !important;
  }

  /* Ensure menu text wraps or hides if too long for small width */
  .nav-sidebar .nav-link p {
      white-space: normal; /* Allow text to wrap to the next line */
      overflow: hidden; /* Hide overflowing text */
      text-overflow: ellipsis; /* Add ellipsis for hidden text */
      display: block; /* Ensure it behaves as a block for wrapping */
      font-size: 0.8em; /* Slightly smaller font size if needed */
  }
  .nav-sidebar .nav-link .nav-icon {
      margin-right: 0; /* Remove margin between icon and text */
      display: block; /* Make icon appear above text */
      text-align: center; /* Center the icon */
      width: 100%; /* Take full width for centering */
      margin-bottom: 3px; /* Space below icon */
  }
  .nav-sidebar .nav-item {
      text-align: center; /* Center menu items for smaller width */
  }
  .nav-sidebar .nav-link {
      padding: 8px 0 !important; /* Adjust padding, removing horizontal padding */
      flex-direction: column; /* Stack icon and text vertically */
      align-items: center; /* Center items in the column */
      display: flex; /* Use flexbox for stacking */
  }
  /* END Styles for OverlayScrollbars and Navigation Items */


  /* Ensure the main content doesn't overlap the sidebar on small screens */
  /* This is crucial for fixing the "empty space" issue on mobile */
  @media (max-width: 767.98px) {
    body:not(.sidebar-mini) .main-sidebar {
        margin-left: 0; /* Ensures sidebar is always visible on mobile */
    }
    .content-wrapper, .main-footer {
        margin-left: 100px !important; /* Maintain consistent margin on all screen sizes */
    }
    .navbar {
        left: 100px !important; /* CRITICAL: Ensure navbar starts after 100px sidebar */
        width: calc(100% - 100px) !important; /* CRITICAL: Ensure navbar width fills remaining space */
        transition: left .3s ease-in-out, width .3s ease-in-out;
    }
    /* Hide the navbar toggle/hamburger button if it still appears */
    .nav-item .nav-link[data-widget="pushmenu"] {
        display: none !important;
    }
  }

  /* Optional: Ensure dark text on primary links for better contrast on black */
  .nav-sidebar .nav-link.active {
    background-color: #0d6efd !important; /* A slightly brighter primary blue if needed */
    color: #ffffff !important;
  }
  .nav-sidebar .nav-link:not(.active) {
    color: #f8f9fa !important; /* Lighter text for non-active links */
  }
  .nav-sidebar .nav-item > .nav-link i {
    color: #f8f9fa !important; /* Icons also light */
  }
</style>
<aside class="main-sidebar sidebar-dark-primary elevation-4 sidebar-no-expand">
    <a href="./" class="brand-link text-sm"> <span class="brand-text font-weight-light">MENU</span>
    </a>
    <div class="sidebar os-host os-theme-light os-host-overflow os-host-overflow-y os-host-resize-disabled os-host-transition os-host-scrollbar-horizontal-hidden">
        <div class="os-resize-observer-host observed">
            <div class="os-resize-observer" style="left: 0px; right: auto;"></div>
        </div>
        <div class="os-size-auto-observer observed" style="height: calc(100% + 1px); float: left;">
            <div class="os-resize-observer"></div>
        </div>
        <div class="os-content-glue" style="margin: 0px -8px; width: 249px; height: 646px;"></div>
        <div class="os-padding">
            <div class="os-viewport os-viewport-native-scrollbars-invisible" style="overflow-y: scroll;">
                <div class="os-content" style="padding: 0px 8px; height: 100%; width: 100%;">
                    <div class="clearfix"></div>
                    <nav class="mt-4">
                        <ul class="nav nav-pills nav-sidebar flex-column text-sm nav-compact nav-flat nav-child-indent nav-collapse-hide-child" data-widget="treeview" role="menu" data-accordion="false">
                            <li class="nav-item dropdown">
                                <a href="./" class="nav-link nav-home">
                                    <i class="nav-icon fas fa-tachometer-alt"></i>
                                    <p>
                                        Dashboard
                                    </p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="./?page=transaction" class="nav-link nav-transaction">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Transactions</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="./?page=linked_accounts/manage" class="nav-link nav-linked_accounts_manage">
                                    <i class="nav-icon fas fa-link"></i>
                                    <p>Linked Accounts</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="./?page=transaction/deposit" class="nav-link nav-transaction_deposit">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Deposit</p>
                                </a>
                            </li>
                            
                            <li class="nav-item">
                                <a href="./?page=transaction/transfer" class="nav-link nav-transaction_transfer">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Transfer</p>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
        <div class="os-scrollbar os-scrollbar-horizontal os-scrollbar-unusable os-scrollbar-auto-hidden">
            <div class="os-scrollbar-track">
                <div class="os-scrollbar-handle" style="width: 100%; transform: translate(0px, 0px);"></div>
            </div>
        </div>
        <div class="os-scrollbar os-scrollbar-vertical os-scrollbar-auto-hidden">
            <div class="os-scrollbar-track">
                <div class="os-scrollbar-handle" style="height: 55.017%; transform: translate(0px, 0px);"></div>
            </div>
        </div>
        <div class="os-scrollbar-corner"></div>
    </div>
</aside>
<script>
    $(document).ready(function() {
        var page = '<?php echo isset($_GET['page']) ? $_GET['page'] : 'home' ?>';
        var s = '<?php echo isset($_GET['s']) ? $_GET['s'] : '' ?>';
        
        // Correctly handle paths with slashes
        var path_parts = page.split('/');
        var main_page = path_parts[0]; // e.g., 'transaction' or 'linked_accounts'
        var sub_page = (path_parts.length > 1) ? path_parts[1] : 'index'; // e.g., 'deposit', 'manage', or 'index'

        // Activate main page link (e.g., 'nav-transaction' or 'nav-linked_accounts')
        if ($('.nav-link.nav-' + main_page).length > 0) {
            $('.nav-link.nav-' + main_page).addClass('active');
            // If it's a parent link with children (like 'transactions' might be)
            if ($('.nav-link.nav-' + main_page).siblings('.nav-treeview').length > 0) {
                $('.nav-link.nav-' + main_page).parent().addClass('menu-open');
            }
        }
        
        // Activate sub-page link (e.g., 'nav-transaction_deposit' or 'nav-linked_accounts_manage')
        // We use combined class for specific sub-page highlighting
        if (path_parts.length > 1) { // Only if a sub-page exists
            var full_nav_class = main_page + '_' + sub_page; // e.g., 'transaction_deposit', 'linked_accounts_manage'
            if ($('.nav-link.nav-' + full_nav_class).length > 0) {
                $('.nav-link.nav-' + full_nav_class).addClass('active');
            }
        } else if (main_page == 'transaction' && sub_page == 'index') {
            // Special handling for the main 'transactions' link if it's the index
            $('.nav-link.nav-transaction').addClass('active');
        }

        // Existing logic for 's' parameter, if still used
        if(s!=''){
            // This part might need adjustment depending on how 's' is used
            // Currently, it's not directly integrated with the new page structure
            // For now, leaving it as is, assuming it handles other specific cases.
            if($('.nav-link.nav-'+page+'_'+s).length > 0){
                $('.nav-link.nav-'+page+'_'+s).addClass('active');
                if($('.nav-link.nav-'+page+'_'+s).hasClass('tree-item') == true){
                    $('.nav-link.nav-'+page+'_'+s).closest('.nav-treeview').siblings('a').addClass('active');
                    $('.nav-link.nav-'+page+'_'+s).closest('.nav-treeview').parent().addClass('menu-open');
                }
                if($('.nav-link.nav-'+page+'_'+s).hasClass('nav-is-tree') == true){
                    $('.nav-link.nav-'+page+'_'+s).parent().addClass('menu-open');
                }
            }
        }
    })
</script>