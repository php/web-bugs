<?php
response_header('Roadmap :: ' . clean($this->package));
show_bugs_menu(clean($this->package));
?>
<h1>package.xml for Package <?php echo clean($this->package); ?>, version <?php echo clean($this->roadmap) ?></h1>
<a href="search.php?package_name[]=<?php echo urlencode(clean($this->package)) ?>&amp;status=Open&amp;cmd=display">Bug Tracker</a>
<ul class="side_pages">
 <li><a href="roadmap.php?package=<?php echo urlencode($this->package) ?>">Back to roadmap list</a></li>
</ul>
<h2>package.xml:</h2>
<pre>
<?php echo htmlspecialchars($this->xml) ?>
</pre>
<?php response_footer(); ?>
