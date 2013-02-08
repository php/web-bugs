<?php if (count($pulls)) { ?>
<div>
Pull requests:<br>
<ul>
<?php foreach ($pulls as $pr) { ?>
  <li><a href="<?php echo htmlentities($pr['github_html_url'], ENT_QUOTES); ?>"><?php echo htmlentities($pr['github_title']); ?></a>
      (<?php echo htmlentities($pr['github_repo'].'/'.$pr['github_pull_id']); ?>)</li>
<?php } ?>
</ul>
</div>
<?php } ?>
