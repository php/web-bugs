<?php

namespace App;

use App\Template\Context;

/**
 * Template engine created for bugs.php.net. Goal is not to reinvent a new
 * template engine and compete with other much better ones out there but to
 * provide a vanilla PHP approach and separate application logic and presentation
 * layers a bit.
 *
 * Engine provides several methods to create main template layout, sections, and
 * escaping of strings to not introduce too common XSS vulnerabilities.
 */
class Template
{
    /**
     * Templates directory contains all application templates.
     */
    private $templatesDir;

    /**
     * Default context of template engine (usage of $this in template belongs to
     * set context).
     * @var Context
     */
    private $context;

    /**
     * Pool of registered functions in the application.
     */
    private $functions = [];

    /**
     * Pool of global variables.
     */
    private $variables = [];

    /**
     * Class constructor.
     */
    public function __construct(string $templatesDir, Context $context)
    {
        $this->templatesDir = $templatesDir;
        $this->context = $context;
    }

    /**
     * This enables adding new variables in the template scope right after
     * initializing a template engine. Some variables in templates are like
     * parameters or globals and should be added only on one place instead of
     * repeating them at each ...->render() call.
     */
    public function add(array $vars = []): void
    {
        $this->variables = array_merge($this->variables, $vars);
    }

    /**
     * Add a template helper function as a callable defined in the (front)
     * controller to the template scope. Useful when a custom function or class
     * method need to be called in the template. A wrapper around the
     * Context::addFunction().
     */
    public function addFunction(string $name, callable $callback)
    {
        $this->context->addFunction($name, $callback);
    }

    /**
     * Renders given template file and populates its scope with variables
     * provided as array elements. Each array key is a variable name in template
     * scope and array item value is set as a variable value. To not mess with
     * the variable scopes too much closure arguments are retrieved dynamically
     * via the func_get_arg(). Note that $this pseudo-variable in the closure
     * refers to the Context::class scope.
     */
    public function render(string $template, array $vars = []): string
    {
        $closure = \Closure::bind(
            function() {
                ob_start();
                extract(func_get_arg(2), EXTR_SKIP);

                try {
                    include func_get_arg(0).'/'.func_get_arg(1);
                } catch (\Exception $e) {
                    ob_end_clean();
                    throw $e;
                }

                ob_end_clean();

                ob_start();
                extract($this->layoutVars);

                include func_get_arg(0).'/'.$this->layout;

                return ob_get_clean();
            },
            $this->context,
            Context::class
        );

        // Merge variables set on the template render level and ones defined
        // earlier in the engine creation level.
        $vars = array_merge($this->variables, $vars);

        return $closure($this->templatesDir, $template, $vars);
    }
}
