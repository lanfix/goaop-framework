<?php
/**
 * Go! OOP&AOP PHP framework
 *
 * @copyright     Copyright 2011, Lissachenko Alexander <lisachenko.it@gmail.com>
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */

namespace Go\Aop\Framework;

use Go\Aop\Intercept\MethodInterceptor;

/**
 * @package go
 */
class ClosureMethodInvocation extends AbstractMethodInvocation
{
    /**
     * Closure to use
     *
     * @var \Closure
     */
    private $closureToCall = null;

    /**
     * Previous scope of invocation
     *
     * @var null
     */
    private $previousScope = null;

    /**
     * Shortcut for ReflectionMethod->name
     *
     * @var string
     */
    private $methodName = '';

    /**
     * {@inheritdoc}
     */
    public function __construct($classNameOrObject, $methodName, array $advices)
    {
        parent::__construct($classNameOrObject, $methodName, $advices);
        $this->methodName    = $methodName;
        $this->closureToCall = $this->getStaticInvoker();
    }

    /**
     * Invokes original method and return result from it
     *
     * @return mixed
     */
    public function proceed()
    {
        if (isset($this->advices[$this->current])) {
            /** @var $currentInterceptor MethodInterceptor */
            $currentInterceptor = $this->advices[$this->current++];
            return $currentInterceptor->invoke($this);
        }

        // Rebind the closure if scope (class name) was changed since last time
        if ($this->previousScope !== $this->instance) {
            $this->closureToCall = $this->closureToCall->bindTo(null, $this->instance);
            $this->previousScope = $this->instance;
        }

        $closureToCall = $this->closureToCall;

        return $closureToCall($this->parentClass, $this->methodName, $this->arguments);

    }

    /**
     * Returns static method invoker
     *
     * @return callable
     */
    private static function getStaticInvoker()
    {
        static $invoker = null;
        if (!$invoker) {
            $invoker = function ($parentClass, $method, array $args) {
                switch(count($args)) {
                    case 0:
                        return parent::$method();
                    case 1:
                        return parent::$method($args[0]);
                    case 2:
                        return parent::$method($args[0], $args[1]);
                    case 3:
                        return parent::$method($args[0], $args[1], $args[2]);
                    case 4:
                        return parent::$method($args[0], $args[1], $args[2], $args[3]);
                    case 5:
                        return parent::$method($args[0], $args[1], $args[2], $args[3], $args[4]);
                    default:
                        return forward_static_call_array(array($parentClass, $method), $args);
                }
            };
        }
        return $invoker;
    }}
