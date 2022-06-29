<?php

// This file is part of HuanLeGuang Project, Created by php-cs-fixer 3.0.

namespace Huanhyperf\Excel;

use Hyperf\Macroable\Macroable;

trait DelegatedMacroable
{
    use Macroable {
        __call as __callMacro;
    }

    /**
     * Dynamically handle calls to the class.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (method_exists($this->getDelegate(), $method)) {
            return call_user_func_array([$this->getDelegate(), $method], $parameters);
        }

        array_unshift($parameters, $this);

        return $this->__callMacro($method, $parameters);
    }

    /**
     * @return object
     */
    abstract public function getDelegate();
}
