<?php response_header('Roadmap :: ' . clean($this->package)); ?>
<h1>Roadmap for Package <?php echo clean($this->package); ?></h1>
<ul class="side_pages">
<?php foreach ($this->roadmap as $info): ?>
 <li class="side_page"><a href="roadmap.php?package=<?php echo urlencode($info['package'])
 ?>#a<?php echo $info['roadmap_version'] ?>"><?php echo $info['roadmap_version'] ?></a> (<a href="roadmap.php?edit=<?php echo $info['id']
 ?>">edit</a>|<a href="roadmap.php?delete=<?php echo $info['id']
 ?>" onclick="return confirm('Really delete roadmap <?php echo $info['roadmap_version']
 ?>?');">delete</a>)</li>
<?php endforeach; ?>
 <li><a href="roadmap.php?package=<?php echo urlencode($this->package) ?>&new=1">New roadmap</a></li>
</ul>
<?php if ($this->errors) {
    foreach ($this->errors as $error) {
        echo '<div class="errors">', htmlspecialchars($error), '</div>';
    }
} ?>
<h2><?php if ($this->isnew) { echo 'Create new'; } else { echo 'Edit'; } ?> Roadmap</h2>
<form name="roadmapform" method="post" action="roadmap.php?<?php
    if ($this->isnew) {
        echo 'package=' . urlencode($this->info['package']) . '&new=1';
    } else {
        echo 'edit=' . $this->info['id'];
    } ?>">
<table>
 <tr>
  <th class="form-label_left">
   Describe the Goals of this Release
  </th>
  <td class="form-input">
   <textarea rows="5" cols="50" name="description"><?php echo htmlspecialchars($this->info['description']) ?></textarea>
  </td>
 </tr>
 <tr>
  <th class="form-label_left">
   Release Version
  </th>
  <td class="form-input">
   <input type="text" name="roadmap_version" value="<?php echo htmlspecialchars($this->info['roadmap_version']) ?>" />
  </td>
 </tr>
 <tr>
  <th class="form-label_left">
   Scheduled Release Date<br />(use &quot;future&quot; for uncertain release date)
  </th>
  <td class="form-input">
   <input type="text" name="releasedate" value="<?php
       if ($this->info['releasedate'] == '1976-09-02 17:15:30') {
           echo 'future';
       } else {
           echo $this->info['releasedate'];
       } ?>" />
  </td>
 </tr>
</table>
<input type="submit" name="go" value="Save" />
</form>
<?php response_footer(); ?>