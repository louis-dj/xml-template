<?php

namespace LouisDj\XmlTemplate;

class XmlTemplate
{

    private string $rawXml;

    public function __construct(
        string $filePath
    ) {

        if (realpath($filePath)) {
            $filePath = realpath($filePath);
        } else {
            $filePath = getcwd() . DIRECTORY_SEPARATOR . $filePath;
        }

        $fileContent = @file_get_contents($filePath);

        if (! $fileContent) {
            throw new \Exception("Invalid file path provided: Could not read file contents\n filepath: $filePath");
        }

        $this->rawXml = $fileContent;
    }

    private function safeAccess(int $index, string $xmlString): string
    {
        if ($index >= strlen($xmlString) || $index < 0) {
            return "\0";
        } else {
            return $xmlString[$index];
        }
    }

    private function nextIdentifier(int &$i, string $xmlString): string
    {
        $id = '';
        $found = false;
        for ($i; $i < strlen($xmlString); $i++) {
            $chr = $this->safeAccess($i, $xmlString);
            if ($chr === ' ' && $found) {
                $i++;
                return $id;
            } else if ($chr === ' ') {
                continue;
            } else {
                $found = true;
                $id .= $chr;
            }
        }
        return $id;
    }

    private function nextToken(int &$i, string $xmlString): Token | false
    {

        // Get next pair of opening braces
        $tokenStart = strpos($xmlString, '{{', $i);
        if (!$tokenStart && $tokenStart !== 0) {
            return false;
        }
        $i = $tokenStart + 2;

        // Find corresponding closing braces
        $tokenEnd = strpos($xmlString, '}}', $tokenStart);
        if (! $tokenEnd) {
            throw new \Exception('Invalid syntax: Brackets starting at chr ' . $tokenStart . ' never closed');
        }

        // Identify token 
        $id = $this->nextIdentifier($i, $xmlString);
        try {
            $tokenType = TokenType::from($id);
        } catch (\Exception) {
            throw new \Exception('Invalid Identifier at chr ' . $tokenStart);
        }

        // All tokens except else and end needs a variable
        if (! ($tokenType === TokenType::ELSE || $tokenType === TokenType::END)) {
            $variableName = $this->nextIdentifier($i, $xmlString);
        } else {
            $variableName = '';
        }

        // Get the current line start index
        $seek = 1;
        $currentLineStart = $tokenEnd - $seek;
        while ($this->safeAccess($currentLineStart, $xmlString) != "\n" && $this->safeAccess($currentLineStart, $xmlString) != "\0") {
            $seek++;
            $currentLineStart = $tokenEnd - $seek;
        }
        // Move to the character after the newline character
        if ($currentLineStart != 0) {
            $currentLineStart++;
        }

        // Create token object
        $token = new Token(
            type: $tokenType,
            start: $tokenStart,
            end: $tokenEnd + 2,
            currentLineStart: $currentLineStart,
            variable: new Variable($variableName),
        );

        // Return token
        return $token;
    }


    private function replaceVariable(
        Token $token,
        array $variableValueMap,
        string $xmlString,
    ): string {
        // Get the text to substitute in 
        try {
            $replaceWith = $token->variable->resolve($variableValueMap);
        } catch (\Exception) {
            throw new \Exception('No variable provided for \'' . $token->variable->name . '\'');
        }

        // Substitute text
        return substr_replace($xmlString, $replaceWith, $token->start, $token->end - $token->start);
    }

    private function replaceIf(
        Token $ifToken,
        array $variableValueMap,
        string $xmlString,
    ): string {

        // Define an index 
        $i = $ifToken->end;

        // Get boolean value 
        try {
            $boolValue = $ifToken->variable->resolve($variableValueMap);
        } catch (\Exception) {
            throw new \Exception('No variable provided for \'' . $ifToken->variable->name . '\'');
        }

        // Find next valid tokens while accounting for nesting
        $openIfs = 0;
        $nextToken = $this->nextToken($i, $xmlString);
        while ($nextToken) {
            if ($nextToken->type === TokenType::IF || $nextToken->type === TokenType::FOREACH) {
                $openIfs++;
            } else if ($nextToken->type === TokenType::END && $openIfs > 0) {
                $openIfs--;
            } else if (($nextToken->type === TokenType::END || $nextToken->type === TokenType::ELSE) && $openIfs === 0) {
                break;
            }
            $nextToken = $this->nextToken($i, $xmlString);
        }

        // Exit if if not closed
        if (!$nextToken) {
            throw new \Exception('Invalid syntax: if statement not closed starting at ch ' . $ifToken->start);
        }

        // Parse single if
        if ($nextToken->type === TokenType::END) {

            // Rename for clarity
            $endToken = $nextToken;

            // Evaluate expression
            if ($boolValue) {
                $xmlString = substr_replace($xmlString, trim(substr($xmlString, $ifToken->end, $endToken->start - $ifToken->end), "\r\n"), $ifToken->currentLineStart, $endToken->end - $ifToken->currentLineStart);
            } else {
                $xmlString = substr_replace($xmlString, '', $ifToken->currentLineStart, ($endToken->end + 1) - $ifToken->currentLineStart);
            }
        }
        // Parse if else
        else if ($nextToken->type === TokenType::ELSE) {

            // Rename for clarity
            $elseToken = $nextToken;

            // Get end token
            $endToken = $this->nextToken($i, $xmlString);
            while ($endToken && $endToken->type !== TokenType::END) {
                $endToken = $this->nextToken($i, $xmlString);
            }

            // Check if never closed
            if (!$endToken) {
                throw new \Exception('Invalid syntax: if else statement not closed starting at chr ' . $ifToken->start);
            }

            // Evaluate expression
            if ($boolValue) {
                $xmlString = substr_replace($xmlString, trim(substr($xmlString, $ifToken->end, $elseToken->start - $ifToken->end), "\r\n"), $ifToken->currentLineStart, $endToken->end - $ifToken->currentLineStart);
            } else {
                $xmlString = substr_replace($xmlString, trim(substr($xmlString, $elseToken->end, $endToken->start - $elseToken->end), "\r\n"), $ifToken->currentLineStart, $endToken->end - $ifToken->currentLineStart);
            }
        }

        return $xmlString;
    }

