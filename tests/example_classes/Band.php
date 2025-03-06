<?php

namespace LouisDj\XmlTemplate\Test\ExampleClasses;

class Band
{
    public string $name;
    public string $frontman;
    public array $members;

    public function __construct(string $name, string $frontman, array $members)
    {
        $this->name = $name;
        $this->frontman = $frontman;
        $this->members = $members;
    }
}
