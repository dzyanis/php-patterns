<?php

namespace DesignPatterns\Behavioral\Interpreter;

include_once 'Interpreter.php';

abstract class Extension
{
    /**
     * @param Interpreter $core
     */
    public function init(Interpreter $core)
    {
        // pass
    }

    /**
     * @param array $arguments
     * @param Interpreter $core
     * @return mixed
     */
    public function calculationArguments(array $arguments, Interpreter $core)
    {
        foreach ($arguments as $key => $argument) {
            $arguments[$key] = $core->calculation($argument);
        }
        return $arguments;
    }

    abstract public function execute(array $arguments, Interpreter $core);
}