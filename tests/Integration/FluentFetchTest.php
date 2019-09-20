<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Tests\Integration;

use Forrest79\PhPgSql\Fluent;
use Tester;

require_once __DIR__ . '/TestCase.php';

/**
 * @testCase
 */
class FluentFetchTest extends TestCase
{
	/** @var Fluent\Connection */
	private $connection;


	protected function setUp(): void
	{
		parent::setUp();
		$this->connection = new Fluent\Connection(\sprintf('%s dbname=%s', $this->getConfig(), $this->getDbName()));
		$this->connection->connect();
	}


	public function testFetch(): void
	{
		$this->connection->query('
			CREATE TABLE test(
				id serial,
  				name text
			);
		');
		$this->connection->query('INSERT INTO test(name) VALUES(?)', 'phpgsql');

		$query = $this->connection
			->select(['id', 'name'])
			->from('test');

		$row = $query->fetch();
		if ($row === NULL) {
			throw new \RuntimeException('No data from database were returned');
		}

		$query->free();

		Tester\Assert::same(1, $row->id);
		Tester\Assert::same('phpgsql', $row->name);
	}


	public function testFetchSingle(): void
	{
		$this->connection->query('
			CREATE TABLE test(
				id integer
			);
		');
		$this->connection->query('INSERT INTO test(id) VALUES(?)', 999);

		$query = $this->connection
			->select(['id'])
			->from('test')
			->limit(1);

		$id = $query->fetchSingle();

		$query->free();

		Tester\Assert::same(999, $id);
	}


	public function testFetchAll(): void
	{
		$this->connection->query('
			CREATE TABLE test(
				id serial,
				type integer,
				name character varying
			);
		');
		$this->connection->query('INSERT INTO test(type, name) SELECT generate_series, \'name\' || generate_series FROM generate_series(3, 1, -1)');

		$query = $this->connection
			->select(['id', 'type', 'name'])
			->from('test')
			->orderBy(['id']);

		Tester\Assert::same(3, $query->count());

		$rows = $query->fetchAll();

		Tester\Assert::same(['id' => 1, 'type' => 3, 'name' => 'name3'], $rows[0]->toArray());
		Tester\Assert::same(['id' => 2, 'type' => 2, 'name' => 'name2'], $rows[1]->toArray());
		Tester\Assert::same(['id' => 3, 'type' => 1, 'name' => 'name1'], $rows[2]->toArray());

		$query->free();
	}


	public function testFetchAssocSimple(): void
	{
		$this->connection->query('
			CREATE TABLE test(
				id serial,
				type integer,
				name character varying
			);
		');
		$this->connection->query('INSERT INTO test(type, name) SELECT generate_series, \'name\' || generate_series FROM generate_series(3, 1, -1)');

		$query = $this->connection
			->select(['id', 'type', 'name'])
			 ->from('test')
			 ->orderBy(['id']);

		$rows = $query->fetchAssoc('type');

		Tester\Assert::same(['id' => 1, 'type' => 3, 'name' => 'name3'], $rows[3]->toArray());
		Tester\Assert::same(['id' => 2, 'type' => 2, 'name' => 'name2'], $rows[2]->toArray());
		Tester\Assert::same(['id' => 3, 'type' => 1, 'name' => 'name1'], $rows[1]->toArray());

		$query->free();
	}


	public function testFetchPairs(): void
	{
		$this->connection->query('
			CREATE TABLE test(
				id serial,
				name character varying
			);
		');
		$this->connection->query('INSERT INTO test(name) SELECT \'name\' || generate_series FROM generate_series(3, 1, -1)');

		$query = $this->connection
			->select(['id', 'name'])
			->from('test')
			->orderBy(['id']);

		$rows = $query->fetchPairs();

		Tester\Assert::same([1 => 'name3', 2 => 'name2', 3 => 'name1'], $rows);

		$query->free();
	}


	public function testResultIterator(): void
	{
		$this->connection->query('
			CREATE TABLE test(
				id serial,
				name character varying
			);
		');
		$this->connection->query('INSERT INTO test(name) SELECT \'name\' || generate_series FROM generate_series(3, 1, -1)');

		$query = $this->connection
			->select(['id', 'name'])
			->from('test')
			->orderBy(['id']);

		Tester\Assert::same(3, \count($query));

		$expected = [
			['id' => 1, 'name' => 'name3'],
			['id' => 2, 'name' => 'name2'],
			['id' => 3, 'name' => 'name1'],
		];
		foreach ($query as $i => $row) {
			Tester\Assert::same($expected[$i], $row->toArray());
		}

		$query->free();
	}


	public function testAffectedRows(): void
	{
		$this->connection->query('
			CREATE TABLE test(
				id serial,
  				name text
			);
		');
		$query = $this->connection
			->insert('test', ['name'])
			->select(['\'name\' || generate_series FROM generate_series(3, 1, -1)']);

		Tester\Assert::same(3, $query->getAffectedRows());

		$query->free();
	}


	public function testReexecute(): void
	{
		$this->connection->query('
			CREATE TABLE test(
				id integer
			);
		');
		$this->connection->query('INSERT INTO test(id) VALUES(?)', 999);

		$query = $this->connection
			->select(['id'])
			->from('test')
			->limit(1);

		Tester\Assert::same(999, $query->fetchSingle());

		$this->connection->query('UPDATE test SET id = ? WHERE id = ?', 888, 999);

		Tester\Assert::same(888, $query->reexecute()->fetchSingle());

		$query->free();
	}


	public function testFreeWithoutResult(): void
	{
		Tester\Assert::exception(function (): void {
			$this->connection->select([1])->free();
		}, Fluent\Exceptions\FluentException::class, NULL, Fluent\Exceptions\FluentException::YOU_MUST_EXECUTE_FLUENT_BEFORE_THAT);
	}


	public function testUpdateExecuted(): void
	{
		$query = $this->connection->select([1]);

		$query->fetchSingle();

		Tester\Assert::exception(static function () use ($query): void {
			$query->from('table');
		}, Fluent\Exceptions\FluentException::class, NULL, Fluent\Exceptions\FluentException::CANT_UPDATE_FLUENT_AFTER_EXECUTE);
	}


	protected function tearDown(): void
	{
		$this->connection->close();
		parent::tearDown();
	}

}

(new FluentFetchTest())->run();