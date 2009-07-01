<?php response_header('Roadmap :: ' . htmlspecialchars($templateData->package)); ?>
<h1>Roadmap for Package <?php echo htmlspecialchars($templateData->package); ?></h1>
<ul class="side_pages">
<?php foreach ($templateData->roadmap as $info): ?>
 <li class="side_page"><a href="roadmap.php?package=<?php echo urlencode($info['package'])
 ?>#a<?php echo $info['roadmap_version'] ?>"><?php echo $info['roadmap_version'] ?></a> (<a href="roadmap.php?edit=<?php echo $info['id']
 ?>">edit</a>|<a href="roadmap.php?delete=<?php echo $info['id']
 ?>" onclick="return confirm('Really delete roadmap <?php echo $info['roadmap_version']
 ?>?');">delete</a>)</li>
<?php endforeach; ?>
 <li><a href="roadmap.php?package=<?php echo urlencode($templateData->package) ?>&new=1">New roadmap</a></li>
</ul>
<?php
if ($templateData->errors) {
    foreach ($templateData->errors as $error) {
        echo '<div class="errors">', htmlspecialchars($error), '</div>';
    }
}
?>
<h2><?php if ($templateData->isnew) { echo 'Create new'; } else { echo 'Edit'; } ?> Roadmap</h2>
<form name="roadmapform" method="post" action="roadmap.php?<?php
    if ($templateData->isnew) {
        echo 'package=' . urlencode($templateData->info['package']) . '&new=1';
    } else {
        echo 'edit=' . $templateData->info['id'];
    } ?>">
<table>
 <tr>
  <th class="form-label_left">
   Describe the Goals of this Release
  </th>
  <td class="form-input">
   <textarea rows="5" cols="50" name="description"><?php echo htmlspecialchars($templateData->info['description']) ?></textarea>
  </td>
 </tr>
 <tr>
  <th class="form-label_left">
   Release Version
  </th>
  <td class="form-input">
   <input type="text" name="roadmap_version" value="<?php echo htmlspecialchars($templateData->info['roadmap_version']) ?>" />
  </td>
 </tr>
 <tr>
  <th class="form-label_left">
   Scheduled Release Date<br />(use &quot;future&quot; for uncertain release date)
  </th>
  <td class="form-input">
   <input type="text" name="releasedate" value="<?php
       if ($templateData->info['releasedate'] == '1976-09-02 17:15:30') {
           echo 'future';
       } else {
           echo $templateData->info['releasedate'];
       } ?>" />
  </td>
 </tr>
<?php
// Check if there has been a release before
if ($templateData->isnew && !empty($templateData->lastRelease)) {
?>
 <tr>
  <th class="form-label_left">
   Import closed bugs since last release (<?php echo $templateData->lastRelease; ?>)
  </th>
  <td class="form-input">
   <input type="checkbox" name="importbugs" <?php if ($templateData->import) { echo 'checked="checked"'; } ?> />
  </td>
 </tr>
<?php
}
?>
</table>
<input type="submit" name="go" value="Save" />
</form>
<?php response_footer(); ?>
