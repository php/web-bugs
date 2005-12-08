<?php
require_once 'prepend.inc';
commonHeader("Generating a backtrace on Win32");
?>

<h1>Generating a backtrace on Win32</h1>

<p>You'll need to install MSVC6 and you'll also need to</p>
<ul>
	<li>either download the debug-pack for your PHP version from <a href="http://snaps.php.net">http://snaps.php.net</a></li>
	<li>or compile your own PHP with <code>--enable-dbg-pack</code> or <code>--enable-debug</code></li>
</ul>

<p>If you downloaded the debug-pack from the snaps site, extract it into
your PHP directory and be sure to put the PDB files that belong to the
extensions into your extension directory.</p>

<p>If you compile PHP by your own, you can also use a newer version of MSVS.</p>

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

<?php commonFooter(); ?>
	