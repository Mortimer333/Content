# Content
Allows for proper iteration and actions on multibyte strings

Note: currently supports only UTF-8

# Why?
If you were to iterate over this string from file encoded with UTF-8:
```php
$string = "óźćżó→ę";
for ($i=0; $i < strlen($string); $i++) {
    echo $string[$i] . ', ';
}
```

Your output will be:
```
�, �, �, �, �, �, �, �, �, �, �, �, �, �, �,
```
And thats because UTF-8 stores most of their symbols on multiple bytes and PHP access one at time.
To properly iterate over this kind of string you have to manually separate string into proper letters.
Or use this library:
```php
$content = new Content\Utf8("óźćżó→ę");
for ($i=0; $i < $content->getLength(); $i++) {
    echo $content->getLetter($i) . ', ';
}
```
Output:
```
ó, ź, ć, ż, ó, →, ę,
```

# Performance
Creating new classes is expensive, so you can create only one instance of `Content` and just add new versions/new strings to it:

```php
$content = new Content\Utf8("óźćżó→ę");
echo "Version 1: " . $content; // óźćżó→ę

$content->cutAndAddContent("ÐÑÒÓÔ");
echo "Version 2: " . $content; // ÐÑÒÓÔ

// If you don't specify constent keeps his older version in memory if it might be needed later
// (to skip the expensive operation of cutting the same string into chunks again)
$content->removeContent();
echo "Version 1: " . $content; // óźćżó→ę
```

This future certainly lacks some flexibility (like assigning ID to version or being able to retrieve version without removing the current one), but this will be added in the future.

# Actions

## To String

Class implements `_toString` method so any instance can be just cast to string:
```php
$string = new Content\Utf8('Foo');
echo (string) $string; // Foo
```

## subStr
You can also just retrieve part of the content by using `subStr` or `iSubStr`:
```php
$string = new Content\Utf8('Foo and Bar');
// By index and length
echo $string->subStr(0, 3); // Foo
// From index to index
echo $string->iSubStr(8, 10); // Bar
```

## cutToContent

Similar to `subStr`, `cutToContent` returns part of the string but in form of new Content:
```php
$string = new Content\Utf8('Foo and Bar');
// By index and length
echo $string->cutToContent(0, 3); // Utf8(Foo)
// From index to index
echo $string->iCutToContent(8, 10); // Utf8(Bar)
```

## cutToArray

Just like `subStr` and `cutToContent` but returns chunked part of string in form of array:
```php
$string = new Content\Utf8('Foo and Bar');
// By index and length
var_dump($string->cutToArray(0, 3)); // Array("F", "o", "o")
// From index to index
var_dump($string->iCutToArray(8, 10)); // Array("B", "a", "r")
```


## trim
You can also trim your content if needed (it will return trimed version, not replace existing one):
```php
$string = new Content\Utf8('==Foo and Bar==');
echo (string) $string->trim("="); // Foo and Bar;
```

## splice

If you want to remove part of the string from current version or replace it/inject new string to it you can use `splice` method:

```php
$string = new Content\Utf8('Foo #error Bar');
$string->splice(4, 6, 'and');                   // Foo and Bar
echo (string) $string->iSplice(4, 6, 'or');     // Foo or Bar
```

## find

IF you need to find index of some needle you can use method `find`:
```php
$string = new Content\Utf8('Foo findme Bar');
echo "`findme` starts at index " . $string->find('findme'); // `findme` starts at index 9
```

## reverse

If you need to reverse your string you can use method `reverse`:
```php
$string = new Content\Utf8('ź = ć');
echo (string) $string->reverse(); // ć = ź
```
