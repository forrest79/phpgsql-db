# PhPgSql\Db

## Basics

### DB connection

First, we need to create a connection to PostgreSQL:

> Format for connection string is the same as for [pg_connect](http://php.net/manual/en/function.pg-connect.php) function.

```php
$connection = new Forrest79\PhPgSql\Db\Connection('host=localhost port=5432 dbname=test user=user1 password=xyz111 connect_timeout=5');
```

> Good habit is to use `connect_timeout` parameter because default value is infinite.

Pass `TRUE` as the second parameter to force new connection (otherwise, existing connection with the same parameters will be reused).

Pass `TRUE` as the third parameter to connect asynchronously (will be described later).

> Personal note: I'm thinking about removing this in then next big release.

You can create a blank `Connection` object and set connetion parameters on this object with functions `setConnectionConfig()`, `setConnectForceNew()` and `setConnectAsync()`. Just set this before `connect()` is executed.

```php
$connection = new Forrest79\PhPgSql\Db\Connection();
$connection->setConnectionConfig('host=localhost port=5432 dbname=test user=user1 password=xyz111 connect_timeout=5');
$connection->setConnectForceNew(TRUE);
$connection->setConnectAsync(TRUE);
```

For async connections, you can set timeout with `setConnectAsyncWaitSeconds()` method. Default value is 15 seconds.

```php
$connection = new Forrest79\PhPgSql\Db\Connection();
$connection->setConnectAsync(TRUE);
$connection->setConnectAsyncWaitSeconds(5);
```

Once you have a connection, you can manually connect it:

```php
$connection->connect();
```

When you omit this, connection is automatically connected to a DB, when some command is executed.

Connection can be manually closed:

```php
$connection->close();
```

> IMPORTANT: if you omit bad conection parameters, exception is thrown in the `connect()` function, not when connection string is set to the object.

Of course, you can get back info about actual configuration:

```php
$connectionConfig = $connection->getConnectionConfig();
```

You can check, if connection is connected:

```php
if ($connection->isConnected()) {
    // connection is connected
}
```

Even if connection is connected, there can be some network problem, server can close the connection, etc. To check this you can ping connection:

```php
if ($connection->ping()) {
    // connection is connected and active
}
```

If there is some error on database site, an exception is thrown. This library is not trying to parse database exceptions to some specific types (foreign key violation, ...). You will get error message right from the PostgreSQL and you can set format for this message.

```php
$connection->setErrorVerbosity(PGSQL_ERRORS_DEFAULT);
$connection->setErrorVerbosity(PGSQL_ERRORS_VERBOSE);
$connection->setErrorVerbosity(PGSQL_ERRORS_TERSE);
```

- `PGSQL_ERRORS_DEFAULT` is default and produces messages include severity, primary text, position, any detail, hint, or
  context fields
- `PGSQL_ERRORS_VERBOSE` includes all available fields
- `PGSQL_ERRORS_TERSE` returned messages include severity, primary text, and position only

> More about constants can be found at https://www.php.net/manual/en/function.pg-set-error-verbosity.php.

Last raised error can be also obtain with the method `getLastError()` (it's also in the format set with the `setErrorVerbosity()` method).

```php
try {
  $connection->query('SELECT bad_column');
} catch (Forrest79\PhPgSql\Db\Exceptions\QueryException $e) {}

$firstErrorLine = strtok($connection->getLastError(), "\n");
dump($firstErrorLine); // (string) 'ERROR:  column \"bad_column\" does not exist'
```

There is possibility to get real connection resource, that can be user in original `pg_*` functions with the method `getResource()`.

```php
dump(pg_ping($connection->getResource())); // (bool) TRUE
```

The final info that, that `Connection` object can't be serialized and deserialized.

### Running queries and getting results

So we have properly set connection, what do we need to know to execute an SQL query? Important is how to safely pass parameters into query. Bellow is whole chapter about it, for know, just use `?` on place, where you want to pass parameter.

Prepared statements and asynchronous queries will be described later.

The only function you need to know is `query()` (or `queryArgs()`). If you only use this one to execute queries, you won't make a mistake.

But there is another one function `execute()`. You can use this one, when you don't need a result from this query and also when you have no params to pass. Another advantage is, you can run more queries at once, just separate it with `;` (these queries are executed one by one in one statement/transaction and they are sending to PostgreSQL a little bit quicker (but really just a little bit)).

```php
$connection->execute('DELETE FROM user_departments WHERE id = 1; DELETE FROM user_departments WHERE id = 2');
```

Also with the `query()` method you can use the same queries separated with the `;` (but you will get result only for the last one and you can't use parameters). When you use `query()` without parameters, internally is used `pg_query()` function (the same when you call `execute()`), that is a little bit quicker to process (but again, just a little bit, you don't need to care about this much).

> The same is true also for `asyncQuery()` and `asyncExecute()`. When you use `asyncQuery` without parameters, you can also pass more queries separated with `;` and internally is used the same `pg_send_query()` function.

```php
$connection->query('DELETE FROM user_departments WHERE id = ?', 1);
$connection->queryArgs('DELETE FROM user_departments WHERE id = ?', [2]);
```

The difference between `query()` and `queryArgs()` is, that `query()` accepts many parameters and `queryArgs()` accept parameters in one `array`.

Passed variable can be scalar, `array` (is rewriten to many ?, ?, ?, ... - is usefull for example for `column IN (?)`), literal (is passed to SQL as string, never pass with this user input, possible SQL-injection), `bool`, `NULL` or another query.

To pass another query, we need to prepare one and then use it:

```php
$query = Forrest79\PhPgSql\Db\Sql\Query::create('SELECT id FROM users WHERE inserted_datetime::date > ?', '2020-01-02');
$queryArgs = Forrest79\PhPgSql\Db\Sql\Query::createArgs('SELECT id FROM users WHERE inserted_datetime::date > ?', ['2020-01-02']);

$result = $connection->query('SELECT d.id, d.name FROM user_departments ud JOIN departments d ON d.id = ud.department_id WHERE ud.user_id IN (?) AND d.active ORDER BY ?', $query, TRUE, Forrest79\PhPgSql\Db\Sql\Literal::create('d.id'));

$rows = $result->fetchAll();

table($rows);
/**
----------------------------------
| id          | name             |
|================================|
| (integer) 1 | (string) 'IT'    |
| (integer) 3 | (string) 'Sales' |
----------------------------------
*/
```

`ORDER BY` defined with a literal is just for example. You can write this directly to the query.

When you call the `query()` or `queryArgs()` method, query is executed in DB and a `Result` object is returned. When you don't need the query result, you don't have to use this. But mostly, you want data from your query, or you want to know how many rows was affected by your query. This and more can be fetched from the result.

- `Result::fetch()` return next row from the result (you can call it in a cycle, `NULL` is returned, when there is no next row).
- `Result::fetchSingle()` return single value from row (first value from the first row)
- `Result::fetchAll()` return array of all rows. You can pass offset and limit (IMPORATANT: this will not affect SQL query definition, just used offset and limit for returned rows from DB).
- `Result::fetchPairs()` return associative array `key->value`, first parameter is column for the `key` and second is for the `value`. Columns are detected, when you omit both argument. First column in a query is for `key` a second for a `value`. You can omit key column and pass value column, in this case, you will get array list of values.
- `Result::fetchAssoc()` return array with a specified structure
   - `col1[]col2` build array `[$column1_value][][$column2_value] => Row`
   - `col1|col2=col3` build array `[$column1_value][$column2_value] => $column3_value`
   - `col1|col2=[]` build array `[$column1_value][$column2_value] => Row::toArray()`

Some examples to make it clear:

```php
$row = $connection->query('SELECT * FROM users WHERE id = ?', 1)->fetch();

dump($row); // (Row) ['id' => 1, 'nick' => 'Bob', 'inserted_datetime' => '2020-01-01 09:00:00', 'active' => TRUE, 'age' => 45, 'height_cm' => 178.2, 'phones' => [200300, 487412]]

$row = $connection->query('SELECT * FROM users WHERE id = ?', -1)->fetch();

dump($row); // (NULL)

$nick = $connection->query('SELECT nick FROM users WHERE id = ?', 1)->fetchSingle();

dump($nick); // (string) 'Bob'

$rows = $connection->query('SELECT id, nick, active FROM users ORDER BY nick')->fetchAll();

table($rows);
/**
---------------------------------------------------
| id          | nick               | active       |
|=================================================|
| (integer) 1 | (string) 'Bob'     | (bool) TRUE  |
| (integer) 2 | (string) 'Brandon' | (bool) TRUE  |
| (integer) 5 | (string) 'Ingrid'  | (bool) TRUE  |
| (integer) 4 | (string) 'Monica'  | (bool) TRUE  |
| (integer) 3 | (string) 'Steve'   | (bool) FALSE |
---------------------------------------------------
*/

$result = $connection->query('SELECT id, nick, active FROM users ORDER BY nick');

// special syntax for creating structure from data

$rows = $result->fetchAssoc('active[]id'); // $rows[TRUE/FALSE (active)][index][(id)] = Db\Row

$rows = $result->fetchAssoc('active|id=nick'); // $rows[TRUE/FALSE (active)][(id)] = (nick)

$rows = $result->fetchAssoc('active|id=[]'); // $rows[TRUE/FALSE (active)][(id)] = Db\Row::toArray()

// get indexed array, key is first column, value is second column or you can choose columns manually

$result = $connection->query('SELECT id, nick FROM users ORDER BY nick');

$rows = $result->fetchPairs();
dump($rows); // (array) [1 => 'Bob', 2 => 'Brandon', 5 => 'Ingrid', 4 => 'Monica', 3 => 'Steve']

$rows = $result->fetchPairs('id', 'nick');
dump($rows); // (array) [1 => 'Bob', 2 => 'Brandon', 5 => 'Ingrid', 4 => 'Monica', 3 => 'Steve']

$rows = $result->fetchPairs(NULL, 'nick');
dump($rows); // (array) ['Bob', 'Brandon', 'Ingrid', 'Monica', 'Steve']

// get row count

$count = $result->getRowCount(); // ->count() or count($result)
dump($count); // (integer) 5
```

There is also posibility to seek in the result - so you can skip some rows or return back to previous rows:

```php
$result = $connection->query('SELECT id, nick, active FROM users ORDER BY nick');

$success = $result->seek(2);
dump($success); // (bool) TRUE

$row = $result->fetch();
dump($row->id); // (integer) 5

$success = $result->seek(0);
dump($success); // (bool) TRUE

$row = $result->fetch();
dump($row->id); // (integer) 1
```

You can also iterate rows without fetching it to the array:

```php
foreach ($connection->query('SELECT id, nick, active FROM users ORDER BY nick') as $row) {
  assert($row instanceof Forrest79\PhPgSql\Db\Row);
}
```

On the result we can also get column type (PostgreSQL type) or all result column names:

```php
$result = $connection->query('SELECT id, nick, active FROM users');

$columnId = $result->getColumnType('id');
dump($columnId); // (string) 'int4'

$columnNick = $result->getColumnType('nick');
dump($columnNick); // (string) 'text'

$columnActive = $result->getColumnType('active');
dump($columnActive); // (string) 'bool'

$columns = $result->getColumns();
dump($columns); // (array) ['id', 'nick', 'active']
```

All row data are automatically parsed to the correct PHP types (detect from the column type). You can get your own data parsed to the same type as the column have:

```php
$result = $connection->query('SELECT id, nick, active FROM users');

$data = $result->parseColumnValue('id', '123');
dump($data); // (integer) 123
```

On the result object, we can also check, what columns was accesed in our application. You can check this before your request ends, and you can get possible columns, that are not needed to select from the DB:

```php
$result = $connection->query('SELECT id, nick, active FROM users ORDER BY id');

$row1 = $result->fetch();
dump($row1->id); // (integer) 1

$row2 = $result->fetch();
dump($row2->id); // (integer) 2
dump($row2->nick); // (string) 'Brandon'

$parsedColumns = $result->getParsedColumns();
dump($parsedColumns); // (array) ['id' => TRUE, 'nick' => TRUE, 'active' => FALSE]

$result = $connection->query('SELECT id, nick, active FROM users ORDER BY id');
$parsedColumns = $result->getParsedColumns();
dump($parsedColumns); // (NULL)
```

We got an `array` with the column names as key and `TRUE`/`FALSE` as a value. `TRUE` means, that in application was this column accessed. When `NULL` is returned, it means, that no column was accesed. This could be for example for `INSERT` queries or even for `SELECT` queries.

And for `INSERT`/`UPDATE`/`DELETE` results we can get number of affected rows:

```php
$result = $connection->query('DELETE FROM users WHERE id IN (?)', [1, 2]);
$affectedRows = $result->getAffectedRows();
dump($affectedRows); // (integer) 2
```

Finally, we can free result to save some memory:

```php
$result = $connection->query('DELETE FROM users WHERE id IN (?)', [1, 2]);
$result->free();
```

You can get the query, that initiated a result:

```php
$result = $connection->query('DELETE FROM users WHERE id IN (?)', [1, 2]);
$query = $result->getQuery();
assert($query instanceof Forrest79\PhPgSql\Db\Query);
```

Or you can get resource, that can be used in native `pg_*` functions:

```php
$result = $connection->query('DELETE FROM users WHERE id IN (?)', [1, 2]);
$resource = $result->getResource();
assert(is_resource($resource));
```

#### Safely passing parameters

Important is how to safety pass parameters to a query. You can do something like this:

```php
$userId = 1;

$connection->execute('DELETE FROM user_departments WHERE id = ' . $userId);
```

But, there is possible **SQL injection**. Imagine this example, where `$userId` can be some user input:

```php
$userId = '1; TRUNCATE user_departments';

$connection->query('DELETE FROM user_departments WHERE id = ' . $userId);

dump($connection->query('SELECT COUNT(*) FROM user_departments')->fetchSingle()); // (integer) 0
```

We need to pass parameter not as concatenating strings but as real query parameter - where we have separated SQL query and list of parameters. In this case DB can fail on this query, because `$userId` is not valid integer and it can't be used in condition with the `id` column.

In this library, there are two possible ways how to do this. Use `?` for a param. This works automatically, and we can use some special functionallity as passing arrays, literals, bools or another queries. We can also use classic parameters `$1`, `$2`, ..., but with this, no special features are available, and you can't combine `?` with `$1` syntax.

Safe example can be:

```php
$userId = 1;

$connection->query('DELETE FROM user_departments WHERE id = ?', $userId);
$connection->query('DELETE FROM user_departments WHERE id = $1', $userId);

dump($connection->query('SELECT COUNT(*) FROM user_departments')->fetchSingle()); // (integer) 6

// ---

$userId = '1; TRUNCATE user_departments';

try {
  $connection->query('DELETE FROM user_departments WHERE id = ?', $userId);
} catch (Forrest79\PhPgSql\Db\Exceptions\QueryException $e) {
  dump($e->getMessage()); // (string) 'Query: 'DELETE FROM user_departments WHERE id = $1' failed with an error: ERROR:  invalid input syntax for type integer: \"1; TRUNCATE user_departments\".'
}

try {
  $connection->query('DELETE FROM user_departments WHERE id = $1', $userId);
} catch (Forrest79\PhPgSql\Db\Exceptions\QueryException $e) {
  dump($e->getMessage()); // (string) 'Query: 'DELETE FROM user_departments WHERE id = $1' failed with an error: ERROR:  invalid input syntax for type integer: \"1; TRUNCATE user_departments\".'
}

dump($connection->query('SELECT COUNT(*) FROM user_departments')->fetchSingle()); // (integer) 6
```

One speciality, you need to know. If you want to use char `?` in a query (not in params), escape it with `\` like this `\?`. This is the only one magic thing in this library.

```php
$stringWithQuestionmark = $connection->query('SELECT \'Question\?\'')->fetchSingle();
dump($stringWithQuestionmark); // (string) 'Question?'
```

##### Literals, expressions and using queries in query - @todo Sql (Expresion / Literal / Query) + Sql.php

Sometimes you need to pass as a parameter piece of some SQL code. For these situations, there're some prepared objects implementing `Forrest79\PhPgSql\Db\Sql` interface. This object can ba passed to `?` in a query. Every object implementing this interface is pass to the query as is (be carefour, this could perform an SQL injection). This object consist from some SQL string part and you can use also parameters and `?` in SQL part.

> These objects can be used also in fluent part of this library. You can use for example `Expression` as `SELECT` columns, so you can pass here securaly some parameters (for example windows function). Other example - in `INSERT` or `UPDATE`, you can use `Literal` as inserted/updated value.

Existing objects:
- `Forrest79\PhPgSql\Db\Sql\Literal` - can't have parameters, just SQL part
- `Forrest79\PhPgSql\Db\Sql\Expression` - can have parameters
- `Forrest79\PhPgSql\Db\Sql\Query` - this object implements logic, that convert SQL with `?` to `$1`, `$2` format (and some other stuff)

> There is similar `Query` object `Forrest79\PhPgSql\Db\Query` - this object can't be extended, can't be used as `?` parameter and is used only to carry final prepared query in format, that is passed to the `pg_*` functions.

Literal example:

```php
$connection->query('INSERT INTO users (nick, inserted_datetime) VALUES(?, ?)', 'Test', Forrest79\PhPgSql\Db\Sql\Literal::create('now()'));
```

Expression example:

```php
$firstname = 'Bob';
$lastname = 'Marley';
$connection->query('INSERT INTO users (nick) VALUES(?)', Forrest79\PhPgSql\Db\Sql\Expression::create('? || \' \' || ?', $firstname, $lastname)); // or Forrest79\PhPgSql\Db\Sql\Expression::createArgs('? || \' \' || ?', [$firstname, $lastname])
```

Query example:

```php
$activeDepartmentsQuery = Forrest79\PhPgSql\Db\Sql\Query::create('SELECT id FROM departments WHERE active = ?', TRUE);

$cnt = $connection->query('SELECT COUNT(*) FROM user_departments WHERE department_id IN (?)', $activeDepartmentsQuery)->fetchSingle();
dump($cnt); // (integer) 7
```

`Query` has also method `createQuery()` that prepare query in the format, that can be used in `pg_*` functions.

```php
$departmentsQuery = Forrest79\PhPgSql\Db\Sql\Query::createArgs('SELECT id FROM departments WHERE id = ?', [1]);

$query = $departmentsQuery->createQuery();

dump($query->getSql()); // (string) 'SELECT id FROM departments WHERE id = $1'
dump($query->getParams()); // (array) [1]
```

## Rows and using custom row factory

All data from DB are automatically convert to PHP types (more about this later). This is done lazy on the `Row` object. Lazy because converting some type can be slow and when you don't need some column, it's unnecessary to convert it.

Row implements these interfaces `ArrayAccess`, `IteratorAggregate`, `Countable`, `JsonSerializable`. With this you can access column value as object property `$row->column_name` and also as array key `$row['column_name']`. You can get column count on row `count($row)` and simply encode whole row as JSON `json_encode($row)`.

```php
$row = Forrest79\PhPgSql\Db\Row::from(['id' => 1, 'text' => 'Some text']);
dump(count($row)); // (integer) 2
dump($row->count()); // (integer) 2
dump(json_encode($row)); // (string) '{\"id\":1,\"text\":\"Some text\"}'
```

Row has ability to set new value on it:

```php
$row = Forrest79\PhPgSql\Db\Row::from([]);
$row->new_value = 'Test';
$row['new_value2'] = 123;
```

You can also use classic `isset()` (and it works in PHP way - column with the `NULL` value returns `FALSE` - use `hasColumn` method to check if column exists in row and `unset()`.

```php
$row = Forrest79\PhPgSql\Db\Row::from(['existing_column' => 1, 'null_column' => NULL]);
dump(isset($row->existing_column)); // (bool) TRUE
dump(isset($row->null_column)); // (bool) FALSE
dump($row->hasColumn('existing_column')); // (bool) TRUE
dump($row->hasColumn('null_column')); // (bool) TRUE
```

You can simply convert `Row` to an array:

```php
$row = Forrest79\PhPgSql\Db\Row::from(['id' => 1, 'text' => 'Some text']);
dump($row->toArray()); // (array) ['id' => 1, 'text' => 'Some text']
```

Get column names:

```php
$row = Forrest79\PhPgSql\Db\Row::from(['id' => 1, 'text' => 'Some text']);
dump($row->getColumns()); // (array) ['id', 'text']
```

Iterate over all columns and values:

```php
$row = Forrest79\PhPgSql\Db\Row::from(['id' => 1, 'text' => 'Some text']);
foreach ($row as $column => $value) {
  if ($column === 'id') {
    dump($value); // (integer) 1
  } else if ($column === 'text') {
    dump($value); // (string) 'Some text'
  }
}
```

Sometimes can be handy creating new `Row` from some data (like in examples above) - just use static factory method `Row::from()`:

```php
$row = Forrest79\PhPgSql\Db\Row::from(['id' => 1, 'text' => 'Some text']);
dump($row); // (Row) ['id' => 1, 'text' => 'Some text']
```

Last thing you need to know rows is, that when you serialize `Row`, all columns are parsed and row is serialized as a simple array with the real converted values.

```php
```php
$row = Forrest79\PhPgSql\Db\Row::from(['id' => 1, 'text' => 'Some text']);

$serializedRow = serialize($row);

dump(unserialize($serializedRow)); // (Row) ['id' => 1, 'text' => 'Some text']
```

### Using custom row factory

You can simply use your own row object. Your row object must `extends` internal `Row` object and you must implement your own `RowFactory`. Then you can set your own factory to `Connection` and it will be used for all results or you can set it just for concrete `Result` object.

```php
class MyOwnRow extends Forrest79\PhPgSql\Db\Row
{
  public function age(): string
  {
    return $this->age . ' years';
  }
}

class MyOwnRowFactory implements Forrest79\PhPgSql\Db\RowFactory
{
  public function createRow(Forrest79\PhPgSql\Db\ColumnValueParser $columnValueParser, array $rawValues): Forrest79\PhPgSql\Db\Row
  {
      return new MyOwnRow($columnValueParser, $rawValues);
  }
}

$result = $connection->query('SELECT age FROM users WHERE id = 1');
$result->setRowFactory(new MyOwnRowFactory());
$row = $result->fetch();
dump($row->age()); // (string) '45 years'

$connection->setDefaultRowFactory(new MyOwnRowFactory());
$row = $connection->query('SELECT age FROM users WHERE id = 2')->fetch();
dump($row->age()); // (string) '24 years'
```

> By default, is used `Forrest79\PhPgSql\Db\RowFactories\Basic` row factory that produce default `Row` objects.

## Data type converting

This library automatically converts PostgreSQL types to PHP types. Basic types are converted by `Forrest79\PhPgSql\Db\DataTypeParsers\Basic`. If some type is not able to be parsed, an exception is thrown. You can extend this parser or write your own, if you need to parse another types or if you want to change parsing behavior.

**Important!** To determine PG types from PG result is by default used function `pg_field_type`. This function has one undocumented behavior, it's sending SQL query `select oid,typname from pg_type` (`https://github.com/php/php-src/blob/master/ext/pgsql/pgsql.c`) for every request to get proper type names. This `SELECT` is relatively fast and parsing works out of the box with this. But this `SELECT` can be slower for bigger databases and in common, there is no need to run it for all requests. We can cache this data and then use function `pg_field_type_oid`. Cache is needed to be flushed only if database structure is changed. You can use simple cache for this and this is a recommended way. One option is to prepare your own cache with `DataTypesCache` interface or use one already prepared, this save cache to PHP file (it's really fast especially with opcache). More about caching is in the chapter **How to use cache** later.

```php
$rows = $connection->query('SELECT * FROM users')->fetchAll();

table($rows);
/**
-------------------------------------------------------------------------------------------------------------------------------------------
| id          | nick               | inserted_datetime          | active       | age          | height_cm      | phones                   |
|=========================================================================================================================================|
| (integer) 1 | (string) 'Bob'     | (Date) 2020-01-01 09:00:00 | (bool) TRUE  | (integer) 45 | (double) 178.2 | (array) [200300, 487412] |
| (integer) 2 | (string) 'Brandon' | (Date) 2020-01-02 12:05:00 | (bool) TRUE  | (integer) 24 | (double) 180.4 | (NULL)                   |
| (integer) 3 | (string) 'Steve'   | (Date) 2020-01-02 12:05:00 | (bool) FALSE | (integer) 41 | (double) 168   | (NULL)                   |
| (integer) 4 | (string) 'Monica'  | (Date) 2020-01-03 13:10:00 | (bool) TRUE  | (integer) 36 | (double) 175.7 | (NULL)                   |
| (integer) 5 | (string) 'Ingrid'  | (Date) 2020-01-04 14:15:00 | (bool) TRUE  | (integer) 18 | (double) 168.2 | (array) [805305]         |
-------------------------------------------------------------------------------------------------------------------------------------------
*/
```

> There're some PostgreSQL types, that is hard to decode to PHP type (some type of array, hstore...) and this types can be simple convert to JSON and this JSON can be simply converted to PHP. Parser throw an exception and give you a hint - convert type is SELECT to JSON. If you need parsing without converting to JSON, you need to write your own PHP logic (and you can create a pull-request for this :-)).

> Internal info: There is the interface `Forrest79\PhPgSql\Db\ColumnValueParser` that requires the `Row` object. Main implemetation is in the `Result` object, that makes the real values parsing. Second is the `DummyColumnValueParser` that is used for manually created rows or for unserialized rows and this parser does nothing and just return a value.

### How to extend default data type parsing

If you need to parse some special DB type, you have two options. You can create your own data type parser implementing interface `Forrest79\PhPgSql\Db\DataTypeParser` with only one function `parse(string $type, ?string $value): mixed`, that get DB type and value as `string` (or `NULL`) and return PHP value. The second option is preferable - you can extend existing `Forrest79\PhPgSql\Db\DataTypeParsers\Basic` and only add new types.

To use your own data type parser, set it on connection with method `setDataTypeParser()`.

Let's say, we want to parse `point` data type:

```php
class PointDataTypeParser extends Forrest79\PhPgSql\Db\DataTypeParsers\Basic
{
  public function parse(string $type, ?string $value)
  {
    if (($type === 'point') && ($value !== NULL)) {
      return \array_map('intval', \explode(',', \substr($value, 1, -1), 2));
    }
    return parent::parse($type, $value);
  }
}
		
$connection->setDataTypeParser(new PointDataTypeParser());

$point = $connection->query('SELECT \'(1,2)\'::point')->fetchSingle();

dump($point); // (array) [1, 2]
```

### How to use data type cache

Preferable way is to use caching to PHP file. There is prepared caching mechanisms for this `Forrest79\PhPgSql\Db\DataTypeCaches\PhpFile`. You just need to pass existing temp directory to constructor:

```php
$phpFileCache = new Forrest79\PhPgSql\Db\DataTypeCaches\PhpFile('/tmp/cache'); // we need connection to load data from DB and each connection can has different data types

$connection = new Forrest79\PhPgSql\Db\Connection();
$connection->setDataTypeCache($phpFileCache);

// when database structure has changed:
$phpFileCache->clean($connection);
```

The `clean()` method can be used to refresh cache.

If you want to use your own caching mechanisms, just implement interface `Forrest79\PhPgSql\Db\DataTypeCache`. There is only one method `load(Connection $connection): array`, that get DB connection and return array with pairs of `oid->type_name`, where `type_name` is passed to `DataTypeParser`. Or you can use abstract `Forrest79\PhPgSql\Db\DataTypeCaches\DbLoader`, that has predefined function `loadFromDb(Db\Connection $connection)` and this function already load typed from DB and return correct `array`, that you can cache whenever you want. Predefined `PhpFile` uses this `DbLoader`.

## Async

We can also run query asynchronously. Just use this (syntax is the same as `query()` and `queryArgs()`):

```php
$asyncQuery = $connection->asyncQuery('SELECT * FROM users WHERE id = ?', 1);
// or $asyncQuery = $connection->asyncQueryArgs('SELECT * FROM users WHERE id = ?', [1]);
```

This return the `AsyncQuery` object. On this object you can get results for all sent queries with method `getNextResult()` and get the query asociated with this async query with `getQuery()` method, that returns `Forrest79\PhPgSql\Db\Query`.

You can run just one async query on connection (but you can run more queries separated with `;` at once in one function call - but only when you don't use parameters - this is `pgsql` extension limitations - with parameters, you can run just one query at once). Before we can run new async query, you need to get results from the previous one. When you pass more queries in one method call, you need to call `getNextResult()` for every query you pass. Results are getting in the same order as queries are pass to the DB. Method `getNextResult()` returns the same `Result` object as standard `query()`/`queryArgs()` methods.

```php
$asyncQuery = $connection->asyncQuery('SELECT nick FROM users WHERE id = ?', 1);

// this code is executed immediately - you can do logic here

$nick = $asyncQuery->getNextResult()->fetchSingle(); // this will wait till query is completed

dump($nick); // (string) 'Bob'
```

Or example with more queries:

```php
$asyncQuery = $connection->asyncQuery('SELECT nick FROM users WHERE id = 1; SELECT nick FROM users WHERE id = 2');

// this code is executed immediately - you can do logic here

$nick1 = $asyncQuery->getNextResult()->fetchSingle(); // this will wait till first query is completed

dump($nick1); // (string) 'Bob'

// this code is executed immediately - you can do logic here

$nick2 = $asyncQuery->getNextResult()->fetchSingle(); // this will wait till second query is completed

dump($nick2); // (string) 'Brandon'
```

If you want to run simple SQL query or queries (separated with `;`) without parameters and you don't care about results, you can use async version `execute()` method - `asyncExecute()`. To be sure, that all queries is completed, call `completeAsyncExecute()`.

```php
$connection->asyncExecute('UPDATE users SET nick = \'Stuart\' WHERE id = 1; UPDATE users SET nick = \'Nicolas\' WHERE id = 2');

// this code is executed immediately - you can do logic here

$connection->completeAsyncExecute(); // this will wait till all queries are completed
```

You can detect, if some async query is running on the connection with `isBusy()` method and you can also cancel it with `cancelAsyncQuery()` method.

```php
$asyncQuery = $connection->asyncQuery('SELECT nick FROM users WHERE id = ?', 1);

dump($connection->isBusy()); // (bool) TRUE

$asyncQuery->getNextResult();

dump($connection->isBusy()); // (bool) FALSE
```

And example with the query cancelation:

```php
$asyncQuery = $connection->asyncQuery('SELECT nick FROM users WHERE id = ?', 1);

dump($connection->isBusy()); // (bool) TRUE

$connection->cancelAsyncQuery();

dump($connection->isBusy()); // (bool) FALSE
```

### Prepared statements

There is also support for prepared statements. You can prepare query on database with defined placeholders and repeatedly run this query with different arguments.

> Using prepared statement for a repeated query has better performance than sending one query repeatedly with different arguments. But this difference is not really big. You can live without using prepared statements at all.

In query, you can also use `?` for parameters (or `$1`, `$2`, ... - but not combine it), but in prepared statements you can use as parameter only scalars, nothing else.

> That's because prepared query must run with the same parameters types.

Query can be prepared with  `prepareStatement()` method on connection. You will get the `PreparedStatement` object, that have just two methods `execute()`/`executeArgs()` that will run query with passed arguments and get back classic `Result` object.

```php
$prepareStatement = $connection->prepareStatement('SELECT nick FROM users WHERE id = ?');

$result1 = $prepareStatement->execute(1);
dump($result1->fetchSingle()); // (string) 'Bob'

$result2 = $prepareStatement->executeArgs([2]);
dump($result2->fetchSingle()); // (string) 'Brandon'
```

And of course, there is a async version too. Just use method `asyncPrepareStatement()` and it will return classic `AsyncQuery`.

```php
$prepareStatement = $connection->asyncPrepareStatement('SELECT nick FROM users WHERE id = ?');
$asyncQuery1 = $prepareStatement->execute(1);

// this code is executed immediately - you can do logic here

$result1 = $asyncQuery1->getNextResult(); // this will wait till all queries are completed
dump($result1->fetchSingle()); // (string) 'Bob'

$asyncQuery2 = $prepareStatement->executeArgs([2]);

// you can do logic here

$result2 = $asyncQuery2->getNextResult(); // this will wait till all queries are completed
dump($result2->fetchSingle()); // (string) 'Brandon'
```

#### Transactions

There is simple transaction helper object. Call `transaction()` method on connection and you will get `Transaction` object. With this object, you can control transaction or use savepoints.

There are methods to control transaction `begin()`, `commit()` and `rollback()` that correspond with SQL. With `begin()` method you can set isolation level - for example  repeatable read: `begin('ISOLATION LEVEL REPEATABLE READ')`.

```php
$transaction = $connection->transaction();

// ---

$transaction->begin();

$connection->query('UPDATE users SET nick = ? WHERE id = ?', 'Test', 1);

$transaction->commit();

dump($connection->query('SELECT nick FROM users WHERE id = ?', 1)->fetchSingle()); // (string) 'Test'

// ---

$transaction->begin('ISOLATION LEVEL REPEATABLE READ');

$connection->query('UPDATE users SET nick = ? WHERE id = ?', 'Test', 2);

$transaction->rollback();

dump($connection->query('SELECT nick FROM users WHERE id = ?', 2)->fetchSingle()); // (string) 'Brandon'
```

You can also use savepoints with methods `savepoint()`, `releaseSavepoint()` and `rollbackToSavepoint()`. You must provide savepoint name to every method.

```php
$transaction = $connection->transaction();

// ---

$transaction->begin();

$transaction->savepoint('svp1');

$connection->query('UPDATE users SET nick = ? WHERE id = ?', 'Test', 1);

$transaction->releaseSavepoint('svp1');

dump($connection->query('SELECT nick FROM users WHERE id = ?', 1)->fetchSingle()); // (string) 'Test'

$transaction->commit();

dump($connection->query('SELECT nick FROM users WHERE id = ?', 1)->fetchSingle()); // (string) 'Test'

// ---

$transaction->begin('ISOLATION LEVEL REPEATABLE READ');

$transaction->savepoint('svp2');

$connection->query('UPDATE users SET nick = ? WHERE id = ?', 'Test', 2);

$transaction->rollbackToSavepoint('svp2');

dump($connection->query('SELECT nick FROM users WHERE id = ?', 2)->fetchSingle()); // (string) 'Brandon'

$transaction->commit();

dump($connection->query('SELECT nick FROM users WHERE id = ?', 2)->fetchSingle()); // (string) 'Brandon'
```

Last usefull method is `isInTransaction()`. With this you can test if connection is actually in active transaction.

> This method is also provided on the connection object.


```php
$transaction = $connection->transaction();

// ---

$transaction->begin();

dump($transaction->isInTransaction()); // (bool) TRUE

$transaction->commit();

dump($connection->isInTransaction()); // (bool) FALSE
```

### Listen to events

You can listen for some events:

- `addOnConnect()` - this is called after connection is made, `Connection` object is passed - so for example you can run some queries here... 
- `addOnClose()` - this is called right before connection is made, connection is still active, so you can perform some cleanin here. `Connection` object is also passed
- `addOnQuery()` - this is called for every query/execute/async/prepared statement executed on connection. `Connection` object is passed, `Query` object is passed. When query is not async, `float $time` is passed (we can't measure time for async queries) and if query is from prepared statement, name is passed in `$prepareStatementName`
- `addOnResult` - this is called when `Result` object is created (only for queriest that create results). `Connection` and `Result` objects are passed. Can be usefull when you want to collect all your result and check what columns was read at the end of request

```php
$connection->addOnConnect(function (Forrest79\PhPgSql\Db\Connection $connection): void {
	// this is call after connect is done...
});

$connection->addOnClose(function (Forrest79\PhPgSql\Db\Connection $connection): void {
	// this is call right before connection is closed...
});

$connection->addOnQuery(function (Forrest79\PhPgSql\Db\Connection $connection, Forrest79\PhPgSql\Db\Query $query, ?float $time, ?string $prepareStatementName): void {
  // $time === NULL for async queries, $prepareStatementName !== NULL for prepared statements queries
  dump($query->getSql()); // (string) 'SELECT nick FROM users WHERE id = $1'
  dump($query->getParams()); // (array) [3]
});

$connection->addOnResult(function (Forrest79\PhPgSql\Db\Connection $connection, Forrest79\PhPgSql\Db\Result $result): void {
  // this is call after result is created (only if query with result is call...)
  dump($result->getQuery()->getSql()); // (string) 'SELECT nick FROM users WHERE id = $1'
  dump($result->getQuery()->getParams()); // (array) [3]
  dump($result->fetchSingle()); // (string) 'Steve'
});

$connection->query('SELECT nick FROM users WHERE id = ?', 3);
```

### Some usefull helpers

On `Forrest79\PhPgSql\Db\Helpers` are three usefull static methods:

- `createStringPgArray()` - create PostgreSQL array syntax for strings, that can be used in SQL query
- `createPgArray()` - create PostgreSQL array syntax for numeric, that can be used in SQL query

> There is no automatic convertion from PHP to PostgreSQL - even arrays are not automatically converted. When you need this, you must perform convetion manually.

```php
$stringArray = Forrest79\PhPgSql\Db\Helper::createStringPgArray(['Bob', 'Brandond']);
dump($stringArray); // (string) '{\"Bob\",\"Brandond\"}'

$array1 = Forrest79\PhPgSql\Db\Helper::createStringPgArray([1, 2]);
dump($array1); // (string) '{\"1\",\"2\"}'

$array2 = Forrest79\PhPgSql\Db\Helper::createStringPgArray([1.2, 3.4]);
dump($array2); // (string) '{\"1.2\",\"3.4\"}'
```

- `dump($sql, $params, $type = 'cli'/'html')` - print SQL query with highlighted syntex. If you pass parameters, query is printed with these parameters and you can simply copy it and run it in DB. `$type` can be `cli` or `html` (everything different from `cli`)

### Getting notices

PostgreSQL can raise a notice. This is very handy for development purposes. Notices can be read with `getNotices(bool $clearAfterRead = TRUE)`. You can call this function after query or at the end of the PHP script. If you pass `FALSE` as a parametr, notices won't be cleared after read.

```php
$connection->execute('DO $BODY$ BEGIN RAISE NOTICE \'Test notice\'; END; $BODY$ LANGUAGE plpgsql;');
$notices = $this->connection->getNotices();
dump($notices); // (array) ['NOTICE:  Test notice']
```
