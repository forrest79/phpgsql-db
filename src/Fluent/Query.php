<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Fluent;

use Forrest79\PhPgSql\Db;

class Query implements Sql
{
	public const QUERY_SELECT = 'select';
	public const QUERY_INSERT = 'insert';
	public const QUERY_UPDATE = 'update';
	public const QUERY_DELETE = 'delete';
	public const QUERY_TRUNCATE = 'truncate';

	public const PARAM_SELECT = 'select';
	public const PARAM_DISTINCT = 'distinct';
	public const PARAM_TABLES = 'tables';
	public const PARAM_TABLE_TYPES = 'table-types';
	public const PARAM_JOIN_CONDITIONS = 'join-conditions';
	public const PARAM_WHERE = 'where';
	public const PARAM_GROUPBY = 'groupBy';
	public const PARAM_HAVING = 'having';
	public const PARAM_ORDERBY = 'orderBy';
	public const PARAM_LIMIT = 'limit';
	public const PARAM_OFFSET = 'offset';
	public const PARAM_COMBINE_QUERIES = 'combine-queries';
	public const PARAM_INSERT_COLUMNS = 'insert-columns';
	public const PARAM_RETURNING = 'returning';
	public const PARAM_DATA = 'data';
	public const PARAM_ROWS = 'rows';
	public const PARAM_PREFIX = 'prefix';
	public const PARAM_SUFFIX = 'suffix';

	public const TABLE_TYPE_MAIN = 'main';
	public const TABLE_TYPE_FROM = 'from';
	public const TABLE_TYPE_JOINS = 'joins';

	private const JOIN_INNER = 'INNER JOIN';
	private const JOIN_LEFT_OUTER = 'LEFT OUTER JOIN';
	private const JOIN_RIGHT_OUTER = 'RIGHT OUTER JOIN';
	private const JOIN_FULL_OUTER = 'FULL OUTER JOIN';
	public const JOIN_CROSS = 'CROSS JOIN';

	private const COMBINE_UNION = 'UNION';
	private const COMBINE_UNION_ALL = 'UNION ALL';
	private const COMBINE_INTERSECT = 'INTERSECT';
	private const COMBINE_EXCEPT = 'EXCEPT';

	private const DEFAULT_PARAMS = [
		self::PARAM_SELECT => [],
		self::PARAM_DISTINCT => FALSE,
		self::PARAM_TABLES => [],
		self::PARAM_TABLE_TYPES => [
			self::TABLE_TYPE_MAIN => NULL,
			self::TABLE_TYPE_FROM => [],
			self::TABLE_TYPE_JOINS => [],
		],
		self::PARAM_JOIN_CONDITIONS => [],
		self::PARAM_WHERE => NULL,
		self::PARAM_GROUPBY => [],
		self::PARAM_HAVING => NULL,
		self::PARAM_ORDERBY => [],
		self::PARAM_LIMIT => NULL,
		self::PARAM_OFFSET => NULL,
		self::PARAM_COMBINE_QUERIES => [],
		self::PARAM_INSERT_COLUMNS => [],
		self::PARAM_RETURNING => [],
		self::PARAM_DATA => [],
		self::PARAM_ROWS => [],
		self::PARAM_PREFIX => [],
		self::PARAM_SUFFIX => [],
	];

	/** @var string */
	private $queryType = self::QUERY_SELECT;

	/** @var array{select: array<int|string, string|int|self|Db\Sql>, distinct: bool, tables: array<string, array{0: string, 1: string}>, table-types: array{main: string|NULL, from: array<string>, joins: array<string>}, join-conditions: array<string, Complex>, where: Complex|NULL, groupBy: array<string>, having: Complex|NULL, orderBy: array<string|Db\Sql|self>, limit: int|NULL, offset: int|NULL, combine-queries: array<array{0: string|self|Db\Sql, 1: string}>, insert-columns: array<string>, returning: array<int|string, string|int|self|Db\Sql>, data: array<string, mixed>, rows: array<int, array<string, mixed>>, prefix: array<mixed>, suffix: array<mixed>} */
	private $params = self::DEFAULT_PARAMS;

	/** @var Db\Sql\Query|NULL */
	private $query;

	/** @var QueryBuilder */
	private $queryBuilder;


	public function __construct(QueryBuilder $queryBuilder)
	{
		$this->queryBuilder = $queryBuilder;
	}


