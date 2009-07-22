<?php
$p = clean($package);
$b = clean($bug);
response_header('Patch Added :: ' . $p . ' :: Bug #' . $b);
show_bugs_menu($p);
?>
<h1>Patch Added to Bug #<?php echo $b; ?>, Package <?php echo $p ?></h1>
<?php
include "{$ROOT_DIR}/templates/listpatches.php";
response_footer();
