<?php

namespace LouisDj\XmlTemplate\Test;

use PHPUnit\Framework\TestCase;
use \LouisDj\XmlTemplate\XmlTemplate;

class Test extends TestCase
{
    public function test_variables()
    {
        $template = new XmlTemplate('tests/templates/testVars.xml');
        $subbed_template = @file_get_contents('tests/subbed_templates/testVars.xml');
        $subbed = $template->replaceWith([
            'firstname' => 'John',
            'lastname' => 'Cena'
        ]);

        $this->assertEquals($subbed_template, $subbed);
    }


    public function test_attrs()
    {
        $template = new XmlTemplate('tests/templates/testAttrs.xml');
        $subbed_template = @file_get_contents('tests/subbed_templates/testAttrs.xml');

        // test with arr
        $subbed = $template->replaceWith([
            'book' => [
                'name' => 'The Great Gatsby',
                'isbn' => '1234'
            ]
        ]);
        $this->assertEquals($subbed_template, $subbed);

        // test with obj
        $subbed = $template->replaceWith([
            'book' => new Book('The Great Gatsby', '1234')
        ]);

        $this->assertEquals($subbed_template, $subbed);
    }

    public function test_ifs()
    {
        $template = new XmlTemplate('tests/templates/testIf.xml');
        $subbed_template = @file_get_contents('tests/subbed_templates/testIf.xml');

        // test with arr
        $subbed = $template->replaceWith([
            'showUsername' => true,
            'showPassword' => (1 === 2),
            'personalEmail' => false,
            'bio' => true,
            'eng' => false,
        ]);

        $this->assertEquals($subbed_template, $subbed);
    }

    public function test_minified()
    {
        $template = new XmlTemplate('tests/templates/testMinified.xml');
        $subbed_template = @file_get_contents('tests/subbed_templates/testMinified.xml');
        $subbed_template = str_replace("\n", "", $subbed_template);

        // test with arr
        $subbed = $template->replaceWith([
            'description' => "This description should be minified as well \t Text after tab \n Text after line"
        ], true);

        $this->assertEquals($subbed_template, $subbed);
    }

    public function test_for()
    {

        $template = new XmlTemplate('tests/templates/testFor.xml');
        $subbed_template = @file_get_contents('tests/subbed_templates/testFor.xml');

        $subbed = $template->replaceWith([
            'venues' => [1, 2, 3],
            'bands' => [
                [
                    'name' => 'Van Coke Kartel',
                    'frontman' => 'Francois van Coke',
                    'members' => [
                        'Valkie van Coke',
                        'Wynand Myburgh'
                    ]
                ],
                [
                    'name' => 'Pink Floyd',
                    'frontman' => 'Syd Barret',
                    'members' => [
                        'Roger Waters',
                        'David Gilmour'
                    ],
                ],
            ]
        ]);

        echo $subbed;

        $this->assertEquals($subbed_template, $subbed);
    }
}


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
