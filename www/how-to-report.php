<?php

session_start();

require_once '../include/prepend.php';

// Authenticate
bugs_authenticate($user, $pw, $logged_in, $user_flags);

response_header('How to Report a Bug');
?>

<h1>How to Report a Bug</h1>

<p>There is a large number of PHP users. There is a much smaller number of people
who actually develop the PHP language and extensions. There is an even smaller
number of people who actively fix bugs reported by users.</p>

<p>What does this mean for you, an aspiring bug reporter? In order to catch the
eye of one of these few stalwart <b>volunteers</b>, you'll need to take to
heart a few tips on how to report a bug so that they can and will help you.</p>

<p>Take special note of that word in bold above. The people who are going to
help you with a bug you report are <b>volunteers</b>. Not only are you not
paying them to help you, but nobody else is either. So, to paraphrase the
immortal words of <a href="https://www.imdb.com/title/tt0096928/">Bill and Ted</a>,
<b>"be excellent to them"</b>.</p>

<p>Beyond that golden rule, what follows are some additional tips on ways to
make your bug report better so that someone will be able to help you.</p>

<h2>The basics: what you did, what you wanted to happen, and what actually
happened.</h2>

<p>Those are the three basic elements of a bug report. You need to tell us
exactly what you did (for example, "My script calls
make_happy_meal('hamburger','onion rings')") , what you expected to have happen
(to continue the example, "I expected PHP to serve me a happy meal with a
hamburger and onion rings"), and what actually happened ("It gave me a happy
meal with french fries.").</p>

<p>Yes, the example is silly. But if your bug report simply said "The
make_happy_meal function doesn't work," we wouldn't be able to say "That's
because you can't have onion rings in a happy meal, you can only have french
fries or curly fries." By telling us what you asked for, what you expected to
get, and what you actually got, we don't have to guess.</p>

<h2>Always search the bug database first.</h2>

<p>Advice is so good, we'll repeat it twice. Always <a
href="search.php">search</a> the bug database first. As we said above, there's
a lot of users of PHP. The odds are good that if you've found a problem,
someone else has found it, too. If you spend a few minutes of your time making
sure that you're not filing a duplicate bug, that's a few more minutes someone
can spend helping to fix that bug rather than sorting out duplicate bug
reports.</p>

<h2>If you don't understand an error message, ask for help.</h2>

<p>Don't report an error message you don't understand as a bug. There are <a
href="https://php.net/support.php">a lot of places you can ask for help</a>
in understanding what is going on before you can claim that an error message
you do not understand is a bug.</p>

<p>(Now, once you've understood the error message, and have a good suggestion
for a way to make the error message more clear, you might consider reporting it
as a feature request.)</p>

<h2>Be brief, but don't leave any important details out.</h2>

<p>This is a fine line to walk. But there are some general guidelines:</p>
<ul>
	<li>
		Remember the three basics: what you did, what you expected to happen,
		and what happened.
	</li>
	<li>
		When you provide code that demonstrates the problem, it should almost
		never be more than ten lines long. Anything longer probably contains a
		lot of code that has nothing to do with the problem, which just increases
		the time to figure out the real problem. (But don't forget to make
		sure that your code still demonstrates the bug you're reporting and
		doesn't have some other problem because you've accidentally trimmed out
		something you thought wasn't important but was!)
	</li>
	<li>
		If PHP is crashing, include a backtrace. Instructions for doing this
		can be found <a href="bugs-generating-backtrace.php">here for *NIX users</a> and
		<a href="bugs-generating-backtrace-win32.php">here for Windows users</a>.
	</li>
	<li>
		<a href="http://valgrind.org">Valgrind</a> log can be also very useful.
		See <a href="bugs-getting-valgrind-log.php">instructions how to generate it</a>.
	</li>
</ul>

<h2>Use English.</h2>

<p>Yes, the PHP user and developer communities are global and include a great
many people who can speak a great many languages. But if you were to report a
bug in a language other than English, many (if not most) of the people who
would otherwise help you won't be able to. If you're worried about your English
skills making it difficult to describe the bug, you might try asking for help
on one of the <a href="https://php.net/support.php#local">non-English
mailing lists</a>.</p>

<h2>Don't report bugs about old versions.</h2>

<p>Every time a new version of PHP is released, dozens of bugs are fixed.  If
you're using a version of PHP that is more than two revisions older than the
latest version, you should upgrade to the latest version to make sure the bug
you are experiencing still exists.</p>

<p>Note that PHP branches which are no longer <a
href="https://php.net/supported-versions.php">actively supported</a> will
receive fixes for critical security issues only. So please do not report
non-security related bugs which do not affect any actively supported PHP
branch.</p>

<h2>Only report one problem in each bug report.</h2>

<p>If you have encountered two bugs that don't appear to be related, create a
new bug report for each one. This makes it easier for different people to help
with the different bugs.</p>

<h2>Check out these other resources.</h2>

<ul>
	<li>
		Eric Raymond's and Rick Moen's
		<a href="http://www.catb.org/~esr/faqs/smart-questions.html">How
		To Ask Questions The Smart Way</a>
	</li>
	<li>
		mozilla.org's
		<a href="https://developer.mozilla.org/en-US/docs/Mozilla/QA/Bug_writing_guidelines">bug
		writing guidelines</a>
	</li>
	<li>
		Simon Tatham's <a href="https://www.chiark.greenend.org.uk/~sgtatham/bugs.html">How
		to Report Bugs Effectively</a>
	</li>
</ul>

<?php response_footer();
