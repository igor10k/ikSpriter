# ikSpriter 0.1

PHP script with html front-end to generate packed CSS sprites.

## Features
* Uses texture packing algorithm that is often used in game industry (thanks to @jakesgordon for [that one](https://github.com/jakesgordon/sprite-factory), had to learn ruby syntax to convert it to PHP)
* Supports CSS, LESS, Stylus syntax
* Mixins for LESS and Stylus as an option

## Script takes
```php
$_POST['prefix'] = "sprite_" // the prefix used for sprite classes
$_POST['type'] = "stylus" ("css", "lessm", "stylusm") // type of the css to be returned
$_POST['group'] // if isset then group selectors for the image declaration
```

## Script returns JSON
```javascript
{
	"width":100, // sprite width in pixels
	"height":100, // sprite height in pixels
	"url":"/sprite.png", // the actual location of the sprite image
	"oldSize":384177, // the overall size in Bytes of all images that were processed
	"newSize":429927, // the size in Bytes of the sprite image
	"css":"..." // formatted CSS
}
```

**By default PHP has a limit for file uploads at 20 files a time. Add something like `max_file_uploads = 500` to your php.ini if you want to be able to process more files.**


There are still a lot of optimizations can be done, but I realized that this will never be published if I will wail till it's perfect. It works and does its job and I'm satisfied with that.
