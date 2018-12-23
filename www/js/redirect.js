'use strict';

/**
 * Servers can't deal properly with URLs containing hash. This redirects bug id
 * passed as #id to the front controller. For example,
 * https://bugs.php.net/#12345
 *
 */

window.addEventListener('load', function () {
    var bugId = window.location.hash.substr(1) * 1;

    if (bugId > 0) {
        var loc = window.location;
        loc.href = loc.protocol + '//' + loc.host + (loc.port ? ':' + loc.port : '') + '/' + bugId;
    }
});
