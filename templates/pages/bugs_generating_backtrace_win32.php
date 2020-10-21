<?php $this->extends('layout.php', ['title' => 'Generating a backtrace on Windows']) ?>

<?php $this->start('content') ?>

<p>
<a href="/bugs-generating-backtrace.php">Unix</a> | <strong>Windows</strong>
</p>

<h1>Generating a backtrace, <u>with</u> a compiler, on Windows</h1>

<p>You'll need to install MS Visual Studio 2008, 2012 or later. You'll also need to</p>
<ul>
    <li>either download the debug-pack for your PHP version from <a href="https://windows.php.net/download/">windows.php.net/download</a></li>
    <li>or compile your own PHP with <code>--enable-dbg-pack</code> or <code>--enable-debug</code></li>
</ul>

<p>If you downloaded the debug-pack from the snaps site, extract it into your
PHP directory and be sure to put the PDB files that belong to the extensions
into your extension directory.</p>

<p>If you compile PHP by your own, you can also use a newer version of MSVC.</p>

<p>When PHP crashes, click <em>Cancel</em> to debug the process. Now MSVC starts
up in <em>Debug View</em>. If you don't already see the call stack, go into the
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

<h1>Generating backtrace, <u>without</u> compiler, on Windows</h1>

<p>Generating a backtrace without compiler is usually a two step process:</p>
<ul>
<li>Generate a crash dump file (.dmp)</li>
<li>Analyze the crash dump file to get a stack backtrace</li>
</ul>
<p>There are several solutions for either step; in the following we describe one solution each.</p>

<h2>Use ProcDump to generate crash dump files</h2>

<p>Download <a href="https://docs.microsoft.com/en-us/sysinternals/downloads/procdump">ProcDump</a>,
and register it as the Just-in-Time (AeDebug) debugger:</p>
<pre><code>procdump -ma -i C:\dumps</code></pre>
<p>Use any suitable folder to store the crash dump files instead of <code>C:\dumps</code>.
Whenever a process crashes, a crash dump file will be written to the specified folder.</p>

<h2>Use Debug Diagnostic Tool to analyze the crash dump</h2>

<ul>
<li>Download and install <a
href="https://www.microsoft.com/en-us/download/details.aspx?id=58210">Debug Diagnostic Tool</a></li>
<li><a href="https://windows.php.net/downloads/">Download</a> and unpack the PHP debug pack corresponding to the PHP version you are running</li>
<li>Start the Debug Diagnostic Tool</li>
<li>Add the path to the unpacked PHP debug pack as symbol search path in the settings</li>
<li>Press "add data file" and select the crash dump file formerly generated</li>
<li>Select "default analysis"</li>
<li>Press "start analysis"</li>
</ul>
<p>After the analysis has finished, the DebugDiag Analysis Report opens in Internet
Explorer; the relevant part of that report is the stack backtrace, which looks similar
to the following:</p>
<p><img src="/images/backtrace-images-win32/backtrace.jpg" alt="Debug report backtrace screenshot"></a></p>

<?php $this->end('content') ?>
