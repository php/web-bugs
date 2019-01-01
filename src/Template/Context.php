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
     * The template of this context.
     *
     * @var string
     */
    private $template;

    /**
     * The current processed template file or snippet.
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
     * Pool of templates used for extending.
     *
     * @var array
     */
    private $extends = [];

    /**
     * Pool of registered callable functions.
     *
     * @var array
     */
    private $functions = [];

    /**
     * When creating closure in the engine, a buffer is used for storing
     * transient output data that is finally returned when rendering the template.
     *
     * @var string
     */
    private $buffer;

    /**
     * Class constructor.
     */
    public function __construct(
        string $dir,
        string $template,
        array $variables = [],
        array $functions = []
    ) {
        $this->dir = $dir;
        $this->template = $template;
        $this->variables = $variables;
        $this->functions = $functions;
    }

    /**
     * Sets a parent layout for the given template. Additional variables in the
     * parent scope can be defined via the second argument.
     */
    public function extends(string $parent, array $variables = []): void
    {
        $this->extends[$this->current] = [$parent, $variables];
    }

    private function bufferize($file, array $variables = [])
    {
        $this->current = $file;
        $this->variables = array_replace($this->variables, $variables);
        unset($variables, $file);

        if (count($this->variables) > extract($this->variables, EXTR_SKIP)) {
            throw new \Exception(
                'Variables with numeric names $0, $1... cannot be imported to scope '.$this->current
            );
        }

        ob_start();

        try {
            include $this->dir.'/'.$this->current;
        } catch (\Exception $e) {
            ob_end_clean();

            throw $e;
        }

        return ob_get_clean();
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

        ob_start();
    }

    /**
     * Ends block output buffering and stores its content into the pool.
     */
    public function end(string $name): void
    {
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
     */
    public function __call(string $method, array $args)
    {
        if (isset($this->functions[$method])) {
            return call_user_func_array($this->functions[$method], $args);
        }
    }
}
