<?php

require_once '../include/prepend.php';

response_header('Report - New');
?>

<p style="background-color: #faa;">
    <strong>This bug tracker no longer accepts new issues. Instead use one of the following:</strong>
    <ul>
        <li>Implementation issues: <a href="https://github.com/php/php-src/issues">php/php-src repository</a></li>
        <li>Documentation issues: <a href="https://github.com/php/doc-en/issues">php/doc-en repository</a></li>
        <li>PECL extension issues: Find the correct extension-specific bug tracker at <a href="https://pecl.php.net/">pecl.php.net</a></li>
        <li>PEAR issues: <a href="https://pear.php.net/bugs/">pear.php.net/bugs</a></li>
        <li>Security issues: <a href="https://github.com/php/php-src/security/advisories/new">php/php-src security advisory</a>, or email <?php echo make_mailto_link("{$site_data['security_email']}?subject=%5BSECURITY%5D+possible+new+bug%21", $site_data['security_email']); ?></li>
    </ul>
</p>

<?php

response_footer();
