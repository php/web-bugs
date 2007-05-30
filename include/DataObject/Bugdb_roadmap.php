<?php
/**
 * Table Definition for packages
 */
require_once 'DB/DataObject.php';

class Bugs_DBDataObject_Bugdb_roadmap extends DB_DataObject 
{
    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    var $__table = 'bugdb_roadmap';  // table name
    var $id;                              // int(8)  not_null primary_key
    var $package;                         // string(80)  not_null
    var $roadmap_version;                 // string(100)
    var $releasedate;                     // datetime(19)  not_null binary
    var $description;                     // blob(65535)  not_null blob

    /* Static get */
    function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('Bugs_DBDataObject_Bugdb_roadmap',$k,$v); }

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE
}
?>