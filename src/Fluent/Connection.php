<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Fluent;

use Forrest79\PhPgSql\Db;

class Connection extends Db\Connection implements Sql
{
	/** @var QueryBuilder */
	private $queryBuilder;


	/**
	 * @param string|Query|Db\Sql $table
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function table($table, ?string $alias = NULL): Query
	{
		return $this->createQuery()->table($table, $alias);
	}


	/**
	 * @param array<int|string, string|int|bool|Query|Db\Sql|NULL> $columns
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function select(array $columns): Query
	{
		return $this->createQuery()->select($columns);
	}


	/**
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function distinct(): Query
	{
		return $this->createQuery()->distinct();
	}


	/**
	 * @param string|Query|Db\Sql $from
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function from($from, ?string $alias = NULL): Query
	{
		return $this->createQuery()->from($from, $alias);
	}


	/**
	 * @param string|Query|Db\Sql $join table or query
	 * @param string|Complex|Db\Sql|NULL $onCondition
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function join($join, ?string $alias = NULL, $onCondition = NULL): Query
	{
		return $this->createQuery()->join($join, $alias, $onCondition);
	}


	/**
	 * @param string|Query|Db\Sql $join table or query
	 * @param string|Complex|Db\Sql|NULL $onCondition
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function innerJoin($join, ?string $alias = NULL, $onCondition = NULL): Query
	{
		return $this->createQuery()->innerJoin($join, $alias, $onCondition);
	}


	/**
	 * @param string|Query|Db\Sql $join table or query
	 * @param string|Complex|Db\Sql|NULL $onCondition
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function leftJoin($join, ?string $alias = NULL, $onCondition = NULL): Query
	{
		return $this->createQuery()->leftJoin($join, $alias, $onCondition);
	}


	/**
	 * @param string|Query|Db\Sql $join table or query
	 * @param string|Complex|Db\Sql|NULL $onCondition
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function leftOuterJoin($join, ?string $alias = NULL, $onCondition = NULL): Query
	{
		return $this->createQuery()->leftOuterJoin($join, $alias, $onCondition);
	}


	/**
	 * @param string|Query|Db\Sql $join table or query
	 * @param string|Complex|Db\Sql|NULL $onCondition
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function rightJoin($join, ?string $alias = NULL, $onCondition = NULL): Query
	{
		return $this->createQuery()->rightJoin($join, $alias, $onCondition);
	}


	/**
	 * @param string|Query|Db\Sql $join table or query
	 * @param string|Complex|Db\Sql|NULL $onCondition
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function rightOuterJoin($join, ?string $alias = NULL, $onCondition = NULL): Query
	{
		return $this->createQuery()->rightOuterJoin($join, $alias, $onCondition);
	}


	/**
	 * @param string|Query|Db\Sql $join table or query
	 * @param string|Complex|Db\Sql|NULL $onCondition
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function fullJoin($join, ?string $alias = NULL, $onCondition = NULL): Query
	{
		return $this->createQuery()->fullJoin($join, $alias, $onCondition);
	}


	/**
	 * @param string|Query|Db\Sql $join table or query
	 * @param string|Complex|Db\Sql|NULL $onCondition
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function fullOuterJoin($join, ?string $alias = NULL, $onCondition = NULL): Query
	{
		return $this->createQuery()->fullOuterJoin($join, $alias, $onCondition);
	}


	/**
	 * @param string|Query|Db\Sql $join table or query
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function crossJoin($join, ?string $alias = NULL): Query
	{
		return $this->createQuery()->crossJoin($join, $alias);
	}


	/**
	 * @param string|Complex|Db\Sql $condition
	 * @param mixed ...$params
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function on(string $alias, $condition, ...$params): Query
	{
		return $this->createQuery()->on($alias, $condition, ...$params);
	}


	/**
	 * @param string|Complex|Db\Sql $condition
	 * @param mixed ...$params
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function where($condition, ...$params): Query
	{
		return $this->createQuery()->where($condition, ...$params);
	}


	/**
	 * @param array<string|array<mixed>|Db\Sql|Complex> $conditions
	 * @throws Exceptions\QueryException
	 */
	public function whereAnd(array $conditions = []): Complex
	{
		return $this->createQuery()->whereAnd($conditions);
	}


