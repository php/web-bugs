<?php

namespace App\Tests\Unit\Template;

use PHPUnit\Framework\TestCase;
use App\Template\Context;

class ContextTest extends TestCase
{
    public function setUp()
    {
        $this->context = new Context(TEST_FIXTURES_DIRECTORY . '/templates');
    }

    public function testBlock()
    {
        $this->context->start('foo');
        echo 'bar';
        $this->context->end('foo');

        $this->assertEquals($this->context->block('foo'), 'bar');

        $this->context->append('foo');
        echo 'baz';
        $this->context->end('foo');

        $this->assertEquals($this->context->block('foo'), 'barbaz');

        $this->context->start('foo');
        echo 'overridden';
        $this->context->end('foo');

        $this->assertEquals($this->context->block('foo'), 'overridden');
    }

    public function testInclude()
    {
        ob_start();
        $this->context->include('includes/banner.php');
        $content = ob_get_clean();

        $this->assertEquals(file_get_contents(TEST_FIXTURES_DIRECTORY . '/templates/includes/banner.php'), $content);
    }

    public function testIncludeReturn()
    {
        $variable = $this->context->include('includes/variable.php');

        $this->assertEquals(include TEST_FIXTURES_DIRECTORY . '/templates/includes/variable.php', $variable);
    }

    /**
     * @dataProvider attacksProvider
     */
    public function testEscaping($malicious, $escaped, $noHtml)
    {
        $this->assertEquals($escaped, $this->context->e($malicious));
    }

    /**
     * @dataProvider attacksProvider
     */
    public function testNoHtml($malicious, $escaped, $noHtml)
    {
        $this->assertEquals($noHtml, $this->context->noHtml($malicious));
    }

    public function attacksProvider()
    {
        return [
            [
                '<iframe src="javascript:alert(\'Xss\')";></iframe>',
                '&lt;iframe src=&quot;javascript:alert(&#039;Xss&#039;)&quot;;&gt;&lt;/iframe&gt;',
                '&lt;iframe src&equals;&quot;javascript&colon;alert&lpar;&apos;Xss&apos;&rpar;&quot;&semi;&gt;&lt;&sol;iframe&gt;'
            ]
        ];
    }
}
