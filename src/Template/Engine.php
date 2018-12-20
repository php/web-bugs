<?php

namespace App\Template;

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
class Engine
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
     * Class constructor.
     */
    public function __construct(string $templatesDir, Context $context)
    {
        $this->templatesDir = $templatesDir;
        $this->context = $context;
    }

    /**
     * Renders given template file and populates its scope with variables
     * provided as array elements. Each array key is a variable name in template
     * scope and array item value is set as a variable value.
     */
    public function render(string $template, array $vars = []): string
    {
        // To not mess with the variable scopes too much closure arguments are
        // retrieved dynamically via func_get_arg
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

        return $closure($this->templatesDir, $template, $vars);
    }

    /**
     * Registering function makes a custom callable defined in the (front)
     * controller available in the template scope. Useful when a customized
     * function or class method needs to be called in the template.
     */
    public function registerFunction(string $name, callable $callback)
    {
        $this->context->addFunction($name, $callback);
    }
}
