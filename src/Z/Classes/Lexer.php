<?php
/**
 * This file is part of the MathExecutor package
 *
 * (c) Alexander Kiryukhin
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code
 */

namespace Z\Classes;
use Z\Classes\Token\AbstractOperator;
use Z\Classes\Token\InterfaceOperator;
use Z\Classes\Token\TokenComma;
use Z\Classes\Token\TokenFunction;
use Z\Classes\Token\TokenLeftBracket;
use Z\Classes\Token\TokenNumber;
use Z\Classes\Token\TokenRightBracket;
use Z\Classes\Token\TokenVariable;
use Z\Exception\IncorrectBracketsException;
use Z\Exception\IncorrectExpressionException;

/**
 * @author Alexander Kiryukhin <alexander@symdev.org>
 */
class Lexer
{
    /**
     * @var TokenFactory
     */
    private $tokenFactory;

    public function __construct($tokenFactory)
    {
        $this->tokenFactory = $tokenFactory;
    }

    /**
     * @param  string                                      $input Source string of equation
     * @return array                                       Tokens stream
     * @throws \Z\Exception\IncorrectExpressionException
     */
    public function stringToTokensStream($input)
    {
        $matches = array();
        preg_match_all($this->tokenFactory->getTokenParserRegex(), $input, $matches);
        $tokenFactory = $this->tokenFactory;
        $tokensStream = array_map(
            function ($token) use ($tokenFactory) {
                return $tokenFactory->createToken($token);
            },
            $matches[0]
        );

        return $tokensStream;
    }

    /**
     * @param  array                                       $tokensStream Tokens stream
     * @return array                                       Array of tokens in revers polish notation
     * @throws \Z\Exception\IncorrectExpressionException
     */
    public function buildReversePolishNotation($tokensStream)
    {
        $output = array();
        $stack = array();

        foreach ($tokensStream as $token) {
            if ($token instanceof TokenNumber) {
                $output[] = $token;
            }
            if ($token instanceof TokenVariable) {
                $output[] = $token;
            }
            if ($token instanceof TokenFunction) {
                array_push($stack, $token);
            }
            if ($token instanceof TokenLeftBracket) {
                array_push($stack, $token);
            }
            if ($token instanceof TokenComma) {
                while (($current = array_pop($stack)) && (!$current instanceof TokenLeftBracket)) {
                    $output[] = $current;
                    if (empty($stack)) {
                        throw new IncorrectExpressionException();
                    }
                }
            }
            if ($token instanceof TokenRightBracket) {
                while (($current = array_pop($stack)) && (!$current instanceof TokenLeftBracket)) {
                    $output[] = $current;
                }
                if (!empty($stack) && ($stack[count($stack)-1] instanceof TokenFunction)) {
                    $output[] = array_pop($stack);
                }
            }

            if ($token instanceof AbstractOperator) {
                while (
                    count($stack) > 0 &&
                    ($stack[count($stack)-1] instanceof InterfaceOperator) &&
                    (
                        $token->getAssociation() == AbstractOperator::LEFT_ASSOC &&
                        $token->getPriority() <= $stack[count($stack)-1]->getPriority()
                    ) || (
                        $token->getAssociation() == AbstractOperator::RIGHT_ASSOC &&
                        $token->getPriority() < $stack[count($stack)-1]->getPriority()
                    )
                ) {
                    $output[] = array_pop($stack);
                }

                array_push($stack, $token);
            }
        }
        while (!empty($stack)) {
            $token = array_pop($stack);
            if ($token instanceof TokenLeftBracket || $token instanceof TokenRightBracket) {
                throw new IncorrectBracketsException();
            }
            $output[] = $token;
        }

        return $output;
    }
}
