<?php 
// Corrected code to use PostgreSQL syntax and prepared statements
$user_data = []; // Initialize an empty array for user data
try {
    // Use a prepared statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT * FROM \"accounts\" WHERE id = ?");
    $stmt->execute([$_settings->userdata('account_id')]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
} catch (PDOException $e) {
    // Log the error to the server's error log
    error_log("Database Error in user/index.php: " . $e->getMessage());
    // Optionally, display a user-friendly error message
    echo "<div class='alert alert-danger'>A database error occurred. Please try again later.</div>";
    // To see the detailed error, you can uncomment the line below for debugging.
    // echo $e->getMessage();
}

// Assign fetched data to the meta array if it exists
if($user_data){
    $meta = $user_data;
} else {
    $meta = [];
}
?>

<?php if($_settings->chk_flashdata('success')): ?>
<script>
    alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')
</script>
<?php endif;?>
<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title">My Account Details</h3>
    </div>
    <div class="card-body">
        <form action="" id="manage-user">    
            <input type="hidden" name="id" value="<?php echo $_settings->userdata('account_id') ?>">
            
            <div class="row">
                <div class="col-md-6 border-right">
                    <h5 class="text-primary">Personal Information</h5>
                    <hr>
                    <div class="form-group">
                        <label for="firstname">First Name</label>
                        <input type="text" name="firstname" id="firstname" class="form-control" value="<?php echo isset($meta['firstname']) ? $meta['firstname']: '' ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="lastname">Last Name</label>
                        <input type="text" name="lastname" id="lastname" class="form-control" value="<?php echo isset($meta['lastname']) ? $meta['lastname']: '' ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="gender">Gender</label>
                        <select name="gender" id="gender" class="form-control" required>
                            <option value="Male" <?php echo (isset($meta['gender']) && $meta['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo (isset($meta['gender']) && $meta['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="phone_number">Phone Number</label>
                        <input type="text" name="phone_number" id="phone_number" class="form-control" value="<?php echo isset($meta['phone_number']) ? $meta['phone_number']: '' ?>" required>
                    </div>
                </div>

                <div class="col-md-6">
                    <h5 class="text-primary">Address & Contact</h5>
                    <hr>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" name="email" id="email" class="form-control" value="<?php echo isset($meta['email']) ? $meta['email']: '' ?>" required autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea name="address" id="address" class="form-control" rows="3" required><?php echo isset($meta['address']) ? $meta['address'] : ''; ?></textarea>
                    </div>

                    <h5 class="text-primary mt-4">Update Password</h5>
                    <hr>
                    <div class="form-group">
                        <label for="password">New Password</label>
                        <input type="password" name="password" id="password" class="form-control" value="" autocomplete="off">
                        <small><i>Leave this blank if you don't want to change the password.</i></small>
                    </div>
                </div>
            </div>

            <hr>
            <div class="row">
                <div class="col-12 d-flex justify-content-end">
                    <button class="btn btn-primary" form="manage-user">Update Profile</button>
                </div>
            </div>
        </form>
    </div>
</div>
<script>
    $('#manage-user').submit(function(e){
        e.preventDefault();
        start_loader();
        $('#msg').html(''); // Clear previous error messages
        $.ajax({
            url:_base_url_+'classes/Master.php?f=save_account',
            data: new FormData($(this)[0]),
            cache: false,
            contentType: false,
            processData: false,
            method: 'POST',
            type: 'POST',
            dataType: 'json',
            error: err=>{
                console.log(err)
                alert_toast("An error occured",'error');
                end_loader();
            },
            success:function(resp){
                if(resp.status == 'success'){
                    // Show success message
                    alert_toast("Profile successfully updated.", 'success');
                    // Reload the page after 1.5 seconds to show new name in header
                    setTimeout(function(){
                        location.reload();
                    }, 1500);
                }else if(!!resp.msg){
                    $('#msg').html('<div class="alert alert-danger">'+resp.msg+'</div>');
                    end_loader();
                }else{
                    $('#msg').html('<div class="alert alert-danger">An error occurred</div>');
                    end_loader();
                }
            }
        })
    })
</script>
