<?php
require_once 'prepend.inc';
require_once 'resolve.inc';

commonHeader("Quick Fix Descriptions");
?>
<table border="2" cellpadding="6">
<?php
foreach ($RESOLVE_REASONS as $key => $reason) {
	echo "
		<tr>
		<td>{$reason['desc']}</td>
		<td>Status: {$reason['status']}</td>  
		<td><pre>{$reason['message']}</pre></td>
		</tr>";
	if (isset($FIX_VARIATIONS[$key])) {
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
commonFooter();
?>
