'use strict';

/**
 * Servers can't deal properly with URLs containing hash. This redirects bug id
 * passed as #id to the front controller. For example,
 * https://bugs.php.net/#12345
 *
 * This is implemented for convenience if typo happens when entering bugs.php.net
 * url with id, since bugs are prefixed with hashes in bug reporting guidelines,
 * PHP commit messages and similar places.
 */

var bugId = location.hash.substr(1) * 1;

if (bugId > 0) {
    var loc = location;
    loc.replace(loc.protocol + '//' + loc.host + (loc.port ? ':' + loc.port : '') + '/' + bugId);
}
