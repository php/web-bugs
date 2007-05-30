<?php
/**
 * Table Definition for packages
 */
require_once 'DB/DataObject.php';

class Chiara_Bugs_DBDataObject_Categories extends DB_DataObject 
{
    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    var $__table = 'categories';                      // table name
    var $id;                              // int(6)  not_null primary_key
    var $name;                            // string(255)  not_null
    var $description;                     // blob(65535)  not_null blob
    var $alias;                           // string(50)  

    /* Static get */
    function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('Chiara_Bugs_DBDataObject_Categories',$k,$v); }

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE
}
?>