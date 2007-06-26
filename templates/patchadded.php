<?php 

response_header('Patch Added :: ' . clean($package) . " :: Bug #{$bug_id}";

?>

<h1>Patch Added to Bug #<?php echo $bug_id; ?>, Package <?php echo clean($package) ?></h1>

<?php include $templates_path . '/templates/listpatches.php';

response_footer();
