<?php response_header('Roadmap :: ' . htmlspecialchars($templateData->package)) . ' :: Manage Bugs'; ?>
<h1>Manage Bugs/Features in Roadmap for Package <?php echo htmlspecialchars($templateData->package); ?></h1>
<a href="/bugs/search.php?package_name[]=<?php echo urlencode(htmlspecialchars($templateData->package)) ?>&status=Open&cmd=display">Bug Tracker</a> |
<a href="roadmap.php?package=<?php echo urlencode(htmlspecialchars($templateData->package)) ?>">Back to Roadmap Manager</a> | <a href="/<?php echo urlencode(htmlspecialchars($templateData->package)) ?>">Package Home</a>
<h2>Version <?php echo $templateData->roadmap ?></h2>
<?php if ($templateData->saved):?>
<div class="errors">Changes Saved</div>
<?php endif; // if ($templateData->saved) ?>
<form name="addbugs" action="roadmap.php" method="post">
<input type="hidden" name="package" value="<?php echo htmlspecialchars($templateData->package); ?>" />
<input type="hidden" name="roadmap" value="<?php echo htmlspecialchars($templateData->roadmap); ?>" />
<table>
 <tr>
  <th class="form-label_left">
   Bugs
  </th>
  <td class="form-input">
<?php foreach ($templateData->bugs as $id => $info): ?>
   <ul>
    <li class="<?php echo $templateData->tla[$info['status']] ?>">
    <input type="checkbox" name="bugs[<?php echo $id ?>]"<?php if ($info['inroadmap']) echo ' checked="true"' ?> /><label for="bugs[<?php echo $id ?>]"><a href="/bugs/bug.php?id=<?php
     echo $id ?>">Bug #<?php echo $id?></a>:<?php echo $info['summary'] ?> | <?php echo $info['status'] ?></label>
    </li>
   </ul>
<?php endforeach; // foreach ($templateData->bugs as $id => $info) ?>
  </td>
 </tr>
 <tr>
  <th class="form-label_left">
   Feature Requests
  </th>
  <td class="form-input">
<?php foreach ($templateData->features as $id => $info): ?>
   <ul>
    <li class="<?php echo $templateData->tla[$info['status']] ?>">
    <input type="checkbox" id="bugs[<?php echo $id ?>]" name="bugs[<?php echo $id ?>]"<?php if ($info['inroadmap']) echo ' checked="true"' ?> /><label for="bugs[<?php echo $id ?>]"><a href="/bugs/bug.php?id=<?php
     echo $id ?>">Feature #<?php echo $id?><a/>:<?php echo $info['summary'] ?> | <?php echo $info['status'] ?></label>
    </li>
   </ul>
<?php endforeach; // foreach ($templateData->features as $id => $info) ?>
  </td>
 </tr>
</table>
<input type="submit" name="saveaddbugs" value="Save Changes" />
</form>
<?php response_footer(); ?>
