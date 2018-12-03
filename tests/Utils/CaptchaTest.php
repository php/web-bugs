<?php

namespace App\Tests\Utils;

use PHPUnit\Framework\TestCase;
use App\Utils\Captcha;

class CaptchaTest extends TestCase
{
    private $captcha;

    public function setUp()
    {
        $this->captcha = new Captcha();
    }

    /**
     * @dataProvider mathProvider
     */
    public function testGetQuestion($first, $last, $operation, $question, $expected)
    {
        $this->captcha->setFirst($first);
        $this->captcha->setLast($last);
        $this->captcha->setOperation($operation);

        $this->assertEquals($question, $this->captcha->getQuestion());
        $this->assertEquals($expected, $this->captcha->getAnswer());
    }

    public function mathProvider()
    {
        return [
            [1, 2, 'addition', '1 + 2 = ?', 3],
            [10, 50, 'subtraction', '50 - 10 = ?', 40],
            [90, 50, 'subtraction', '90 - 50 = ?', 40],
            [14, 14, 'subtraction', '14 - 14 = ?', 0],
            [10, 5, 'multiplication', '10 + 5 = ?', 15],
            [12, 2, 'foo', '12 + 2 = ?', 14],
        ];
    }
}
