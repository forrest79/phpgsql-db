PhPgSql
=======

[![Latest Stable Version](https://poser.pugx.org/forrest79/phpgsql/v)](//packagist.org/packages/forrest79/phpgsql)
[![Monthly Downloads](https://poser.pugx.org/forrest79/phpgsql/d/monthly)](//packagist.org/packages/forrest79/phpgsql)
[![License](https://poser.pugx.org/forrest79/phpgsql/license)](//packagist.org/packages/forrest79/phpgsql)
[![Build](https://github.com/forrest79/PhPgSql/actions/workflows/build.yml/badge.svg?branch=master)](https://github.com/forrest79/PhPgSql/actions/workflows/build.yml)

Simple and fast PHP database library for PostgreSQL with auto converting DB types to PHP and a powerfull fluent interface that can be used to simply create most of SQL queries using PHP.

- lightweight
- no magic
- no database structure reading
- automatically convert PG data types to PHP data types
- support async connect to DB and async query
- simple creating queries with parameters - char `?` for variable is automatically replaced with `$1`, `$2` and so...
  - as variable you can pass scalar, bool, array, literal or other query

DB and fluent part can be used separately.


## Installation

The recommended way to install PhPgSql is through Composer:

```sh
composer require forrest79/phpgsql
```

PhPgSql requires PHP 7.1.0 and pgsql binary extension. It doesn't work with PDO!

If you're using [PHPStan](https://phpstan.org/) you can install [settings](https://github.com/forrest79/PhPgSql-PHPStan) for this great tool. 

```sh
composer require --dev forrest79/phpgsql-phpstan
```

And if you're using [Nette framework](https://nette.org/), there is existing integration with [Tracy](https://tracy.nette.org/) panel.


## Documentation

[Complete documentation](doc/index.md) is in `docs` directory and has self-tested examples.
