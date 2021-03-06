<?php

namespace DesignPatterns\Behavioral\Interpreter;

include_once 'Parser.php';

class Lexer
{
    const ARGUMENT_DELIMITER = ',';

    //Maximum function nesting level of '100'
    const NESTING_MAX = 20;

    protected $nesting = 0;

    /**
     * @var Parser
     */
    protected $parser = null;

    /**
     * @var array
     */
    protected $tokens = [];

    function __construct($params = array())
    {
        if (isset($params['parser'])) {
            $this->setParser($params['parser']);
        }
    }

    /**
     * @param Parser $parser
     */
    public function setParser(Parser $parser)
    {
        $this->parser = $parser;
    }

    /**
     * @return Parser
     */
    public function getParser()
    {
        return $this->parser;
    }

    public function reset()
    {
        $this->tokens = [];
    }

    protected function resetNesting()
    {
        $this->nesting = 0;
    }

    protected function checkNesting()
    {
        if (self::NESTING_MAX <= $this->nesting) {
            throw new \Exception('Maximum function nesting level of '.self::NESTING_MAX);
        }
    }

    protected function incrementNesting()
    {
        $this->nesting++;
    }

    /**
     * @param $code
     * @return mixed
     */
    public function setFunction($code)
    {
        $function = $this->parseFunction($code);
        $token = $this->getParser()->setFunction(
            $code,
            $function['name'],
            $function['arguments']
        );
        return $token;
    }

    public function parseFunction($function)
    {
        if (preg_match_all('/([A-Za-z_][A-Za-z0-9_]*)(\(.*\))/', $function, $matches)) {
            return array(
                'name'      => $matches[1][0],
                'arguments' => $this->parseArguments($matches[2][0])
            );
        }
    }

    /**
     * @param string $arguments
     * @return array
     */
    public function parseArguments($arguments)
    {
        $arguments = $this->removeParentheses($arguments);
        $arguments = explode(self::ARGUMENT_DELIMITER, $arguments);
        $arguments = array_map(function($argument){
            return $this->getParser()->variable(trim($argument));
        }, $arguments);

        return $arguments;
    }

    /**
     * @param $key
     * @param mixed $default
     * @return mixed
     */
    public function getFunction($key, $default = null)
    {
        return isset($this->tokens[$key])
             ? $this->tokens[$key] : $default;
    }

    /**
     * Get code with string, changes it to special tag and return
     * @param string $code
     * @return string
     */
    public function parseScalarText($code)
    {
        $code = preg_replace_callback("/'([^']|\n)*'/s", function($original){
            $text = $this->removeQuotationMarks($original[0]);
            return $this->getParser()->setScalarText($text, $original[0]);
        }, $code);

        return $code;
    }

    /**
     * @param string $text
     * @return string
     */
    protected function removeQuotationMarks($text)
    {
        $lastIndex = strlen($text) - 1;
        if ($text[0] === "'" && $text[$lastIndex] === "'") {
            $text = substr($text, 1);
            $text = substr($text, 0, $lastIndex - 1);
        }

        return $text;
    }

    /**
     * @param string $text
     * @return string
     */
    protected function removeParentheses($text)
    {
        $lastIndex = strlen($text) - 1;
        if ($text[0] === "(" && $text[$lastIndex] === ")") {
            $text = substr($text, 1);
            $text = substr($text, 0, $lastIndex - 1);
        }

        return $text;
    }

    /**
     * @param string $code
     * @return Parser
     */
    public function code($code)
    {
        $code = $this->parseScalarText($code);
        $code = $this->removeUnnecessarySymbols($code);
        $code = $this->parseFunctions($code);
        $firstLineOfTree = $this->splitTokens($code);

        $this->getParser()->setTree($firstLineOfTree);
        return $this->getParser();
    }

    /**
     * @param string $code
     * @return array
     */
    public function splitTokens($code)
    {
        return preg_split('/ /', $code);
    }

    /**
     * @param string $code
     * @return string mixed
     */
    public function removeUnnecessarySymbols($code)
    {
        $code = preg_replace('/\s+/', ' ', $code);

        return $code;
    }

    /**
     * @param string $code
     * @return string
     */
    public function parseFunctions($code)
    {
        $this->checkNesting();
        $this->incrementNesting();

        if (preg_match_all('/[A-Za-z_]+\([^(^)]*\)/', $code, $matches)) {
            foreach ($matches[0] as $function) {
                $token = $this->setFunction($function);
                $code = str_replace($function, $token, $code);
            }

            $code = $this->parseFunctions($code);
        }

        $this->resetNesting();

        return $code;
    }
}