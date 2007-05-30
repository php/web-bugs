<?php
/**
 * Bug statistics
 * @package pearweb
 */
class PEAR_Bugs
{
    var $_dbh;
    function PEAR_Bugs()
    {
        $this->_dbh = $GLOBALS['dbh'];
    }

    function packageBugStats($packageid)
    {
        $info = $this->_dbh->getAll('
            SELECT
                COUNT(bugdb.id) as count,
                AVG(TO_DAYS(NOW()) - TO_DAYS(ts1)) as average,
                MAX(TO_DAYS(NOW()) - TO_DAYS(ts1)) as oldest
            FROM bugdb, packages
            WHERE
                name=? AND
                bugdb.package_name = packages.name AND
                status IN ("Open","Feedback","Assigned","Analyzed","Verified","Critical") AND
                bug_type IN ("Bug","Documentation Problem")
            ', array($packageid), DB_FETCHMODE_ASSOC);
        $total = $this->_dbh->getOne('
            SELECT COUNT(bugdb.id) FROM bugdb WHERE bugdb.package_name=?
            ', array($packageid));
        return array_merge($info[0], array('total' => $total));
    }

    function bugRank()
    {
        $info = $this->_dbh->getAll('
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
        ', array(), DB_FETCHMODE_ASSOC);
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
        $assigned = $this->_dbh->getOne('SELECT COUNT(b.status)
             FROM bugdb b, maintains m, packages p
             WHERE
              m.handle = ? AND
              p.id = m.package AND
              b.package_name = p.name AND
              b.bug_type \!= "Feature/Change Request" AND
              b.assign = ?', array($handle, $handle));
        $openage = $this->_dbh->getOne('SELECT ROUND(AVG(TO_DAYS(NOW()) - TO_DAYS(b.ts1)))
             FROM bugdb b, maintains m, packages p
             WHERE
              m.handle = ? AND
              p.id = m.package AND
              b.package_name = p.name AND
              b.bug_type \!= "Feature/Change Request" AND
              b.status IN ("Assigned", "Analyzed", "Feedback", "Open", "Critical", "Verified") AND
              (b.assign = ? OR b.assign IS NULL OR b.assign="")', array($handle, $handle));
        $opened = $this->_dbh->getOne('SELECT COUNT(*) FROM bugdb WHERE
            handle=?', array($handle));
        $commented = $this->_dbh->getOne('SELECT COUNT(*) FROM bugdb_comments WHERE
            handle=?', array($handle));
        $opencount = $this->_dbh->getOne('SELECT COUNT(*)
             FROM bugdb b, maintains m, packages p
             WHERE
              m.handle = ? AND
              p.id = m.package AND
              b.package_name = p.name AND
              b.bug_type \!= "Feature/Change Request" AND
              b.status IN ("Assigned", "Analyzed", "Feedback", "Open", "Critical", "Verified") AND
              (b.assign = ? OR b.assign IS NULL OR b.assign="")', array($handle, $handle));
        $bugrank = $this->_dbh->getAll('SELECT COUNT(*) as c, u.handle
                 FROM bugdb b, users u
                 WHERE
                  b.bug_type != "Feature/Change Request" AND
                  b.assign = u.handle AND
                  b.status = "Closed"
                 GROUP BY u.handle
                 ORDER BY c DESC, b.ts2 DESC', array(), DB_FETCHMODE_ASSOC);
        $patches = $this->_dbh->getOne('SELECT COUNT(*)
                FROM bugdb_patchtracker
                WHERE
                 developer=?', array($handle));
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
            $bugrank = $this->_dbh->getAll('SELECT COUNT(*) as c, u.handle
                 FROM bugdb b, users u
                 WHERE
                  b.bug_type != "Feature/Change Request" AND
                  b.assign = u.handle AND
                  b.status = "Closed"
                 GROUP BY u.handle
                 ORDER BY c DESC, b.ts2 DESC', array(), DB_FETCHMODE_ASSOC);
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
                 ORDER BY c DESC, b.ts2 DESC', array(), DB_FETCHMODE_ASSOC);
    }

    function lastMonthStats()
    {
        return $this->_dbh->getAll('SELECT COUNT(*) as c, u.handle
                 FROM bugdb b, users u
                 WHERE
                  TO_DAYS(NOW()) - TO_DAYS(b.ts2) <= 30 AND
                  b.bug_type != "Feature/Change Request" AND
                  b.assign = u.handle AND
                  b.status = "Closed"
                 GROUP BY u.handle
                 ORDER BY c DESC, b.ts2 DESC', array(), DB_FETCHMODE_ASSOC);
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
                 ORDER BY u.handle', false, array(), DB_FETCHMODE_ASSOC);
        $comments = $this->_dbh->getAssoc('SELECT u.handle, COUNT(*) as c
                 FROM bugdb_comments b, bugdb d, users u
                 WHERE
                  b.handle = u.handle AND
                  u.registered = 1 AND
                  d.id = b.bug AND
                  d.status NOT IN ("Spam", "Bogus")
                 GROUP BY u.handle
                 ORDER BY u.handle', false, array(), DB_FETCHMODE_ASSOC);
        $patches = $this->_dbh->getAssoc('SELECT u.handle, COUNT(*) as c
                 FROM bugdb_patchtracker p, bugdb b, users u
                 WHERE
                  b.handle = u.handle AND
                  u.registered = 1 AND
                  b.id = p.bugdb_id AND
                  b.status NOT IN ("Spam", "Bogus")
                 GROUP BY u.handle
                 ORDER BY u.handle', false, array(), DB_FETCHMODE_ASSOC);
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
?>