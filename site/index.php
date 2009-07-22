<?php

/**
 * The bug system home page
 *
 * This source file is subject to version 3.0 of the PHP license,
 * that is bundled with this package in the file LICENSE, and is
 * available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_0.txt.
 * If you did not receive a copy of the PHP license and are unable to
 * obtain it through the world-wide-web, please send a note to
 * license@php.net so we can mail you a copy immediately.
 *
 * @category  pearweb
 * @package   Bugs
 * @copyright Copyright (c) 1997-2005 The PHP Group
 * @license   http://www.php.net/license/3_0.txt  PHP License
 * @version   $Id$
 */

require_once './include/prepend.inc';

/* If 'id' is passed redirect to the bug page */
$id = !empty($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id) {
	localRedirect("bug.php?id={$id}");
}

response_header('Bugs');

include $templates_path . "/templates/index_{$site}.php";

response_footer();
