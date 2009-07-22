<?php
response_header('Roadmap :: ' . clean($this->package)) . ' :: Manage Bugs';
show_bugs_menu(clean($this->package));
?>
<h1>Manage Bugs/Features in Roadmap for Package <?php echo clean($this->package); ?></h1>
<h2>Version <?php echo $this->roadmap ?></h2>
<?php if ($this->saved):?>
<div class="errors">Changes Saved</div>
<?php endif; // if ($this->saved) ?>
<form name="addbugs" action="roadmap.php" method="post">
<input type="hidden" name="package" value="<?php echo clean($this->package); ?>" />
<input type="hidden" name="roadmap" value="<?php echo clean($this->roadmap); ?>" />
<table>
 <tr>
  <th class="form-label_left">
   Bugs
  </th>
  <td class="form-input">
<?php foreach ($this->bugs as $id => $info): ?>
   <ul>
    <li class="<?php echo $this->tla[$info['status']] ?>">
    <input type="checkbox" name="bugs[<?php echo $id ?>]"<?php if ($info['inroadmap']) echo ' checked="true"' ?> /><label for="bugs[<?php echo $id ?>]"><a href="bug.php?id=<?php
     echo $id ?>">Bug #<?php echo $id?></a>:<?php echo $info['summary'] ?> | <?php echo $info['status'] ?></label>
    </li>
   </ul>
<?php endforeach; // foreach ($this->bugs as $id => $info) ?>
  </td>
 </tr>
 <tr>
  <th class="form-label_left">
   Feature Requests
  </th>
  <td class="form-input">
<?php foreach ($this->features as $id => $info): ?>
   <ul>
    <li class="<?php echo $this->tla[$info['status']] ?>">
    <input type="checkbox" id="bugs[<?php echo $id ?>]" name="bugs[<?php echo $id ?>]"<?php if ($info['inroadmap']) echo ' checked="true"' ?> /><label for="bugs[<?php echo $id ?>]"><a href="bug.php?id=<?php
     echo $id ?>">Feature #<?php echo $id?><a/>:<?php echo $info['summary'] ?> | <?php echo $info['status'] ?></label>
    </li>
   </ul>
<?php endforeach; // foreach ($this->features as $id => $info) ?>
  </td>
 </tr>
</table>
<input type="submit" name="saveaddbugs" value="Save Changes" />
</form>
<?php response_footer(); ?>
