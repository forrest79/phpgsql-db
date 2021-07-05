# PhPgSql\Fluent

**@todo**
- Connection (Connection.php) + QueryExecution (QueryExecution.php) - how to use own Query
- Query (Query.php) - all, how to return your own Query
- Complex (Complex.php)
- QueryBuilder (QueryBuiler.php) - how to extend

## Common use

Fluent interface can be used to simply create SQL queries using PHP.

Fluent methods are defined in the `Forrest79\PhPgSql\Fluent\Sql` interface. There're 3 object implementing this interface. You can start your query in the same way from all these objects:

- `Forrest79\PhPgSql\Fluent\Query` - this is the basic object, that can generate queries (but can't execute them)
- `Forrest79\PhPgSql\Fluent\QueryExecute` - this is `Fluent\Query` object extension, that requires `Db\Connection` object and can execute queries in database
- `Forrest79\PhPgSql\Fluent\Connection` - this is `Db\Connection` extension that creates `Fluent\QueryExecute` object with the correct `Db\Connection` - you will be probably using this most

Both `Query` and `QueryExecute` needs `QueryBuilder` object. `Fluent\Connection` pass this object automatically.

Fluent generates `Db\Sql\Query` object with `?` as placeholders for parameters that is handled by `PhPgSql\Db` part. Object is created and used internally but you can create it manually, if you want, with the `Fluent\Query::createSqlQuery()` method. 

```php
$fluent = new Forrest79\PhPgSql\Fluent\Query(new Forrest79\PhPgSql\Fluent\QueryBuilder());

$query = $fluent
  ->select(['*'])
  ->from('users')
  ->where('id', 1)
  ->createSqlQuery();

dump($query->getSql()); // (string) 'SELECT * FROM users WHERE id = ?'
dump($query->getParams()); // (array) [1]
```

With the `QueryExecute` you can run this query in DB. This object has all `fetch*()` methods as the `Db\Result`.

```php
$fluent = new Forrest79\PhPgSql\Fluent\QueryExecute(new Forrest79\PhPgSql\Fluent\QueryBuilder(), $connection);

$row = $fluent
  ->select(['*'])
  ->from('users')
  ->where('id', 1)
  ->fetch();

dump($row); // (Row) ['id' => 1, 'nick' => 'Bob', 'inserted_datetime' => '2020-01-01 09:00:00', 'active' => TRUE, 'age' => 45, 'height_cm' => 178.2, 'phones' => [200300, 487412]]
```

But you don't want to do this so complicated. Use `Fluent\Connection` to create `QueryExecute` easily:

```php
$userNick = $connection
  ->select(['nick'])
  ->from('users')
  ->where('id', 2)
  ->fetchSingle();

dump($userNick); // (string) 'Brandon'
```

## Writing SQL queries

This is list all methods you can use to generate query. This covers most of the defaults SQL commands. If there is something missing - you must write your query manually as a string (or you can extend `Fluent\Query` and `Fluent\QueryBuilder` with your functionality - more about this later). Many methods define alias, some needs it and can't be used without set one.

You can start query with whatever method you want. Methods only sets query properties and from there properties is generated final SQL query.

Every query is `SELECT` at first, until you call `->insert(...)`, `->update(...)`, `->delete(...)` or `->truncate(...)`, which change query to apropriate SQL command (you can set type more than once in one query, the last is used - except `INSERT` - `SELECT`). So you can prepare you query in common way and at the end, you can decide if you want to `SELECT`  or `DELETE` data or whatsoever. If you call some command more than once, data is merged, for example, this `->select(['column1'])->select(['column2'])` is the same as `->select(['column1', 'column2'])`. Conditions are connected with the logic `AND`.

- `table($table, ?string $alias = NULL)` - create `Query` object with defined main table - this table can be used for selects, updates, inserts or deletes - you don't need to use concrete method to define table. `$table` can be simple `string` or other `Query` or `Db\Sql`.


- `select(array $columns)` - defines columns (array `key => column`) to `SELECT`. String array key is column alias. Column can be `string`, `int`, `bool`, `Query`, `Db\Sql` or `NULL`


- `distinct(): Query` - create `SELECT DISCTINCT`


- `from($from, ?string $alias = NULL)` - defines table for `SELECT` query. `$from` can be simple `string` or other `Query` or `Db\Sql`.


- `join($join, ?string $alias = NULL, $onCondition = NULL)` (or `innetJoin(...)`/`leftJoin(...)`/`leftOuterJoin(...)`/`rightJoin(...)`/`rightOuterJoin(...)`/`fullJoin(...)`/`fullOuterJoin(...)`) - join table or query. You must provide alias if you want to add more conditions to `ON`. `$join` can be simple string or other `Query` or `Db\Sql`. `$onCondition` can be simple string or other `Complex` or `Db\Sql`. `Db\Sql` can be used for some complex expression, where you need to use `?` and parameters. 


- `crossJoin($join, ?string $alias = NULL)` - defines cross join. `$join` can be simple string or other `Query` or `Db\Sql`. There is no `ON` condition.


- `on(string $alias, $condition, ...$params)` - defines new `ON` condition for joins. More `ON` conditions for one join is connected with `AND`. If `$condition` is `string`, you can use `?` and parameters in `$params`. Otherwise `$condition` can be `Complex` or `Db\Sql`.


- `where($condition, ...$params)` (or `having(...)`) - defines `WHERE` or `HAVING` conditions. you can provide condition as a `string`. When `string` condition is used, you can add `$parameters`. When in the condition is no `?` and only one parameter is used, comparision is made between condition and parameter. If parameter is scalar, simple `=` is used, for array is used `IN` operator, the same applies ale for `Query` (`Fluent\Query` or `Db\Sql`). And for `NULL` is used `IS` operator. This could be handy, when you want to you more parameter types in ona condition. For example, you can provide `int` and `=` will be use and if you provide `array<int>` - `IN` operator will be used and query will be working for the both parameter types. More complex conditions can be written manually as string with `?` for parameters. Or you can use `Complex` or `Db\Sql` as condition. In this case, `$params` must be blank. All `where()` or `having()` calls is connected with logic `AND`.


- `whereAnd(array $conditions = []): Complex` (or `whereOr(...)` / `havingAnd(...)` / `havingOr()`) - with these methods, you can generate condition groups. Ale provided conditions are connected with logic `AND` for `whereAnd()` and `havingAnd()` and with logic `OR` for `whereOr()` and `havingOr()`. All these methods returns `Complex` object (more about this later). `$conditions` items can be simple `string`, another `array` (this is a little bit magic - this works as `where()`/`having()` method - first item in this `array` is conditions and next items is parameters), `Complex` or `Db\Sql`. 


- `groupBy(string ...$columns)`: - generate `GROUP BY` statement, one or more `string` parameters must be provided


- `orderBy(...$columns): Query` - generate `ORDER BY` statement, one or more parameters must be provided. Parameter can be simple `string`, another `Query` or `Db\Sql`.


- `limit(int $limit)` - generate `LIMIT` statement with `int` parameter.


- `offset(int $offset)` - generate `OFFSET` statement with `int` parameter.


- `union($query)` (or `` / `` / ``) - connect 2 queries with `UNION`, `UNION ALL`, `INTERSECT` or `EXCEPT`. Query can be simple `string,` another `Query` or `Db\Sql`.


- `insert(?string $into = NULL, ?array $columns = [])` - set query as `INSERT`. When the main table in not provided yet, you can set it or rewrite it with `$into` parameter. If you want use `INSERT ... SELECT ...` you must provide column names in `$columns` parameter.


- `values(array $data)` - set data to insert. Key is columns name and value is inseted value. Value can be scalar or `Db\Sql`. Method can be call multipletimes and provided data is merged.


- `rows(array $rows)` - this method can be used to insert multiple rows in one query. `$rows` is `array` of arrays. Each array is one row (the same as for the `values()` method). All rows must have the same columns. Method can be called multipletimes and all rows are merged.


- `update(?string $table = NULL, ?string $alias = NULL)`: - set query for update. If main tabel is not set, you must set it or rewrite with the `$table` parameter. `$alies` can be provided, when you want to use `UPDATE ... FROM ...`.


- `set(array $data)` - data to update. Rules for the data are the same as for the `values()` method.


- `delete(?string $from = NULL, ?string $alias = NULL)` - set query for deletion. If main table is not set, you must provide/rewrite it with `$from` parameter. `$alias` can be provided if you want use `DELETE ... FROM ...`.


- `returning(array $returning)` - generate `RETURNING` statement for `INSERT`, `UPDATE` or `DELETE`. Syntax for `$returning` is the same as for the `select()` method.


- `truncate(?string $table = NULL)` - truncate table. If the main table is not set, you must provide/rewrite it with the `$table` parameter.


- `prefix(string $queryPrefix/$querySufix, ...$params)` (or `sufix(...)`) - with this, you can define univerzal query prefix or sufix. This is usefull for actually not supperted fluent syntax. With prefix, you can create CTE (Common Table Expression) queries. With sufix, you can create `SELECT ... FOR UPDATE` for example. Definition can be simple `string` or you can use `?` and parameters.


If you want to create copy of existing query, just use `clone`:

```php
$query = $connection->select(['nick'])->from('users');
$newQuery = clone $query;
```

`Query` internally saves own state for the `QueryBuilder`. You can check, if some internal state is already set with method `has(...)`. Use `Query::PARAM_*` constants as parameter. You can also reset some settings with `reset(...)` method.

```php
$query = $connection->where('column', TRUE);

dump($query->has($query::PARAM_WHERE)); // (bool) TRUE

$query->reset($query::PARAM_WHERE);

dump($query->has($query::PARAM_WHERE)); // (bool) FALSE
```
Every table definition command (like `->table(...)`, `->from(...)`, joins, update table, ...) has table alias definition - it's optional, but for some query, you define alias (also for joins, if you want to use another `on()` method - you must target `ON` conditon to the concrete table with the table aliase
).

