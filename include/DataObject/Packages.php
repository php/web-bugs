<?php
/**
 * Table Definition for packages
 */
require_once 'DB/DataObject.php';

class Bugs_DBDataObject_Packages extends DB_DataObject 
{
    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    var $__table = 'packages';                        // table name
    var $id;                              // int(11)  not_null primary_key
    var $name;                            // string(80)  not_null
    var $category;                        // int(6)  not_null
    var $stablerelease;                   // string(20)  not_null
    var $develrelease;                    // string(20)  not_null
    var $license;                         // string(50)  not_null
    var $summary;                         // blob(65535)  not_null blob
    var $description;                     // blob(65535)  not_null blob
    var $homepage;                        // string(255)
    var $package_type;                    // string(4)  not_null
    var $doc_link;                        // string(255)  not_null
    var $cvs_link;                        // string(255)  not_null
    var $approved;                        // int(4)  not_null
    var $wiki_area;                       // int(1)  not_null
    var $unmaintained;                    // int(1)  not_null
    var $newpk_id;                        // int(11)
    var $blocktrackbacks;                 // int(4)  not_null
    var $newpackagename;                  // string(100)
    var $newchannel;                      // string(255)

    /* Static get */
    function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('Bugs_DBDataObject_Packages',$k,$v); }

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE

    function hasReleases()
    {
        $rel = &DB_DataObject::factory('releases');
        $rel->channel = $this->channel;
        $rel->package = $this->package;
        return $rel->find();
    }
}
?>