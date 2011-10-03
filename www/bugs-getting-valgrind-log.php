<?php
require_once '../include/prepend.php';
response_header('Generating a valgrind log');
?>

<h1>Generating a valgrind log</h1>

<h3>Important!</h3>
To get a meaningful log you must have
PHP configured with <code>--enable-debug</code> 
and disable Zend memory manager.

<h3>Disabling Zend MM</h3>

<p>
 Zend Engine uses its own routines to optimize memory management, 
 but because of this valgrind cannot see most of the memory issues.
 You must disable Zend memory manager before running PHP with valgrind.
 In order to do this you need to set USE_ZEND_ALLOC environment
 variable to 0. 
</p>
<p>
 Use
 <pre><code>export USE_ZEND_ALLOC=0</code></pre> or 
 <pre><code>setenv USE_ZEND_ALLOC 0</code></pre> (the syntax depends on 
 what your shell supports).
</p>
<p>
 This works since PHP 5.2, in older versions you had to reconfigure PHP with
 <code>--disable-zend-memory-manager</code> option.
</p>


<h3>Running PHP CLI or PHP CGI through valgrind</h3>

<p>
 To generate the valgrind log using PHP CLI/CGI, 
 you need to execute the following command: 
</p>

<pre>
 <code>
 valgrind --tool=memcheck --num-callers=30 --log-file=php.log /path/to/php-cli script.php
 </code>
</pre>

<p>
 This should put the log into php.log file in the current working directory.
</p>

<h3>Running PHP Apache module through valgrind</h3>

<p>
 If you compiled PHP and Apache statically, make sure the Apache binary 
 is not stripped after <code>make install</code>, otherwise you lose 
 the required debug info. To check it, run <code>file /path/to/httpd</code>, 
 it should output something like this (notice &quot;not stripped&quot;):
</p>
<pre>
 <code>
 # file /usr/local/apache2/bin/httpd
 /usr/local/apache2/bin/httpd: ELF 64-bit LSB executable, x86-64, version 1 (SYSV), for GNU/Linux 2.6.4, dynamically linked (uses shared libs), not stripped
 </code>
</pre>

<p>
 To generate the valgrind log using PHP as Apache module, 
 you need to run the Apache itself under valgrind: 
</p>

<pre>
 <code>
 valgrind --tool=memcheck --num-callers=30 --log-file=apache.log /usr/local/apache/bin/httpd -X
 </code>
</pre>

<p>
 This should put all the memory errors into into apache.log file.
</p>

<?php response_footer();
