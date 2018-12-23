<?php

namespace App\Template;

/**
 * Default template engine context. Context represents a template scope where
 * $this pseudo-variable is used in template files and the context methods can
 * be called as $this->method().
 */
class Context
{
    /**
     * Templates directory.
     *
     * @var string
     */
    private $templatesDir;

    /**
     * The template of this context.
     *
     * @var string
     */
    private $template;

    /**
     * All assigned and set variables for the template.
     *
     * @var array
     */
    private $variables = [];

    /**
     * Pool of sections for the template context.
     *
     * @var array
     */
    private $sections = [];

    /**
     * Current layout for the template context.
     *
     * @var string
     */
    private $layout;

    /**
     * Each layout can have its own variables set in the template directly.
     *
     * @var array
     */
    private $layoutVariables = [];

    /**
     * Pool of registered callable functions.
     *
     * @var array
     */
    private $functions = [];

    /**
     * Class constructor.
     */
    public function __construct(
        string $templatesDir,
        string $template,
        array $variables = [],
        array $functions = []
    ) {
        $this->templatesDir = $templatesDir;
        $this->template = $template;
        $this->variables = $variables;
        $this->functions = $functions;
    }

    /**
     * Sets a layout for a given template. Additional variables in the layout
     * scope can be defined via second argument.
     */
    public function layout(string $name, array $variables = []): void
    {
        $this->layout = $name;
        $this->layoutVariables = $variables;
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
    public function start(string $name): void
    {
        $this->sections[$name] = '';

        ob_start();
    }

    /**
     * Append content to a template section. If no section with the key name
     * exists yet it starts a new one.
     */
    public function append(string $name): void
    {
        if (!isset($this->sections[$name])) {
            $this->sections[$name] = '';
        }

        ob_start();
    }

    /**
     * Ends section output buffering and store the section content into sections
     * pool.
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
     * Include template file into existing template.
     */
    public function include(string $template): string
    {
        return include $this->templatesDir.'/'.$template;
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
