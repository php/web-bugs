<?php /* vim: set noet ts=4 sw=4: : */

/**
 * User interface for viewing and editing bug details
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
 * Obtain common includes
 */
require_once './include/prepend.inc';

response_header('Quick Fix Descriptions'); 
?> 
<table border="2" cellpadding="6">
<?php
$RESOLVE_REASONS = get_resolve_reasons();

foreach ($RESOLVE_REASONS as $key => $reason) {
	echo "
		<tr>
		 <td>{$reason['desc']}</td>
		 <td>Status: {$reason['status']}</td>
		 <td><pre>{$reason['message']}</pre></td>
		</tr>
	";
	if (isset($FIX_VARIATIONS) && isset($FIX_VARIATIONS[$key])) {
		foreach ($FIX_VARIATIONS[$key] as $type => $variation) {
			echo "
				<tr>
					<td>{$reason['desc']} ({$type})</td>
					<td>Status: {$reason['status']}</td>
					<td><pre>{$variation}</pre></td>
				</tr>";
		}
	}
} 
?>
</table>
<?php

response_footer();
