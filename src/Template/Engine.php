<?php

namespace App\Template;

/**
 * A simple template engine that assigns global variables to the templates and
 * renders given template.
 */
class Engine
{
    /**
     * Templates directory contains all application templates.
     *
     * @var string
     */
    private $dir;

    /**
     * Registered callables.
     *
     * @var array
     */
    private $callables = [];

    /**
     * Assigned variables after template initialization and before calling the
     * render method.
     *
     * @var array
     */
    private $variables = [];

    /**
     * Template context.
     *
     * @var Context
     */
    private $context;

    /**
     * Class constructor.
     */
    public function __construct(string $dir)
    {
        if (!is_dir($dir)) {
            throw new \Exception($dir.' is missing or not a valid directory.');
        }

        $this->dir = $dir;
    }

    /**
     * This enables assigning new variables to the template scope right after
     * initializing a template engine. Some variables in templates are like
     * parameters or globals and should be added only on one place instead of
     * repeating them at each ...->render() call.
     */
    public function assign(array $variables = []): void
    {
        $this->variables = array_replace($this->variables, $variables);
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
     * controller to the template scope.
     */
    public function register(string $name, callable $callable): void
    {
        if (method_exists(Context::class, $name)) {
            throw new \Exception(
                $name.' is already registered by the template engine. Use a different name.'
            );
        }

        $this->callables[$name] = $callable;
    }

    /**
     * Renders given template file and populates its scope with variables
     * provided as array elements. Each array key is a variable name in template
     * scope and array item value is set as a variable value.
     */
    public function render(string $template, array $variables = []): string
    {
        $variables = array_replace($this->variables, $variables);

        $this->context = new Context(
            $this->dir,
            $variables,
            $this->callables
        );

        $buffer = $this->bufferize($template, $variables);

        while (!empty($current = array_shift($this->context->tree))) {
            $buffer = trim($buffer);
            $buffer .= $this->bufferize($current[0], $current[1]);
        }

        return $buffer;
    }

    /**
     * Processes given template file, merges variables into template scope using
     * output buffering and returns the rendered content string. Note that $this
     * pseudo-variable in the closure refers to the scope of the Context class.
     */
    private function bufferize(string $template, array $variables = []): string
    {
        if (!is_file($this->dir.'/'.$template)) {
            throw new \Exception($template.' is missing or not a valid template.');
        }

        $closure = \Closure::bind(
            function ($template, $variables) {
                $this->current = $template;
                $this->variables = array_replace($this->variables, $variables);
                unset($variables, $template);

                if (count($this->variables) > extract($this->variables, EXTR_SKIP)) {
                    throw new \Exception(
                        'Variables with numeric names $0, $1... cannot be imported to scope '.$this->current
                    );
                }

                ++$this->bufferLevel;

                ob_start();

                try {
                    include $this->dir.'/'.$this->current;
                } catch (\Exception $e) {
                    // Close all opened buffers
                    while ($this->bufferLevel > 0) {
                        --$this->bufferLevel;

                        ob_end_clean();
                    }

                    throw $e;
                }

                --$this->bufferLevel;

                return ob_get_clean();
            },
            $this->context,
            Context::class
        );

        return $closure($template, $variables);
    }
}
