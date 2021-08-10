/*jslint browser: true*/
/*global window, $, zxcvbn, user_inputs, logEvent*/

var delay = 250; //ms
var startStrengthCalc = -1;
var strength_meter_hints = ['Example Tech', 'example', 'Example.Tech', 'example.tech', 'Study', 'Prolific'];
var passnewentry = {events: [], pasted: false, pw: ""};
var passconfirmentry = {events: [], pasted: false, pw: ""};

function storeRegistrationData(password, pw_timespans) {
    'use strict';
    var json_data = {
        password: password,
        strength: zxcvbn(password, strength_meter_hints).guesses,
        gender: $("#gender").val(),
        age: $("#age").val(),
        ui_timespan: pw_timespans
    };
    $.post('../includes/lib/json_register_p.php', {
        json: JSON.stringify(json_data)
    }, function (result) {
        logEvent("storeRegistrationData->" + result.status);
        if (result.status === true) {
            location.href = 'complete_rp.php';
        } else {
            if (result.attempt >= 3) {
                location.href = 'complete_rp.php';
            } else {
                $("#alertbox").html("<i class=\"fas fa-exclamation-triangle\"></i>Registration failed. Please try again!");
                $("#alertbox").css('visibility', 'visible');
            }
        }
    }, 'json');
}

function checkStrength(passnew) {
    'use strict';
    // Reset - Cleanup
    $("#pass-new").removeClass("is-valid").removeClass("is-invalid").removeClass("was-validated");
    $("#pass-confirm").removeClass("is-valid").removeClass("is-invalid").removeClass("was-validated");
    $('.progress-bar').removeClass("bg-muted").removeClass("bg-danger").removeClass("bg-warning").removeClass("bg-info").removeClass("bg-success");
    $('.progress-bar').css('width', '0%').attr('aria-valuenow', 0);
    $('.progress-bar').addClass("bg-muted");
    $("#passStrength").html('');
    $("#passHelp").addClass("text-muted");
    $("#passHelp").html('Must be at least 8 characters long.'); // THIS ONE
    document.getElementById('pass-new').setCustomValidity("Invalid field.");
    document.getElementById('pass-confirm').setCustomValidity("Invalid field.");

    // Too short
    if (passnew.length < 8) {
        logEvent("form->pwStrength->tooShort->" + passnew);
        $("#pass-new").removeClass("is-valid").addClass("is-invalid").addClass("was-validated");
        $('.progress-bar').addClass("bg-danger");
        $('.progress-bar').css('width', '0%').attr('aria-valuenow', 0);
        $("#passStrength").html('<span class="text-danger" style="font-weight:bold;">😵&nbsp; That\'s too short.</span> You need at least 8 characters.');
        document.getElementById('pass-new').setCustomValidity("Invalid field.");
        return false;
    }

    // Length policy satifsfied, now continuing to check the strength requirment
    var strength = zxcvbn(passnew, strength_meter_hints);
    if (strength.score === 0 || strength.score === 1) {
        logEvent("form->pwStrength->Terrible->" + passnew);
        $("#pass-new").removeClass("is-valid").addClass("is-invalid").addClass("was-validated");
        $('.progress-bar').addClass("bg-danger");
        $('.progress-bar').css('width', '15%').attr('aria-valuenow', 1);
        $("#passStrength").html('<span class="text-danger" style="font-weight:bold;">😟&nbsp; Terrible.</span> This password is very easy to guess.');
        document.getElementById('pass-new').setCustomValidity("Invalid field.");
        return false;
    }
    if (strength.score === 2) {
        logEvent("form->pwStrength->Weak->" + passnew);
        $("#pass-new").removeClass("is-valid").addClass("is-invalid").addClass("was-validated");
        $('.progress-bar').addClass("bg-warning");
        $('.progress-bar').css('width', '33%').attr('aria-valuenow', 2);
        $("#passStrength").html('<span class="text-warning" style="font-weight:bold;">😐&nbsp; Weak.</span> Your password needs to be better.');
        document.getElementById('pass-new').setCustomValidity("Invalid field.");
        return false;
    }
    if (strength.score === 3) {
        logEvent("form->pwStrength->Good->" + passnew);
        $("#pass-new").removeClass("is-invalid").addClass("is-valid").addClass("was-validated");
        $('.progress-bar').addClass("bg-info");
        $('.progress-bar').css('width', '66%').attr('aria-valuenow', 3);
        $("#passStrength").html('<span class="text-info" style="font-weight:bold;">🙂&nbsp; Good.</span> Your password is pretty good.');
        document.getElementById('pass-new').setCustomValidity("");
    }
    if (strength.score === 4) {
        logEvent("form->pwStrength->Excellent->" + passnew);
        $("#pass-new").removeClass("is-invalid").addClass("is-valid").addClass("was-validated");
        $('.progress-bar').addClass("bg-success");
        $('.progress-bar').css('width', '100%').attr('aria-valuenow', 4);
        $("#passStrength").html('<span class="text-success" style="font-weight:bold;">😇&nbsp; Excellent.</span> Your password appears strong.');
        document.getElementById('pass-new').setCustomValidity("");
    }
    return true;
}

