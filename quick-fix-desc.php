<?php
require_once 'prepend.inc';
commonHeader("Quick Fix Descriptions");
?>
<table border="2" cellpadding="6">
    <tr>
        <td>Fixed in CVS</td>
        <td>Status: Closed</td>  
        <td>
            This bug has been fixed in CVS. You can grab a snapshot of the
            CVS version at http://snaps.php.net/. In case this was a documentation 
            problem, the fix will show up soon at http://www.php.net/manual/.
            In case this was a PHP.net website problem, the change will show
            up on the PHP.net site and on the mirror sites.
            Thank you for the report, and for helping us make PHP better.
        </td>    
    </tr>
    <tr>
        <td>Fixed in release</td>  
        <td>Status: Closed</td>         
        <td>
            Thank you for your bug report. This issue has already been fixed
            in the latest released version of PHP, which you can download at 
            http://www.php.net/downloads.php
        </td>    
    </tr>    
    <tr>
        <td>Need backtrace</td>  
        <td>Status: Feedback</td>         
        <td>
            Thank you for this bug report. To properly diagnose the problem, we
            need a backtrace to see what is happening behind the scenes. To
            find out how to generate a backtrace, please read
            http://bugs.php.net/bugs-generating-backtrace.php

            Once you have generated a backtrace, please submit it to this bug
            report and change the status back to "Open". Thank you for helping
            us make PHP better.
        </td>    
    </tr> 
    <tr>
        <td>Try newer version</td>  
        <td>Status: Bogus</td>         
        <td>
            Thank you for taking the time to report a problem with PHP.
            Unfortunately you are not using a current version of PHP -- 
            the problem might already be fixed. Please download a new
            PHP version from http://www.php.net/downloads.php

            If you are able to reproduce the bug with one of the latest
            versions of PHP, please change the PHP version on this bug report
            to the version you tested and change the status back to "Open".
            Again, thank you for your continued support of PHP.
        </td>    
    </tr> 
    <tr>
        <td>Not developer issue</td>  
        <td>Status: Bogus</td>         
        <td>
            Sorry, but the bug system is not the appropriate forum for asking
            support questions. Your problem does not imply a bug in PHP itself.
            For a list of more appropriate places to ask for help using PHP,
            please visit http://www.php.net/support.php

            Thank you for your interest in PHP.
        </td>    
    </tr> 
    <tr>
        <td>No feedback</td>  
        <td>Status: No feedback</td>         
        <td>
            No feedback was provided. The bug is being suspended because
            we assume that you are no longer experiencing the problem.
            If this is not the case and you are able to provide the
            information that was requested earlier, please do so and
            change the status of the bug back to "Open". Thank you.
        </td>    
    </tr> 
    <tr>
        <td>Expected behavior</td>  
        <td>Status: Bogus</td>         
        <td>
            Thank you for taking the time to write to us, but this is not
            a bug. Please double-check the documentation available at
            http://www.php.net/manual/ and the instructions on how to report
            a bug at http://bugs.php.net/how-to-report.php
        </td>    
    </tr> 
    <tr>
        <td>Not enough info</td>  
        <td>Status: Bogus</td>         
        <td>
            Not enough information was provided for us to be able
            to handle this bug. Please re-read the instructions at
            http://bugs.php.net/how-to-report.php

            If you can provide more information, feel free to add it
            to this bug and change the status back to "Open".

            Thank you for your interest in PHP.
        </td>    
    </tr> 
    <tr>
        <td>Submitted twice</td>  
        <td>Status: Bogus</td>         
        <td>
            Please do not submit the same bug more than once. An existing
            bug report already describes this very problem. Even if you feel
            that your issue is somewhat different, the resolution is likely
            to be the same. Because of this, we hope you add your comments
            to the original bug instead.

            Thank you for your interest in PHP.
        </td>    
    </tr> 
    <tr>
        <td>register_globals</td>  
        <td>Status: Bogus</td>         
        <td>
            In PHP 4.2.0, the 'register_globals' setting default changed to
            be off. See http://www.php.net/release_4_2_0.php for more info.
            We are sorry about the inconvenience, but this change was a necessary
            part of our efforts to make PHP scripting more secure and portable.
        </td>    
    </tr>                                               
    <tr>
        <td>php3</td>  
        <td>Status: Bogus</td>         
        <td>
            We are sorry, but can not support PHP 3 related problems anymore.
            Momentum is gathering for PHP 5, and we think supporting PHP 3 will
            lead to a waste of resources which we want to put into getting PHP 5
            ready. Ofcourse PHP 4 will will continue to be supported for the
            forseeable future.
        </td>    
    </tr>                                               
    <tr>
        <td>dst</td>  
        <td>Status: Bogus</td>         
        <td>
            We are happy to tell you that you just discovered Daylight Savings
            Time. For more information see:
            http://webexhibits.org/daylightsaving/b.html
        </td>    
    </tr>                                               
</table>    
<?php 
commonFooter();
?>