	/**
	 * @param string|self|Db\Sql $table
	 * @return static
	 * @throws Exceptions\QueryException
	 */
	public function table($table, ?string $alias = NULL): self
	{
		return $this->addTable(self::TABLE_TYPE_MAIN, $table, $alias);
	}


	/**
	 * @param array<int|string, string|int|bool|self|Db\Sql|NULL> $columns
	 * @return static
	 * @throws Exceptions\QueryException
	 */
	public function select(array $columns): self
	{
		$this->resetQuery();

		foreach ($columns as $alias => &$column) {
			if (\is_int($alias)) {
				$alias = NULL;
			}
			if ($column === NULL) {
				$column = 'NULL';
			} else if ($column === TRUE) {
				$column = 'TRUE';
			} else if ($column === FALSE) {
				$column = 'FALSE';
			}
			$this->checkAlias($column, $alias);
		}

		$this->params[self::PARAM_SELECT] = \array_merge($this->params[self::PARAM_SELECT], $columns);

		return $this;
	}


	/**
	 * @return static
	 * @throws Exceptions\QueryException
	 */
	public function distinct(): self
	{
		$this->resetQuery();
		$this->params[self::PARAM_DISTINCT] = TRUE;
		return $this;
	}


	/**
	 * @param string|self|Db\Sql $from
	 * @return static
	 * @throws Exceptions\QueryException
	 */
	public function from($from, ?string $alias = NULL): self
	{
		return $this->addTable(self::TABLE_TYPE_FROM, $from, $alias);
	}


	/**
	 * @param string|self|Db\Sql $join table or query
	 * @param string|Complex|Db\Sql|NULL $onCondition
	 * @return static
	 * @throws Exceptions\QueryException
	 */
	public function join($join, ?string $alias = NULL, $onCondition = NULL): self
	{
		return $this->innerJoin($join, $alias, $onCondition);
	}


	/**
	 * @param string|self|Db\Sql $join table or query
	 * @param string|Complex|Db\Sql|NULL $onCondition
	 * @return static
	 * @throws Exceptions\QueryException
	 */
	public function innerJoin($join, ?string $alias = NULL, $onCondition = NULL): self
	{
		return $this->addTable(self::JOIN_INNER, $join, $alias, $onCondition);
	}


	/**
	 * @param string|self|Db\Sql $join table or query
	 * @param string|Complex|Db\Sql|NULL $onCondition
	 * @return static
	 * @throws Exceptions\QueryException
	 */
	public function leftJoin($join, ?string $alias = NULL, $onCondition = NULL): self
	{
		return $this->leftOuterJoin($join, $alias, $onCondition);
	}


	/**
	 * @param string|self|Db\Sql $join table or query
	 * @param string|Complex|Db\Sql|NULL $onCondition
	 * @return static
	 * @throws Exceptions\QueryException
	 */
	public function leftOuterJoin($join, ?string $alias = NULL, $onCondition = NULL): self
	{
		return $this->addTable(self::JOIN_LEFT_OUTER, $join, $alias, $onCondition);
	}


	/**
	 * @param string|self|Db\Sql $join table or query
	 * @param string|Complex|Db\Sql|NULL $onCondition
	 * @return static
	 * @throws Exceptions\QueryException
	 */
	public function rightJoin($join, ?string $alias = NULL, $onCondition = NULL): self
	{
		return $this->rightOuterJoin($join, $alias, $onCondition);
	}


	/**
	 * @param string|self|Db\Sql $join table or query
	 * @param string|Complex|Db\Sql|NULL $onCondition
	 * @return static
	 * @throws Exceptions\QueryException
	 */
	public function rightOuterJoin($join, ?string $alias = NULL, $onCondition = NULL): self
	{
		return $this->addTable(self::JOIN_RIGHT_OUTER, $join, $alias, $onCondition);
	}


	/**
	 * @param string|self|Db\Sql $join table or query
	 * @param string|Complex|Db\Sql|NULL $onCondition
	 * @return static
	 * @throws Exceptions\QueryException
	 */
	public function fullJoin($join, ?string $alias = NULL, $onCondition = NULL): self
	{
		return $this->fullOuterJoin($join, $alias, $onCondition);
	}


	/**
	 * @param string|self|Db\Sql $join table or query
	 * @param string|Complex|Db\Sql|NULL $onCondition
	 * @return static
	 * @throws Exceptions\QueryException
	 */
	public function fullOuterJoin($join, ?string $alias = NULL, $onCondition = NULL): self
	{
		return $this->addTable(self::JOIN_FULL_OUTER, $join, $alias, $onCondition);
	}