function matchPass(passnew, passconfirm) {
    'use strict';
    // Reset - Cleanup
    $("#pass-new").removeClass("is-valid").removeClass("is-invalid").removeClass("was-validated");
    $("#pass-confirm").removeClass("is-valid").removeClass("is-invalid").removeClass("was-validated");
    $('.progress-bar').removeClass("bg-muted").removeClass("bg-danger").removeClass("bg-warning").removeClass("bg-info").removeClass("bg-success");
    $('.progress-bar').css('width', '0%').attr('aria-valuenow', 0);
    $('.progress-bar').addClass("bg-muted");
    $("#passStrength").html('');
    $("#passHelp").addClass("text-muted");
    $("#passHelp").html('Must be at least 8 characters long.'); // THIS ONE TOO
    document.getElementById('pass-new').setCustomValidity("Invalid field.");
    document.getElementById('pass-confirm').setCustomValidity("Invalid field.");

    // Check for compliance
    if (checkStrength(passnew) === false) {
        $("#passHelp").removeClass("text-muted");
        if (passnew.length < 8) {
            logEvent("form->pwMatch->tooShort->" + passnew);
            $("#passHelp").html('<span class="text-danger" style="font-weight:bold;">❌&nbsp; Your password is not long enough.</span>');
        } else {
            logEvent("form->pwMatch->notStrong->" + passnew);
            $("#passHelp").html('<span class="text-danger" style="font-weight:bold;">❌&nbsp; Your password is not strong enough.</span>');
        }
        $("#pass-confirm").removeClass("is-valid").addClass("is-invalid").addClass("was-validated");
        document.getElementById('pass-confirm').setCustomValidity("Invalid field.");
        return false;
    }

    // Strong enough, check if both are the same
    if (passnew !== passconfirm) {
        logEvent("form->pwMatch->noMatch->" + passnew + "!=" + passconfirm);
        $("#passHelp").removeClass("text-muted");
        $("#passHelp").html('<span class="text-danger" style="font-weight:bold;">❌&nbsp; Passwords do not match.</span> Check them for typos.');
        $("#pass-confirm").removeClass("is-valid").addClass("is-invalid").addClass("was-validated");
        document.getElementById('pass-confirm').setCustomValidity("Invalid field.");
        return false;
    } else {
        logEvent("form->pwMatch->Match->" + passnew + "==" + passconfirm);
        $("#passHelp").removeClass("text-muted");
        $("#passHelp").html('<span class="text-success" style="font-weight:bold;">✅&nbsp; Passwords match.</span>');
        // If we reach this, we can be sure that the passwords are compliant and match
        $("#pass-confirm").removeClass("is-invalid").addClass("is-valid").addClass("was-validated");
        document.getElementById('pass-confirm').setCustomValidity("");
        return true;
    }
}

function passNew() {
    'use strict';
    if ($("#pass-confirm").val().length > 0) {
        matchPass($("#pass-new").val(), $("#pass-confirm").val());
    } else {
        if ($("#pass-new").val().length > 0) {
            checkStrength($("#pass-new").val());
        }
    }
    clearInterval(startStrengthCalc); //disable the timer
}

function passConfirm() {
    'use strict';
    matchPass($("#pass-new").val(), $("#pass-confirm").val());
    clearInterval(startStrengthCalc); //disable the timer
}

function makeScrollIntoViewHandler(input) {
    'use strict';
    return function () {
        input.scrollIntoView();
    };
}

