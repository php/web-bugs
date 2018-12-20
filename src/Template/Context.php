<?php

namespace App\Template;

/**
 * Default template engine context. Context represents a template scope where
 * $this pseudo-variable is used in templates and the context methods can be
 * called in the template as $this->methodCall().
 */
class Context
{
    /**
     * Pool of sections in a given template context.
     * @var array
     */
    private $sections = [];

    /**
     * Current layout for the given template context.
     * @var string
     */
    private $layout;

    /**
     * Each layout can have its own variables set in the template directly.
     * @var array
     */
    private $layoutVars = [];

    /**
     * Pool of registered callable functions.
     * @var array
     */
    private $functions = [];

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
     * Return a section from the pool by name.
     */
    public function section(string $name): string
    {
        return $this->sections[$name] ?? '';
    }

    /**
     * Starts a new template section. Under the hood a simple separate output
     * buffering is used to capture the section content. Content can be also
     * appended to previously set same section name.
     */
    public function start(string $name, bool $append = false): void
    {
        if (!$append) {
            $this->sections[$name] = '';
        }

        ob_start();
    }

    /**
     * Ends started section. Under the hood separate output buffering is used
     * to capture all section content to a sections pool.
     */
    public function end(string $name): void
    {
        $content = ob_get_clean();

        if (!empty($this->sections[$name])) {
            $this->sections[$name] .= $content;
        } else {
            $this->sections[$name] = $content;
        }
    }

    /**
     * Scalpel when preventing XSS vulnerabilities. This escapes given string
     * and still preserves certain characters as HTML.
     * TODO - refactor and fix.
     */
    public function e(string $string): string
    {
        return htmlspecialchars($string, ENT_QUOTES);
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
     * Add a callable function to the functions pool.
     */
    public function addFunction(string $name, callable $callback): void
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
