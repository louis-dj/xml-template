# Xml Templating for php

Generate dynamic xml strings using templated xml files.

![Screenshot 2025-03-06 at 21 10 39](https://github.com/user-attachments/assets/80df3472-e26c-4295-aa56-b303da3ccebb)

## Motivation 
> Avoid building dynamic xml strings with messy string concatenations in your code.<br>
> Avoid adding static boilerplate elements when using a useful package like <a href='https://github.com/spatie/array-to-xml'>ArrayToXml</a>.

## Usage

Install the package with composer: 

```
composer install louis-dj/xml-template
```

Create an xml file with templating directives

```handlebars
<xml>
  {{ var randomVar }}
</xml>
```

Import the package and convert with the `replaceWith` method

```php
<?php
use LouisDj\XmlTemplate\XmlTemplate;

$template = new XmlTemplate('./test.xml');
$output = $template->replaceWith(['randomVar' => 'Some string']);
```

## Features

- **Minified**: The `replaceWith` method has an optional boolean parameter `minified` to remove all newline characters from the xml
- **Object & Array compatibility**: The associative array passed to the `replaceWith` method can contain string keys OR objects with field names corresponding to the variables OR both
- See the syntax below for full feature set

## Syntax & Rules

- All directives must be enclosed in `{{}}` and contain a space on the inside of both pairs
- Variable names use a global scope, even variables defined in `foreach` directives
- All directives can be freely nested as long as it is property closed with an `end` directive
- `if`, `else`, `foreach` and `end` directives must be on their own line, but may be indented.

**Variable Directives**
```handlebars
<xml>{{ var myVariableName }}</xml>
```

**If statements**

- All `if` statements must be closed by an `end` directive

```handlebars
{{ if booleanValue }}
<conditionalcontent></conditionalcontent>
{{ end }}
```

**If else statements**

- All `if else` statements must be closed by an `end` directive

```handlebars
{{ if booleanValue }}
<True></True>
{{ else }}
<False></False>
{{ end }}
```

**Foreach statements**

- All `foreach` statements must be closed by an `end` directive

```handlebars
{{ foreach countableVariable }}
<item>This will appear n=(length of countableVariable) times</item>
{{ end }}
```

**Foreach as statements**

- All `foreach as` statements must be closed by an `end` directive

```handlebars
{{ foreach arrayVariable as arrayItem }}
<item>The array contains: {{ var arrayItem }}</item>
{{ end }}
```

**Attribute access**

- The dot (.) operator works to access associative array keys' values too.

```handlebars
<xml>{{ var someObjectOrArray.attrName }}</xml>
```

**Pass in Objects to the `replaceWith` method**

- View `tests/Test.php` for more detailed use cases 

```php
$subbed = $template->replaceWith([
  'book' => new Book('The Great Gatsby', '1234')
]);
```

## Testing

Testing is done with phpunit and all tests are contained in the `tests/` directory. 

Run tests with:
```
vendor/bin/phpunit
```


