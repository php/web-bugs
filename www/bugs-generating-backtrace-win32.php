<?php

session_start();

require_once '../include/prepend.php';

// Authenticate
bugs_authenticate($user, $pw, $logged_in, $user_flags);

response_header('Generating a backtrace on Windows');

backtrace_inline_menu('Windows');
?>

<h1>Generating a backtrace, <u>with</u> a compiler, on Windows</h1>

<p>You'll need to install MS Visual Studio 2008, 2012 or later. You'll also need to</p>
<ul>
	<li>either download the debug-pack for your PHP version from <a href="https://windows.php.net/download/">windows.php.net/download</a></li>
	<li>or compile your own PHP with <code>--enable-dbg-pack</code> or <code>--enable-debug</code></li>
</ul>

<p>If you downloaded the debug-pack from the snaps site, extract it into
your PHP directory and be sure to put the PDB files that belong to the
extensions into your extension directory.</p>

<p>If you compile PHP by your own, you can also use a newer version of MSVC.</p>

<p>When PHP crashes, click <em>Cancel</em> to debug the process.  Now MSVC starts
up in <em>Debug View</em>.  If you don't already see the call stack, go into the
<em>View</em> menu and choose <em>Debug Windows</em> &rarr; <em>Call Stack</em>.</p>

<p>You'll now see something similar to the following lines, this is the backtrace:</p>
<pre><code>
_efree(void * 0x00000000) line 286 + 3 bytes
zif_http_test(int 0, _zval_struct * 0x007bc3b0, _zval_struct * * 0x00000000, _zval_struct * 0x00000000, int 0, void * * * 0x00792cd0) line 1685 + 8 bytes
zend_do_fcall_common_helper_SPEC(_zend_execute_data * 0x0012fd6c, void * * * 0x00792cd0) line 188 + 95 bytes
ZEND_DO_FCALL_SPEC_CONST_HANDLER(_zend_execute_data * 0x0012fd6c, void * * * 0x00792cd0) line 1578 + 13 bytes
execute(_zend_op_array * 0x007bc880, void * * * 0x00792cd0) line 88 + 13 bytes
zend_eval_string(char * 0x00793bce, _zval_struct * 0x00000000, char * 0x00404588 tring', void * * * 0x00792cd0) line 1056 + 14 bytes
zend_eval_string_ex(char * 0x00793bce, _zval_struct * 0x00000000, char * 0x00404588 tring', int 1, void * * * 0x00792cd0) line 1090 + 21 bytes
main(int 3, char * * 0x00793ba8) line 1078 + 23 bytes
PHP! mainCRTStartup + 227 bytes
KERNEL32! 77e81af6()
</code></pre>

<!--
	Everything below is stolen from Pierre Joye
	https://blog.thepimp.net/index.php/post/2007/06/10/debug-pack-or-how-to-generate-backtrack-on-windows-without-compiling
-->
<h1>Generating backtrace, <u>without</u> compiler, on Windows</h1>
<p>You'll need:</p>
<ul>
<li>A PHP <a href="https://windows.php.net/downloads/snaps/">snapshot</a> or <a href="https://windows.php.net/download/">stable</a> release</li>
<li>PHP Debug pack (<a href="https://windows.php.net/downloads/snaps/">snapshot</a> or <a href="https://windows.php.net/download/">stable</a>)</li>
<li>Microsoft <a href="https://www.microsoft.com/en-us/download/details.aspx?id=49924">Debug Diagnostic Tools</a></li>
<li>Evil script to crash PHP</li>
</ul>
<p>For the sake of this example, we will simply use PHP in the shell.
The same method can be used for IIS or any other process or services.</p>


<p>Once you have installed the Debug diagnostic tools and uncompressed
PHP and its debug pack (they can be kept in two separate folders), the
first step is to configure the diagnostic tools. Select the
tools menu and click on "Options and settings". The first tab contains
the path to the symbols files, add the "debug folder" to the existing
list using the "browse" button:</p>
<p><img src="/images/backtrace-images-win32/dbg_options.png" alt="Options"></p>

<p>Now we are ready to generate our backtrace.</p>
<p>We will use the wizard, click the "Add a rule" button and choose "Crash" as the rule type:</p>
<p><img src="/images/backtrace-images-win32/dbg_wizard_1.png" alt="Wizard #1"></p>

<p>In the next window, select "a specific process":</p>
<p><img src="/images/backtrace-images-win32/dbg_wizard_2.png" alt="Wizard #2"></p>

<p>Add a "sleep(10);" for the first run (from the cmd: "php.exe
crashme.php"), it will let you enough time to click "next" and select
the php process. I you are debugging the Apache module, start Apache with -X option and choose
httpd.exe instead of php.exe from the process list.
Then proceed further:</p>
<p><img src="/images/backtrace-images-win32/dbg_select_php.png" alt="Select the php process"></p>

<p>Click again next and let it crash. If everything went well, you should see your new rule as shown in the image below:</p>
<p><img src="/images/backtrace-images-win32/rules.jpg" alt="rules list"></p>

<p>It also detected that "php.exe" was used. A rule has been created
for all instance of "php.exe". It will save you the sleep and process
selection.</p>
<p>Now you can click the "Analyze data" button:</p>
<p><img src="/images/backtrace-images-win32/analyze.jpg" alt="Analyze"></p>

<p>Et voila, the complete report will show up in your internet explorer (compressed html):</p>
<p><img src="/images/backtrace-images-win32/backtrace.jpg" alt="Debug report backtrace screenshot"></a></p>

<p>What we need is the backtrace itself which can be found under "Thread X - System ID XXX".</p>

<?php response_footer();
