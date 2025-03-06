# Xml Templating for php

Generate dynamic xml strings using templated xml files.

TODO: add image after packagist submit

## Motivation 
> Avoid building dynamic xml strings with messy string concatenations in your code.<br>
> Avoid adding static boilerplate elements when using a useful package like <a href='https://github.com/spatie/array-to-xml'>ArrayToXml</a>.

## Usage

BASIC SETUP MET FILES EN STUFF 

```
Code usage example met array of object
```

Optional minification

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

- All if statements must be closed by an `end` directive

```handlebars
{{ if booleanValue }}
<conditionalcontent></conditionalcontent>
{{ end }}
```

**If else statements**

```handlebars
{{ if booleanValue }}
<True></True>
{{ else }}
<False></False>
{{ end }}
```

**Foreach statements**

```handlebars
{{ foreach countableVariable }}
<item>This will appear n=(length of countableVariable) times</item>
{{ end }}
```

**Foreach as statements**

```handlebars
{{ foreach arrayVariable as arrayItem }}
<item>The array contains: {{ var arrayItem }}</item>
{{ end }}
```

## Testing

Testing is done with phpunit and all tests are contained in the `tests/` directory. 

Run tests with:
```
vendor/bin/phpunit
```


