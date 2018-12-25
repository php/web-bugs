<?php

namespace App\Template;

/**
 * A simple template engine created for bugs.php.net. Goal is not to reinvent
 * a new template engine and compete with other much better ones out there but
 * instead provide a vanilla PHP approach and separate the application logic
 * from the presentation.
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
     * Pool of registered functions in the application.
     *
     * @var array
     */
    private $functions = [];

    /**
     * Assigned variables after template initialization and before calling the
     * render method.
     *
     * @var array
     */
    private $variables = [];

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
     * controller to the template scope.
     */
    public function register(string $name, callable $callback): void
    {
        if (method_exists(Context::class, $name)) {
            throw new \Exception(
                $name.' is already registered by the template engine. Use a different name.'
            );
        }

        $this->functions[$name] = $callback;
    }

    /**
     * Renders given template file and populates its scope with variables
     * provided as array elements. Each array key is a variable name in template
     * scope and array item value is set as a variable value. Note that $this
     * pseudo-variable in the closure refers to the scope of the Context class.
     */
    public function render(string $template, array $variables = []): string
    {
        if (!is_file($this->dir.'/'.$template)) {
            throw new \Exception($template.' is missing or not a valid template.');
        }

        $context = new Context(
            $this->dir,
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
                    include $this->dir.'/'.$this->template;
                } catch (\Exception $e) {
                    ob_end_clean();

                    throw $e;
                }

                $this->buffer = ob_get_clean();

                if (isset($this->layout) && is_file($this->dir.'/'.$this->layout)) {
                    $this->buffer = trim($this->buffer);
                    ob_start();
                    extract($this->layoutVariables);
                    include $this->dir.'/'.$this->layout;
                    $this->buffer .= ob_get_clean();
                }

                return $this->buffer;
            },
            $context,
            Context::class
        );

        return $closure();
    }
}
