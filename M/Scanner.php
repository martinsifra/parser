<?php

namespace M;

use Nette\Utils\Tokenizer,
    Nette\Utils\TokenIterator;

/**
 * M language scanner, hey gimme some tokens!
 *
 * @author Martin Šifra <me@martinsifra.cz>
 */
class Scanner
{
    /**
     * Token types
     */
    const   T_EOL = 1,
            T_INDENT = 2,
            T_COLON = 9,
            T_OPEN_PARENTHESIS = 11,
            T_CLOSE_PARENTHESIS = 12,
            T_OPEN_CURLY = 13,
            T_CLOSE_CURLY = 14,
            T_IF = 15,
            T_ELSE = 16,
            T_ELSEIF = 17,
            T_RETURN = 18,
            T_STRING = 19,
            T_PARAMETER = 20,
            T_VARIABLE = 21,
            T_COMMENT = 22;
    
    
    /**
     * Token regex patterns
     * @var array
     */
    private static $patterns = [
        self::T_EOL => '\v+',
        self::T_COMMENT => '\h+#.*[^\v]',
        self::T_INDENT => '[ \t]+',
        self::T_IF => 'if\h+',
        self::T_ELSE => 'else\h*',
        self::T_ELSEIF => 'elif\h+',
        self::T_RETURN => 'return\h+',
        T_DNUMBER => '\d+',
        self::T_STRING => '".*"',
        self::T_PARAMETER => ':[a-zA-Z][a-zA-Z0-9_-]*',
        self::T_VARIABLE => '[a-zA-Z_][a-zA-Z0-9_-]*',
        555 => '[<]',
        565 => '[,=:\-\><\'"\.\+/\?\(\)]',
    ];
    
    
    
    /**
     * 
     * @param string $input
     * @return \Nette\Utils\TokenIterator
     */
    public static function scan($input)
    {
        $tokenizer = new Tokenizer(self::$patterns);
        $tokens = $tokenizer->tokenize($input);
    
        $iterator = new TokenIterator($tokens);
        
        return $iterator;
    }
}
