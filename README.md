XSLT2Processor
==============

***WARNING:** This project is in **alpha** state, and as such, does not have any stable release and partial support. Use at your own risk, but feel free to contribute via pull requests, bug issues or feature requests.*

XSLT2Processor is a pure PHP library for processing XSLT stylesheets up to version 2.0 and XPath expressions. It offers support for:

- XPath syntax parsing, evaluation and query
- Standard XPath function library and EXSLT function library
- XSLT v2.0 stylesheets execution

## Requirements

This library requires:
- PHP 5.6 or greater
- PHP Extension mbstring
- PHP Extension xsl 


## Installation

### Composer
To install the library using composer, execute the following line in your favourite terminal, at the root of your project:

```
$ php composer.phar require jdomenechb/xslt2processor
```

## Usage

### XSLT

To use XSLT2Processor in your code:

```
$processor = new Jdomenechb\XSLT2Processor\XSLT\Processor($xslt, $xml);
$result = $processor->transformXML();
```

`$xslt` and `$xml` are `DOMDocument` objects, each one containing the XSLT stylesheet and the XML to be transformed, respectivelly.

#### Caching
To speed up the transformation, a caching class following the [PSR-6 recommendation](http://www.php-fig.org/psr/psr-6/) `CacheItemPoolInterface` can be provided to the processor to avoid parsing again expressions that have already been parsed. Note that it should be provided before processing.

```
$processor->setCache($cacheItemPool);
```

### XPath
The full list of available XPath classes can be found under [the XPath src folder](src/XPath). But generally, a developer might be more interested in parsing a string that represents an XPath. For this goal, the `Factory` class may be more useful:
 
```
$factory = new Jdomenechb\XSLT2Processor\XPath\Factory;
$xPath = $factory->create('/*/some/x-path[representation = 1]');
```

Once you have parsed or build your xPath, you can evaluate or query the expression by executing:

```
// Evaluates the expression
$evaluationResult = $xPath->evaluate($context);

// Queries the nodes represented by the expression
$queryResult = $xPath->query($context);
```

`$content` can be any element extending the PHP class `DOMNode`  (http://php.net/manual/en/class.domnode.php).
