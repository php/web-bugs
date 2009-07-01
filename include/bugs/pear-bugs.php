<?php
/**
 * Bug statistics
 * @package pearweb
 */
class PEAR_Bugs
{
    var $_dbh;

    function __construct()
    {
        $this->_dbh = $GLOBALS['dbh'];
    }

    function packageBugStats($packageid)
    {
        $info = $this->_dbh->prepare('
            SELECT
                COUNT(bugdb.id) as count,
                AVG(TO_DAYS(NOW()) - TO_DAYS(ts1)) as average,
                MAX(TO_DAYS(NOW()) - TO_DAYS(ts1)) as oldest
            FROM bugdb, packages
            WHERE
                name=? AND
                bugdb.package_name = packages.name AND
                status IN ("Open","Feedback","Assigned","Analyzed","Verified","Critical") AND
                bug_type IN ("Bug","Documentation Problem") AND
                bugdb.registered = 1
            ')->execute(array($packageid))->fetchAll(MDB2_FETCHMODE_ASSOC);
        $total = $this->_dbh->prepare('
            SELECT COUNT(bugdb.id) FROM bugdb WHERE bugdb.package_name=? AND bugdb.registered = 1
            ')->execute(array($packageid))->fetchOne();
        return array_merge($info[0], array('total' => $total));
    }

    function bugRank()
    {
        $info = $this->_dbh->prepare('
            SELECT
                name,
                AVG(TO_DAYS(NOW()) - TO_DAYS(ts1)) as average
            FROM bugdb, packages
            WHERE
                bugdb.package_name = packages.name AND
                status IN ("Open","Feedback","Assigned","Analyzed","Verified","Critical") AND
                bug_type IN ("Bug","Documentation Problem") AND
                package_type="pear"
            GROUP BY package_name
            ORDER BY average ASC
        ')->execute(array())->fetchAll(MDB2_FETCHMODE_ASSOC);
        return $info;
    }

    function developerBugStats($handle)
    {
        $allbugs = $this->_dbh->getAssoc('SELECT b.status, COUNT(b.status) as c
             FROM bugdb b, maintains m, packages p
             WHERE
              m.handle = ? AND
              p.id = m.package AND
              b.package_name = p.name AND
              b.bug_type \!= "Feature/Change Request"
             GROUP BY b.status;', false, array($handle));
        $total = 0;
        foreach ($allbugs as $buginfo)
        {
            $total += $buginfo;
        }
        $assigned = $this->_dbh->prepare('SELECT COUNT(b.status)
             FROM bugdb b, maintains m, packages p
             WHERE
              m.handle = ? AND
              p.id = m.package AND
              b.package_name = p.name AND
              b.bug_type \!= "Feature/Change Request" AND
              b.assign = ?')->execute(array($handle, $handle))->fetchOne();
        $openage = $this->_dbh->prepare('SELECT ROUND(AVG(TO_DAYS(NOW()) - TO_DAYS(b.ts1)))
             FROM bugdb b, maintains m, packages p
             WHERE
              m.handle = ? AND
              p.id = m.package AND
              b.package_name = p.name AND
              b.bug_type \!= "Feature/Change Request" AND
              b.status IN ("Assigned", "Analyzed", "Feedback", "Open", "Critical", "Verified") AND
              (b.assign = ? OR b.assign IS NULL OR b.assign="")')->execute(array($handle, $handle))->fetchOne();
        $opened = $this->_dbh->prepare('SELECT COUNT(*) FROM bugdb WHERE
            handle=?')->execute(array($handle))->fetchOne();
        $commented = $this->_dbh->prepare('SELECT COUNT(*) FROM bugdb_comments WHERE
            handle=?')->execute(array($handle))->fetchOne();
        $opencount = $this->_dbh->prepare('SELECT COUNT(*)
             FROM bugdb b, maintains m, packages p
             WHERE
              m.handle = ? AND
              p.id = m.package AND
              b.package_name = p.name AND
              b.bug_type \!= "Feature/Change Request" AND
              b.status IN ("Assigned", "Analyzed", "Feedback", "Open", "Critical", "Verified") AND
              (b.assign = ? OR b.assign IS NULL OR b.assign="")')->execute( array($handle, $handle))->fetchOne();
        // Fetch all assigned and open reports regardless of package
        $open_c_assigned = $this->_dbh->prepare('SELECT COUNT(*) as c
                 FROM bugdb b, maintains m, packages p
                 WHERE
                  b.assign = ? AND
                  b.bug_type != "Feature/Change Request" AND
                  b.status IN ("Assigned", "Analyzed", "Feedback", "Open", "Critical", "Verified") AND
                  p.package_name = p.name AND
                  p.id = m.package AND
                  m.handle = ?
                 GROUP BY b.id 
                 ORDER BY c DESC, b.ts2 DESC')->execute(array($handle, $handle))->fetchOne();
        $opencount = $opencount + $open_c_assigned;
        $bugrank = $this->_dbh->prepare('SELECT COUNT(*) as c, u.handle
                 FROM bugdb b, users u
                 WHERE
                  b.bug_type != "Feature/Change Request" AND
                  b.assign = u.handle AND
                  b.status = "Closed"
                 GROUP BY u.handle
                 ORDER BY c DESC, b.ts2 DESC')->execute(array())->fetchAll(MDB2_FETCHMODE_ASSOC);
        $patches = $this->_dbh->prepare('SELECT COUNT(*)
                FROM bugdb_patchtracker
                WHERE
                 developer=?')->execute(array($handle))->fetchOne();
        $rank = count($bugrank);
        $alltimecount = 0;
        foreach ($bugrank as $i => $inf) {
            if ($inf['handle'] == $handle) {
                $rank = $i + 1;
                $alltimecount = $inf['c'];
                break;
            }
        }
        return array(
            'total' => $total,
            'assigned' => $total ? $assigned / $total : 0,
            'openage' => $openage ? $openage : 0,
            'opencount' => $opencount ? $opencount : 0,
            'info' => $allbugs,
            'rankings' => $bugrank,
            'rank' => $rank,
            'alltime' => $alltimecount,
            'patches' => $patches,
            'opened' => $opened,
            'commented' => $commented,
        );
    }

    function getRank($handle)
    {
        static $bugrank = false;
        if (!$bugrank) {
            $bugrank = $this->_dbh->prepare('SELECT COUNT(*) as c, u.handle
                 FROM bugdb b, users u
                 WHERE
                  b.bug_type != "Feature/Change Request" AND
                  b.assign = u.handle AND
                  b.status = "Closed"
                 GROUP BY u.handle
                 ORDER BY c DESC, b.ts2 DESC')->execute()->fetchAll(MDB2_FETCHMODE_ASSOC);
        }
        $rank = count($bugrank) + 1;
        $alltimecount = 0;
        foreach ($bugrank as $i => $inf) {
            if ($inf['handle'] == $handle) {
                $rank = $i + 1;
                $alltimecount = $inf['c'];
                break;
            }
        }
        return array($rank, count($bugrank) + 1);
    }

    function allDevelStats()
    {
        return $this->_dbh->getAll('SELECT COUNT(*) as c, u.handle
                 FROM bugdb b, users u
                 WHERE
                  b.bug_type != "Feature/Change Request" AND
                  b.assign = u.handle AND
                  b.status = "Closed"
                 GROUP BY u.handle
                 ORDER BY c DESC, b.ts2 DESC', array(), MDB2_FETCHMODE_ASSOC);
    }

    function lastMonthStats()
    {
        return $this->_dbh->prepare('SELECT COUNT(*) as c, u.handle
                 FROM bugdb b, users u
                 WHERE
                  TO_DAYS(NOW()) - TO_DAYS(b.ts2) <= 30 AND
                  b.bug_type != "Feature/Change Request" AND
                  b.assign = u.handle AND
                  b.status = "Closed"
                 GROUP BY u.handle
                 ORDER BY c DESC, b.ts2 DESC')->execute()->fetchAll(MDB2_FETCHMODE_ASSOC);
    }

    function reporterStats()
    {
        $bugs = $this->_dbh->getAssoc('SELECT u.handle, COUNT(*) as c
                 FROM bugdb b, users u
                 WHERE
                  b.handle = u.handle AND
                  u.registered = 1 AND
                  b.status NOT IN ("Spam", "Bogus")
                 GROUP BY u.handle
                 ORDER BY u.handle', false, array(),null, MDB2_FETCHMODE_ASSOC);
        $comments = $this->_dbh->getAssoc('SELECT u.handle, COUNT(*) as c
                 FROM bugdb_comments b, bugdb d, users u
                 WHERE
                  b.handle = u.handle AND
                  u.registered = 1 AND
                  d.id = b.bug AND
                  d.status NOT IN ("Spam", "Bogus")
                 GROUP BY u.handle
                 ORDER BY u.handle', false, array(), null, MDB2_FETCHMODE_ASSOC);
        $patches = $this->_dbh->getAssoc('SELECT u.handle, COUNT(*) as c
                 FROM bugdb_patchtracker p, bugdb b, users u
                 WHERE
                  b.handle = u.handle AND
                  u.registered = 1 AND
                  b.id = p.bugdb_id AND
                  b.status NOT IN ("Spam", "Bogus")
                 GROUP BY u.handle
                 ORDER BY u.handle', false, array(), null, MDB2_FETCHMODE_ASSOC);
        foreach ($comments as $handle => $count) {
            if (!isset($bugs[$handle])) {
                $bugs[$handle] = 0;
            }
            $bugs[$handle] += $count;
        }
        foreach ($patches as $handle => $count) {
            if (!isset($bugs[$handle])) {
                $bugs[$handle] = 0;
            }
            $bugs[$handle] += $count;
        }
        arsort($bugs);
        return $bugs;
    }
}
