Sidux PHP Generator
===================
![Code Analysis](https://github.com/sidux/php-generator/workflows/Code%20Analysis/badge.svg)
[![Coverage Status](https://coveralls.io/repos/github/sidux/php-generator/badge.svg?branch=master)](https://coveralls.io/github/sidux/php-generator?branch=master)
![Type Coverage Status](https://shepherd.dev/github/sidux/php-generator/coverage.svg)
![Dependabot](https://badgen.net/github/dependabot/sidux/php-generator)

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
$class = new Sidux\PhpGenerator\Model\Struct('Demo');

$class
    ->setFinal()
    ->setExtends('ParentClass')
    ->addImplement('Countable')
    ->addTrait('Foo\Bar')
    ->addComment("Description of class.\nSecond line\n")
    ->addComment('@property-read Sidux\Forms\Form $form');

// to generate PHP code simply cast to string or use echo:
echo $class;
