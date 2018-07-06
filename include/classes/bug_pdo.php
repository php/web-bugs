<?php
/**
 * Thin compatibility layer between MDB2 and PDO for bugs.php.net.
 * 
 * Please mind that it's not meant to implement full feature set, 
 * but only this used by our existing codebase. New code interacting
 * with the database should be written using standard PDO's approach.
 *
 * @author Maciej Sobaczewski <sobak@php.net>
 */

// Define missing constants
define('MDB2_FETCHMODE_ASSOC', null);
define('MDB2_FETCHMODE_ORDERED', null);
define('MDB2_PREPARE_MANIP', null);

class Bug_PDO extends PDO
{
    /**
     * When creating new PDO object, automagically switch PDOStatement with
     * own extended implementation.
     */
    public function __construct($dsn, $username = '', $password = '', array $options = [])
    {
        parent::__construct($dsn, $username, $password, $options);

        $this->setAttribute(PDO::ATTR_STATEMENT_CLASS, ['Bug_PDOStatement']);
    }

    /**
     * MDB2::espace() doesn't put apostrophes around the text and PDO does so we
     * need to strip outermost characters.
     */
    public function escape($text, $escape_wildcards = false)
    {
        return substr($this->quote($text), 1, -1);
    }

    public function queryAll($query, $types = null, $fetchmode = null, $rekey = false, $force_array = false, $group = false)
    {
        return $this->query($query)->fetchAll();
    }
}

class Bug_PDOStatement extends PDOStatement
{
    /**
     * MDB2 allows for chaining execute() method like so:
     *   $db->query('SELECT a FROM b WHERE c = ?')->execute('foo')->fetch();
     * PDOStatement::execute(), on the other hand, returns boolean. Change it
     * to return $this and thus allow futher method chaining.
     */
    public function execute(array $input_parameters = [])
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
        return $this->fetch(PDO::FETCH_NUM)[0];
    }

    public function fetchRow($mode)
    {
        return $this->fetch();
    }
}