If you want to create alias for column in `SELECT`, use `string` key in `array` definition (the same for `returning()`):

```php
$query = $connection
	->select(['column1', 'alias' => 'column_with_alias']);

dump($query); // (Query) SELECT column1, column_with_alias AS \"alias\"
```

To almost every parameter (`select()`, `where()`, `having()`, `on()`, `orderBy()`, `returning()`, `from()`, `joins()`, unions, ...) you can pass `Db\Sql\Query` (or anything with `Db\Sql` interface) or other `Fluent\Query` object. At some places (`select()`, `from()`, joins), you must provide alias if you want to pass this objects.

```php
$query = $connection
	->select(['column'])
	->from('table')
	->limit(1);

$queryA = $connection
	->select(['c' => $query]);

dump($queryA); // (Query) SELECT (SELECT column FROM table LIMIT $1) AS \"c\" [Params: (array) [1]]

$queryB = $connection
    ->select(['column'])
	->from($query, 'c');

dump($queryB); // (Query) SELECT column FROM (SELECT column FROM table LIMIT $1) AS c [Params: (array) [1]]

$queryC = $connection
    ->select(['column'])
    ->from('table', 't')
	->join($query, 'c', 'c.id = t.id');

dump($queryC); // (Query) SELECT column FROM table AS t INNER JOIN (SELECT column FROM table LIMIT $1) AS c ON c.id = t.id [Params: (array) [1]]

$queryD = $connection
    ->select(['column'])
    ->from('table', 't')
	->where('id IN (?)', $query);

dump($queryD); // (Query) SELECT column FROM table AS t WHERE id IN (SELECT column FROM table LIMIT $1) [Params: (array) [1]]

$queryE = $connection
    ->select(['column1', 'column2'])
	->union($query);

dump($queryE); // (Query) SELECT column1, column2 UNION (SELECT column FROM table LIMIT $1) [Params: (array) [1]]
```

