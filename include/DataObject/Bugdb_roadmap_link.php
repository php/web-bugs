<?php
/**
 * Table Definition for packages
 */
require_once 'DB/DataObject.php';

class Bugs_DBDataObject_Bugdb_roadmap_link extends DB_DataObject 
{
    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    var $__table = 'bugdb_roadmap_link';  // table name
    var $id;                              // int(8)  not_null primary_key
    var $roadmap_id;                      // int(8)  not_null primary_key

    /* Static get */
    function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('Bugs_DBDataObject_Bugdb_roadmap_link',$k,$v); }

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE
}
?>