	/**
	 * @param string|self|Db\Sql $join table or query
	 * @return static
	 * @throws Exceptions\QueryException
	 */
	public function crossJoin($join, ?string $alias = NULL): self
	{
		return $this->addTable(self::JOIN_CROSS, $join, $alias);
	}


	/**
	 * @param string|self|Db\Sql $name
	 * @param string|Complex|Db\Sql|NULL $onCondition
	 * @return static
	 * @throws Exceptions\QueryException
	 */
	private function addTable(string $type, $name, ?string $alias, $onCondition = NULL): self
	{
		$this->resetQuery();

		$this->checkAlias($name, $alias);

		if (($type === self::TABLE_TYPE_MAIN) && ($this->params[self::PARAM_TABLE_TYPES][self::TABLE_TYPE_MAIN] !== NULL)) {
			throw Exceptions\QueryException::onlyOneMainTable();
		}

		if ($alias === NULL) {
			/** @var string $alias */
			$alias = $name;
		}

		if (isset($this->params[self::PARAM_TABLES][$alias])) {
			throw Exceptions\QueryException::tableAliasAlreadyExists($alias);
		}

		$this->params[self::PARAM_TABLES][$alias] = [$name, $type];

		if ($type === self::TABLE_TYPE_MAIN) {
			$this->params[self::PARAM_TABLE_TYPES][$type] = $alias;
		} else {
			$this->params[self::PARAM_TABLE_TYPES][$type === self::TABLE_TYPE_FROM ? $type : self::TABLE_TYPE_JOINS][] = $alias;
		}

		if ($onCondition !== NULL) {
			$this->getComplexParam(self::PARAM_JOIN_CONDITIONS, $alias)->add($onCondition);
		}

		return $this;
	}


	/**
	 * @param string|Complex|Db\Sql $condition
	 * @param mixed ...$params
	 * @return static
	 * @throws Exceptions\QueryException
	 */
	public function on(string $alias, $condition, ...$params): self
	{
		$this->resetQuery();
		$this->getComplexParam(self::PARAM_JOIN_CONDITIONS, $alias)->add($condition, ...$params);
		return $this;
	}


	/**
	 * @param string|Complex|Db\Sql $condition
	 * @param mixed ...$params
	 * @return static
	 * @throws Exceptions\QueryException
	 */
	public function where($condition, ...$params): self
	{
		$this->resetQuery();
		$this->getComplexParam(self::PARAM_WHERE)->add($condition, ...$params);
		return $this;
	}


	/**
	 * @param array<string|array<mixed>|Db\Sql|Complex> $conditions
	 * @throws Exceptions\QueryException
	 */
	public function whereAnd(array $conditions = []): Complex
	{
		$this->resetQuery();
		$complex = Complex::createAnd($conditions, NULL, $this);
		$this->getComplexParam(self::PARAM_WHERE)->add($complex);
		return $complex;
	}


	/**
	 * @param array<string|array<mixed>|Db\Sql|Complex> $conditions
	 * @throws Exceptions\QueryException
	 */
	public function whereOr(array $conditions = []): Complex
	{
		$this->resetQuery();
		$complex = Complex::createOr($conditions, NULL, $this);
		$this->getComplexParam(self::PARAM_WHERE)->add($complex);
		return $complex;
	}


	/**
	 * @param string ...$columns
	 * @return static
	 * @throws Exceptions\QueryException
	 */
	public function groupBy(string ...$columns): self
	{
		$this->resetQuery();
		$this->params[self::PARAM_GROUPBY] = \array_merge($this->params[self::PARAM_GROUPBY], $columns);
		return $this;
	}


	/**
	 * @param string|Complex|Db\Sql $condition
	 * @param mixed ...$params
	 * @return static
	 * @throws Exceptions\QueryException
	 */
	public function having($condition, ...$params): self
	{
		$this->resetQuery();
		$this->getComplexParam(self::PARAM_HAVING)->add($condition, ...$params);
		return $this;
	}


	/**
	 * @param array<string|array<mixed>|Db\Sql|Complex> $conditions
	 * @throws Exceptions\QueryException
	 */
	public function havingAnd(array $conditions = []): Complex
	{
		$this->resetQuery();
		$complex = Complex::createAnd($conditions, NULL, $this);
		$this->getComplexParam(self::PARAM_HAVING)->add($complex);
		return $complex;
	}


