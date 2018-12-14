<?php
header('Content-type: text/xml');

echo '<?xml version="1.0"?>
<?xml-stylesheet
href="http://www.w3.org/2000/08/w3c-synd/style.css" type="text/css"
?>
<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns="http://purl.org/rss/1.0/"
xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
xmlns:admin="http://webns.net/mvcb/" xmlns:content="http://purl.org/rss/1.0/modules/content/">';
echo "\n  <channel rdf:about=\"{$site_method}://{$site_url}{$basedir}/rss/search.php\">\n";
echo "	<title>{$siteBig} Bug Search Results</title>\n";
echo "	<link>{$site_method}://{$site_url}{$basedir}/rss/search.php?" , clean(http_build_query($_GET)) , "</link>\n";
echo "	<description>Search Results</description>\n";
echo "	<dc:language>en-us</dc:language>\n";
echo "	<dc:creator>{$site}-webmaster@lists.php.net</dc:creator>\n";
echo "	<dc:publisher>{$site}-webmaster@lists.php.net</dc:publisher>\n";
echo "	<admin:generatorAgent rdf:resource=\"{$site_method}://{$site_url}{$basedir}\"/>\n";
echo "	<sy:updatePeriod>hourly</sy:updatePeriod>\n";
echo "	<sy:updateFrequency>1</sy:updateFrequency>\n";
echo "	<sy:updateBase>2000-01-01T12:00+00:00</sy:updateBase>\n";
echo '	<items>
	 <rdf:Seq>
';

$items = '';
if ($total_rows > 0) {
	foreach ($result as $row) {
		$desc = "{$row['package_name']} ({$row['bug_type']})\nReported by ";
		if (preg_match('/@php.net$/i', $row['email'])) {
			$desc .= substr($row['email'], 0, strpos($row['email'], '@')) ."\n";
	   	} else {
	   		$desc .= substr($row['email'], 0, strpos($row['email'], '@')) . "@...\n";
		}
		$desc .= date(DATE_ATOM, $row['submitted']) . "\n";
		$desc .= "PHP: {$row['php_version']}, OS: {$row['php_os']}\n\n";
		$desc .= $row['ldesc'];
		$desc = '<pre>' . clean($desc) . '</pre>';

		echo "	  <rdf:li rdf:resource=\"{$site_method}://{$site_url}{$basedir}/{$row['id']}\" />\n";
		$items .= "  <item rdf:about=\"{$site_method}://{$site_url}{$basedir}/{$row['id']}\">\n";
		$items .= '	<title>' . clean("{$row['bug_type']} {$row['id']} [{$row['status']}] {$row['sdesc']}") . "</title>\n";
		$items .= "	<link>{$site_method}://{$site_url}{$basedir}/{$row['id']}</link>\n";
		$items .= '	<content:encoded><![CDATA[' .  $desc . "]]></content:encoded>\n";
		$items .= '	<description><![CDATA[' . $desc . "]]></description>\n";
		if (!$row['unchanged']) {
			$items .= '	<dc:date>' . date(DATE_ATOM, $row['submitted']) . "</dc:date>\n";
		} else {
			$items .= '	<dc:date>' . date(DATE_ATOM, $row['modified']) . "</dc:date>\n";
		}
		$items .= '	<dc:creator>' . clean(spam_protect($row['email'])) . "</dc:creator>\n";
		$items .= '	<dc:subject>' . clean($row['package_name']) . ' ' . clean($row['bug_type']) . "</dc:subject>\n";
		$items .= "  </item>\n";
	}
} else {
	$warnings[] = "No bugs matched your criteria";
}

echo <<< DATA
	 </rdf:Seq>
	</items>
  </channel>

  <image rdf:about="{$site_method}://{$site_url}{$basedir}/images/{$site}-logo.gif">
	<title>{$siteBig} Bugs</title>
	<url>{$site_method}://{$site_url}{$basedir}/images/{$site}-logo.gif</url>
	<link>{$site_method}://{$site_url}{$basedir}</link>
  </image>

{$items}
DATA;
?>
</rdf:RDF>
