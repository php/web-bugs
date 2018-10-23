<?php

session_start();

require '../include/prepend.php';

// Authenticate
bugs_authenticate($user, $pw, $logged_in, $user_flags);

response_header("How to search the bug database");
?>

<h1>How to Search</h1>

<p>
This HOWTO will allow for a useful experience while scouring the bug database.
Do note that a lot of information is entered in by the general public and
therefore cannot be fully trusted. Also, the information contained within
a bug report is what setup found the bug, so other setups may apply.
</p>

<h3>Basic Search</h3>

<p>
Within every <a href="<?php echo $basedir, '/'; ?>"><?php echo $site_url, $basedir; ?>/</a>
webpage header is a search box, this is the <i>basic</i> search option.
You may enter in a numeric bug ID to redirect to that bugs page or
enter in a search term to perform a default bug search.
Load the advanced search to view the default values.
</p>

<h3>Advanced Search</h3>
<p>
Some explanations for most of the PHP bugs <a href="search.php">advanced search</a>
options.
</p>
<table cellpadding="2" cellspacing="0" border="1" bgcolor="#eeeeee">
	<tr class="headerbottom" align="left">
		<td><strong>Feature</strong></td>
		<td><strong>Explanation</strong></td>
		<td><strong>Possible reasons for use</strong></td>
	</tr>
	<tr valign="top">
		<td>Find bugs</td>
		<td>
			The main search text box for your search terms with each term being separated
			by a space. The searched database fields are: author email, subject, and description.
			Minimum term length is three characters. There are three types of searches:
			<ul>
				<li><i>all</i> : (default) All search terms are required.</li>
				<li><i>any</i> : One or more (any) of the search terms may be present.</li>
				<li>
					<i>raw</i> : Allows full use of MySQL's
					<a href="https://dev.mysql.com/doc/en/fulltext-search.html">FULLTEXT</a>
					boolean search operators.
				</li>
			</ul>
		</td>
		<td>
			For <strong>any</strong>, you might search for a function and its alias while
			not caring which shows up. Or a name that has changed in PHP 5 from PHP 4.
			Use of <strong>all</strong> makes sense if you require every term in your
			results, as this can provide precise searching. The <strong>raw</strong>
			option is for custom searches, like you might require one term but also want
			to disallow another from the result. Also, adding optional terms always
			affects relevancy/order.
		</td>
	</tr>
	<tr valign="top">
		<td>Status</td>
		<td>
			Each bug has a status, this allows searching for a specific (or all) status type.
			Here are a few explanations:
			<ul>
				<li>
					<strong>Open</strong>: This also includes <i>assigned</i>, <i>analyzed</i>,
					<i>critical</i>, and <i>verified</i> bugs. (default)
				</li>
				<li>
					<strong>Feedback</strong>: Bugs requesting feedback. Typically a bug that
					requests feedback will be marked as <i>No Feedback</i> if no feedback transpires
					after 15 days.
				</li>
				<li><strong>Old feedback</strong>: Bugs that have been requesting feedback for over60 days.</li>
				<li>
					<strong>Fresh</strong> : Bugs commented on in the last 30 days that are not closed,
					duplicates, or not-a-bug. Only developers and the original author can affect this
					date as public comments do not.
				</li>
				<li>
					<strong>Stale</strong>: Bugs last commented on at least 30 days ago that are not
					closed, duplicates, or not-a-bug. Only developers and the original author can affect
					this date as public comments do not count.
				</li>
				<li><strong>All</strong>: All types, even not-a-bug.</li>
			</ul>
		</td>
		<td>If you're only interested in critical bugs, or want to see which have been verified, or perhaps just those seeking feedback.</td>
	</tr>
	<tr valign="top">
		<td>Category</td>
		<td>
			Bugs are categorized although sometimes it might seem like a bug could be in
			multiple categories. You may choose a specific category or allow any, and
			also disallow certain categories. If you're unable to locate a bug, consider
			trying a <i>feature request</i> or <i>any</i> status.
		</td>
		<td>&nbsp;</td>
	</tr>
	<tr valign="top">
		<td>OS</td>
		<td>
			Bugs that may be specific to an operating system. This value is entered in by the
			reporter as the OS they used while finding the bug so this may or may not have meaning.
			Also, the value isn't regulated so for example Windows may be written as Win32, Win,
			Windows, Win98, NT, etc. Or perhaps a distribution name rather than simply Linux.
			The query uses a SQL LIKE statement like so: <i>'%$os%'</i>.
		</td>
		<td>Although not an accurate field, it may be of some use.</td>
	</tr>
	<tr valign="top">
		<td>Version</td>
		<td>
			Limit bugs to a specific version of PHP. A one character integer of 3, 4 or
			5 is standard. Entering a length greater than one will perform a SQL LIKE
			statement like so: <i>'$version%'</i>. Defaults to both 4 and 5.
		</td>
		<td>
			Limit returned bugs to a specific version of PHP. This is fairly reliable as initial
			version entries are standardized, but on occasion people are known to enter in bogus
			version information.
		</td>
	</tr>
	<tr valign="top">
		<td>Assigned</td>
		<td>
			Some bugs get assigned to PHP developers, in which case you may specify by
			entering in the PHP username of said developer.
		</td>
		<td>Example use is limiting the bugs assigned to yourself.</td>
	</tr>
	<tr valign="top">
		<td>Author email</td>
		<td>Takes on an email address of the original author of a bug.</td>
		<td>Looking for all bugs that a particular person initiated.</td>
	</tr>
	<tr valign="top">
		<td>Date</td>
		<td>
			Limit bugs that were reported by a specific time period. This is not only the
			amount of time since a comment or developer remark was last made, but this is
			the time when the bug was originally reported.
		</td>
		<td>
			Looking for recently reported bugs. For example, choosing <i>30 days ago</i>
			will limit the search to all bugs reported in the last 30 days.
		</td>
	</tr>
</table>

<h1>Bug System Statistics</h1>

<p>
You can view a variety of statistics about the bugs that have been
reported on our <a href="stats.php">bug statistics page</a>.
</p>

<?php response_footer();
