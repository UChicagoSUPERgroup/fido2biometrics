/*jslint browser: true */
/*global window, $, publicKey, PublicKeyCredential, logEvent, alert*/

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

function storeAuthenticationData(myPublicKeyCredential, ui_timespan, success) {
    'use strict';
    var json_data;
    if (success === true) {
        json_data = {
            webauthn: myPublicKeyCredential,
            ui_timespan: ui_timespan
        };
    } else {
        json_data = {};
    }
    $.post('../includes/lib/json_authenticate_w.php', {
        json: JSON.stringify(json_data)
    }, function (result) {
        if (result.status === true) {
            location.href = 'complete_aw.php';
        } else {
            if (result.attempt >= 3) {
                location.href = 'complete_aw.php';
            } else {
                $("#alertbox").html("<i class=\"fas fa-exclamation-triangle\"></i>Authentication failed. Please try again!");
                $("#alertbox").css('visibility', 'visible');
            }
        }
    }, 'json');
}

function webAuthnGetCredentials() {
    'use strict';
    $("#alertbox").css('visibility', 'hidden');
    $("#alertbox").text("PLACEHOLDER TEXT");
    logEvent("webAuthnGetCredentials->start");
    var ts_start = Date.now();
    navigator.credentials.get({publicKey: publicKey})
        .then(function (data) {
            const myPublicKeyCredential = {
                id: data.id,
                type: data.type,
                rawId: arrayToBase64String(new Uint8Array(data.rawId)),
                response: {
                    authenticatorData: arrayToBase64String(new Uint8Array(data.response.authenticatorData)),
                    clientDataJSON: arrayToBase64String(new Uint8Array(data.response.clientDataJSON)),
                    signature: arrayToBase64String(new Uint8Array(data.response.signature)),
                    userHandle: data.response.userHandle
                        ? arrayToBase64String(new Uint8Array(data.response.userHandle))
                        : null
                }
            };
            var ui_timespan = Date.now() - ts_start;
            logEvent("webAuthnGetCredentials->success");
            storeAuthenticationData(myPublicKeyCredential, ui_timespan, true);
        })
        .catch(function (error) {
            var ui_timespan = Date.now() - ts_start;
            logEvent("webAuthnGetCredentials->" + ui_timespan + "->" + error);
            storeAuthenticationData("", -1, false);
            $("#alertbox").html("<i class=\"fas fa-exclamation-triangle\"></i>Authentication failed. Please try again!");
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
        location.href = 'help_aw.php?d1=wrongbrowser';
    }
    /*
    if ($.ua.browser.name === "Chrome") {
        var isChrome = true;
    } else {
        var isChrome = false;
    }
    if (!isChrome) {
        logEvent("userAgent->isNotChrome->" + navigator.userAgent);
        location.href = 'help_aw.php?d1=wrongbrowser';
    }
    var ua = navigator.userAgent;
    var isAndroid = ua.indexOf("Android") > -1;
    if (!isAndroid) {
        logEvent("userAgent->isNotAndroid->" + navigator.userAgent);
        location.href = 'help_aw.php?d1=notandroid';
    }*/

    // Load server data (different for authentication)
    publicKey.challenge = Uint8Array.from(window.atob(base64UrlToBase64(publicKey.challenge)), function (c) {
        return c.charCodeAt(0);
    });
    if (publicKey.allowCredentials) {
        publicKey.allowCredentials = publicKey.allowCredentials.map(function (data) {
            data.id = Uint8Array.from(window.atob(base64UrlToBase64(data.id)), function (c) {
                return c.charCodeAt(0);
            });
            return data;
        });
    }

    // Start authentication process on fallback Model close event
    $('#fallbackModal').on('hidden.bs.modal', function () {
        logEvent("fallbackModal->close");
        webAuthnGetCredentials();
    });

    $("#authentication-form").submit(function (e) {
        e.preventDefault();
        logEvent("clicked()->login");
        if (fallback === true) {
            logEvent("fallbackModal->start");
            $('#fallbackModal').modal();
        } else {
            webAuthnGetCredentials();
        }
    });


});