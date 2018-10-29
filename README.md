Jasny validation
===

[![Build Status](https://travis-ci.org/jasny/validation.svg?branch=master)](https://travis-ci.org/jasny/validation)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/jasny/validation/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/jasny/validation/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/jasny/validation/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/jasny/validation/?branch=master)
[![BCH compliance](https://bettercodehub.com/edge/badge/jasny/validation?branch=master)](https://bettercodehub.com/)
[![Packagist Stable Version](https://img.shields.io/packagist/v/jasny/validation.svg)](https://packagist.org/packages/jasny/validation)
[![Packagist License](https://img.shields.io/packagist/l/jasny/validation.svg)](https://packagist.org/packages/jasny/validation)

This library is intended for validating object properties' values. Validation is performed with respect to meta-information, taken from reflection and from doc-comments of class and it's properties.

Install
---

    composer require jasny/validation

Usage
---

* First we obtain a metadata for given class
* Then we pass obtained metadata to a validator constructor
* Call `validate` method, passing class instance as parameter

Here's a standard example of using validation:

```php
use Jasny/Validation;

$validation = new Validation($meta);
$result = $validation->validate($object);
```

Here:
* `$meta` is an instance of `Jasny/Meta/MetaClass`. It is described in [Jasny Meta](https://github.com/jasny/meta) repo.
* `$object` is an instance of class, for which `$meta` is obtained
* `$result` is an instance of `Jasny/ValidationResult`, described in [Jasny Validation Result](https://github.com/jasny/validation-result) repo
