<?php

namespace LouisDj\XmlTemplate;

class Variable
{
    public string $name;
    public ?Variable $attr;

    function __construct(string $rawText)
    {
        $attrStartIndex = strpos($rawText, '.');
        if ($attrStartIndex) {
            $this->name = substr($rawText, 0, $attrStartIndex);
            $this->attr = new Variable(substr($rawText, $attrStartIndex + 1));
        } else {
            $this->name = $rawText;
            $this->attr = null;
        }
    }

    function resolve(array $variableValueMap)
    {
        $firstLevel = $variableValueMap[$this->name];

        // resolve attrs 
        if ($this->attr) {
            $attrName = $this->attr->name;
            $firstLevel = (object) $firstLevel;
            $variableValueMap[$attrName] = $firstLevel->$attrName;
            return $this->attr->resolve($variableValueMap);
        }
        return $firstLevel;
    }
}
