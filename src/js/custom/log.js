/*jslint browser: true */
/*global window, $, Lockr, URL*/

function ajaxError(action_log) {
    'use strict';
    // Restore the Lockr data
    var i, key, val;
    for (i = 0; i < action_log.length; i += 1) {
        key = Object.keys(action_log[i])[0];
        val = action_log[i][key];
        Lockr.sadd(key, val);
    }
}

function storeActionLog() {
    'use strict';
    var action_log = Lockr.getAll(true);
    Lockr.flush();
    if (action_log.length === 0) {
        //setTimeout(storeActionLog, 2000);
        return false;
    }
    var json_data = {
        action_log: action_log
    };
    $.ajax({
        async: true,
        type: "POST",
        url: '../includes/lib/json_actionlog.php',
        data: {json: JSON.stringify(json_data)},
        dataType: "json"
    }).done(function (result) {
        if (result.status !== true) { // If db insert is not successful
            ajaxError(action_log);
        }
    }).fail(function () { // Triggers on HTTP error 500
        ajaxError(action_log);
    });/*.always(function () {
        setTimeout(storeActionLog, 2000);
    });*/
}

function logEvent(name) {
    'use strict';
    var url = new URL(window.location.href);
    var path = url.pathname.replace('/static/', '');
    Lockr.sadd(Date.now(), path + "->" + name);
    storeActionLog();
}

$(document).ready(function () {
    'use strict';
    logEvent('document.ready()');
});

