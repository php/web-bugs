<?php

/**
 * Produces a CAPTCHA image
 *
 * Uses the value of <var>$_SESSION['captcha']</var> for the image's text.
 * That value is set in generate_captcha() in include/pear-format-html.php.
 *
 * If <var>$_SESSION['captcha']</var> is not set, the request is bogus, so
 * exit without any output in order to save resources.
 *
 * Rules:
 *   + Use fonts that are fuzzy.
 *   + Use a random font for each character.
 *   + Have each character be a random size, start at a random vertical
 *     location and appear at random angles.
 *   + Allow characters to sometimes overlap a little and flow a bit off
 *     the canvas.
 *   + Use two random colors for the foreground (text and arcs).
 *   + The foreground color must contrast with the background color.
 *   + The foreground arc colors must be the same as the text in order to
 *     make them harder to remove.
 *   + Some arcs will also be the background color, causing them to cut up
 *     the foreground items.
 *   + Encourage arcs not to touch the image's edges.
 *   + Have the arcs run from left to right as to not look like 1 or l.
 *   + Make sure each arc crosses through most of the text.
 *
 * Based on a combination of concepts found in:
 *   + http://www.viebrock.ca/code/10/turing-protection
 *   + http://lists.nyphp.org/pipermail/talk/2004-June/010218.html
 *   + http://lists.nyphp.org/pipermail/talk/2004-July/010996.html
 *
 * LICENSE: This source file is subject to version 3.0 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_0.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category  php-bugs-web
 * @package   php-bugs-web
 * @author    Daniel Convissor <danielc@php.net>
 * @copyright Copyright (c) 2004-2009 The PHP Group
 * @license   http://www.php.net/license/3_0.txt  PHP License
 * @version   $Id$
 * @see       generate_captcha(), validate_captcha()
 */

session_start();

if (!isset($_SESSION['captcha'])) {
    exit;
}



/*
 * SETTINGS  ===========================
 */
$arc_count        = 6;
$arc_deg_vary     = 30;
$arc_pad_max      = 20;
$arc_pad_min      = 5;
$color_bg_max     = 5;
$color_bg_min     = 4;
$color_fg_max     = 2;
$color_fg_min     = 1;
$font_angle_max   = 20;
$font_angle_min   = -20;
$font_padding     = 2;
$font_size_max    = 25;
$font_size_min    = 18;

/*
 * Use full path to the fonts to avoid problems.
 * The fonts are located in php-bugs-web/include/fonts.
 * Ease dev box configuration by doing this funky string replace.
 */
$font_dir = $_SERVER['DOCUMENT_ROOT'] . '/include/fonts/';

/*
 * This array contains the list of font names and the number the base
 * font size should be multiplied by to make all the fonts appear about
 * the same height.
 *
 * Fonts were obtained from http://www.freefontsnow.com/.  They are free
 * for use under certain circumstances (such as the php.net websites).
 */
$fonts = array(
    'coolveti.ttf'      => 1,    // by Ray Larabie
);



/*
 * CALCULATE THE TEXT MEASUREMENTS  ====
 */
$image_width  = 0;
$image_height = 0;
$data         = array();

for ($i = 0; $i < strlen($_SESSION['captcha']); $i++) {
    $char = substr($_SESSION['captcha'], $i, 1);

    $font_name = array_rand($fonts);
    $font      = $font_dir . $font_name;

    $size  = mt_rand($font_size_min, $font_size_max) * $fonts[$font_name];
    switch ($char) {
        case 'Q':
            // Keep Q's tail from being off the bottom of the image.
            $angle = mt_rand(0, $font_angle_max);
            break;
        default:
            $angle = mt_rand($font_angle_min, $font_angle_max);
    }

    $bbox   = imagettfbbox($size, $angle, $font, $char);
    $width  = max($bbox[2], $bbox[4]) - min($bbox[0], $bbox[6]);
    $height = max($bbox[1], $bbox[3]) - min($bbox[7], $bbox[5]);

    $image_width += $width + $font_padding;
    $image_height = max($image_height, $height);

    $data[] = array(
        'font'   => $font,
        'char'   => $char,
        'size'   => $size,
        'angle'  => $angle,
        'height' => $height,
        'width'  => $width,
    );
}

$image_width -= $font_padding;

/*
 * BASE IMAGE  =========================
 */
$im = imagecreate($image_width, $image_height);

/*
 * COLORS  =============================
 *
 * 0 is the background color.
 * 1 through x are the foreground colors.
 */
$colors = array(
    imagecolorallocate($im,
                       51 * mt_rand($color_bg_min, $color_bg_max),
                       51 * mt_rand($color_bg_min, $color_bg_max),
                       51 * mt_rand($color_bg_min, $color_bg_max)),
    imagecolorallocate($im,
                       51 * mt_rand($color_fg_min, $color_fg_max),
                       51 * mt_rand($color_fg_min, $color_fg_max),
                       51 * mt_rand($color_fg_min, $color_fg_max)),
    imagecolorallocate($im,
                       51 * mt_rand($color_fg_min, $color_fg_max),
                       51 * mt_rand($color_fg_min, $color_fg_max),
                       51 * mt_rand($color_fg_min, $color_fg_max)),
);
$color_max = count($colors) - 1;

/*
 * DISPLAY TEXT  =======================
 */
$pos_x = 0;
$y_min = $image_height - 15;
$y_max = $image_height - 3;

foreach ($data as $d) {
    $pos_y  = mt_rand($y_min, $y_max);
    imagettftext($im, $d['size'], $d['angle'], $pos_x, $pos_y,
                 $colors[mt_rand(1, $color_max)], $d['font'], $d['char']);
    $pos_x += $d['width'] + $font_padding;
}

/*
 * ARCS ================================
 */
for ($i = 0; $i < $arc_count; $i++) {
    // Start on left side, arc upward, then ending on the right side.
    $start = mt_rand(180 - $arc_deg_vary, 180 + $arc_deg_vary);
    $end   = mt_rand(0 - $arc_deg_vary, $arc_deg_vary);
    if ($end < 0) {
        $end = 360 - $end;
    }

    $half_w   = $image_width / 2;
    $center_x = mt_rand($half_w - $arc_pad_min, $half_w + $arc_pad_min);
    $tmp_w    = ($half_w - abs($half_w - $center_x)) * 2;
    $width    = mt_rand($tmp_w - $arc_pad_min, $tmp_w - $arc_pad_max);
    $center_y = mt_rand($image_height / 2, $image_height - $arc_pad_min);
    $height   = mt_rand(3, $center_y * 2 - $arc_pad_min);

    if ($i % 2) {
        // Flip arc to a downward one.
        $tmp      = $end;
        $end      = $start;
        $start    = $tmp;
        $center_y = $image_height - $center_y;
    }

    imagearc($im, $center_x, $center_y, $width, $height,
             $start, $end, $colors[mt_rand(0, $color_max)]);
}

/*
 * OUTPUT  =============================
 */
header('Content-type: image/jpeg');
imagejpeg($im, null, 100);

?>
