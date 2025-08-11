<?php if($_settings->chk_flashdata('success')): ?>
<script>
    alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')
</script>
<?php endif;?>
<div class="col-lg-12">
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h5 class="card-title">Create New Admin Account</h5>
        </div>
        <div class="card-body">
            <form action="" id="manage-user">
                <input type="hidden" name="id" value="">
                <div class="form-group">
                    <label for="firstname">First Name</label>
                    <input type="text" name="firstname" id="firstname" class="form-control" value="" required>
                </div>
                <div class="form-group">
                    <label for="lastname">Last Name</label>
                    <input type="text" name="lastname" id="lastname" class="form-control" value="" required>
                </div>
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" name="username" id="username" class="form-control" value="" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" class="form-control" value="">
                </div>
                <div class="form-group">
                    <label for="type">User Type</label>
                    <select name="type" id="type" class="custom-select custom-select-sm">
                        <option value="1">Administrator</option>
                        <option value="2">Staff</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="" class="control-label">Avatar</label>
                    <div class="custom-file">
                    <input type="file" class="custom-file-input rounded-circle" id="customFile" name="img" onchange="displayImg(this,$(this))">
                    <label class="custom-file-label" for="customFile">Choose file</label>
                    </div>
                </div>
                <div class="form-group d-flex justify-content-center">
                    <img src="<?php echo validate_image('') ?>" alt="" id="cimg" class="img-fluid img-thumbnail rounded-circle" >
                </div>
            </form>
        </div>
        <div class="card-footer">
            <div class="col-md-12">
                <div class="row">
                    <button class="btn btn-sm btn-primary" form="manage-user">Save Details</button>
                    <a class="btn btn-sm btn-default" href="./?page=user/list">Cancel</a>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
    img#cimg{
        height: 15vh;
        width: 15vh;
        object-fit: scale-down;
        border-radius: 100% 100%;
    }
</style>
<script>
    function displayImg(input,_this) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function (e) {
                $('#cimg').attr('src', e.target.result);
            }

            reader.readAsDataURL(input.files[0]);
        }
    }
    $('#manage-user').submit(function(e){
        e.preventDefault();
        start_loader()
        $.ajax({
            url:_base_url_+'classes/Users.php?f=save',
            data: new FormData($(this)[0]),
            cache: false,
            contentType: false,
            processData: false,
            method: 'POST',
            type: 'POST',
            success:function(resp){
                if(resp == 1){
                    location.href = './?page=user/list';
                }else{
                    $('#msg').html('<div class="alert alert-danger">Username already exists</div>')
                    end_loader()
                }
            }
        })
    })
</script>
