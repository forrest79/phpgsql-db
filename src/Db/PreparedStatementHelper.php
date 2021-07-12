<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

abstract class PreparedStatementHelper
{
	/** @var int */
	private static $id = 1;

	/** @var Connection */
	protected $connection;

	/** @var Events */
	protected $events;

	/** @var string */
	protected $query;

	/** @var string|NULL */
	protected $statementName = NULL;


	public function __construct(Connection $connection, Events $events, string $query)
	{
		$this->connection = $connection;
		$this->events = $events;
		$this->query = $query;
	}


	protected static function getNextStatementName(): string
	{
		return 'phpgsql' . self::$id++;
	}


	protected static function prepareQuery(string $query): string
	{
		$paramIndex = 0;

		return (string) \preg_replace_callback(
			'/([\\\\]?)\?/',
			static function ($matches) use (&$paramIndex): string {
				if ($matches[1] === '\\') {
					return '?';
				}

				return '$' . ++$paramIndex;
			},
			$query
		);
	}


	/**
	 * @param array<mixed> $params
	 * @return array<mixed>
	 */
	protected static function prepareParams(array $params): array
	{
		return \array_map(static function ($value) {
			if (\is_bool($value)) {
				return $value ? 'TRUE' : 'FALSE';
			}
			return $value;
		}, $params);
	}

}
