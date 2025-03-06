<?php

namespace LouisDj\XmlTemplate\Test\ExampleClasses;

class Book
{

    public string $name;
    public string $isbn;

    function __construct(
        string $name,
        string $isbn
    ) {
        $this->name = $name;
        $this->isbn = $isbn;
    }
}
