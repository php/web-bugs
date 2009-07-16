<?php

$xml = new XMLWriter();
$xml->openMemory();
$xml->setIndent(true);
$xml->startDocument('1.0','UTF-8');


$xml->startElement("rss");
$xml->writeAttribute("version", "2.0");

$xml->startElement("channel");
$xml->writeElement("title","{$bug['package_name']} Bug #{$bug['id']}");
$xml->writeElement("link",$uri);
$xml->writeElement("description",utf8_encode(htmlspecialchars("[{$bug['status']}] {$bug['sdesc']}")));
$xml->writeElement("pubDate",date('r',$bug['ts1a']));
$xml->writeElement("lastBuildDate",$uri);

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


$xml->startElement("item");
$xml->writeElement("title", utf8_encode(($bug['handle'])? htmlspecialchars($bug['handle']):htmlspecialchars(substr($bug['email'], 0, strpos($bug['email'], '@'))) . "@... [{$bug['ts1']}]"));
$xml->startElement("description");
$xml->writeCdata($desc);
$xml->endElement(); //end description
$xml->writeElement("pubDate",date("r",$bug['ts1a']));
$xml->writeElement("guid",$uri);
$xml->endElement(); //end item


foreach ($comments as $comment) {
    if (empty($comment['registered'])) continue;
    $displayts = date('Y-m-d H:i', $comment['added'] - date('Z', $comment['added']));
    $xml->startElement("item");
    $xml->writeElement("title", utf8_encode( ($comment['handle'])? htmlspecialchars($comment['handle']) . " [$displayts]": htmlspecialchars(substr($comment['email'], 0, strpos($comment['email'], '@')) . "@... [$displayts]")));
    $xml->startElement("description");
    $xml->writeCdata("<pre>".utf8_encode(htmlspecialchars($comment['comment']))."</pre>");
    $xml->endElement(); //end description
    $xml->writeElement("pubDate",date("r",$comment['added']));
    $xml->writeElement("guid",$uri."#".$comment['added']);
    $xml->endElement(); //end item
    
}


$xml->endElement(); //end channel
$xml->endElement(); //end rss


echo $xml->outputMemory(true);

