<?php
# spacer()
# print a IMG tag for a sized spacer GIF
#

function spacer($width=1, $height=1, $align=false, $extras=false) {
	printf('<img src="/gifs/spacer.gif" width="%d" height="%d" border="0" alt="" %s%s>',
		$width,
		$height,
		($align ? 'align="'.$align.'" ' : ''),
		($extras ? $extras : '')
	);
}



# resize_image()
# tag the output of make_image() and resize it manually
#

function resize_image($img, $width=1, $height=1) {
	$str = preg_replace('/width=\"([0-9]+?)\"/i', '', $img );
	$str = preg_replace('/height=\"([0-9]+?)\"/i', '', $str );
	$str = substr($str,0,-1) . sprintf(' height="%s" width="%s">', $height, $width );
	return $str;
}



# make_image()
# return an IMG tag for a given file (relative to the images dir)
#

function make_image($file, $alt=false, $align=false, $extras=false, $dir=false, $border=0) {
	global $HTTP_SERVER_VARS;
	if (!$dir) {
		$dir = 'gifs';
	}
	$path = $HTTP_SERVER_VARS['DOCUMENT_ROOT']."/$dir/$file";
	if ($size = @getimagesize($path)) {
		$image = sprintf('<img src="%s/%s" border="%d" %s alt="%s" %s%s>',
			$dir,
			$file,
			$border,
			$size[3],
			($alt    ? $alt : ''),
			($align  ? ' align="'.$align.'"'  : ''),
			($extras ? ' '.$extras            : '')
		);
	} else {
		$image = sprintf('<img src="%s/%s" border="%d" alt="%s" %s%s>',
			$dir,
			$file,
			$border,
			($alt    ? $alt : ''),
			($align  ? ' ALIGN="'.$align.'"'  : ''),
			($extras ? ' '.$extras            : '')
		);
	}
	return $image;
}



# print_image()
# print an IMG tag for a given file
#

function print_image($file, $alt=false, $align=false, $extras=false, $dir=false, $border=0) {
	print make_image($file, $alt, $align, $extras, $dir);
}



# make_submit()
#  - make a submit button image
#
function make_submit($file, $alt=false, $align=false, $extras=false, $dir=false, $border=0) {
	if (!$dir) {
		$dir = '/gifs';
	}
	$return = make_image($file, $alt, $align, $extras, $dir, $border);
	if ($return != "<img>") {
		$return = '<input type="image"'.substr($return,4);
	} else {
		$return = '<input type="submit">';
	}
	return $return;
}



# delim()
# print a pipe delimiter
#

function delim($color=false) {
	if (!$color) {
		return '&nbsp;|&nbsp;';
	}
	return sprintf('<font color="%s">&nbsp;|&nbsp;</font>', $color );
}



# hdelim()
# print a horizontal delimiter (just a wide line);
#

function hdelim($color="#000000") {
	if (!$color) {
		return '<hr noshade size="1">';
	}
	return sprintf('<hr noshade size="1" color="%s">', $color );
}



# make_link()
# return a hyperlink to something, within the site
#

function make_link ($url, $linktext=false, $target=false, $extras=false) {
	return sprintf("<a href=\"%s\"%s%s>%s</a>",
		$url,
		($target ? ' target="'.$target.'"' : ''),
		($extras ? ' '.$extras : ''),
		($linktext ? $linktext : $url)
	);
}



# print_link()
# echo a hyperlink to something, within the site
#

function print_link($url, $linktext=false, $target=false, $extras=false) {
	echo make_link($url, $linktext, $target, $extras);
}



function make_email($email, $linktext=false) {
	return sprintf("<a href=\"mailto:%s\">%s</a>",
		$email,
		($linktext ? $linktext : $email)
	);
}

function print_email($email, $linktext=false) {
	echo make_email($email, $linktext);
}

# commonheader()
#
#

