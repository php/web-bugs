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
    public function execute($parameters = null): self
    {
        parent::execute($parameters);

        return $this;
    }
}
