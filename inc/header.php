<?php
require_once('sess_auth.php'); // Ensure session and authentication logic is included

?>
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
    <link rel="stylesheet" href="<?php echo base_url ?>plugins/select2/css/select2.min.css">
    <link rel="stylesheet" href="<?php echo base_url ?>plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
    <link rel="stylesheet" href="<?php echo base_url ?>plugins/icheck-bootstrap/icheck-bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo base_url ?>plugins/jqvmap/jqvmap.min.css">
    <link rel="stylesheet" href="<?php echo base_url ?>dist/css/adminlte.css">
    <link rel="stylesheet" href="<?php echo base_url ?>dist/css/custom.css">
    <link rel="stylesheet" href="<?php echo base_url ?>assets/css/styles.css">
    <link rel="stylesheet" href="<?php echo base_url ?>plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
    <link rel="stylesheet" href="<?php echo base_url ?>plugins/daterangepicker/daterangepicker.css">
    <link rel="stylesheet" href="<?php echo base_url ?>plugins/summernote/summernote-bs4.min.css">
    <link rel="stylesheet" href="<?php echo base_url ?>plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css">
    <style type="text/css">/* Chart.js */
        @keyframes chartjs-render-animation{from{opacity:.99}to{opacity:1}}.chartjs-render-monitor{animation:chartjs-render-animation 1ms}.chartjs-size-monitor,.chartjs-size-monitor-expand,.chartjs-size-monitor-shrink{position:absolute;direction:ltr;left:0;top:0;right:0;bottom:0;overflow:hidden;pointer-events:none;visibility:hidden;z-index:-1}.chartjs-size-monitor-expand>div{position:absolute;width:1000000px;height:1000000px;left:0;top:0}.chartjs-size-monitor-shrink>div{position:absolute;width:200%;height:200%;left:0;top:0}
    </style>
    <link rel="stylesheet" href="<?php echo base_url ?>plugins/fullcalendar/main.css">

    <style>
        .err_msg {
            display: none !important;
        }
    </style>
    <script src="<?php echo base_url ?>plugins/jquery/jquery.min.js"></script>
    <script src="<?php echo base_url ?>plugins/jquery-ui/jquery-ui.min.js"></script>
    <script src="<?php echo base_url ?>plugins/sweetalert2/sweetalert2.min.js"></script>
    <script src="<?php echo base_url ?>plugins/toastr/toastr.min.js"></script>
   

    <script src="<?php echo base_url ?>plugins/moment/moment.min.js"></script>
    <script src="<?php echo base_url ?>plugins/fullcalendar/main.js"></script>

    <script src="<?php echo base_url ?>dist/js/script.js"></script>
    <script src="<?php echo base_url ?>assets/js/scripts.js"></script>
    <script>
    window._base_url_ = '<?php echo base_url ?>';
</script>
    <style>
        #main-header{
            position:relative;
            min-height: 80vh; /* Adjust as needed */
            display: flex;
            align-items: center;
            justify-content: center;
            color: white; /* For the welcome text */
            text-align: center;
            background-size: cover;
            background-repeat: no-repeat;
            background-color: transparent !important; /* Ensure no black flash */
        }
        .navbar-light .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg viewBox='0 0 30 30' xmlns='http://www.w3.org/2000/svg'%3e%3cpath stroke='rgba(255, 255, 255, 0.75)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }
    </style>

    <script>
        $(document).ready(function() {
            console.log('JavaScript is running!');
            var images = [
                '<?php echo base_url ?>uploads/uk_city1.png',
                '<?php echo base_url ?>uploads/uk_city3.png', 
                '<?php echo base_url ?>uploads/uk_city2.png'
            ];
            var currentIndex = 0;
            var mainHeader = $('#main-header');

            function changeBackground() {
                currentIndex = (currentIndex + 1) % images.length;
                var nextImage = images[currentIndex];
                mainHeader.css('background-image', 'url("' + nextImage + '")');
                console.log('Background changed to: ' + nextImage);
                setTimeout(changeBackground, 5000);
            }

            // Set the initial background image
            mainHeader.css('background-image', 'url("' + images[0] + '")');
            // Start the slideshow
            setTimeout(changeBackground, 5000);
        });
    </script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.min.css">

    <script type="module">
        // Import the functions you need from the SDKs you need
        import { initializeApp } from "https://www.gstatic.com/firebasejs/9.6.1/firebase-app.js";
        import { getAuth } from "https://www.gstatic.com/firebasejs/9.6.1/firebase-auth.js";
        // TODO: Add SDKs for Firebase products that you want to use
        // https://firebase.google.com/docs/web/setup#available-libraries

        // Your web app's Firebase configuration
        const firebaseConfig = {
            apiKey: "AIzaSyAkHD7A-HnZYakoiV5YxIVJamEwMe2r86w",
            authDomain: "usbmicro-ca116.firebaseapp.com",
            projectId: "usbmicro-ca116",
            storageBucket: "usbmicro-ca116.firebasestorage.app",
            messagingSenderId: "774331717251",
            appId: "1:774331717251:web:986a6350209aea275bedb6"
        };

        // Initialize Firebase
        const app = initializeApp(firebaseConfig);
        // Get the Auth service instance
        window.auth = getAuth(app); // Make auth accessible globally for other scripts
        console.log("Firebase initialized and Auth service available.");
    </script>
</head>