	/**
	 * @param array<string|array<mixed>|Db\Sql|Complex> $conditions
	 * @throws Exceptions\QueryException
	 */
	public function havingOr(array $conditions = []): Complex
	{
		$this->resetQuery();
		$complex = Complex::createOr($conditions, NULL, $this);
		$this->getComplexParam(self::PARAM_HAVING)->add($complex);
		return $complex;
	}


	private function getComplexParam(string $param, ?string $alias = NULL): Complex
	{
		if ($param === self::PARAM_JOIN_CONDITIONS) {
			if (!isset($this->params[$param][$alias])) {
				$this->params[$param][$alias] = Complex::createAnd();
			}
			return $this->params[$param][$alias];
		} elseif (($param === self::PARAM_WHERE) || ($param === self::PARAM_HAVING)) {
			if ($this->params[$param] === NULL) {
				$this->params[$param] = Complex::createAnd();
			}
			return $this->params[$param];
		}

		throw new \InvalidArgumentException('todo');
	}


	/**
	 * @param string|self|Db\Sql ...$columns
	 * @return static
	 * @throws Exceptions\QueryException
	 */
	public function orderBy(...$columns): self
	{
		$this->resetQuery();
		$this->params[self::PARAM_ORDERBY] = \array_merge($this->params[self::PARAM_ORDERBY], $columns);
		return $this;
	}


	/**
	 * @return static
	 * @throws Exceptions\QueryException
	 */
	public function limit(int $limit): self
	{
		$this->resetQuery();
		$this->params[self::PARAM_LIMIT] = $limit;
		return $this;
	}


	/**
	 * @return static
	 * @throws Exceptions\QueryException
	 */
	public function offset(int $offset): self
	{
		$this->resetQuery();
		$this->params[self::PARAM_OFFSET] = $offset;
		return $this;
	}


	/**
	 * @param string|self|Db\Sql $query
	 * @return static
	 */
	public function union($query): self
	{
		return $this->addCombine(self::COMBINE_UNION, $query);
	}


	/**
	 * @param string|self|Db\Sql $query
	 * @return static
	 */
	public function unionAll($query): self
	{
		return $this->addCombine(self::COMBINE_UNION_ALL, $query);
	}


	/**
	 * @param string|self|Db\Sql $query
	 * @return static
	 */
	public function intersect($query): self
	{
		return $this->addCombine(self::COMBINE_INTERSECT, $query);
	}


	/**
	 * @param string|self|Db\Sql $query
	 * @return static
	 */
	public function except($query): self
	{
		return $this->addCombine(self::COMBINE_EXCEPT, $query);
	}


	/**
	 * @param string|self|Db\Sql $query
	 * @return static
	 */
	private function addCombine(string $type, $query): self
	{
		$this->params[self::PARAM_COMBINE_QUERIES][] = [$query, $type];
		return $this;
	}


	/**
	 * @param array<string>|NULL $columns
	 * @return static
	 * @throws Exceptions\QueryException
	 */
	public function insert(?string $into = NULL, ?array $columns = []): self
	{
		$this->resetQuery();

		$this->queryType = self::QUERY_INSERT;

		if ($into !== NULL) {
			$this->table($into);
		}

		$this->params[self::PARAM_INSERT_COLUMNS] = $columns;

		return $this;
	}


	/**
	 * @param array<string, mixed> $data
	 * @return static
	 * @throws Exceptions\QueryException
	 */
	public function values(array $data): self
	{
		$this->resetQuery();
		$this->queryType = self::QUERY_INSERT;
		$this->params[self::PARAM_DATA] = $data + $this->params[self::PARAM_DATA];
		return $this;
	}


	/**
	 * @param array<array<string, mixed>> $rows
	 * @return static
	 * @throws Exceptions\QueryException
	 */
	public function rows(array $rows): self
	{
		$this->resetQuery();

		$this->queryType = self::QUERY_INSERT;
		$this->params[self::PARAM_ROWS] = \array_merge($this->params[self::PARAM_ROWS], $rows);

		return $this;
	}


	/**
	 * @return static
	 * @throws Exceptions\QueryException
	 */
	public function update(?string $table = NULL, ?string $alias = NULL): self
	{
		$this->resetQuery();

		$this->queryType = self::QUERY_UPDATE;

		if ($table !== NULL) {
			$this->table($table, $alias);
		}

		return $this;
	}


