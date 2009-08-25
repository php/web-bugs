<?php

/**
 * Obtain common includes
 */
require_once '../include/prepend.php';

list($RESOLVE_REASONS, $FIX_VARIATIONS) = get_resolve_reasons($site);

response_header('Quick Fix Descriptions'); 

?> 
<table border="1" cellpadding="3" cellspacing="1">
<?php

foreach ($RESOLVE_REASONS as $key => $reason) {
	if (!empty($reason['package_name']))
		$reason['title'] = "{$reason['title']} ({$reason['package_name']})";

	echo "
		<tr>
		 <td>{$reason['title']}</td>
		 <td>Status: {$reason['status']}</td>
		 <td><pre>{$reason['message']}</pre></td>
		</tr>
	";
    if (isset($FIX_VARIATIONS[$key])) {
		foreach ($FIX_VARIATIONS[$key] as $type => $variation) {
			echo "
				<tr>
					<td>{$reason['title']} ({$type})</td>
					<td>Status: {$reason['status']}</td>
					<td><pre>{$variation}</pre></td>
				</tr>";
		}
	}
} 
?>
</table>

<?php response_footer();
