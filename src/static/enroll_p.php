<?php

    // Some session management
    if (session_status() == PHP_SESSION_NONE) {
        date_default_timezone_set('UTC');
        session_set_cookie_params(7200, '/', 'example.tech', TRUE, TRUE);
        session_name('ExampleTech');
        session_start();
    }

    // Check session/prolific ID
    if ( !isset($_SESSION['prolific']) ) {
        header("Location: dis.php");
        die();
    }

    if ( isset($_SESSION['completed_rp']) ) {
        if( $_SESSION['completed_rp'] === True ) {
            header("Location: complete_rp.php");
            die();
        }
    }

    if ( !isset($_SESSION['header_template']) ) {
        $_SESSION['header_template'] = 'header_rw.html.php';
    }
    include '../includes/template/'.$_SESSION['header_template'];

?>
        <script src="../js/thirdparty/zxcvbn.js"></script>
        <script src="../js/custom/register_password.js"></script>
        <div class="container">
            <h1>Registration</h1>
            <?php
                if( isset($_SESSION['msg']) and ($_SESSION['msg'] != '') ) {
                    echo '<div id="alertbox" class="alert alert-danger" role="alert" style="visibility: visible">'.$_SESSION['msg'].'</div>';
                    $_SESSION['msg'] = '';
                } else {
                    echo '<div id="alertbox" class="alert alert-danger" role="alert" style="visibility: hidden">PLACEHOLDER TEXT</div>';
                }
            ?>
            <div class="row">
                <div class="col-12">

                <form id="registration-form" class="needs-validation" novalidate>

                    <!-- Prolific ID -->
                    <div class="form-group">
                        <label class="control-label" for="user"><i class="fas fa-id-card"></i>Your Prolific ID:</label>
                        <?php
                            echo '<input type="text" class="form-control" name="user" id="user" value='.$_SESSION['prolific'].' autocomplete="username" required disabled data-index="1">';
                        ?>
                    </div>

                    <!-- Gender -->
                    <div class="form-group">
                        <label for="gender"><i class="fas fa-times"></i>What is your gender?</label>
                        <select class="form-control" id="gender" autocomplete="sex" required data-index="2">
                            <option value="" disabled selected>Please select...</option>
                            <option value="Woman">Woman</option>
                            <option value="Man">Man</option>
                            <option value="Non-binary">Non-binary</option>
                            <option value="Self-describe">Prefer to self-describe</option>
                            <option value="Not-say">Prefer not to say</option>
                        </select>
                        <div class="invalid-feedback">Please select an option.</div>
                    </div>
                    <script>
                        $('#gender').on('change', function() {
                            var selectedGender = $(this).children("option:selected").val();
                            if (selectedGender === "Self-describe")  {
                                // visibility: visible | hidden
                                $("#gender_self_desc").css('display', 'block');
                                $("#gender_self_desc_text").prop('required', true);
                            } else {
                                $("#gender_self_desc").css('display', 'none');
                                $("#gender_self_desc_text").removeAttr('required');
                            }
                        });
                    </script>
                    <div class="form-group" id="gender_self_desc" style="display:none">
                        <label for="gender_self_desc_text">Your description:</label>
                        <input type="text" class="form-control" name="gender_self_desc_text" id="gender_self_desc_text" autocomplete="sex" minlength="1" placeholder="" data-index="3">
                        <div class="invalid-feedback">Please specify.</div>
                    </div>

                    <!-- Age -->
                    <div class="form-group">
                        <label class="control-label" for="age"><i class="fas fa-birthday-cake"></i>How old are you?</label>
                        <input type="number" class="form-control" name="age" id="age" min="18" max="120" placeholder="" data-index="4">
                        <div class="invalid-feedback">Range: 18 to 120 years.</div>
                    </div>

                    <!-- Password -->
                    <div class="form-group required">
                        <label class="control-label" for="pass-new"><i class="fas fa-key"></i> Password</label>
                        <small id="passStrength" class="form-text" style="margin-top: 0.0rem;">&nbsp;</small>
                        <div class="progress" style="height: 2px;margin-bottom: 1ex;">
                            <div class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="4"></div>
                        </div>
                        <div class="input-group">
                            <input type="password" class="form-control" data-toggle="password" id="pass-new" aria-describedby="passStrength" placeholder="Enter password" autocomplete="new-password" minlength="8" data-index="5" required>
                            <div class="input-group-append" style="margin-left: 0px;">
                                <span class="btn btn-light reveal" id="eye-new-text" data-toggle="tooltip" data-placement="right" title="Show password" style="border-top: 1px solid #ced4da;border-bottom: 1px solid #ced4da;border-right: 1px solid #ced4da;"> <i id="eye-new" class='far fa-eye'></i> </span>
                            </div>
                        </div>
                    </div>

                    <!-- Password Confirm-->
                    <div class="form-group required">
                        <div class="input-group">
                            <input type="password" class="form-control" data-toggle="password" id="pass-confirm" aria-describedby="passHelp" placeholder="Confirm password" autocomplete="new-password" minlength="8" data-index="6" required>
                            <div class="input-group-append" style="margin-left: 0px;">
                                <span class="btn btn-light reveal" id="eye-confirm-text" data-toggle="tooltip" data-placement="right" title="Show password" style="border-top: 1px solid #ced4da;border-bottom: 1px solid #ced4da;border-right: 1px solid #ced4da;"> <i id="eye-confirm" class='far fa-eye'></i> </span>
                            </div>
                        </div>
                        <small id="passHelp" class="form-text text-muted" style="margin-top: 0.0rem;">Must be at least 8 characters long.</small>
                        <div class="invalid-feedback">Must be at least 8 characters long.</div>
                    </div>

                    <!--- Register button -->
                    <button type="submit" id="register" class="btn btn-primary btn-lg btn-block" data-index="7">Register</button>

                </form>

                </div>
            </div>
            <br>
        </div><!-- end container -->
        <br>
        <br>

        <?php include '../includes/template/footer.html.php'; ?>