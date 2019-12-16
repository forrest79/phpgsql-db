<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db\DataTypeCaches;

use Forrest79\PhPgSql\Db;

class PhpFile extends DbLoader
{
	/** @var array */
	private $cache = [];

	/** @var string */
	private $cacheDirectory;


	public function __construct(string $cacheDirectory)
	{
		$this->cacheDirectory = \rtrim($cacheDirectory, '\/');
	}


	public function load(Db\Connection $connection): array
	{
		$connectionConfig = $connection->getConnectionConfig();

		if (!isset($this->cache[$connectionConfig])) {
			$cacheFile = $this->getCacheFile($connectionConfig);
			if (!\is_file($cacheFile)) {
				if (!\is_dir($this->cacheDirectory)) {
					@\mkdir($this->cacheDirectory, 0777, TRUE); // @ - dir may already exist
				}

				$lockFile = $cacheFile . '.lock';
				$handle = \fopen($lockFile, 'c+');
				if (($handle === FALSE) || !\flock($handle, \LOCK_EX)) {
					throw new \RuntimeException(\sprintf('Unable to create or acquire exclusive lock on file \'%s\'.', $lockFile));
				}

				// cache still not exists
				if (!\is_file($cacheFile)) {
					$tempFile = $cacheFile . '.tmp';
					\file_put_contents(
						$tempFile,
						'<?php declare(strict_types=1);' . \PHP_EOL . \sprintf('return [%s];', self::prepareCacheArray($this->loadFromDb($connection)))
					);
					\rename($tempFile, $cacheFile); // atomic replace (in Linux)

					if (\function_exists('opcache_invalidate')) {
						\opcache_invalidate($cacheFile, TRUE);
					}
				}

				\flock($handle, \LOCK_UN);
				\fclose($handle);
				@\unlink($lockFile); // intentionally @ - file may become locked on Windows
			}

			$this->cache[$connectionConfig] = require $cacheFile;
		}

		return $this->cache[$connectionConfig];
	}


	public function clean(Db\Connection $connection): void
	{
		$connectionConfig = $connection->getConnectionConfig();

		@\unlink($this->getCacheFile($connectionConfig)); // intentionally @ - file may not exists
		unset($this->cache[$connectionConfig]);
	}


	private function getCacheFile(string $connectionConfig): string
	{
		return $this->cacheDirectory . \DIRECTORY_SEPARATOR . \md5($connectionConfig) . '.php';
	}


	private static function prepareCacheArray(array $data): string
	{
		$cache = '';
		foreach ($data as $oid => $typname) {
			$cache .= \sprintf("%d=>'%s',", $oid, \str_replace("'", "\\'", $typname));
		}
		return $cache;
	}

}
