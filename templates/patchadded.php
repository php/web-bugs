<?php 

response_header('Patch Added :: ' . htmlspecialchars($package) . " :: Bug #{$bug_id}");

?>

<h1>Patch Added to Bug #<?php echo $bug_id; ?>, Package <?php echo htmlspecialchars($package) ?></h1>

<?php include "{$ROOT_DIR}/templates/listpatches.php";

response_footer();
