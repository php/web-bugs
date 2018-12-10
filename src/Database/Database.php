<?php

namespace App\Database;

use App\Database\Statement;

/**
 * Thin PDO wrapper for bugs.php.net.
 *
 * @author Maciej Sobaczewski <sobak@php.net>
 */
class Database extends \PDO
{
    /**
     * When creating new PDO object, automagically switch PDOStatement with own
     * extended implementation.
     */
    public function __construct(string $dsn, string $username = '', string $password = '', array $options = [])
    {
        parent::__construct($dsn, $username, $password, $options);

        $this->setAttribute(\PDO::ATTR_STATEMENT_CLASS, [Statement::class]);
    }

    /**
     * PDO puts apostrophes around the text so we need to strip the outermost
     * characters.
     */
    public function escape($text, $escape_wildcards = false)
    {
        return substr($this->quote($text), 1, -1);
    }
}
