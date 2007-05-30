<?php

/**
 * Produces the $trusted_developers array
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

/**
 * Figure out PEAR karma levels
 */
require_once "Damblan/Karma.php";


$karma = new Damblan_Karma($dbh);
$dev_list = $karma->getUsers("pear.bug.admin");
$trusted_developers = array();
foreach ($dev_list as $dev) {
    $trusted_developers[] = $dev['user'];
}
