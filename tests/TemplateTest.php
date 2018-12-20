<?php

namespace App\Tests;

use PHPUnit\Framework\TestCase;
use App\Template\Context;
use App\Template;

class TemplateTest extends TestCase
{
    public function setUp()
    {
        $this->template = new Template(__DIR__.'/fixtures/templates', new Context());
    }

    public function testView()
    {
        $content = $this->template->render('pages/index.html.php', [
            'foo'     => 'Lorem ipsum dolor sit amet.',
            'sidebar' => 'PHP is a popular general-purpose scripting language that is especially suited to web development'
        ]);

        $this->assertRegexp('/Lorem ipsum dolor sit amet/', $content);
        $this->assertRegexp('/PHP is a popular general-purpose/', $content);
    }

    public function testAddFunction()
    {
        $this->template->addFunction('addAsterisks', function($var) {
            return '***'.$var.'***';
        });

        $content = $this->template->render('pages/asterisks.html.php', [
            'foo'     => 'Lorem ipsum dolor sit amet.',
            'sidebar' => 'PHP is a popular general-purpose scripting language that is especially suited to web development'
        ]);

        $this->assertRegexp('/\*\*\*Lorem ipsum dolor sit amet\.\*\*\*/', $content);
        $this->assertRegexp('/PHP is a popular general-purpose/', $content);
    }

    public function testAddingVariables()
    {
        $this->template->add([
            'parameter' => 'FooBarBaz',
        ]);

        $content = $this->template->render('pages/variables.html.php', [
            'foo'     => 'Lorem ipsum dolor sit amet.',
            'sidebar' => 'PHP is a popular general-purpose scripting language that is especially suited to web development'
        ]);

        $this->assertRegexp('/Lorem ipsum dolor sit amet\./', $content);
        $this->assertRegexp('/PHP is a popular general-purpose/', $content);
        $this->assertRegexp('/FooBarBaz/', $content);
    }

    public function testAppendingSections()
    {
        $content = $this->template->render('pages/appending_sections.html.php');

        $this->assertRegexp('/file\_1\.js/', $content);
        $this->assertRegexp('/file\_2\.js/', $content);
    }
}
