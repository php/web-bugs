<?php

namespace App\Template;

/**
 * Default template engine context. Context represents a template scope where
 * the $this is used in templates and the context methods can be called in the
 * template as $this->methodCall().
 */
class Context
{
    /**
     * Pool of sections in a given template context.
     */
    private $sections;

    /**
     * Current layout for the given template context.
     */
    private $layout;

    /**
     * Each layout can have its own variables set in the template directly.
     */
    private $layoutVars;

    /**
     * Pool of registered callable functions.
     */
    private $functions = [];

    /**
     * Returns given section from a pool of all set sections.
     */
    public function section(string $name): string
    {
        return $this->sections[$name];
    }

    /**
     * Sets a layout for a given template. Additional variables in the layout
     * scope can be defined via second argument.
     */
    public function layout(string $name, array $vars = []): void
    {
        $this->layout = $name;
        $this->layoutVars = $vars;
    }

    /**
     * Escape given variable if it's a string.
     * TODO - refactor and fix.
     */
    public function e(string $var): string
    {
        return htmlspecialchars($var, ENT_QUOTES);
    }

    /**
     * Sanitize strings and remove all HTML.
     */
    public function noHtml(string $string): string
    {
        return htmlentities($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Starts a new template section. Under the hood a simple separate output
     * buffering is used to capture the section content.
     */
    public function start(string $name): void
    {
        $this->sections[$name] = '';
        ob_start();
    }

    /**
     * Ends started section. Under the hood separate output buffering is used
     * to capture all section content to a sections pool.
     */
    public function end(string $name): void
    {
        $this->sections[$name] = ob_get_clean();
    }

    /**
     * Add a callable function to the functions pool.
     */
    public function addFunction(string $name, callable $callback)
    {
        $this->functions[$name] = $callback;
    }

    /**
     * A proxy to call registered functions if needed.
     */
    public function __call(string $method, $args)
    {
        if (isset($this->functions[$method])) {
            return call_user_func_array($this->functions[$method], $args);
        }
    }
}