function commonHeader($title = false, $showsearch = true) {
	global $SIDEBAR_DATA;

?><html>
<head>
 <title>PHP Bugs<?php if ($title) { echo ': '.$title; } ?></title>
 <link rel="stylesheet" href="style.css" />
 <script type="text/javascript" src="util.js"></script>
</head>

<body
	topmargin="0" leftmargin="0"
	marginheight="0" marginwidth="0"
        bgcolor="#ffffff"
        text="#000000"
        link="#000033"
        alink="#0099ff"
        vlink="#000033"
><a name="TOP"></a>
<table border="0" cellspacing="0" cellpadding="0" height="48" width="100%">
  <tr class="headertop">
    <td align="left" rowspan="2">
<?php print_link('/', make_image('logo-bug.gif', 'PHP Bugs', false, 'vspace="2" hspace="2"') ); ?><br>
    </td>
  </tr>

  <tr class="headertop">
    <td align="right" valign="bottom" nowrap>
      <?php
	print_link('http://www.php.net/', 'php.net', false, 'class="menuWhite"');
	echo delim();
	print_link('http://www.php.net/support.php', 'support', false, 'class="menuWhite"');
	echo delim();
	print_link('http://www.php.net/manual/en/', 'documentation', false, 'class="menuWhite"');
	echo delim();
	print_link('report.php', 'report a bug', false, 'class="menuWhite"');
	echo delim();
	print_link('search.php', 'advanced search', false, 'class="menuWhite"');
	echo delim();
	print_link('bugstats.php', 'statistics', false, 'class="menuWhite"');
      ?>
      <?php spacer(2,2); ?><br>
    </td>
  </tr>

<?php if ($showsearch) { ?>
  <tr class="headersep"><td colspan="2"><?php spacer(1,1);?><br></td></tr>

  <tr class="headerbottom">
    <form method="get" action="search.php">
    <input type="hidden" name="cmd" value="display" />
      <td align="right" valign="top" colspan="2" nowrap><font color="#ffffff">
        <small>search bugs for</small>
<input class="small" type="text" name="search_for" value="<?php echo htmlspecialchars($prevsearch) ?>" size="30" />
        <?php echo make_submit('small_submit_white.gif', 'search', 'bottom');?>&nbsp;<br>
     </font></td>
    </form>
  </tr>
<?php } ?>

  <tr class="headersep"><td colspan="2"><?php spacer(1,1);?><br></td></tr>
</table>
<div id="content">
<?php
}




# commonfooter()
#
#

function commonFooter() {
	global $HTTP_SERVER_VARS;
?>
</div>
<table border="0" cellspacing="0" cellpadding="0" width="100%">
  <tr class="headersep"><td><?php spacer(1,1);?><br></td></tr>
  <tr class="headerbottom">
    <td align="right" valign="bottom"><?
      print_link('/source.php?url='.$HTTP_SERVER_VARS['SCRIPT_NAME'], 'show source', false, 'class="menuWhite"');
      echo delim();
      print_link('/credits.php', 'credits', false, 'class="menuWhite"');
      ?>&nbsp;<br>
    </td>
  </tr>
  <tr class="headersep"><td><?php spacer(1,1); ?><br></td></tr>
</table>

<table border="0" cellspacing="0" cellpadding="6" width="100%">
  <tr valign="top" bgcolor="#cccccc">
    <td><small>
      <?php print_link('http://www.php.net/', make_image('php-logo.gif', 'PHP', 'left') ); ?>
      <?php print_link('http://www.php.net/copyright.php', 'Copyright &copy; 2001, 2002 The PHP Group'); ?><BR>
      All rights reserved.<BR>
      </small>
    </td>
    <td align="right"><small>
	  Last updated: <?php echo strftime("%c %Z", getlastmod()); ?><BR>
      </small><br>
    </td>
  </tr>
</table>

</body>
</html>
<?php

}


function clean_note($text) {
	$text = htmlspecialchars($text);
	$fixes = array('<br>','<p>','</p>');
	foreach ($fixes as $f) {
		$text=str_replace(htmlspecialchars($f), $f, $text);
		$text=str_replace(htmlspecialchars(strtoupper($f)), $f, $text);
	}
	$text = "<tt>".nl2br($text)."</tt>";
	return $text;
}


function sect_to_file($string) {
        $string = strtolower($string);
        $string = str_replace(' ','-',$string);
        $string = str_replace('_','-',$string);
        $func = "function.$string.php";
        $chap = "ref.$string.php";
        $feat = "features.$string.php";
        $struct = "control-structures.$string.php";
        if(is_file($func)) return $func;
        else if(is_file($chap)) return $chap;
        else if(is_file($feat)) return $feat;
        else if(is_file($struct)) return $struct;
        else return "$string.php";
}
