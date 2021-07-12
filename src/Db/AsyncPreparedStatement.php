<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

class AsyncPreparedStatement extends PreparedStatementHelper
{
	/** @var AsyncHelper */
	private $asyncHelper;


	public function __construct(Connection $connection, AsyncHelper $asyncHelper, Events $events, string $query)
	{
		parent::__construct($connection, $events, $query);
		$this->asyncHelper = $asyncHelper;
	}


	/**
	 * @param mixed ...$params
	 */
	public function execute(...$params): AsyncQuery
	{
		return $this->executeArgs($params);
	}


	/**
	 * @param array<mixed> $params
	 */
	public function executeArgs(array $params): AsyncQuery
	{
		$statementName = $this->prepareStatement();

		$params = self::prepareParams($params);

		$query = new Query($this->query, $params);

		$success = @\pg_send_execute($this->connection->getResource(), $statementName, $params); // intentionally @
		if ($success === FALSE) {
			throw Exceptions\QueryException::asyncPreparedStatementQueryFailed(
				$statementName,
				$query,
				$this->connection->getLastError()
			);
		}

		if ($this->events->hasOnQuery()) {
			$this->events->onQuery($query, NULL, $statementName);
		}

		return $this->asyncHelper->createAndSetAsyncQuery($query, $statementName);
	}


	private function prepareStatement(): string
	{
		if ($this->statementName === NULL) {
			$statementName = self::getNextStatementName();
			$this->query = self::prepareQuery($this->query);
			$success = @\pg_send_prepare($this->connection->getResource(), $statementName, $this->query); // intentionally @
			if ($success === FALSE) {
				throw Exceptions\QueryException::asyncPreparedStatementQueryFailed(
					$statementName,
					new Query($this->query, []),
					$this->connection->getLastError()
				);
			}
			$result = \pg_get_result($this->connection->getResource());
			if (($result === FALSE) || (!$this->asyncHelper::checkAsyncQueryResult($result))) {
				throw Exceptions\QueryException::asyncPreparedStatementQueryFailed(
					$statementName,
					new Query($this->query, []),
					($result !== FALSE) ? (string) \pg_result_error($result) : $this->connection->getLastError()
				);
			}
			$this->statementName = $statementName;
		}

		return $this->statementName;
	}

}
