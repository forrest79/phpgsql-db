<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Fluent;

use Forrest79\PhPgSql\Db;

interface Sql
{

	/**
	 * @param string|Query|Db\Sql $table
	 */
	function table($table, ?string $alias = NULL): Query;


	/**
	 * @param array<int|string, string|int|bool|Query|Db\Sql|NULL> $columns
	 */
	function select(array $columns): Query;


	function distinct(): Query;


	/**
	 * @param string|Query|Db\Sql $from
	 */
	function from($from, ?string $alias = NULL): Query;


	/**
	 * @param string|Query|Db\Sql $join table or query
	 * @param string|Complex|Db\Sql|NULL $onCondition
	 */
	function join($join, ?string $alias = NULL, $onCondition = NULL): Query;


	/**
	 * @param string|Query|Db\Sql $join table or query
	 * @param string|Complex|Db\Sql|NULL $onCondition
	 */
	function innerJoin($join, ?string $alias = NULL, $onCondition = NULL): Query;


	/**
	 * @param string|Query|Db\Sql $join table or query
	 * @param string|Complex|Db\Sql|NULL $onCondition
	 */
	function leftJoin($join, ?string $alias = NULL, $onCondition = NULL): Query;


	/**
	 * @param string|Query|Db\Sql $join table or query
	 * @param string|Complex|Db\Sql|NULL $onCondition
	 */
	function leftOuterJoin($join, ?string $alias = NULL, $onCondition = NULL): Query;


	/**
	 * @param string|Query|Db\Sql $join table or query
	 * @param string|Complex|Db\Sql|NULL $onCondition
	 */
	function rightJoin($join, ?string $alias = NULL, $onCondition = NULL): Query;


	/**
	 * @param string|Query|Db\Sql $join table or query
	 * @param string|Complex|Db\Sql|NULL $onCondition
	 */
	function rightOuterJoin($join, ?string $alias = NULL, $onCondition = NULL): Query;


	/**
	 * @param string|Query|Db\Sql $join table or query
	 * @param string|Complex|Db\Sql|NULL $onCondition
	 */
	function fullJoin($join, ?string $alias = NULL, $onCondition = NULL): Query;


	/**
	 * @param string|Query|Db\Sql $join table or query
	 * @param string|Complex|Db\Sql|NULL $onCondition
	 */
	function fullOuterJoin($join, ?string $alias = NULL, $onCondition = NULL): Query;


	/**
	 * @param string|Query|Db\Sql $join table or query
	 */
	function crossJoin($join, ?string $alias = NULL): Query;


	/**
	 * @param string|Complex|Db\Sql $condition
	 * @param mixed ...$params
	 */
	function on(string $alias, $condition, ...$params): Query;


	/**
	 * @param string|Complex|Db\Sql $condition
	 * @param mixed ...$params
	 */
	function where($condition, ...$params): Query;


	/**
	 * @param array<string|array<mixed>|Db\Sql|Complex> $conditions
	 */
	function whereAnd(array $conditions = []): Complex;


	/**
	 * @param array<string|array<mixed>|Db\Sql|Complex> $conditions
	 */
	function whereOr(array $conditions = []): Complex;


	function groupBy(string ...$columns): Query;


	/**
	 * @param string|Complex|Db\Sql $condition
	 * @param mixed ...$params
	 */
	function having($condition, ...$params): Query;


	/**
	 * @param array<string|array<mixed>|Db\Sql|Complex> $conditions
	 */
	function havingAnd(array $conditions = []): Complex;


	/**
	 * @param array<string|array<mixed>|Db\Sql|Complex> $conditions
	 */
	function havingOr(array $conditions = []): Complex;


	/**
	 * @param string|Query|Db\Sql ...$columns
	 */
	function orderBy(...$columns): Query;


	function limit(int $limit): Query;


	function offset(int $offset): Query;


	/**
	 * @param string|Query|Db\Sql $query
	 */
	function union($query): Query;


	/**
	 * @param string|Query|Db\Sql $query
	 */
	function unionAll($query): Query;


	/**
	 * @param string|Query|Db\Sql $query
	 */
	function intersect($query): Query;


	/**
	 * @param string|Query|Db\Sql $query
	 */
	function except($query): Query;


	/**
	 * @param array<string>|NULL $columns
	 */
	function insert(?string $into = NULL, ?array $columns = []): Query;


	/**
	 * @param array<string, mixed> $data
	 */
	function values(array $data): Query;


	/**
	 * @param array<array<string, mixed>> $rows
	 */
	function rows(array $rows): Query;


	function update(?string $table = NULL, ?string $alias = NULL): Query;


	/**
	 * @param array<string, mixed> $data
	 */
	function set(array $data): Query;


	function delete(?string $from = NULL, ?string $alias = NULL): Query;


	/**
	 * @param array<int|string, string|int|Query|Db\Sql> $returning
	 */
	function returning(array $returning): Query;


	function truncate(?string $table = NULL): Query;


	/**
	 * @param mixed ...$params
	 */
	function prefix(string $queryPrefix, ...$params): Query;


	/**
	 * @param mixed ...$params
	 */
	function sufix(string $querySufix, ...$params): Query;

}
