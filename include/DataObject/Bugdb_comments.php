<?php
/**
 * Table Definition for packages
 */
require_once 'DB/DataObject.php';

class Bugs_DBDataObject_Bugdb_Comments extends DB_DataObject 
{
    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    var $__table = 'bugdb_comments';        // table name
    var $id;                              // int(8)  not_null primary_key
    var $bug;                             // int(8)  not_null
    var $email;                           // string(40)  not_null
    var $handle;                          // string(20)  not_null
    var $ts;                              // datetime(19)
    var $comment;                         // blob(65535)  not_null blob

    /* Static get */
    function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('Bugs_DBDataObject_Bugdb_Comments',$k,$v); }

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE
}
?>