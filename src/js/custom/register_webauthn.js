/*jslint browser: true */
/*global window, $, publicKey, PublicKeyCredential, logEvent*/

function arrayToBase64String(a) {
    'use strict';
    return window.btoa(String.fromCharCode(...a));
}

function base64UrlToBase64(input) {
    'use strict';
    input = input.replace(/\=/g, "").replace(/-/g, "+").replace(/_/g, "/");
    const pad = input.length % 4;
    if (pad) {
        if (pad === 1) {
            throw new Error("InvalidLengthError: Input base64url string is the wrong length to determine padding");
        }
        var x;
        for (x = 1; x < 5 - pad; x += 1) {
            input += '=';
        }
    }
    return input;
}

function storeRegistrationData(myPublicKeyCredential, ui_timespan, success) {
    'use strict';
    var json_data;
    if (success === true) {
        json_data = {
            webauthn: myPublicKeyCredential,
            gender: $("#gender").val(),
            age: $("#age").val(),
            ui_timespan: ui_timespan
        };
    } else {
        json_data = {};
    }
    $.post('../includes/lib/json_register_w.php', {
        json: JSON.stringify(json_data)
    }, function (result) {
        if (result.status === true) {
            location.href = 'complete_rw.php';
        } else {
            if (result.attempt >= 3) {
                location.href = 'complete_rw.php';
            } else {
                $("#alertbox").html("<i class=\"fas fa-exclamation-triangle\"></i>Registration failed. Please try again!");
                $("#alertbox").css('visibility', 'visible');
            }
        }
    }, 'json');
}

function webAuthnCreateCredentials() {
    'use strict';
    $("#alertbox").css('visibility', 'hidden');
    $("#alertbox").text("PLACEHOLDER TEXT");
    logEvent("webAuthnCreateCredentials->start");
    var ts_start = Date.now();
    navigator.credentials.create({publicKey: publicKey})
        .then(function (data) {
            const myPublicKeyCredential = {
                id: data.id,
                rawId: arrayToBase64String(new Uint8Array(data.rawId)),
                response: {
                    attestationObject: arrayToBase64String(new Uint8Array(data.response.attestationObject)),
                    clientDataJSON: arrayToBase64String(new Uint8Array(data.response.clientDataJSON))
                },
                type: data.type
            };
            var ui_timespan = Date.now() - ts_start;
            logEvent("webAuthnCreateCredentials->success");
            storeRegistrationData(myPublicKeyCredential, ui_timespan, true);
        }, function (error) {
            var ui_timespan = Date.now() - ts_start;
            logEvent("webAuthnCreateCredentials->" + ui_timespan + "->" + error);
            storeRegistrationData("", -1, false);
            $("#alertbox").html("<i class=\"fas fa-exclamation-triangle\"></i>Registration failed. Please try again!");
            $("#alertbox").css('visibility', 'visible');
            $("#register").html('Retry');
        });
}

$(document).ready(function () {
    'use strict';
    // Check whether correct device and browser is used
    if (window.PublicKeyCredential) {
        console.log("WebAuthn is supported");
    } else {
        logEvent("window.PublicKeyCredential->isNotSupported->" + navigator.userAgent);
        location.href = 'help_rw.php?d1=wrongbrowser';
    }
    /*
    if ($.ua.browser.name === "Chrome") {
        var isChrome = true;
    } else {
        var isChrome = false;
    }
    if (!isChrome) {
        logEvent("userAgent->isNotChrome->" + navigator.userAgent);
        location.href = 'help_rw.php?d1=wrongbrowser';
    }
    var ua = navigator.userAgent;
    var isAndroid = ua.indexOf("Android") > -1;
    if (!isAndroid) {
        logEvent("userAgent->isNotAndroid->" + navigator.userAgent);
        location.href = 'help_rw.php?d1=notandroid';
    }
    */

    // Load server data
    publicKey.challenge = Uint8Array.from(window.atob(base64UrlToBase64(publicKey.challenge)), function (c) {
        return c.charCodeAt(0);
    });
    publicKey.user.id = Uint8Array.from(window.atob(publicKey.user.id), function (c) {
        return c.charCodeAt(0);
    });
    if (publicKey.excludeCredentials) {
        publicKey.excludeCredentials = publicKey.excludeCredentials.map(function (data) {
            data.id = Uint8Array.from(window.atob(base64UrlToBase64(data.id)), function (c) {
                return c.charCodeAt(0);
            });
            return data;
        });
    }

    // Start registration process on fallback Model close event
    $('#fallbackModal').on('hidden.bs.modal', function () {
        logEvent("fallbackModal->close");
        webAuthnCreateCredentials();
    });

    // Register event listener
    var forms = document.getElementsByClassName('needs-validation');
    Array.prototype.filter.call(forms, function (form) {
        form.addEventListener('submit', function (event) {
            logEvent("clicked()->submit");
            event.preventDefault();
            event.stopPropagation();
            if (form.checkValidity() === true) {
                if (fallback === true) {
                    logEvent("fallbackModal->start");
                    $('#fallbackModal').modal();
                } else {
                    webAuthnCreateCredentials();
                }
            } else {
                logEvent("form->isInvalid");
            }
            form.classList.add('was-validated');
        }, false);
    });

});