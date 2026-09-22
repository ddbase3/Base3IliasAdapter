<?php declare(strict_types=1);

/**
 * Class ilBase3IliasAdapterPlugin
 * @author Daniel Dahme <dahme@qualitus.de>
 */
class ilBase3IliasAdapterPlugin extends ilUserInterfaceHookPlugin

{
	/** @var string */
	const PLUGIN_ID = 'base3iliasadapter';

	/** @var string */
	const PLUGIN_NAME = 'Base3IliasAdapter';

	/** @var string */
	const PLUGIN_SETTINGS = 'base3iliasadapter';

	/** @var string */
	const PLUGIN_NS = 'Base3IliasAdapter';

	/** @var self|null */
	protected static $instance = NULL;

	/** @var ilSetting|null */
	protected $settings = NULL;

	public static function getInstance(): self {
		if (!(self::$instance instanceof self)) {
			throw new RuntimeException('Plugin instance not initialized yet.');
		}

		return self::$instance;
	}

	public function getPluginName(): string {
		return self::PLUGIN_NAME;
	}

	/**
	 * @return ilSetting
	 */
	public function getSettings(): ilSetting {
		if ($this->settings === NULL) {
			$this->settings = new ilSetting(self::PLUGIN_SETTINGS);
		}

		return $this->settings;
	}

	/**
	 * @return void
	 */
	protected function init(): void {
		global $DIC;

		self::$instance = $this;

		$this->settings = new ilSetting(self::PLUGIN_SETTINGS);
		$this->publishSelf();
		self::bootIlias9Compatibility();

		if ($DIC->isDependencyAvailable('globalScreen')) {
			require_once(__DIR__ . '/class.ilBase3IliasAdapterMainBarProvider.php');
			$this->provider_collection->setMainBarProvider(
				new ilBase3IliasAdapterMainBarProvider($DIC, $this)
			);

			require_once(__DIR__ . '/class.ilBase3IliasAdapterMetaBarProvider.php');
			$this->provider_collection->setMetaBarProvider(
				new ilBase3IliasAdapterMetaBarProvider($DIC, $this)
			);
		}
	}

	/**
	 * @return void
	 */
	protected function publishSelf(): void {
		global $DIC;

		if (!isset($DIC['de.qualitus.plugin.' . self::PLUGIN_ID])) {
			$DIC['de.qualitus.plugin.' . self::PLUGIN_ID] = $this;
		}

		self::loadDependencies();
	}

	/**
	 * @param string $dep_name
	 * @param bool $redirect
	 * @return bool
	 */
	public function checkDependency(string $dep_name, $redirect = true): bool {
		global $DIC;

		if (!isset($DIC['de.qualitus.plugin.' . $dep_name])) {
			if ($redirect) {
				ilUtil::sendFailure(sprintf($this->txt('missing_dependency_abort'), $dep_name), true);
				$DIC->ctrl()->redirectToURL(
					$DIC->ctrl()->getLinkTargetByClass(
						'ilObjComponentSettingsGUI',
						'view',
						false,
						false,
						false
					)
				);
			} else {
				ilUtil::sendInfo(sprintf($this->txt('missing_dependency_info'), $dep_name), true);
			}

			return false;
		}

		return true;
	}

	/**
	 * @return void
	 */
	public static function loadDependencies(): void {
		global $DIC;

		$dependency_file = realpath(dirname(__FILE__)) . '/../dependencies.php';

		if (!file_exists($dependency_file)) {
			$DIC->logger()->root()->warning('File missing: dependencies.php');
			ilUtil::sendFailure(self::getInstance()->txt('dependencies_file_missing'), true);
			return;
		}

		require_once($dependency_file);

		if (isset($dependencies) && !empty($dependencies)) {
			foreach ($dependencies as $dep_name => $dep_data) {
				if (!isset($DIC['de.qualitus.plugin.' . $dep_name])) {
					$dep_plugin = ilPluginAdmin::getPluginObject(
						$dep_data[0],
						$dep_data[1],
						$dep_data[2],
						$dep_data[3]
					);

					if (!isset($dep_plugin) || !$dep_plugin instanceof ilPlugin) {
						$DIC->logger()->root()->debug(
							'Could not load dependency: de.qualitus.plugin.' . $dep_name
						);
						continue;
					}

					if (!isset($DIC['de.qualitus.plugin.' . $dep_name])) {
						$DIC['de.qualitus.plugin.' . $dep_name] = $dep_plugin;
					}
				}
			}
		}
	}

	/**
	 * Boots the isolated ILIAS 9 compatibility path when the plugin is installed
	 * outside the public directory. ILIAS 10+ keeps its existing component-based
	 * integration unchanged.
	 */
	private static function bootIlias9Compatibility(): void {
		require_once __DIR__ . '/class.ilBase3IliasAdapterIlias9Compatibility.php';
		ilBase3IliasAdapterIlias9Compatibility::bootIfRequired();
	}

	/**
	 * @inheritdoc
	 */
	protected function afterActivation(): void {
	}

	/**
	 * @inheritdoc
	 */
	protected function beforeUninstall(): bool {
		return parent::deactivate();
	}
}
