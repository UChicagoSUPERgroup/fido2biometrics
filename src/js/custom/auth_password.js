/*jslint browser: true */
/*global window, $, logEvent, alert*/

var passcurrententry = {events: [], pasted: false, pw: ""};

function storeAuthenticationData(password, ui_timespan) {
    'use strict';
    logEvent("storeAuthenticationData->start");
    var json_data = {
        password: password,
        ui_timespan: ui_timespan
    };
    $.post('../includes/lib/json_authenticate_p.php', {
        json: JSON.stringify(json_data)
    }, function (result) {
        logEvent("storeAuthenticationData->result->" + JSON.stringify(result));
        if (result.status === true) {
            location.href = 'complete_ap.php';
        } else {
            if (result.attempt >= 3) {
                location.href = 'complete_ap.php';
            } else {
                $("#alertbox").html("<i class=\"fas fa-exclamation-triangle\"></i>Authentication failed. Please try again!");
                $("#alertbox").css('visibility', 'visible');
                $("#pass-current").val("");
            }
        }
    }, 'json');
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
        location.href = 'help_ap.php?d1=wrongbrowser';
    }
    var ua = navigator.userAgent;
    var isAndroid = ua.indexOf("Android") > -1;
    if (!isAndroid) {
        logEvent("userAgent->isNotAndroid->" + navigator.userAgent);
        location.href = 'help_ap.php?d1=notandroid';
    }*/

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
    $('#authentication-form').on('keydown', 'input', function (event) {
        if (event.which === 13) {
            event.preventDefault();
            var $this = $(event.target);
            var index = parseFloat($this.attr('data-index'));
            $(`[data-index="${index + 1}"]`).focus();
        }
    });

    /* Logging */
    $(document).on("focusin", "input, select", function (e) {
        logEvent("focusin()->" + e.target.id);
    });
    $(document).on("focusout", "input, select", function (e) {
        logEvent("focusout()->" + e.target.id + "->" + e.target.value);
    });
    $('#pass-current').on('keydown', function (event) {
        if (event !== undefined) {
            if (event.key !== undefined) {
                passcurrententry.events.push(event.key);
            }
        }
    });
    $('#pass-current').on('paste', function () {
        passcurrententry.pasted = true;
    });
    $("#pass-current").focusout(function (e) {
        passcurrententry.pw = $("#pass-current").val();
        logEvent("focusout()->" + e.target.id + "->" + JSON.stringify(passcurrententry));
        passcurrententry = {events: [], pasted: false, pw: ""}; //reset
    });

    /* Register event listener to start time measurement */
    $("#pass-current").focusin(function () {
        logEvent("form->pass-current->focusin");
        if (ts_start === -1) {
            ts_start = Date.now();
        }
    });
    $("#pass-current").focusout(function () {
        logEvent("form->pass-current->focusout");
        if (ts_start !== -1) {
            var timespan = Date.now() - ts_start;
            pw_timespans.push(timespan);
            ts_start = -1;
        }
    });

    /* Press the eye button to show the password in plain text */
    $(".reveal").on('touchstart', function (event) {
        event.preventDefault();
        logEvent("form->revealPassword");
        if ($('#eye-current').hasClass("far fa-eye")) {
            $('#eye-current').removeClass('far fa-eye').addClass('far fa-eye-slash');
        } else {
            $('#eye-current').removeClass('far fa-eye-slash').addClass('far fa-eye');
        }
        // Change tooltip text
        if ($('#eye-current-text').attr('title') === "Show password") {
            $('#eye-current-text').attr('title', 'Hide password');
        } else {
            $('#eye-current-text').attr('title', 'Show password');
        }
        $('#pass-current').attr('type', function (ignore, attr) {
            if (attr === 'text') {
                return 'password';
            } else {
                return 'text';
            }
        });
    }).on('mousedown', function (event) {
        event.preventDefault();
        logEvent("form->revealPassword");
        if ($('#eye-current').hasClass("far fa-eye")) {
            $('#eye-current').removeClass('far fa-eye').addClass('far fa-eye-slash');
        } else {
            $('#eye-current').removeClass('far fa-eye-slash').addClass('far fa-eye');
        }
        // Change tooltip text
        if ($('#eye-current-text').attr('title') === "Show password") {
            $('#eye-current-text').attr('title', 'Hide password');
        } else {
            $('#eye-current-text').attr('title', 'Show password');
        }
        $('#pass-current').attr('type', function (ignore, attr) {
            if (attr === 'text') {
                return 'password';
            } else {
                return 'text';
            }
        });
    });

    /* Register button */
    var forms = document.getElementsByClassName('needs-validation');
    Array.prototype.filter.call(forms, function (form) {
        form.addEventListener('submit', function (event) {
            logEvent("clicked()->login");
            event.preventDefault();
            event.stopPropagation();
            if (form.checkValidity() === true) {
                storeAuthenticationData($("#pass-current").val(), pw_timespans);
            } else {
                logEvent("form->isInvalid");
            }
            form.classList.add('was-validated');
        }, false);
    });
});
