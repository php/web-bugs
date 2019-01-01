<?php

namespace App\Template;

/**
 * Context represents a template variable scope where $this pseudo-variable can
 * be used in the templates and context methods can be called as $this->method().
 */
class Context
{
    /**
     * Templates directory.
     *
     * @var string
     */
    private $dir;

    /**
     * The current processed template or snippet file.
     *
     * @var string
     */
    private $current;

    /**
     * All assigned and set variables for the template.
     *
     * @var array
     */
    private $variables = [];

    /**
     * Pool of blocks for the template context.
     *
     * @var array
     */
    private $blocks = [];

    /**
     * Parent templates extended by child templates.
     *
     * @var array
     */
    public $tree = [];

    /**
     * Pool of registered callable functions.
     *
     * @var array
     */
    private $functions = [];

    /**
     * Current nesting level of the output buffering mechanism.
     */
    private $bufferLevel = 0;

    /**
     * Class constructor.
     */
    public function __construct(
        string $dir,
        array $variables = [],
        array $functions = []
    ) {
        $this->dir = $dir;
        $this->variables = $variables;
        $this->functions = $functions;
    }

    /**
     * Sets a parent layout for the given template. Additional variables in the
     * parent scope can be defined via the second argument.
     */
    public function extends(string $parent, array $variables = []): void
    {
        if (isset($this->tree[$this->current])) {
            throw new \Exception('Extending '.$parent.' is not possible.');
        }

        $this->tree[$this->current] = [$parent, $variables];
    }

    /**
     * Return a block content from the pool by name.
     */
    public function block(string $name): string
    {
        return $this->blocks[$name] ?? '';
    }

    /**
     * Starts a new template block. Under the hood a simple separate output
     * buffering is used to capture the block content. Content can be also
     * appended to previously set same block name.
     */
    public function start(string $name): void
    {
        $this->blocks[$name] = '';

        ++$this->bufferLevel;

        ob_start();
    }

    /**
     * Append content to a template block. If no block with the key name exists
     * yet it starts a new one.
     */
    public function append(string $name): void
    {
        if (!isset($this->blocks[$name])) {
            $this->blocks[$name] = '';
        }

        ++$this->bufferLevel;

        ob_start();
    }

    /**
     * Ends block output buffering and stores its content into the pool.
     */
    public function end(string $name): void
    {
        --$this->bufferLevel;

        $content = ob_get_clean();

        if (!empty($this->blocks[$name])) {
            $this->blocks[$name] .= $content;
        } else {
            $this->blocks[$name] = $content;
        }
    }

    /**
     * Include template file into existing template.
     *
     * @return mixed
     */
    public function include(string $template)
    {
        return include $this->dir.'/'.$template;
    }

    /**
     * Scalpel when preventing XSS vulnerabilities. This escapes given string
     * and still preserves certain characters as HTML.
     */
    public function e(string $string): string
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Hammer when protecting against XSS. Sanitize strings and replace all
     * characters to their applicable HTML entities from it.
     */
    public function noHtml(string $string): string
    {
        return htmlentities($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * A proxy to call registered functions if needed.
     *
     * @return mixed
     */
    public function __call(string $method, array $args)
    {
        if (isset($this->functions[$method])) {
            return call_user_func_array($this->functions[$method], $args);
        }
    }
}
