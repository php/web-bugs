<?php
require_once 'prepend.inc';
require_once 'resolve.inc';

commonHeader("Quick Fix Descriptions");
?>
<table border="2" cellpadding="6">
<?php

	foreach ($RESOLVE_REASONS as $reason) {
		echo "
				<tr>
					<td>{$reason['desc']}</td>
					<td>Status: {$reason['status']}</td>  
					<td>{$reason['message']}</td>
				</tr>
		";
	} 
?>
</table>    
<?php 
commonFooter();
?>
