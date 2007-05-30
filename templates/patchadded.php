<?php response_header('Patch Added :: ' . clean($package) . ' :: Bug #' . clean($bug)); ?>
<h1>Patch Added to Bug #<?php echo clean($bug); ?>, Package <?php echo clean($package) ?></h1>
<?php include dirname(__FILE__) . '/listpatches.php';
response_footer();