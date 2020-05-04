Sidux PHP Generator
===================

Introduction
------------

Generate PHP code, classes, namespaces etc. with a simple programmatical API.

Installation
------------

The recommended way to install is via Composer:

```
composer require sidux/php-generator
```

Usage
-----

Usage is very easy. Let's start with generating class:

```php
$class = new Sidux\PhpGenerator\Model\PhpStruct('Demo');

$class
    ->setFinal()
    ->setExtends('ParentClass')
    ->addImplement('Countable')
    ->addTrait('Foo\Bar')
    ->addComment("Description of class.\nSecond line\n")
    ->addComment('@property-read Sidux\Forms\Form $form');

// to generate PHP code simply cast to string or use echo:
echo $class;
