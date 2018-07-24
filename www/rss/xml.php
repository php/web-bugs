<?php
echo "<bug>\n";
foreach ($bug as $key => $value) {
	if (is_int($key)) continue;
	echo "  <$key>", clean($value), "</$key>\n";
}
foreach ($comments as $comment) {
	if (empty($comment['registered'])) continue;
	echo "  <comment>\n";
	foreach ($comment as $key => $value) {
		if (is_int($key)) continue;
		echo "	<$key>", clean($value), "</$key>\n";
	}
	echo "  </comment>\n";
}
echo "</bug>\n";