### Complex conditions

Every condition (`WHERE`/`HAVING`/`ON`) are internally handled as `Complex` object. With this, you can define really complex conditions connected with logic `AND` or `OR`. One condition can be simple `string`, can have one argument with `=`/`IN`/`NULL`/`bool` detection or can have many arguments using `?` and parameters.

Complex is a list of conditions that're all connected with `AND` or `OR`. The magic is, that condition can be also another complex with different type (`AND` or `OR`).

Complex can be created with static factory methods `Complex::createAnd(...)` or `Complex::createOr(...)`. The first argument can be `array` with condition list. New condition can be inserted with `add(...)` method.

With methods `addComplexAnd(...)` or `addComplexOr(...)` you can add new `Complex` object to the conditions list and this new `Complex` object is returned. These `Complex` objects are connected into tree structure (and can be connected to the `Query` object). When you need to use simply fluent interface, you can use `parent()` method, that returns parent `Complex` or `query()` that returns connected `Query` object.

Method `getType()` return `AND` or `OR` and `getConditions()` returns list of all conditions. You will probably don't need this methods at all.

`Complex` also implements `ArrayAccess`, so you can add new condition with simple `$complex[] = ...` syntax, get concrete condition with `$condtition = $complex[...]` or remova one condition with `unset($complex[...])`.

