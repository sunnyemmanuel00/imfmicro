<?php
require_once(__DIR__ . '/../../config.php');
if(isset($_GET['id'])){
    $qry = $conn->query("SELECT * FROM `inquiries` where id = '{$_GET['id']}'");
    if($qry->num_rows > 0){
        foreach($qry->fetch_array() as $k => $v){
            if(!is_numeric($k))
            $$k = $v;
        }
    }
    // Mark the message as "Read" by updating its status
    $conn->query("UPDATE `inquiries` set status = 1 where id = '{$_GET['id']}'");
}
?>
<style>
    #uni_modal .modal-content {
        background-color: #212529; /* Bootstrap's standard dark color */
        color: #f8f9fa;           /* A light off-white color for text */
    }
    #uni_modal .modal-header {
        border-bottom: 1px solid #495057; /* A lighter border for visibility */
    }
    #uni_modal .modal-header .close span {
        color: #f8f9fa; /* Make the 'x' button white */
    }
    #uni_modal .inquiry-label, #uni_modal .text-muted {
        color: #adb5bd !important; /* A lighter grey for labels like 'Sender Name' */
    }
    #uni_modal .inquiry-data {
        border-bottom-color: #495057; /* A lighter border for data rows */
        color: #ffffff;             /* Make the actual data pure white */
    }
    #uni_modal .inquiry-message {
        background-color: #343a40; /* A slightly lighter dark for the message box */
        border-left-color: #007bff; /* Keep the blue accent border */
    }
    #uni_modal .modal-footer{
        display: none; /* This keeps the default modal footer hidden */
    }
</style>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-6">
            <p class="inquiry-label">Sender Name:</p>
            <p class="inquiry-data"><?php echo isset($name) ? htmlspecialchars($name) : '' ?></p>

            <p class="inquiry-label mt-3">Email Address:</p>
            <p class="inquiry-data"><?php echo isset($email) ? htmlspecialchars($email) : '' ?></p>

            <p class="inquiry-label mt-3">Phone Number:</p>
            <p class="inquiry-data"><?php echo isset($phone) && !empty($phone) ? htmlspecialchars($phone) : 'N/A' ?></p>
        </div>
        <div class="col-md-6">
            <p class="inquiry-label">Inquiry Type:</p>
            <p class="inquiry-data"><?php echo isset($type) ? htmlspecialchars($type) : '' ?></p>

            <p class="inquiry-label mt-3">Subject:</p>
            <p class="inquiry-data"><?php echo isset($subject) ? htmlspecialchars($subject) : '' ?></p>
        </div>
    </div>
    
    <hr>

    <div class="row">
        <div class="col-12">
            <p class="inquiry-label">Full Message:</p>
            <div class="inquiry-message">
                <?php echo isset($message) ? nl2br(htmlspecialchars($message)) : '' ?>
            </div>
        </div>
    </div>

    <div class="w-100 d-flex justify-content-end mt-4">
        <button class="btn btn-sm btn-dark btn-flat" type="button" data-dismiss="modal">Close</button>
    </div>
</div>