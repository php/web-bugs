<?php

require_once '../include/prepend.inc';

setcookie("MAGIC_COOKIE","",time()-3600,'/','.php.net');
setcookie("MAGIC_COOKIE","",time()-3600,'/');

response_header('Logout');
?>
<p>You've been logged out.</p>
<?php
response_footer();
