<?php

namespace App\Tests\Template;

use PHPUnit\Framework\TestCase;
use App\Template\Context;

class ContextTest extends TestCase
{
    public function setUp()
    {
        $this->context = new Context(__DIR__.'/../fixtures/templates', 'pages/view.php');
    }

    public function testSection()
    {
        $this->context->start('foo');
            echo 'bar';
        $this->context->end('foo');

        $this->assertEquals($this->context->section('foo'), 'bar');

        $this->context->start('foo', true);
            echo 'baz';
        $this->context->end('foo');

        $this->assertEquals($this->context->section('foo'), 'barbaz');

        $this->context->start('foo');
            echo 'overridden';
        $this->context->end('foo');

        $this->assertEquals($this->context->section('foo'), 'overridden');
    }
}
