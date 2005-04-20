<?php

/**
 * Help regarding our CAPTCHAs.
 *
 * To keep things easy to understand, don't output the actual image or
 * audio links if <var>$_SESSION['captcha']</var> isn't set.
 *
 * This source file is subject to version 3.0 of the PHP license,
 * that is bundled with this package in the file LICENSE, and is
 * available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_0.txt.
 * If you did not receive a copy of the PHP license and are unable to
 * obtain it through the world-wide-web, please send a note to
 * license@php.net so we can mail you a copy immediately.
 *
 * @category  pearweb
 * @package   pearweb
 * @author    Daniel Convissor <danielc@php.net>
 * @copyright Copyright (c) 2004-2005 The PHP Group
 * @license   http://www.php.net/license/3_0.txt  PHP License
 * @version   $Id$
 * @see       generate_captcha(), validate_captcha(), captcha-image.php
 */

require_once 'prepend.inc';

commonHeader('CAPTCHA :: Help');

?>

<h1>CAPTCHA Help</h1>

<p>Some forms on the PHP Bugs website use
<a href="http://www.parc.xerox.com/research/istl/projects/captcha/"><acronym
 title="Completely Automated Public Turing test to tell Computers and Humans Apart"
 >CAPTCHA</acronym></a>s in order to avoid our getting spammed.</p>

<?php

if (isset($_SESSION['captcha'])) {

    ?>

<p>Your CAPTCHA is <img src="/captcha-image.php" alt="CAPTCHA text" />.
If you are having a hard time reading it, reloading this page
will modify how the image looks.</p>

<p>If you are visually impaired, please send a note to the
<?php echo make_mailto_link('webmaster@php.net', 'php-webmaster') ?>
 mailing list. Be sure to explain your situation and include all
of the information from the form you are trying to submit.</p>

    <?php

} else {

    ?>

<p>This page provides further information once you view a
form which requires a CAPTCHA.</p>

    <?php

}

commonFooter();

?>
