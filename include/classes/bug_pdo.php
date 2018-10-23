<?php
/**
 * Thin PDO wrapper for bugs.php.net.
 *
 * @author Maciej Sobaczewski <sobak@php.net>
 */

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
     * PDO puts apostrophes around the text so we need to strip the outermost
     * characters.
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
     * This allows chaining execute() method:
     *   $db->query('SELECT a FROM b WHERE c = ?')->execute('foo')->fetch();
     * PDOStatement::execute(), on the other hand, returns boolean. Change it to
     * return $this and thus allow further method chaining.
     */
    public function execute($input_parameters = NULL)
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

    public function fetchRow($mode=NULL)
    {
        if (!$mode) {
             $mode = PDO::FETCH_BOTH;
	}
        return $this->fetch($mode);
    }
}
