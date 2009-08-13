<?php
$p = clean($package_name);
$b = clean($bug_id);
response_header('Patch Added :: ' . $p . ' :: Bug #' . $b);
?>
<h1>Patch Added to Bug #<?php echo $b; ?>, Package <?php echo $p; ?></h1>
<?php
include "{$ROOT_DIR}/templates/listpatches.php";
response_footer();
