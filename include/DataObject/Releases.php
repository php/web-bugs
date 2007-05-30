<?php
/**
 * Table Definition for releases
 */
require_once 'DB/DataObject.php';

class Bugs_DBDataObject_Releases extends DB_DataObject 
{
    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    var $__table = 'releases';                        // table name
    var $id;                              // int(11)  not_null primary_key
    var $package;                         // string(80)  not_null
    var $version;                         // string(20)  not_null
    var $state;                           // string(8)  not_null enum
    var $doneby;                          // string(20)  not_null
    var $license;                         // string(20)  not_null
    var $summary;                         // blob(65535)  not_null blob
    var $description;                     // blob(65535)  not_null blob
    var $releasedate;                     // datetime(19)  not_null binary
    var $releasenotes;                    // blob(65535)  not_null blob
    var $packagefile;                     // blob(65535)  not_null blob

    /* Static get */
    function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('Bugs_DBDataObject_Releases',$k,$v); }

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE
}
?>