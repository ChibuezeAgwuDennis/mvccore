# MvcCore
PHP MVC framework to develop and pack projects (partialy or completely) into super fast single file apps and tools.

## Main features
- MVC core framework for classic web apps with any request types and HTML/AJAX responses
- about 35% faster results from packed app then development version with separate PHP scripts and fastcgi/op_cache
- partial or complete application packaging into a single file
	- including only file extensions you want
	- or including all files (binary or text, doesn't metter)
- packing to PHAR package (slower) or to PHP single file (faster)
- main packing configuration features:
	- including/excluding folders by regular expression
	- result code regexp and string replacements
	- PHTML templates minification
	- PHP scripts minification
- minimization for PHP/HTML/CSS/JS by third party tools supported
- url rewrite with .htaccess or web.config
- posibility to use any third party library or framework in Libs folder

## Usage
- check out examples:
	- [**Hello World**](https://github.com/mvccore/example-helloworld)
	- [**Pig Latin Translator**](https://github.com/mvccore/example-translator)
	- [**CD Collection**](https://github.com/mvccore/example-cdcol)
- begin with Hello world example and read the code:
	- MvcCore frameworks has only 3 files (647 lines, including comments, cca 215 lines per file)
	- Hello world example has only 2 important controllers (Default.php and Base.php)
- to develop application - work in development directory
- to build single file application - use make.cmd and configure build proces in php.php or phar.php
- test your builded application in release directory