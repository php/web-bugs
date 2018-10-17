<?php
header('Content-type: application/rss+xml');
echo '<?xml version="1.0"?>' . "\n";
?>
<rss version="2.0">
 <channel>
  <title><?php echo $siteBig; ?> Bug Search Results</title>
<?php echo "  <link>{$site_method}://{$site_url}{$basedir}/rss/search.php?" , clean(http_build_query($_GET)) , "</link>\n"; ?>
  <description>Search Results</description>
<?php
if ($total_rows > 0) {
	foreach ($result as $row) {
		echo "  <item>\n";
		echo '   <title>' . clean($row['sdesc']) . "</title>\n";
		echo "   <link>{$site_method}://{$site_url}{$basedir}/{$row['id']}</link>\n";
		echo "   <category>{$row['status']}</category>\n";
		echo '   <pubDate>' . date(DATE_RSS, $row['submitted']) . "</pubDate>\n";
		echo "  </item>\n";
	}
} else {
	$warnings[] = "No bugs matched your criteria";
}
?>
 </channel>
</rss>
