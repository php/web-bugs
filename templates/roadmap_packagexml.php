<?php response_header('Roadmap :: ' . clean($this->package));?>
<h1>package.xml for Package <?php echo clean($this->package); ?>, version <?php echo clean($this->roadmap) ?></h1>
<a href="/bugs/search.php?package_name[]=<?php echo urlencode(clean($this->package)) ?>&status=Open&cmd=display">Bug Tracker</a>
<ul class="side_pages">
 <li><a href="roadmap.php?package=<?php echo urlencode($this->package) ?>">Back to roadmap list</a></li>
</ul>
<h2>package.xml:</h2>
<pre>
<?php echo htmlspecialchars($this->xml) ?>
</pre>
<?php response_footer(); ?>