    public function replaceForeach(
        Token $forToken,
        array $variableValueMap,
        string $xmlString,
    ): string {

        // Define an index
        $i = $forToken->start;

        // Hacky, but seek until after variable name
        $this->nextIdentifier($i, $xmlString);
        $this->nextIdentifier($i, $xmlString);
        $this->nextIdentifier($i, $xmlString);
        $asKeyword = $this->nextIdentifier($i, $xmlString);

        // Replace child variable if present
        $childVariableName = '';
        if ($asKeyword === 'as') {
            $childVariableName = $this->nextIdentifier($i, $xmlString);
            if (! $childVariableName) {
                throw new \Exception("Invalid syntax: Expected variable name after keyword 'as' at chr $i");
            }
        }

        // Get end token while accounting for nesting
        $openIfs = 0;
        $endToken = $this->nextToken($i, $xmlString);
        while ($endToken) {

            if ($endToken->type === TokenType::IF || $endToken->type === TokenType::FOREACH) {
                $openIfs++;
            } else if ($endToken->type === TokenType::END && $openIfs > 0) {
                $openIfs--;
            } else if ($endToken->type === TokenType::END && $openIfs === 0) {
                break;
            }
            $endToken = $this->nextToken($i, $xmlString);
        }

        if (!$endToken) {
            throw new \Exception("Invalid syntax: Expected end token after foreach token starting at chr $forToken->start");
        }

        // Build replacing string
        $replacedString = "";
        $toReplaceChunk = substr($xmlString, $forToken->end, $endToken->currentLineStart - $forToken->end);
        $toLoopThrough = $forToken->variable->resolve($variableValueMap);
        foreach ($toLoopThrough as $childValue) {

            // Add child variable to variable map
            if ($childVariableName) {
                $variableValueMap[$childVariableName] = $childValue;
            }

            // Replace the chunk of string and add to total
            $thisIteration =  $this->replaceWith($variableValueMap, false, $toReplaceChunk);
            $replacedString .=  trim($thisIteration, "\r\n") . "\n";
        }

        // Replace tokens
        return substr_replace($xmlString, trim($replacedString, "\r\n"), $forToken->currentLineStart, $endToken->end - $forToken->currentLineStart);
    }


    public function replaceWith(
        array $variableValueMap,
        bool $minified = false,
        string $xmlString = ''
    ): string {

        // Initialise string and index
        if (!$xmlString) {
            $xmlString = $this->rawXml;
        }
        $i = 0;

        // Parse all tokens in xml
        while (true) {
            $token = $this->nextToken($i, $xmlString);
            if (!$token) {
                break;
            }

            // Handle each token
            $xmlString = match ($token->type) {
                TokenType::VARIABLE => $this->replaceVariable($token, $variableValueMap, $xmlString),
                TokenType::IF => $this->replaceIf($token, $variableValueMap, $xmlString),
                TokenType::FOREACH => $this->replaceForeach($token, $variableValueMap, $xmlString),
                default => $xmlString,
            };

            // Seek back to the start of the token
            $i = $token->start;
        }

        // Minify xml string
        if ($minified) {
            $xmlString = preg_replace('/\s+/', ' ', $xmlString);
            $xmlString = trim($xmlString);
        }

        return $xmlString;
    }
}

// TODO: docs:
    // TODO: nb , global scope, moet in documentation se
    // TODO: spaces are required
//  TODO: add to the docs a required thing that for if and else directives MUST be on their own line
// TODO: wat van 'n ander extension, met sy eie treesitter grammar ? 
//
//      -- DESCRIPTION
//
//      // -- MOTIVATION
//      // avoid building dynamic xml strings with clunky concatenations
//      // avoid adding all the static boilerplate with arrtoxml
//
//      -- DEMO (CODE EXAMPLE USAGE)
//      Gebruik net die complicated test
//
//      -- SYNTAX
//      Directives
//      Rules
//
//      -- TESTING & other admin thingies 
//
