<?php
/**
 * Table Definition for maintainers
 */
require_once 'DB/DataObject.php';

class Bugs_DBDataObject_Maintains extends DB_DataObject 
{
    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    var $__table = 'maintains';                     // table name
    var $handle;                          // string(20)  not_null primary_key
    var $package;                         // int(11)  not_null primary_key
    var $role;                            // string(30)  not_null
    var $active;                          // int(4)  not_null

    /* Static get */
    function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('Bugs_DBDataObject_Maintainers',$k,$v); }

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE
}
?>