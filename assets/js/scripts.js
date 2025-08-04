/*
 * Start Bootstrap - Shop Homepage v5.0.1 (https://startbootstrap.com/template/shop-homepage)
 * Copyright 2013-2021 Start Bootstrap
 * Licensed under MIT (https://github.com/StartBootstrap/startbootstrap-shop-homepage/blob/master/LICENSE)
 */

// This file is intended to add JavaScript to your project

// Ensure _base_url_ is defined globally. It is crucial for the loader image path.
// This typically comes from a PHP variable in your header, e.g., <script>var _base_url_ = '<?php echo base_url; ?>';</script>

function start_loader(){
    var preloader = $('#preloader');
    if(preloader.length > 0) {
        preloader.css({ 'visibility': 'visible', 'opacity': '1', 'display': 'flex' });
    } else {
        // Fallback: If preloader not found, append it (though it's better to have it in HTML)
        $('body').append('<div id="preloader" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255, 255, 255, 0.9); z-index: 9999; display: flex; justify-content: center; align-items: center;"><img src="' + _base_url_ + 'uploads/loader.gif" alt="Loading..."></div>');
    }
}

function end_loader(){
    var preloader = $('#preloader');
    if(preloader.length > 0) {
        preloader.css({ 'opacity': '0' });
        // After transition, hide it fully to prevent clicks
        setTimeout(function() {
            preloader.css({ 'visibility': 'hidden', 'display': 'none' });
        }, 300); // Match this timeout to the CSS transition duration
    }
}

// THIS IS THE CRUCIAL PART FOR HIDING THE INITIAL PAGE LOADER
// It ensures end_loader() is called only after the entire page (including images) is fully loaded.
$(window).on('load', function() {
    end_loader(); // Hide the preloader when everything is loaded
    toastr.clear(); // Clear any lingering toastr messages on initial page load
});

// You can also place the background image slideshow script here if it's not needed in internet_banking.php directly:
$(document).ready(function() {
    var images = [
        _base_url_ + 'uploads/uk_city1.png',
        _base_url_ + 'uploads/uk_city3.png',
        _base_url_ + 'uploads/uk_city2.png'
    ];
    var currentIndex = 0;
    var mainHeader = $('#main-header');

    if(mainHeader.length > 0) { // Check if mainHeader exists on the page
        function changeBackground() {
            currentIndex = (currentIndex + 1) % images.length;
            var nextImage = images[currentIndex];
            mainHeader.css('background-image', 'url("' + nextImage + '")');
            setTimeout(changeBackground, 5000);
        }
        // Set the initial background image
        mainHeader.css('background-image', 'url("' + images[0] + '")');
        // Start the slideshow
        setTimeout(changeBackground, 5000);
    }
});