```php
$param = [1, 2];

$complex = Forrest79\PhPgSql\Fluent\Complex::createAnd([
    'column1 = 1',
    ['column2', TRUE],
    ['column3', $param],
    ['column4 < ? OR column5 != ?', 5, 10],
]);

$complex->add('column1', 81);
$complex->add('column4 < ? OR column5 != ?', 5, 10);
$complex[] = ['column1', 71]; // column1 = 1

$complex->addComplexOr([
    'column != TRUE'
])
    ->add('column2', TRUE)
    ->parent() // this return original complex object
        ->add('column3 < 1');
```

This defined complex can be used in `where($complex)` method, `having($complex)` method or as `on($complex)`/`join(..., $complex)` condition.

To create complex condition in a simplier way, there are methods `whereAnd()`/`whereOr()`/`havingAnd()`/`havingOr()` on `Query` that return new `Complex` connected to a query.

```php
$query = $connection->table('users')
	->whereOr() // add new OR (return Complex object)
		->add('column', 1) // this is add to OR
		->add('column2', [2, 3]) // this is also add to OR
		->addComplexAnd() // this is also add to OR and can contains more ANDs
			->add('column', $connection->createQuery()->select([1])) // this is add to AND
			->add('column2 = ANY(?)', Forrest79\PhPgSql\Db\Sql\Query::create('SELECT 2')) // this is add to AND
		->parent() // get original OR
		->add('column3 IS NOT NULL') // and add to OR new condition
	->query() // back to original query object
	->select(['*']);

dump($query); // (Query) SELECT * FROM users WHERE (column = $1) OR (column2 IN ($2, $3)) OR ((column IN (SELECT 1)) AND (column2 = ANY(SELECT 2))) OR (column3 IS NOT NULL) [Params: (array) [1, 2, 3]]
```

### Inserts

You can insert simple row:

```php
$result = $connection
  ->insert('users')
  ->values([
    'nick' => 'James',
    'inserted_datetime' => Forrest79\PhPgSql\Db\Sql\Literal::create('now()'),
    'active' => TRUE,
    'age' => 37,
    'height_cm' => NULL,
    'phones' => Forrest79\PhPgSql\Db\Helper::createStringPgArray(['732123456', '736987654']),
  ])
  ->execute();

dump($result->getAffectedRows()); // (integer) 1

$insertedRows = $connection
  ->insert('users')
  ->values([
    'nick' => 'Jimmy',
  ])
  ->getAffectedRows();

dump($insertedRows); // (integer) 1
```

Or you can use returning statement:

```php
$insertedData = $connection
  ->insert('users')
  ->values([
    'nick' => 'Jimmy',
  ])
  ->returning(['id', 'nick'])
  ->fetch();

dump($insertedData); // (Row) ['id' => 6, 'nick' => 'Jimmy']
```

You can use multi-insert too:

```php
$insertedRows = $connection
  ->insert('users')
  ->rows([
    ['nick' => 'Luis'],
    ['nick' => 'Gilbert'],
    ['nick' => 'Zoey'],
  ])
  ->getAffectedRows();

dump($insertedRows); // (integer) 3
```

