<?php

namespace App\Database;

/**
 * PDOStatement wrapper class.
 */
class Statement extends \PDOStatement
{
    /**
     * This allows chaining execute() method:
     *   $db->query('SELECT a FROM b WHERE c = ?')->execute('foo')->fetch();
     * \PDOStatement::execute(), on the other hand, returns boolean. Change it
     * to return $this and thus allow further method chaining.
     */
    public function execute($input_parameters = null)
    {
        parent::execute($input_parameters);

        return $this;
    }

    public function fetchAll($fetchode = null, $rekey = false, $force_array = false, $group = false)
    {
        return parent::fetchAll();
    }

    public function fetchCol($colnum)
    {
        return parent::fetchColumn($colnum);
    }

    public function fetchOne($colnum = 0, $rownum = null)
    {
        return $this->fetch(\PDO::FETCH_NUM)[0];
    }

    public function fetchRow($mode = null)
    {
        if (!$mode) {
            $mode = \PDO::FETCH_BOTH;
        }

        return $this->fetch($mode);
    }
}
