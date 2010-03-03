<?php
echo '<?xml version="1.0"?>';

$desc = "{$bug['package_name']} {$bug['bug_type']}\nReported by ";
if ($bug['handle']) {
	$desc .= "{$bug['handle']}\n";
} else {
	$desc .= substr($bug['email'], 0, strpos($bug['email'], '@')) . "@...\n";
}
$desc .= date(DATE_ATOM, $bug['ts1a']) . "\n";
$desc .= "PHP: {$bug['php_version']}, OS: {$bug['php_os']}, Package Version: {$bug['package_version']}\n\n";
$desc .= $bug['ldesc'];
$desc = '<pre>' . utf8_encode(htmlspecialchars($desc)) . '</pre>';

?>
<rss version="2.0">
	<channel>
		<title><?php echo "{$bug['package_name']} Bug #{$bug['id']}"; ?></title>
		<link><?php echo $uri; ?></link>
		<description><?php echo utf8_encode(htmlspecialchars("[{$bug['status']}] {$bug['sdesc']}")); ?></description>
		<pubDate><?php echo date('r',$bug['ts1a']); ?></pubDate>
		<lastBuildDate><?php echo date('r',$bug['ts2a']); ?></lastBuildDate>
		
		<item>
			<title><?php echo utf8_encode(($bug['handle'])? htmlspecialchars($bug['handle']):htmlspecialchars(substr($bug['email'], 0, strpos($bug['email'], '@'))) . "@... [{$bug['ts1']}]"); ?></title>
			<description><![CDATA[ <?php echo $desc; ?> ]]></description>
			<pubDate><?php echo date("r",$bug['ts1a']);?></pubDate>
			<guid><?php echo $uri; ?></guid>
		</item>
		
<?php
	foreach ($comments as $comment) {
		if (empty($comment['registered'])) continue;
		$displayts = date('Y-m-d H:i', $comment['added'] - date('Z', $comment['added']));
?>
			<item>
				<title><?php echo utf8_encode( ($comment['handle'])? htmlspecialchars($comment['handle']) . " [$displayts]": htmlspecialchars(substr($comment['email'], 0, strpos($comment['email'], '@')) . "@... [$displayts]")); ?></title>
				<description><![CDATA[ <?php echo "<pre>".utf8_encode(htmlspecialchars($comment['comment']))."</pre>"; ?>]]></description>
				<pubDate><?php echo date("r",$comment['added']); ?></pubDate>		
				<guid><?php echo $uri."#".$comment['added']; ?></guid>
			</item>
<?php } ?>
	</channel>
</rss>
