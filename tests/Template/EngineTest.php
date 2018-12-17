<?php

namespace App\Tests\Template;

use PHPUnit\Framework\TestCase;
use App\Template\Engine;
use App\Template\Context;

class EngineTest extends TestCase
{
    public function testView()
    {
        $templateEngine = new Engine(__DIR__.'/../fixtures/templates', new Context());

        $content = $templateEngine->render('pages/index.html.php', [
            'foo'     => 'Lorem ipsum dolor sit amet.',
            'sidebar' => 'PHP is a popular general-purpose scripting language that is especially suited to web development'
        ]);

        $this->assertRegexp('/Lorem ipsum dolor sit amet/', $content);
        $this->assertRegexp('/PHP is a popular general-purpose/', $content);
    }
}
