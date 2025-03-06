<?php

namespace LouisDj\XmlTemplate;

enum TokenType: string
{
    case VARIABLE = 'var';
    case IF = 'if';
    case ELSE = 'else';
    case FOREACH = 'foreach';
    case END = 'end';
}
