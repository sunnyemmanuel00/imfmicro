<script>
    // This corrected line will now work locally and on the live server without errors.
    console.log(`PHP is defining base_url as: ${<?php echo json_encode(base_url); ?>}`);

    // This is the original code that defines the variable for your app to use
    if (typeof window._base_url_ === 'undefined') {
        window._base_url_ = <?php echo json_encode(base_url); ?>;
    }
</script>