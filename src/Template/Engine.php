<?php

namespace App\Template;

use App\Template\Context;

/**
 * A thin and simple template engine created for bugs.php.net site in particular.
 * Goal is not to reinvent a new template engine and compete with much better
 * template engines out there but to provide a vanilla PHP approach and separate
 * application logic and presentation layers a bit.
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
     */
    private $context;

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
        $closure = function($templatesDir, $template, $vars) {
            ob_start();
            extract($vars, EXTR_SKIP);

            try {
                include $templatesDir.'/'.$template;
            } catch (\Exception $e) {
                ob_end_clean();
                throw $e;
            }

            ob_end_clean();

            ob_start();
            extract($this->layoutVars);

            include $templatesDir.'/'.$this->layout;

            return ob_get_clean();
        };

        // Create a closure
        $closure = $closure->bindTo($this->context, $this->context);

        return $closure($this->templatesDir, $template, $vars);
    }
}
