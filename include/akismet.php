<?php
// This requires 
//
// pear install channel://pear.php.net/Net_URL2-0.3.1
// pear install channel://pear.php.net/HTTP_Request2-0.5.1
// pear install channel://pear.php.net/Services_Akismet2-0.3.0

include 'Services/Akismet2.php';

function akismet($author,$authorEmail,$authorUri,$content) {
	static $apiKey = getenv('AKISMET_KEY');

	$comment = new Services_Akismet2_Comment(array(
		'author'      => $author,
		'authorEmail' => $authorEmail,
		'authorUri'   => $authorUri,
		'content'     => $content
	));

	$isSpam = false;
	try {
		$akismet = new Services_Akismet2('http://bugs.php.net/', $apiKey);
		if ($akismet->isSpam($comment)) {
			$isSpam = true;
		} 
	} catch (Services_Akismet2_InvalidApiKeyException $keyException) {
		echo 'Invalid API key!';
	} catch (Services_Akismet2_HttpException $httpException) {
		echo 'Error communicating with Akismet API server: ' .  $httpException->getMessage();
	} catch (Services_Akismet2_InvalidCommentException $commentException) {
		echo 'Specified comment is missing one or more required fields.' .  $commentException->getMessage();
	}
	
	return $isSpam;
}
