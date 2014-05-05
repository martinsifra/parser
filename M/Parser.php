<?php

namespace M;

/**
 * M language parser
 *
 * @author Martin Šifra <me@martinsifra.cz>
 */
class Parser
{
    
    /** @var array */
    private $parameters;
    
    /** @var Nette\Utils\TokenIterator */
    private $iterator;
    
    /** @var string */
    private $php;
    
    /** @var array Stack of indentation height */
    private $indents = [];
    
    /** @var int Indentation size of actual row in returned PHP code */
    private $phpIndent = 0;
    
    /**
     * Parse M source code to PHP
     * @param string $source M language source code
     */
    public function parse($source)
    {
        $this->iterator = Scanner::scan($source);
        
        $this->parseBlock(FALSE);
        
        return $this;
    }
    
    public function setParameters($parameters)
    {
        if (is_array($parameters)) {
            $this->parameters = $parameters;
        }
        
        return $this;
    }
    
    public function getPHP()
    {
        return $this->php;
    }
    
    
    private function parseBlock($newBlock = FALSE)
    {

        $closedBlocks = 0;

        // STATEMENT
        while ($this->iterator->nextToken()) {
            $tType = $this->iterator->currentToken()[Tokenizer::TYPE];

            if ($tType == self::T_IF) {
                $this->php .= 'if (';
                $this->php .= $this->iterator->joinUntil(self::T_EOL, self::T_COMMENT);
                $this->php .= ") {\n";
                
                $closedBlocks = $this->parse(TRUE);
                if ($closedBlocks > 1) {
                    $closedBlocks--;
                    break;
                }

                // ELSEIF (0-N repeat)
                while ($this->iterator->nextToken(self::T_ELSEIF)) {
                    $this->php .= 'elseif (';
                    $this->php .= $this->iterator->joinUntil(self::T_EOL, self::T_COMMENT);
                    $this->php .= ") {\n";

                    $closedBlocks = $this->parse(TRUE);
                    if ($closedBlocks > 1) {
                        $closedBlocks--;
                        break 2;
                    }
                }

                // T_ELSE
                if ($this->iterator->nextToken(self::T_ELSE)) {
                    if ($this->iterator->isNext(self::T_EOL, self::T_COMMENT)) {
                        $this->php .= "else {\n";

                        $closedBlocks = $this->parse(TRUE);
                        if ($closedBlocks > 1) {
                            $closedBlocks--;
                            break;
                        }
                        
                    } else {
                        $this->error('Syntax error.');
                    }
                }

            } elseif ($tType == self::T_EOL) {
                if ($this->iterator->isNext(self::T_INDENT) ) {
                    $newIndent = strlen($this->iterator->nextValue());

                    if ($this->iterator->isNext(self::T_EOL, self::T_COMMENT)) { // Skip empty lines
                        continue;
                    }
                    
                    $prevIndent = array_sum($this->indents);
                    
                    if ($newIndent > $prevIndent) {
                        if ($newBlock) {                        
                            $this->indents[] = $newIndent - $prevIndent;
                            
                            $this->phpIndent += 4;
                            $this->php .= str_repeat(' ', $this->phpIndent);
                            $newBlock = FALSE;

                        } else {
                            $this->error('Unexpected indentation. (more)');
                        }

                    } elseif ($newIndent == $prevIndent) {
                        $this->php .= str_repeat(' ', $this->phpIndent);
                        
                    } else {
                        if ($newBlock) { // A new indented block expected
                            $this->error('Expected an indented block.');
                        }

                        $diff = $prevIndent - $newIndent;
                        $count = 0;
                        $latestIndent = 0;

                        while ($latestIndent += array_pop($this->indents)) {
                            $count++;

                            if ($latestIndent < $diff) {
                                continue;
                            } elseif ($latestIndent == $diff) {

                                $closedBlocks = $count;

                                while ($count) {
                                    $this->phpIndent -= 4;
                                    $this->php .= str_repeat(' ', $this->phpIndent) . "}\n";
                                    $count--;
                                }

                                $this->php .= str_repeat(' ', $this->phpIndent);

                                break;
                            } else {
                                $this->error('Unexpected indentation. (less)');
                            }
                        }
                        break;
                    }
                } else {
                    if ($this->iterator->isNext(self::T_COMMENT)) {
                        continue;
                    }
                    
                    $closedBlocks = $count = count($this->indents);
                    $this->indents = [];

                    while ($count) {
                        $this->phpIndent -= 4;
                        $this->php .= str_repeat(' ', $this->phpIndent) . "}\n";
                        $count--;
                    }
                    break;
                }

            } elseif ($tType == self::T_INDENT) {
                $this->error('Unexcepted indentation. ');

            } elseif ($tType == self::T_RETURN) {
                $this->php .= 'return ' . $this->iterator->joinUntil(self::T_EOL, self::T_COMMENT) . ";\n";

            } elseif ($tType == self::T_PARAMETER) {
                $this->error('Parameter cannot not be L-value.');
//                $this->php .= '$_parameters["'.substr($this->iterator->currentValue(), 1).'"]';
                
            } elseif ($tType == self::T_VARIABLE) {
                $this->php .= $this->iterator->currentValue() . $this->iterator->joinUntil(self::T_EOL, self::T_COMMENT) . ";\n";
            
            } elseif ($tType == self::T_COMMENT) {
//                $this->php .= str_repeat(' ', $this->phpIndent) . '/* ' . $this->iterator->currentValue() . " */\n";
                
            } else {
                $this->error('Syntax error: Unexpected \'' . trim($this->iterator->currentValue()) .'\'.');
            }
        }

        return $closedBlocks;
    }
    
    
    private function error($message = "Unexpected '%s'")
    {
        throw new ParserException('Máš tam chybu, vole!');
    }

}



class ParserException extends \Exception
{    
}