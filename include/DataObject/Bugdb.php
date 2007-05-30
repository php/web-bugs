<?php
/**
 * Table Definition for packages
 */
require_once 'DB/DataObject.php';

class Bugs_DBDataObject_Bugdb extends DB_DataObject 
{
    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    var $__table = 'bugdb';                // table name
    var $id;                              // int(8)  not_null primary_key
    var $package_name;                    // string(80)  not_null
    var $bug_type;                        // string(32)  not_null
    var $email;                           // string(40)  not_null
    var $handle;                          // string(20)  not_null
    var $sdesc;                           // string(80)  not_null
    var $ldesc;                           // blob(65535)  not_null blob
    var $package_version;                 // string(100)
    var $php_version;                     // string(100)
    var $php_os;                          // string(32)
    var $status;                          // string(16)
    var $ts1;                             // datetime(19)
    var $ts2;                             // datetime(19)
    var $assign;                          // string(16)
    var $passwd;                          // string(20)
    var $reporter_name;                   // string(80)

    /* Static get */
    function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('Bugs_DBDataObject_Bugdb',$k,$v); }

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE
}
?>