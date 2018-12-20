<?php

namespace App\Tests\Template;

use PHPUnit\Framework\TestCase;
use App\Template\Context;
use App\Template\Engine;

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

    public function testRegisterFunction()
    {
        $templateEngine = new Engine(__DIR__.'/../fixtures/templates', new Context());
        $templateEngine->registerFunction('addAsterisks', function($var) {
            return '***'.$var.'***';
        });

        $content = $templateEngine->render('pages/asterisks.html.php', [
            'foo'     => 'Lorem ipsum dolor sit amet.',
            'sidebar' => 'PHP is a popular general-purpose scripting language that is especially suited to web development'
        ]);

        $this->assertRegexp('/\*\*\*Lorem ipsum dolor sit amet\.\*\*\*/', $content);
        $this->assertRegexp('/PHP is a popular general-purpose/', $content);
    }
}
