<?php response_header('Roadmap :: ' . htmlspecialchars($this->package));?>
<h1>package.xml for Package <?php echo htmlspecialchars($this->package); ?>, version <?php echo htmlspecialchars($this->roadmap) ?></h1>
<a href="/bugs/search.php?package_name[]=<?php echo urlencode(htmlspecialchars($this->package)) ?>&status=Open&cmd=display">Bug Tracker</a>
<ul class="side_pages">
 <li><a href="roadmap.php?package=<?php echo urlencode($this->package) ?>">Back to roadmap list</a></li>
</ul>
<h2>package.xml:</h2>
<pre>
<?php echo htmlspecialchars($this->xml) ?>
</pre>
<?php response_footer(); ?>
