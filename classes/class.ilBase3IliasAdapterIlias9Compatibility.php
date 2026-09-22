<?php declare(strict_types=1);

/**
 * Temporary ILIAS 9 compatibility bootstrap for BASE3.
 *
 * ILIAS 10+ loads BASE3 from the native components/Base3 layout. ILIAS 9 has
 * no component directory, so the adapter keeps the same BASE3 packages under
 * its local lib directory and prepares the directory constants and autoloaders
 * before Base3IliasRuntime is started.
 *
 * This class is intentionally isolated so ILIAS 9 support can be removed
 * without changing the normal ILIAS 10+ integration path.
 */
final class ilBase3IliasAdapterIlias9Compatibility {

	private const LEGACY_PLUGIN_PATH = 'Customizing/global/plugins/';
	private const MODERN_PLUGIN_PATH = 'public/Customizing/global/plugins/';

	public static function bootIfRequired(): void {
		$pluginRoot = realpath(dirname(__DIR__));
		if ($pluginRoot === false) {
			throw new RuntimeException('Base3IliasAdapter plugin directory could not be resolved.');
		}

		$iliasRoot = self::findIliasRoot($pluginRoot);
		if ($iliasRoot === null || !self::isIlias9Layout($pluginRoot, $iliasRoot)) {
			return;
		}

		self::defineDirectories($pluginRoot, $iliasRoot);
		self::registerAutoloaders();

		\Base3Ilias\Base3\Base3IliasRuntime::bootOnce(false, true);
	}

	private static function findIliasRoot(string $start): ?string {
		$current = $start;

		while (true) {
			if (is_file($current . DIRECTORY_SEPARATOR . 'ilias.ini.php')) {
				return $current;
			}

			$parent = dirname($current);
			if ($parent === $current) {
				return null;
			}

			$current = $parent;
		}
	}

	private static function isIlias9Layout(string $pluginRoot, string $iliasRoot): bool {
		$relativePath = self::relativePath($iliasRoot, $pluginRoot);
		if ($relativePath === null) {
			return false;
		}

		$relativePath = str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);

		if (str_starts_with($relativePath, self::MODERN_PLUGIN_PATH)) {
			return false;
		}

		return str_starts_with($relativePath, self::LEGACY_PLUGIN_PATH);
	}

	private static function defineDirectories(string $pluginRoot, string $iliasRoot): void {
		$base3Root = $pluginRoot . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR;
		$frameworkRoot = $base3Root . 'Base3Framework' . DIRECTORY_SEPARATOR;
		$base3IliasRoot = $base3Root . 'Base3Ilias' . DIRECTORY_SEPARATOR;

		if (!is_dir($frameworkRoot . 'src')) {
			throw new RuntimeException('BASE3 Framework not found in Base3IliasAdapter/lib/Base3Framework.');
		}

		if (!is_dir($base3IliasRoot . 'src')) {
			throw new RuntimeException('Base3Ilias not found in Base3IliasAdapter/lib/Base3Ilias.');
		}

		if (!defined('DIR_ILIAS')) define('DIR_ILIAS', rtrim($iliasRoot, '/\\') . DIRECTORY_SEPARATOR);
		if (!defined('DIR_COMPONENTS')) define('DIR_COMPONENTS', DIR_ILIAS . 'components' . DIRECTORY_SEPARATOR);
		if (!defined('DIR_BASE3')) define('DIR_BASE3', $base3Root);
		if (!defined('DIR_FRAMEWORK')) define('DIR_FRAMEWORK', $frameworkRoot);
		if (!defined('DIR_SRC')) define('DIR_SRC', DIR_FRAMEWORK . 'src' . DIRECTORY_SEPARATOR);
		if (!defined('DIR_TEST')) define('DIR_TEST', DIR_FRAMEWORK . 'test' . DIRECTORY_SEPARATOR);
		if (!defined('DIR_PLUGIN')) define('DIR_PLUGIN', DIR_BASE3);
	}

	private static function registerAutoloaders(): void {
		$autoloaderFile = DIR_SRC . 'Core' . DIRECTORY_SEPARATOR . 'Autoloader.php';
		if (!is_file($autoloaderFile)) {
			throw new RuntimeException('BASE3 autoloader not found: ' . $autoloaderFile);
		}

		require_once $autoloaderFile;

		\Base3\Core\Autoloader::registerPlugin(
			'Base3\\Base3Ilias\\',
			DIR_BASE3 . 'Base3Ilias' . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR
		);
		\Base3\Core\Autoloader::register();
	}

	private static function relativePath(string $basePath, string $path): ?string {
		$basePath = rtrim(str_replace('\\', '/', $basePath), '/');
		$path = rtrim(str_replace('\\', '/', $path), '/');

		if ($path === $basePath) {
			return '';
		}

		$prefix = $basePath . '/';
		if (!str_starts_with($path, $prefix)) {
			return null;
		}

		return substr($path, strlen($prefix));
	}
}
