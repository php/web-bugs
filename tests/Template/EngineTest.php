<?php

namespace App\Tests\Template;

use PHPUnit\Framework\TestCase;
use App\Template\Engine;

class EngineTest extends TestCase
{
    public function setUp()
    {
        $this->template = new Engine(__DIR__.'/../fixtures/templates');
    }

    public function testView()
    {
        $content = $this->template->render('pages/view.php', [
            'foo' => 'Lorem ipsum dolor sit amet.',
            'sidebar' => 'PHP is a popular general-purpose scripting language that is especially suited to web development',
        ]);

        $this->assertRegexp('/Lorem ipsum dolor sit amet/', $content);
        $this->assertRegexp('/PHP is a popular general-purpose/', $content);
    }

    public function testRegisterNew()
    {
        // Register callable function
        $this->template->register('addAsterisks', function ($var) {
            return '***'.$var.'***';
        });

        // Register callable object and method
        $object = new class {
            public $property;

            public function doSomething($argument) {}
        };
        $this->template->register('doSomething', [$object, 'doSomething']);

        $content = $this->template->render('pages/add_function.php', [
            'foo' => 'Lorem ipsum dolor sit amet.',
        ]);

        $this->assertRegexp('/\*\*\*Lorem ipsum dolor sit amet\.\*\*\*/', $content);
    }

    public function testRegisterExisting()
    {
        $this->expectException(\Exception::class);

        $this->template->register('noHtml', function ($var) {
            return $var;
        });
    }

    public function testAssignments()
    {
        $this->template->assign([
            'parameter' => 'FooBarBaz',
        ]);

        $content = $this->template->render('pages/assignments.php', [
            'foo' => 'Lorem ipsum dolor sit amet.',
        ]);

        $this->assertRegexp('/Lorem ipsum dolor sit amet\./', $content);
        $this->assertRegexp('/FooBarBaz/', $content);
    }

    public function testMerge()
    {
        $this->template->assign([
            'foo',
            'bar',
            'qux' => 'quux',
        ]);

        $this->template->assign([
            'baz',
            'qux' => 'quuz',
        ]);

        $this->assertEquals(['baz', 'bar', 'qux' => 'quuz'], $this->template->getVariables());
    }

    public function testVariablesScope()
    {
        $this->template->assign([
            'parameter' => 'Parameter value',
        ]);

        $content = $this->template->render('pages/invalid_variables.php', [
            'foo' => 'Lorem ipsum dolor sit amet',
        ]);

        $expected = var_export([
            'parameter' => 'Parameter value',
            'foo' => 'Lorem ipsum dolor sit amet',
        ], true);

        $this->assertEquals($expected, $content);
    }

    public function testInvalidVariables()
    {
        $this->template->assign([
            'Invalid value with key 0',
            'parameter' => 'Parameter value',
            'Invalid value with key 1',
        ]);

        $this->expectException(\Exception::class);

        $content = $this->template->render('pages/invalid_variables.php', [
            'foo' => 'Lorem ipsum dolor sit amet',
            1 => 'Invalid overridden value with key 1',
        ]);
    }

    public function testOverrides()
    {
        $this->template->assign([
            'pageParameter_1' => 'Page parameter 1',
            'pageParameter_2' => 'Page parameter 2',
            'layoutParameter_1' => 'Layout parameter 1',
            'layoutParameter_2' => 'Layout parameter 2',
            'layoutParameter_3' => 'Layout parameter 3',
        ]);

        $content = $this->template->render('pages/overrides.php', [
            'pageParameter_2' => 'Overridden parameter 2',
            'layoutParameter_2' => 'Layout overridden parameter 2',
        ]);

        $this->assertRegexp('/Page parameter 1/', $content);
        $this->assertRegexp('/^((?!Page parameter 2).)*$/s', $content);
        $this->assertRegexp('/Overridden parameter 2/', $content);
        $this->assertRegexp('/Layout parameter 1/', $content);
        $this->assertRegexp('/^((?!Layout parameter 2).)*$/s', $content);
        $this->assertRegexp('/Layout overridden parameter 2/', $content);
    }

    public function testAppending()
    {
        $content = $this->template->render('pages/appending.php');

        $this->assertRegexp('/file\_1\.js/', $content);
        $this->assertRegexp('/file\_2\.js/', $content);
    }

    public function testIncluding()
    {
        $content = $this->template->render('pages/including.php');

        $this->assertRegexp('/\<form method\=\"post\"\>/', $content);
        $this->assertRegexp('/Banner inclusion/', $content);
    }

    public function testNoLayout()
    {
        $content = $this->template->render('pages/no_layout.rss');

        $this->assertEquals(file_get_contents(__DIR__.'/../fixtures/templates/pages/no_layout.rss'), $content);
    }

    public function testMissingTemplate()
    {
        $this->template->assign([
            'parameter' => 'Parameter value',
        ]);

        $this->expectException(\Exception::class);

        $content = $this->template->render('pages/this/does/not/exist.php', [
            'foo' => 'Lorem ipsum dolor sit amet',
        ]);
    }

    public function testExtending()
    {
        $this->expectException(\Exception::class);

        $html = $this->template->render('pages/extends.php');
    }
}