	/**
	 * @param array<string, mixed> $data
	 * @return static
	 * @throws Exceptions\QueryException
	 */
	public function set(array $data): self
	{
		$this->resetQuery();
		$this->queryType = self::QUERY_UPDATE;
		$this->params[self::PARAM_DATA] = $data + $this->params[self::PARAM_DATA];
		return $this;
	}


	/**
	 * @return static
	 * @throws Exceptions\QueryException
	 */
	public function delete(?string $from = NULL, ?string $alias = NULL): self
	{
		$this->resetQuery();

		$this->queryType = self::QUERY_DELETE;

		if ($from !== NULL) {
			$this->table($from, $alias);
		}

		return $this;
	}


	/**
	 * @param array<int|string, string|int|self|Db\Sql> $returning
	 * @return static
	 * @throws Exceptions\QueryException
	 */
	public function returning(array $returning): self
	{
		$this->resetQuery();
		$this->params[self::PARAM_RETURNING] = \array_merge($this->params[self::PARAM_RETURNING], $returning);
		return $this;
	}


	/**
	 * @return static
	 * @throws Exceptions\QueryException
	 */
	public function truncate(?string $table = NULL): self
	{
		$this->resetQuery();

		$this->queryType = self::QUERY_TRUNCATE;

		if ($table !== NULL) {
			$this->table($table);
		}

		return $this;
	}


	/**
	 * @param mixed ...$params
	 * @return static
	 * @throws Exceptions\QueryException
	 */
	public function prefix(string $queryPrefix, ...$params): self
	{
		$this->resetQuery();
		\array_unshift($params, $queryPrefix);
		$this->params[self::PARAM_PREFIX][] = $params;
		return $this;
	}


	/**
	 * @param mixed ...$params
	 * @return static
	 * @throws Exceptions\QueryException
	 */
	public function sufix(string $querySufix, ...$params): self
	{
		$this->resetQuery();
		\array_unshift($params, $querySufix);
		$this->params[self::PARAM_SUFFIX][] = $params;
		return $this;
	}


	public function has(string $param): bool
	{
		if (!\array_key_exists($param, self::DEFAULT_PARAMS)) {
			throw Exceptions\QueryException::nonExistingQueryParam($param, \array_keys(self::DEFAULT_PARAMS));
		}

		return $this->params[$param] !== self::DEFAULT_PARAMS[$param];
	}


	/**
	 * @return static
	 * @throws Exceptions\QueryException
	 */
	public function reset(string $param): self
	{
		if (!\array_key_exists($param, self::DEFAULT_PARAMS)) {
			throw Exceptions\QueryException::nonExistingQueryParam($param, \array_keys(self::DEFAULT_PARAMS));
		}

		$this->resetQuery();

		$this->params[$param] = self::DEFAULT_PARAMS[$param];

		return $this;
	}


	protected function resetQuery(): void
	{
		$this->query = NULL;
	}


	/**
	 * @param self|Db\Sql|mixed $data
	 * @throws Exceptions\QueryException
	 */
	private function checkAlias($data, ?string $alias): void
	{
		if ((($data instanceof self) || ($data instanceof Db\Sql)) && ($alias === NULL)) {
			throw Exceptions\QueryException::sqlMustHaveAlias();
		} else if (!\is_scalar($data) && !($data instanceof self) && !($data instanceof Db\Sql)) {
			throw Exceptions\QueryException::columnMustBeScalarOrExpression();
		}
	}


	/**
	 * @throws Exceptions\QueryBuilderException
	 */
	public function createSqlQuery(): Db\Sql\Query
	{
		if ($this->query === NULL) {
			$this->query = $this->queryBuilder->createSqlQuery($this->queryType, $this->params);
		}
		return $this->query;
	}


	public function __clone()
	{
		$this->resetQuery();

		foreach ($this->params[self::PARAM_JOIN_CONDITIONS] as $alias => $joinCondition) {
			$this->params[self::PARAM_JOIN_CONDITIONS][$alias] = clone $joinCondition;
		}

		if ($this->params[self::PARAM_WHERE] !== NULL) {
			$this->params[self::PARAM_WHERE] = clone $this->params[self::PARAM_WHERE];
		}

		if ($this->params[self::PARAM_HAVING] !== NULL) {
			$this->params[self::PARAM_HAVING] = clone $this->params[self::PARAM_HAVING];
		}
	}

}
