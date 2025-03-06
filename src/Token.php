<?php

namespace LouisDj\XmlTemplate;

class Token
{
    public $type;
    public $start;
    public $end;
    public $currentLineStart;
    public $variable;

    public function __construct(
        TokenType $type,
        int $start,
        int $end,
        int $currentLineStart,
        Variable $variable,
    ) {
        $this->type = $type;
        $this->start = $start;
        $this->end = $end;
        $this->currentLineStart = $currentLineStart;
        $this->variable = $variable;
    }
}
