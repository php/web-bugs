<?php

namespace App\Utils;

/**
 * Captcha utility class for providing a simple math question with additions or
 * subtractions to prevent spam.
 */
class Captcha
{
    /**
     * First operand.
     */
    private $first;

    /**
     * Last operand.
     */
    private $last;

    /**
     * Highest possible operands value for randomization at initialization.
     */
    const MAX = 50;

    /**
     * Supported equation operations. Keys are operation symbols and values are
     * class method names to execute.
     */
    const OPERATIONS = [
        '+' => 'addition',
        '-' => 'subtraction',
    ];

    /**
     * Current operation.
     */
    private $operation;

    /**
     * Class constructor where operands random values and operation are set.
     */
    public function __construct()
    {
        $this->randomize();
    }

    /**
     * Set random operands values and operation.
     */
    public function randomize()
    {
        $this->setFirst(rand(1, self::MAX));
        $this->setLast(rand(1, self::MAX));
        $this->setOperation(self::OPERATIONS[array_rand(self::OPERATIONS)]);
    }

    /**
     * First operand number setter to override default random pick. Defined as a
     * separate method for convenience when unit testing.
     */
    public function setFirst($number)
    {
        $this->first = $number;
    }

    /**
     * Last operand number setter to override default random pick. Defined as a
     * separate method for convenience when unit testing.
     */
    public function setLast($number)
    {
        $this->last = $number;
    }

    /**
     * Set the operation. If provided operation is invalid it falls back to addition.
     */
    public function setOperation($operation)
    {
        $this->operation = in_array($operation, self::OPERATIONS) ? $operation : 'addition';
    }

    /**
     * Get current question equation string for displaying it to the user.
     */
    public function getQuestion()
    {
        $this->sortOperands();

        $symbol = array_search($this->operation, self::OPERATIONS);
        $symbol = $symbol === false ? '+' : $symbol;

        return $this->first.' '.$symbol.' '.$this->last.' = ?';
    }

    /**
     * The correct current answer of the given equation question.
     */
    public function getAnswer()
    {
        $this->sortOperands();

        return \call_user_func([Captcha::class, $this->operation], $this->first, $this->last);
    }

    /**
     * When the current operation is subtraction, sort operands to have a bigger
     * operand first. With this, negative results are omitted for simplicity and
     * possible better user experience.
     */
    private function sortOperands()
    {
        $first = $this->first;
        $last = $this->last;

        if ($this->operation === 'subtraction') {
            $this->first = $first > $last ? $first : $last;
            $this->last = $first > $last ? $last : $first;
        }
    }

    /**
     * Addition of two operands.
     */
    private function addition($first, $last)
    {
        return $first + $last;
    }

    /**
     * Subtraction of two operands.
     */
    private function subtraction($first, $last)
    {
        return $first - $last;
    }
}
