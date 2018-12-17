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
    public function e($var)
    {
        return htmlspecialchars($var, ENT_QUOTES);
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
}
