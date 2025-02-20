<?php


class XmlTemplate
{

    private string $rawXml;

    public function __construct(
        string $filePath
    ) {
        $fileContent = @file_get_contents($filePath);

        if (! $fileContent) {
            throw new Exception('Invalid file path provided: Could not read file contents');
        }

        $this->rawXml = $fileContent;
    }

    private function safeAccess(int $index, string $xmlString): string
    {
        if ($index >= strlen($xmlString)) {
            return '\0';
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
        if (!$tokenStart) {
            return false;
        }
        $i = $tokenStart + 2;

        // Find corresponding closing braces
        $tokenEnd = strpos($xmlString, '}}', $tokenStart);
        if (! $tokenEnd) {
            throw new Exception('Invalid syntax: Brackets starting at chr ' . $tokenStart . ' never closed');
        }

        // Identify token 
        $id = $this->nextIdentifier($i, $xmlString);
        try {
            $tokenType = TokenType::from($id);
        } catch (Exception) {
            throw new Exception('Invalid Identifier at chr ' . $tokenStart);
        }

        // All tokens except else needs a variable
        if (! ($tokenType === TokenType::ELSE)) {
            $variableName = $this->nextIdentifier($i, $xmlString);
        } else {
            $variableName = '';
        }

        // Create token object
        $token = new Token(
            type: $tokenType,
            start: $tokenStart,
            end: $tokenEnd + 2,
            variableName: $variableName,
        );

        // Return token
        return $token;
    }


    private function replaceVariable(
        Token $token,
        array $variableValueMap,
        string $xmlString,
    ) {
        // Get the text to substitute in 
        try {
            $replaceWith = $variableValueMap[$token->variableName];
        } catch (Exception) {
            throw new Exception('No string variable provided for \'' . $token->variableName . '\'');
        }

        // Substitute text
        return substr_replace($xmlString, $replaceWith, $token->start, $token->end - $token->start);
    }


    public function replaceWith(
        array $variableValueMap,
        bool $minified = false,
    ): string {

        // Initialise string and index
        $xmlString = $this->rawXml;
        $i = 0;

        // Parse all tokens in xml
        while (true) {
            $token = $this->nextToken($i, $xmlString);
            if (!$token) {
                break;
            }

            $xmlString = match ($token->type) {
                TokenType::VARIABLE => $this->replaceVariable($token, $variableValueMap, $xmlString),
                default => $xmlString,
            };
        }

        return $xmlString;
    }
}


class Token
{
    public $type;
    public $start;
    public $end;
    public $variableName;

    public function __construct(
        TokenType $type,
        int $start,
        int $end,
        string $variableName,
    ) {
        $this->type = $type;
        $this->start = $start;
        $this->end = $end;
        $this->variableName = $variableName;
    }
}

enum TokenType: string
{
    case VARIABLE = 'var';
    case IF = 'if';
    case ELIF = 'elif';
    case ELSE = 'else';
    case FOREACH = 'foreach';
}
