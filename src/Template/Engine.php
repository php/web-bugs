<?php

namespace App\Template;

use App\Template\Context;

/**
 * Template engine created for bugs.php.net. Goal is not to reinvent a new
 * template engine and compete with other much better ones out there but instead
 * provide a vanilla PHP approach and separate application logic from
 * presentation.
 *
 * Several methods are provided to create a template with one main layout,
 * sections, and escaping of strings to not introduce too common XSS
 * vulnerabilities.
 */
class Engine
{
    /**
     * Templates directory contains all application templates.
     * @var string
     */
    private $templatesDir;

    /**
     * Pool of registered functions in the application.
     * @var array
     */
    private $functions = [];

    /**
     * Assigned variables after template initialization and before calling the
     * render method.
     * @var array
     */
    private $variables = [];

    /**
     * Class constructor.
     */
    public function __construct(string $templatesDir)
    {
        if (!is_dir($templatesDir)) {
            throw new \Exception($templatesDir.' is missing or not a valid directory.');
        }

        $this->templatesDir = $templatesDir;
    }

    /**
     * This enables assigning new variables to the template scope right after
     * initializing a template engine. Some variables in templates are like
     * parameters or globals and should be added only on one place instead of
     * repeating them at each ...->render() call.
     */
    public function assign(array $variables = []): void
    {
        $this->variables = $this->merge($this->variables, $variables);
    }

    /**
     * Merge arrays together. Wrapped separately for unit testing the expected
     * template engine functionality. Numeric and string keys are overridden in
     * case they repeat in arrays.
     */
    protected function merge(array ...$variables): array
    {
        return array_replace(...$variables);
    }

    /**
     * Get assigned variables of the template.
     */
    public function getVariables(): array
    {
        return $this->variables;
    }

    /**
     * Add new template helper function as a callable defined in the (front)
     * controller to the template scope. Useful when a custom function or class
     * method needs to be called in the template.
     */
    public function register(string $name, callable $callback): void
    {
        $this->functions[$name] = $callback;
    }

    /**
     * Renders given template file and populates its scope with variables
     * provided as array elements. Each array key is a variable name in template
     * scope and array item value is set as a variable value. Note that $this
     * pseudo-variable in the closure refers to the Context::class scope.
     */
    public function render(string $template, array $variables = []): string
    {
        if (!is_file($this->templatesDir.'/'.$template)) {
            throw new \Exception($template.' is missing or not a valid template.');
        }

        $context = new Context(
            $this->templatesDir,
            $template,
            $this->merge($this->variables, $variables),
            $this->functions
        );

        $closure = \Closure::bind(
            function () {
                if (count($this->variables) > extract($this->variables, EXTR_SKIP)) {
                    throw new \Exception(
                        'Variables with numeric names $0, $1... cannot be imported to scope '.$this->template
                    );
                }

                ob_start();

                try {
                    include $this->templatesDir.'/'.$this->template;
                } catch (\Exception $e) {
                    ob_end_clean();
                    throw $e;
                }

                $content = ob_get_clean();

                ob_start();

                if (isset($this->layout) && is_file($this->templatesDir.'/'.$this->layout)) {
                    extract($this->layoutVariables);
                    include $this->templatesDir.'/'.$this->layout;
                }

                return ob_get_clean().$content;
            },
            $context,
            Context::class
        );

        return $closure();
    }
}