$(document).ready(function () {
    'use strict';
    /*
    // Check whether correct device and browser is used
    if ($.ua.browser.name === "Chrome") {
        var isChrome = true;
    } else {
        var isChrome = false;
    }
    if (!isChrome) {
        logEvent("userAgent->isNotChrome->" + navigator.userAgent);
        location.href = 'help_rp.php?d1=wrongbrowser';
    }
    var ua = navigator.userAgent;
    var isAndroid = ua.indexOf("Android") > -1;
    if (!isAndroid) {
        logEvent("userAgent->isNotAndroid->" + navigator.userAgent);
        location.href = 'help_rp.php?d1=notandroid';
    }
    */

    /* Init global time variable */
    var ts_start = -1;
    var pw_timespans = [];

    /* Setup an event listener for every input element */
    var allInputs = document.getElementsByTagName('input');
    var i, item;
    for (i = 0; i < allInputs.length; i += 1) {
        item = allInputs[i];
        item.onfocus = makeScrollIntoViewHandler(item);
    }

    /* On Enter key move on to next input field */
    $('#registration-form').on('keydown', 'input', function (event) {
        if (event.which === 13) {
            event.preventDefault();
            var $this = $(event.target);
            var index = parseFloat($this.attr('data-index'));
            $(`[data-index="${index + 1}"]`).focus();
        }
    });

    /* Logging required for the user study */
    $(document).on("focusin", "input, select", function (e) {
        logEvent("focusin()->" + e.target.id);
    });
    $(document).on("focusout", "input, select", function (e) {
        logEvent("focusout()->" + e.target.id + "->" + e.target.value);
    });
    $('#pass-new').on('keydown', function (event) {
        if (event !== undefined) {
            if (event.key !== undefined) {
                passnewentry.events.push(event.key);
            }
        }
    });
    $('#pass-new').on('paste', function () {
        passnewentry.pasted = true;
    });
    $("#pass-new").focusout(function (e) {
        passnewentry.pw = $("#pass-new").val();
        logEvent("focusout()->" + e.target.id + "->" + JSON.stringify(passnewentry));
        passnewentry = {events: [], pasted: false, pw: ""}; //reset
    });
    $('#pass-confirm').on('keydown', function (event) {
        if (event !== undefined) {
            if (event.key !== undefined) {
                passconfirmentry.events.push(event.key);
            }
        }
    });
    $('#pass-confirm').on('paste', function () {
        passconfirmentry.pasted = true;
    });
    $("#pass-confirm").focusout(function (e) {
        passconfirmentry.pw = $("#pass-confirm").val();
        logEvent("focusout()->" + e.target.id + "->" + JSON.stringify(passconfirmentry));
        passconfirmentry = {events: [], pasted: false, pw: ""}; //reset
    });


    /* Register event listener to start time measurement */
    $("#pass-new").focusin(function () {
        logEvent("form->pass-new->focusin");
        if (ts_start === -1) {
            ts_start = Date.now();
        }
        if (startStrengthCalc === -1) {
            startStrengthCalc = setInterval(passNew, delay);
        }
    });
    $("#pass-confirm").focusout(function () {
        logEvent("form->pass-confirm->focusout");
        if (ts_start !== -1) {
            var timespan = Date.now() - ts_start;
            pw_timespans.push(timespan);
            ts_start = -1;
        }
    });

    /* Press the eye button to show the password in plain text */
    $(".reveal").on('touchstart', function () {
        //event.preventDefault();
        logEvent("form->revealPassword");
        if ($('#eye-new').hasClass("far fa-eye")) {
            $('#eye-new').removeClass('far fa-eye').addClass('far fa-eye-slash');
            $('#eye-confirm').removeClass('far fa-eye').addClass('far fa-eye-slash');
        } else {
            $('#eye-new').removeClass('far fa-eye-slash').addClass('far fa-eye');
            $('#eye-confirm').removeClass('far fa-eye-slash').addClass('far fa-eye');
        }
        // Change tooltip text
        if ($('#eye-new-text').attr('title') === "Show password") {
            $('#eye-new-text').attr('title', 'Hide password');
            $('#eye-confirm-text').attr('title', 'Hide password');
        } else {
            $('#eye-new-text').attr('title', 'Show password');
            $('#eye-confirm-text').attr('title', 'Show password');
        }
        $('#pass-new').attr('type', function (ignore, attr) {
            if (attr === 'text') {
                return 'password';
            } else {
                return 'text';
            }
        });
        $('#pass-confirm').attr('type', function (ignore, attr) {
            if (attr === 'text') {
                return 'password';
            } else {
                return 'text';
            }
        });
    }).on('mousedown', function () {
        logEvent("form->revealPassword");
        if ($('#eye-new').hasClass("far fa-eye")) {
            $('#eye-new').removeClass('far fa-eye').addClass('far fa-eye-slash');
            $('#eye-confirm').removeClass('far fa-eye').addClass('far fa-eye-slash');
        } else {
            $('#eye-new').removeClass('far fa-eye-slash').addClass('far fa-eye');
            $('#eye-confirm').removeClass('far fa-eye-slash').addClass('far fa-eye');
        }
        // Change tooltip text
        if ($('#eye-new-text').attr('title') === "Show password") {
            $('#eye-new-text').attr('title', 'Hide password');
            $('#eye-confirm-text').attr('title', 'Hide password');
        } else {
            $('#eye-new-text').attr('title', 'Show password');
            $('#eye-confirm-text').attr('title', 'Show password');
        }
        $('#pass-new').attr('type', function (ignore, attr) {
            if (attr === 'text') {
                return 'password';
            } else {
                return 'text';
            }
        });
        $('#pass-confirm').attr('type', function (ignore, attr) {
            if (attr === 'text') {
                return 'password';
            } else {
                return 'text';
            }
        });
    });

    /* Check password compliance */
    $("#pass-new").keyup(function () {
        clearInterval(startStrengthCalc); //disable the timer
        startStrengthCalc = setInterval(passNew, delay); // start new timer
    });
    $("#pass-confirm").keyup(function () {
        clearInterval(startStrengthCalc); //disable the timer
        startStrengthCalc = setInterval(passConfirm, delay); // start new timer
    });

    /* Register button */
    var forms = document.getElementsByClassName('needs-validation');
    Array.prototype.filter.call(forms, function (form) {
        form.addEventListener('submit', function (event) {
            logEvent("clicked()->submit");
            event.preventDefault();
            event.stopPropagation();
            matchPass($("#pass-new").val(), $("#pass-confirm").val());
            if (form.checkValidity() === true) {
                storeRegistrationData($("#pass-new").val(), pw_timespans);
            } else {
                logEvent("form->isInvalid");
            }
            form.classList.add('was-validated');
        }, false);
    });
});

