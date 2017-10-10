<?php

/**
 * This file is part of the MathExecutor package
 *
 * (c) Alexander Kiryukhin
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code
 */

namespace Z;

use Z\Classes\Calculator;
use Z\Classes\Lexer;
use Z\Classes\Token;
use Z\Classes\TokenFactory;

/**
 * Class MathExecutor
 * @package Z
 */
class MathExecutor
{
    /**
     * Available variables
     *
     * @var array
     */
    private $variables = array();

    /**
     * @var TokenFactory
     */
    private $tokenFactory;

    /**
     * @var array
     */
    private $cache = array();

    /**
     * Base math operators
     */
    public function __construct()
    {
        $this->addDefaults();
    }

    public function __clone()
    {
        $this->addDefaults();
    }

    /**
     * Set default operands and functions
     */
    protected function addDefaults()
    {
        $this->tokenFactory = new TokenFactory();

        $this->tokenFactory->addOperator('Z\Classes\Token\TokenPlus');
        $this->tokenFactory->addOperator('Z\Classes\Token\TokenMinus');
        $this->tokenFactory->addOperator('Z\Classes\Token\TokenMultiply');
        $this->tokenFactory->addOperator('Z\Classes\Token\TokenDivision');
        $this->tokenFactory->addOperator('Z\Classes\Token\TokenDegree');

        $this->tokenFactory->addFunction('sin', 'sin');
        $this->tokenFactory->addFunction('cos', 'cos');
        //change tn to tan for consistency
        $this->tokenFactory->addFunction('tan', 'tan');
        $this->tokenFactory->addFunction('asin', 'asin');
        $this->tokenFactory->addFunction('acos', 'acos');
        $this->tokenFactory->addFunction('atan', 'atan');
        //added atan2 function
        $this->tokenFactory->addFunction('atant', 'atan2', 2);
        $this->tokenFactory->addFunction('min', 'min', 2);
        $this->tokenFactory->addFunction('max', 'max', 2);
        $this->tokenFactory->addFunction('avg', function($arg1, $arg2) { return ($arg1 + $arg2) / 2; }, 2);

        //for AB_P02
        $this->tokenFactory->addFunction('ptwo', function($arg1, $arg2, $arg3) { return ($arg1 > 0) ? $arg2 : $arg3; } , 3);

        $this->tokenFactory->addFunction('abs', 'abs');

        $this->tokenFactory->addFunction('exp', 'exp');
        $this->tokenFactory->addFunction('ln', 'log');
        $this->tokenFactory->addFunction('log', 'log10');

        $this->setVars(array(
            'pi' => 3.14159265359,
            'e'  => 2.71828182846
        ));
    }

    /**
     * Add variable to executor
     *
     * @param  string        $variable
     * @param  integer|float $value
     * @throws \Exception
     * @return MathExecutor
     */
    public function setVar($variable, $value)
    {
        if (!is_numeric($value)) {
            throw new \Exception("Variable value must be a number");
        }

        $this->variables[$variable] = $value;

        return $this;
    }

    /**
     * Add variables to executor
     *
     * @param  array        $variables
     * @param  bool         $clear     Clear previous variables
     * @return MathExecutor
     */
    public function setVars(array $variables, $clear = true)
    {
        if ($clear) {
            $this->removeVars();
        }

        foreach ($variables as $name => $value) {
            $this->setVar($name, $value);
        }

        return $this;
    }

    /**
     * Remove variable from executor
     *
     * @param  string       $variable
     * @return MathExecutor
     */
    public function removeVar($variable)
    {
        unset ($this->variables[$variable]);

        return $this;
    }

    /**
     * Remove all variables
     */
    public function removeVars()
    {
        $this->variables = array();

        return $this;
    }

    /**
     * Add operator to executor
     *
     * @param  string       $operatorClass Class of operator token
     * @return MathExecutor
     */
    public function addOperator($operatorClass)
    {
        $this->tokenFactory->addOperator($operatorClass);

        return $this;
    }

    /**
     * Add function to executor
     *
     * @param  string       $name     Name of function
     * @param  callable     $function Function
     * @param  int          $places   Count of arguments
     * @return MathExecutor
     */
    public function addFunction($name, callable $function = null, $places = 1)
    {
        $this->tokenFactory->addFunction($name, $function, $places);

        return $this;
    }

    /**
     * Execute expression
     *
     * @param $expression
     * @return number
     */
    public function execute($expression)
    {

        //weird issue if simple operation isn't first, also change pi to number
        //variables don't work for some reason
        $expression = "1 - 1 + " . str_replace('pi', '3.1415926535898', $expression);

        if (!array_key_exists($expression, $this->cache)) {
            $lexer = new Lexer($this->tokenFactory);
            $tokensStream = $lexer->stringToTokensStream($expression);
            $tokens = $lexer->buildReversePolishNotation($tokensStream);
            $this->cache[$expression] = $tokens;
        } else {
            $tokens = $this->cache[$expression];
        }
        $calculator = new Calculator();
        $result = $calculator->calculate($tokens, $this->variables);

        return $result;
    }
}
