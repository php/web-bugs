<?php response_header('Roadmap :: ' . htmlspecialchars($this->package)) . ' :: Manage Bugs'; ?>
<h1>Manage Bugs/Features in Roadmap for Package <?php echo htmlspecialchars($this->package); ?></h1>
<a href="/bugs/search.php?package_name[]=<?php echo urlencode(htmlspecialchars($this->package)) ?>&status=Open&cmd=display">Bug Tracker</a> |
<a href="roadmap.php?package=<?php echo urlencode(htmlspecialchars($this->package)) ?>">Back to Roadmap Manager</a> | <a href="/<?php echo urlencode(htmlspecialchars($this->package)) ?>">Package Home</a>
<h2>Version <?php echo $this->roadmap ?></h2>
<?php if ($this->saved):?>
<div class="errors">Changes Saved</div>
<?php endif; // if ($this->saved) ?>
<form name="addbugs" action="roadmap.php" method="post">
<input type="hidden" name="package" value="<?php echo htmlspecialchars($this->package); ?>" />
<input type="hidden" name="roadmap" value="<?php echo htmlspecialchars($this->roadmap); ?>" />
<table>
 <tr>
  <th class="form-label_left">
   Bugs
  </th>
  <td class="form-input">
<?php foreach ($this->bugs as $id => $info): ?>
   <ul>
    <li class="<?php echo $this->tla[$info['status']] ?>">
    <input type="checkbox" name="bugs[<?php echo $id ?>]"<?php if ($info['inroadmap']) echo ' checked="true"' ?> /><label for="bugs[<?php echo $id ?>]"><a href="/bugs/bug.php?id=<?php
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
    <input type="checkbox" id="bugs[<?php echo $id ?>]" name="bugs[<?php echo $id ?>]"<?php if ($info['inroadmap']) echo ' checked="true"' ?> /><label for="bugs[<?php echo $id ?>]"><a href="/bugs/bug.php?id=<?php
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
