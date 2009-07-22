<?php
response_header('Roadmap :: ' . clean($this->package));
show_bugs_menu(clean($this->package));
?>

<h1>Roadmap for Package <?php echo clean($this->package); ?></h1>
<ul class="side_pages">
 <li><a href="roadmap.php?package=<?php echo urlencode($this->package) ?>&amp;new=1">New roadmap</a></li>
<?php foreach ($this->roadmap as $info): ?>
 <li><a href="roadmap.php?package=<?php echo urlencode($info['package'])
 ?>#a<?php echo $info['roadmap_version'] ?>"><?php echo $info['roadmap_version'] ?></a> (<a href="roadmap.php?edit=<?php echo $info['id']
 ?>">edit</a>|<a href="roadmap.php?delete=<?php echo $info['id']
 ?>" onclick="return confirm('Really delete roadmap <?php echo $info['roadmap_version']
 ?>?');">delete</a>)</li>
<?php endforeach; ?>
</ul>
<?php
if ($this->errors) {
    foreach ($this->errors as $error) {
        echo '<div class="errors">', htmlspecialchars($error), '</div>';
    }
}
?>
<h2 class="no-border"><?php if ($this->isnew) { echo 'Create new'; } else { echo 'Edit'; } ?> Roadmap</h2>
<form id="roadmapform" method="post" style="display: table; width: 80em" action="roadmap.php?<?php
    if ($this->isnew) {
        echo 'package=' . urlencode($this->info['package']) . '&amp;new=1';
    } else {
        echo 'edit=' . $this->info['id'];
    } ?>">


   <p><label>Release Version<br />
   <input type="text" name="roadmap_version" value="<?php echo htmlspecialchars($this->info['roadmap_version']) ?>" /></label></p>


   <p><label>Scheduled Release Date (YYYY-MM-DD)<br />
       <input type="text" name="releasedate" value="<?php
           if ($this->info['releasedate'] == '1976-09-02 17:15:30') {
               echo 'future';
           } else {
               echo $this->info['releasedate'];
           } ?>" /></label><br />
        (use &quot;future&quot; for uncertain release date)</p>
        

   <p><label>Describe the Goals of this Release<br />
   <textarea rows="5" name="description" cols="80" style="margin: 0"><?php echo htmlspecialchars($this->info['description']) ?></textarea></label></p>




<?php
// Check if there has been a release before
if ($this->isnew && !empty($this->lastRelease) ) {
?>
   <p>Import closed bugs since last release (<?php echo $this->lastRelease; ?>)<br />

   <input type="checkbox" name="importbugs" <?php if ($this->import) { echo 'checked="checked"'; } ?> /></p>
<?php
}
?>
            <p><input type="submit" name="go" value="Save" /></p>

</form>
<?php
response_footer();