	/**
	 * @param array<string|array<mixed>|Db\Sql|Complex> $conditions
	 * @throws Exceptions\QueryException
	 */
	public function whereOr(array $conditions = []): Complex
	{
		return $this->createQuery()->whereOr($conditions);
	}


	/**
	 * @param string ...$columns
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function groupBy(string ...$columns): Query
	{
		return $this->createQuery()->groupBy(...$columns);
	}


	/**
	 * @param string|Complex|Db\Sql $condition
	 * @param mixed ...$params
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function having($condition, ...$params): Query
	{
		return $this->createQuery()->having($condition, ...$params);
	}


	/**
	 * @param array<string|array<mixed>|Db\Sql|Complex> $conditions
	 * @throws Exceptions\QueryException
	 */
	public function havingAnd(array $conditions = []): Complex
	{
		return $this->createQuery()->havingAnd($conditions);
	}


	/**
	 * @param array<string|array<mixed>|Db\Sql|Complex> $conditions
	 * @throws Exceptions\QueryException
	 */
	public function havingOr(array $conditions = []): Complex
	{
		return $this->createQuery()->havingOr($conditions);
	}


	/**
	 * @param string|Query|Db\Sql ...$columns
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function orderBy(...$columns): Query
	{
		return $this->createQuery()->orderBy(...$columns);
	}


	/**
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function limit(int $limit): Query
	{
		return $this->createQuery()->limit($limit);
	}


	/**
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function offset(int $offset): Query
	{
		return $this->createQuery()->offset($offset);
	}


	/**
	 * @param string|Query|Db\Sql $query
	 * @return QueryExecute
	 */
	public function union($query): Query
	{
		return $this->createQuery()->union($query);
	}


	/**
	 * @param string|Query|Db\Sql $query
	 * @return QueryExecute
	 */
	public function unionAll($query): Query
	{
		return $this->createQuery()->unionAll($query);
	}


	/**
	 * @param string|Query|Db\Sql $query
	 * @return QueryExecute
	 */
	public function intersect($query): Query
	{
		return $this->createQuery()->intersect($query);
	}


	/**
	 * @param string|Query|Db\Sql $query
	 * @return QueryExecute
	 */
	public function except($query): Query
	{
		return $this->createQuery()->except($query);
	}


	/**
	 * @param array<string>|NULL $columns
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function insert(?string $into = NULL, ?array $columns = []): Query
	{
		return $this->createQuery()->insert($into, $columns);
	}


	/**
	 * @param array<string, mixed> $data
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function values(array $data): Query
	{
		return $this->createQuery()->values($data);
	}


	/**
	 * @param array<array<string, mixed>> $rows
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function rows(array $rows): Query
	{
		return $this->createQuery()->rows($rows);
	}


	/**
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function update(?string $table = NULL, ?string $alias = NULL): Query
	{
		return $this->createQuery()->update($table, $alias);
	}


	/**
	 * @param array<string, mixed> $data
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function set(array $data): Query
	{
		return $this->createQuery()->set($data);
	}


	/**
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function delete(?string $from = NULL, ?string $alias = NULL): Query
	{
		return $this->createQuery()->delete($from, $alias);
	}


	/**
	 * @param array<int|string, string|int|Query|Db\Sql> $returning
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function returning(array $returning): Query
	{
		return $this->createQuery()->returning($returning);
	}


	/**
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function truncate(?string $table = NULL): Query
	{
		return $this->createQuery()->truncate($table);
	}


	/**
	 * @param mixed ...$params
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function prefix(string $queryPrefix, ...$params): Query
	{
		return $this->createQuery()->prefix($queryPrefix, ...$params);
	}


	/**
	 * @param mixed ...$params
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function sufix(string $querySufix, ...$params): Query
	{
		return $this->createQuery()->sufix($querySufix, ...$params);
	}


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

}