Here is column names detected from the first row. You can also pass columns as second parametr in `insert()` method:

```php
$insertedRows = $connection
  ->insert('users', ['nick', 'age'])
  ->rows([
    ['Luis', 31],
    ['Gilbert', 18],
    ['Zoey', 28],
  ])
  ->getAffectedRows();

dump($insertedRows); // (integer) 3
```

And of course, you can use `INSERT` - `SELECT`:

```php
$insertedRows = $connection
  ->insert('users', ['nick'])
  ->select(['name' || '\'_\'' || 'age'])
  ->from('departments')
  ->where('id', [1, 2])
  ->getAffectedRows();

dump($insertedRows); // (integer) 2
```

And if you're using the same names for columns in `INSERT` and `SELECT`, you can call insert without columns list and it will be detected from select columns.

```php
$insertedRows = $connection
  ->insert('users',)
  ->select(['nick'])
  ->from('users', 'u2')
  ->where('id', [1, 2])
  ->getAffectedRows();

dump($insertedRows); // (integer) 2
```

You have to use alias `u2` when you're inserting to the same table as selecting from.

### Update

You can use simple update:

```php
$updatedRows = $connection
  ->update('users')
  ->set([
    'nick' => 'Thomas',
  ])
  ->where('id', 10)
  ->getAffectedRows();

dump($updatedRows); // (integer) 0
```

There is no row with the `id = 10`, so `0` rows was updated.

Or complex with from (and joins, ...):

```php
$result = $connection
  ->update('users', 'u')
  ->set([
    'nick' => Forrest79\PhPgSql\Db\Sql\Literal::create('u.nick || \' - \' || d.name'),
    'age' => NULL,
  ])
  ->from('departments', 'd')
  ->execute();

dump($result->getAffectedRows()); // (integer) 5
```

### Delete

Simple delete with a condition:

```php
$deleteRows = $connection
  ->delete('users')
  ->where('id', 1)
  ->getAffectedRows();

dump($deleteRows); // (integer) 1
```

### Truncate

Just with table name:

```php
$connection
	->truncate('user_departments')
	->execute();

$connection
	->table('departments')
	->truncate()
	->sufix('CASCADE') // generate `TRUNCATE departments CASCADE`
	->execute();
```

## Fetching data from DB

On `QueryExecute`, you can use all fetch functions as on `Db\Result`. All `fetch*()` methods call `execute()` that run query in DB and return `Db\Result` object. `execute()` method can be used everytime, but it's handy mostly for queriest, that return no data.

You can update your query till `execute()` is call, after that, no updates on query is available, you can only execute this query again by calling `reexecute()`:

```php
$query = $connection
  ->select(['nick'])
  ->from('users')
  ->where('id', 1);
 
$userNick = $query->fetchSingle();

dump($userNick); // (string) 'Bob'

$connection
    ->update('users')
    ->set(['nick' => 'Thomas'])
    ->where('id', 1)
    ->execute();

$updatedUserNick = $query->reexecute()->fetchSingle();

dump($updatedUserNick); // (string) 'Thomas'
```

If you clone already executed query, copy is cloned with reset resutl, so you can still update the query and then execute it.

You can also run async query with a `asyncExecute()` method.


```php
$asyncQuery = $connection
  ->select(['nick'])
  ->from('users')
  ->where('id', 1)
  ->asyncExecute();

// do some logic here

$result = $asyncQuery->getNextResult();
 
$userNick = $result->fetchSingle();

dump($userNick); // (string) 'Bob'
```

## Extending `Query`

@todo QueryExecute / QueryBuilder (+methods)

```
connection



	public function setQueryBuilder(QueryBuilder $queryBuilder): self
	{
		$this->queryBuilder = $queryBuilder;
		return $this;
	}


	protected function getQueryBuilder(): QueryBuilder
	{
		if ($this->queryBuilder === NULL) {
			$this->queryBuilder = new QueryBuilder();
		}

		return $this->queryBuilder;
	}


	public function createQuery(): QueryExecute
	{
		return new QueryExecute($this->getQueryBuilder(), $this);
	}
```

## Query examples

@todo