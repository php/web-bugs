<?php

class Bug_DataObject
{
    function init()
    {
    	global $ROOT_DIR;

        require_once 'DB/DataObject.php';

        $options = &PEAR::getStaticProperty('DB_DataObject','options');
        $type = extension_loaded('mysqli') ? 'mysqli' : 'mysql';
        $options = array(
            'database'         => PEAR_DATABASE_DSN,
            'schema_location'  => "{$ROOT_DIR}/include/DataObject/schema",
            'class_location'   => "{$ROOT_DIR}/include/DataObject",
            'require_prefix'   => 'DataObject/',
            'class_prefix'     => 'Bugs_DBDataObject_',
        );
    }

    function bugDB($table)
    {
        $a = DB_DataObject::factory($table);
        if (!is_a($a, 'DB_DataObject')) {
            PEAR::raiseError('unknown table "' . $table . '"');
        }
        $a->database('pear');
        return $a;
    }

    function pearDB($table)
    {
        $a = DB_DataObject::factory($table);
        if (!is_a($a, 'DB_DataObject')) {
            PEAR::raiseError('unknown table "' . $table . '"');
        }
        $a->database('pear');
        return $a;
    }

    function getChannel()
    {
        return PEAR_CHANNELNAME;
    }

    /**
     * Some channels have subdirectories, so return the host portion only
     *
     * @return string
     */
    function getHost()
    {
        $a = parse_url('http://' . PEAR_CHANNELNAME);
        return $a['host'];
    }

    /**
     * Return the host in a format that can be used in a regular expression
     *
     * @return string
     */
    function getPregHost()
    {
        return str_replace(array('.'),
            array('\\.'),
            Bug_DataObject::getHost());
    }

    function isDeveloper($email)
    {
        $db = Bug_DataObject::pearDB('users');
        $db->email = $email;
        return $db->find();
    }

    function getPath($uri, $bugs = false)
    {
        if ($bugs) {
            $uri = '/bugs/' . $uri;
        }
        return str_replace(Bug_DataObject::getHost(), '', PEAR_CHANNELNAME) . '/' . $uri;
    }

    /**
     * Creates a link to the bug system
     */
    function link($package, $type = 'list', $linktext = '')
    {
        switch ($type) {
            case 'bugurl':
                return '/bugs/' . $package;
            case 'bugsearchurl':
                return '/bugs/search.php';
            case 'url':
                return '/' . $package;
            case 'normal':
                return '<a href="/' .
                        $package . "\">$linktext</a>";
            case 'listurl':
                return '/bugs/search.php?' .
                       'cmd=display&amp;status=Open&amp;package[]=' .
                        urlencode($package);
            case 'roadmapurl':
                return '/bugs/roadmap.php?' .
                       'package=' .
                        urlencode($package);
            case 'list':
                if (!$linktext) {
                    $linktext = 'Package Bugs';
                }
                return '<a href="/bugs/search.php?' .
                       'cmd=display&amp;status=Open&amp;package[]=' .
                        urlencode($package) . "\">$linktext</a>";
            case 'report':
                if (!$linktext) {
                    $linktext = 'Report a new bug';
                }
                return '<a href="/bugs/report.php?' .
                       'package=' .
                        urlencode($package) . "\">$linktext</a>";
            case 'reporturl':
                return '/bugs/report.php?' .
                       'package=' .
                        urlencode($package);
            case 'packages':
                if (!$linktext) {
                    $linktext = 'Browse Packages';
                }
                return '<a href="/packages.php">' .
                    $linktext . '</a>';
            case 'search':
                if (!$linktext) {
                    $linktext = 'Search Packages';
                }
                return '<a href="/search.php">' .
                    $linktext . '</a>';
            case 'searchurl':
                return '/search.php';
            case 'home':
                return '/' . urlencode($package);
        }
    }

    function template()
    {
        return 'bugs'; // this will be more customizable very shortly
    }

}
