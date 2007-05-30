<?php
/**
 * Table Definition for maintainer handles
 */
require_once 'DB/DataObject.php';

class Bugs_DBDataObject_Users extends DB_DataObject 
{
    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    var $__table = 'users';                         // table name
    var $handle;                          // string(20)  not_null primary_key
    var $password;                        // string(50)
    var $name;                            // string(100)
    var $email;                           // string(100)
    var $homepage;                        // string(255)
    var $created;                         // datetime
    var $created_by;                      // string(20)
    var $lastlogin;                       // datetime
    var $showemail;                       // int(4)
    var $registered;                      // int(4)
    var $admin;                           // int(11)
    var $userinfo;                        // text
    var $pgpkeyid;                        // string(20)
    var $pgpkey;                          // text
    var $wishlist;                        // string(255)

    /* Static get */
    function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('Bugs_DBDataObject_Handles',$k,$v); }

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE
}
?>