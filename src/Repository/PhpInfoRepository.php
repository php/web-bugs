<?php

namespace App\Repository;

/**
 * Repository class for fetching information about PHP's configuration
 */
class PhpInfoRepository
{
    public function getInfo(): string
    {
        ob_start();
        phpinfo();

        $phpInfo = $this->replaceSensitiveInformation(ob_get_clean());
        $phpInfo = $this->cleanHtml($phpInfo);

        return $phpInfo;
    }

    private function replaceSensitiveInformation(string $phpInfo): string
    {
        return str_replace([
            getenv('AUTH_TOKEN'),
            getenv('USER_TOKEN'),
            getenv('USER_PWD_SALT'),
        ], '&lt;hidden&gt;', $phpInfo);
    }

    /**
     * This method unwrap the information from the body tag, removes zend and php logos and fixes sizes
     * for presentation purposes
     *
     * We might want to do it proper at some point using DOM methods
     */
    private function cleanHtml(string $phpInfo): string
    {
        preg_match('!<body.*?>(.*)</body>!ims', $phpInfo, $m);

        $m[1] = preg_replace('!<a href="http://www.php.net/"><img.*?></a>!ims', '', $m[1]);
        $m[1] = preg_replace('!<a href="http://www.zend.com/"><img.*?></a>!ims', '', $m[1]);
        $m[1] = str_replace(' width="600"', ' width="80%"', $m[1]);

        return $m[1];
    }
}
