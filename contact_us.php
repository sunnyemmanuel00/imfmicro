<?php require_once('config.php'); ?>
<!DOCTYPE html>
<html lang="en">
<?php require_once('inc/header.php') ?>
<body>
<?php require_once('inc/topBarNav.php') ?>

<header class="bg-dark py-5" id="main-header">
    <div class="container px-4 px-lg-5 my-5">
        <div class="text-center text-white">
            <h1 class="display-4 fw-bolder">Contact Us</h1>
            <p class="lead fw-normal text-white mb-0">We'd love to hear from you!</p>
        </div>
    </div>
</header>
<section class="py-5">
    <div class="container px-4 px-lg-5">
        <div class="row gx-4 gx-lg-5 justify-content-center">
            <div class="col-lg-8">
                <div class="card rounded-0">
                    <div class="card-body">
                        <h2 class="fw-bolder mb-3">Send us a message</h2>
                        <form id="contactForm">
                            <div class="mb-3">
                                <label for="name" class="form-label">Your Name</label>
                                <input class="form-control" name="name" id="name" type="text" placeholder="Your Name" required />
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Your Email</label>
                                <input class="form-control" name="email" id="email" type="email" placeholder="Your Email" required />
                            </div>
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input class="form-control" name="phone" id="phone" type="tel" placeholder="Your Phone Number (Optional)" />
                            </div>
                            
                            <div class="mb-3">
                                <label for="type" class="form-label">Inquiry Type</label>
                                <select class="form-control" name="type" id="type" required>
                                    <option value="" disabled selected>Select an option...</option>
                                    <option value="General Enquiry">General Enquiry</option>
                                    <option value="Feedback">Feedback</option>
                                    <option value="Technical Support">Technical Support</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="subject" class="form-label">Subject</label>
                                <input class="form-control" name="subject" id="subject" type="text" placeholder="Subject" required />
                            </div>
                            <div class="mb-3">
                                <label for="message" class="form-label">Message</label>
                                <textarea class="form-control" name="message" id="message" rows="5" placeholder="Enter your message" required></textarea>
                            </div>
                           <div id="progressContainer" class="mb-3" style="display: none;">
                            <p class="text-center text-muted mb-2" id="progressText">Securely sending your message...</p>
                            <div class="progress" style="height: 20px;">
                                <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                            </div>
                        </div>

                        <div class="d-grid">
                            <button class="btn btn-primary btn-lg" id="submitButton" type="submit">Send Message</button>
                        </div>
                        </form>
                        
                        <hr>
                        <div class="text-center w-100">
                            <p>You can also send us an email directly at <a href="mailto:support@imfpayments.online">support@imfpayments.online</a></p>
                        </div>
                        
                        <div id="responseMessage" class="mt-3" style="display: none;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    // Your existing Javascript remains unchanged
    $(function(){
        $('#contactForm').submit(function(e){
            e.preventDefault();
            var _this = $(this);
            var form_data = _this.serialize();
            $('#submitButton').hide();
            $('#responseMessage').slideUp();
            $('#progressContainer').slideDown();
            
            var progress = 0;
            var ajax_call_done = false;
            var ajax_response;

            $.ajax({
                url: _base_url_ + "classes/Application.php?f=submit_inquiry",
                method: 'POST',
                data: form_data,
                dataType: 'json',
                success: function(resp) {
                    ajax_response = resp;
                },
                error: function(err) {
                    console.log(err);
                    ajax_response = {status: 'error', msg: 'A network error occurred. Please try again later.'};
                },
                complete: function() {
                    ajax_call_done = true;
                }
            });

            var interval = setInterval(function() {
                progress += 1;
                var percentage = Math.min(Math.round((progress / 150) * 100), 100);
                $('#progressBar').css('width', percentage + '%').text(percentage + '%');
                if (progress >= 150) {
                    clearInterval(interval);
                    var check_ajax = setInterval(function(){
                        if(ajax_call_done){
                            clearInterval(check_ajax);
                            $('#progressContainer').slideUp();
                            if(ajax_response.status == 'success'){
                                $('#responseMessage').removeClass('alert-danger').addClass('alert-success').text(ajax_response.msg).slideDown();
                                $('#contactForm')[0].reset();
                                setTimeout(function(){
                                    $('#responseMessage').slideUp();
                                    $('#submitButton').show();
                                }, 60000); 
                            } else {
                                $('#responseMessage').removeClass('alert-success').addClass('alert-danger').text(ajax_response.msg).slideDown();
                                $('#submitButton').show();
                            }
                        }
                    }, 100);
                }
            }, 100);
        });
    });
</script>

<?php require_once('inc/footer.php') ?>
</body>
</html>