<?php declare(strict_types=1);

namespace App\Tests\Unit\Utils;

use PHPUnit\Framework\TestCase;
use App\Utils\Captcha;

class CaptchaTest extends TestCase
{
    /** @var Captcha */
    private $captcha;

    public function setUp(): void
    {
        $this->captcha = new Captcha();
    }

    /**
     * @dataProvider mathProvider
     */
    public function testGetQuestion(int $first, int $last, string $operation, string $question, int $expected): void
    {
        $this->captcha->setFirst($first);
        $this->captcha->setLast($last);
        $this->captcha->setOperation($operation);

        $this->assertEquals($question, $this->captcha->getQuestion());
        $this->assertEquals($expected, $this->captcha->getAnswer());
    }

    public function mathProvider(): array
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
