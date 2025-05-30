<?php declare(strict_types=1);

/**
 * Class ilBase3IliasAdapterPlugin
 * @author Daniel Dahme <dahme@qualitus.de>
 * @ilCtrl_isCalledBy ilAdminOverviewLivePlugin: ilPCPluggedGUI
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

    /** @var self */
    protected static $instance;

    /** @var ilSetting */
    protected $settings;

    public static function getInstance() {
        if (self::$instance === NULL) {
            self::$instance = new self();
        }
	return self::$instance;
    }

    function getPluginName(): string {
        return self::PLUGIN_NAME;
    }

    /**
     * @return ilSetting
     */
    public function getSettings() : ilSetting {
        if ($this->settings === NULL) $this->settings = new ilSetting(self::PLUGIN_SETTINGS);
        return $this->settings;
    }

    /**
     * @return void
     */
    protected function init(): void {
        self::registerAutoloader();
        $this->settings = new ilSetting(self::PLUGIN_SETTINGS);
        $this->publishSelf();
    }


    /**
     * @return void
     */
    protected function publishSelf() {
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
    public function checkDependency(string $dep_name, $redirect = true) {
        global $DIC;

        if (!isset($DIC['de.qualitus.plugin.' . $dep_name])) {
            if ($redirect) {
                ilUtil::sendFailure('Abort because of missing dependency: ' . $dep_name, true);
                $DIC->ctrl()->redirectToURL(
                    $DIC->ctrl()->getLinkTargetByClass(
                        'ilObjComponentSettingsGUI', 'view', false, false, false
                    )
                );
            } else {
                ilUtil::sendInfo('Missing dependency: ' . $dep_name, true);
            }
            return false;
        }
        return true;
    }

    /**
     * @return void
     */
    public static function loadDependencies() {
        global $DIC;

        if (!file_exists(realpath(dirname(__FILE__)) . '/../dependencies.php')) {
            $DIC->logger()->root()->warning('File missing: dependencies.php');
            ilUtil::sendFailure('File missing: dependencies.php', true);
            return;
        }
        require_once(realpath(dirname(__FILE__)) . '/../dependencies.php');
        if (isset($dependencies) && !empty($dependencies)) {
            foreach ($dependencies as $dep_name => $dep_data) {
                if (!isset($DIC['de.qualitus.plugin.' . $dep_name])) {
                    $dep_plugin = ilPluginAdmin::getPluginObject(
                        $dep_data[0], $dep_data[1], $dep_data[2], $dep_data[3]
                    );
                    if (!isset($dep_plugin) || !$dep_plugin instanceof ilPlugin) {
                        $DIC->logger()->root()->debug('Could not load dependency: de.qualitus.plugin.' . $dep_name);
                        continue;
                    }
                    // workaround for plugins without
                    if (!isset($DIC['de.qualitus.plugin.' . $dep_name])) {
                        $DIC['de.qualitus.plugin.' . $dep_name] = $dep_plugin;
                    }
                }
            }
        }
    }

    /**
     * @return void
     */
    public static function registerAutoloader() {
/*
        global $DIC;

        if (!isset($DIC['qualitus.autoload'])) {
            require_once(realpath(dirname(__FILE__)) . '/Autoload/QualitusAutoloader.php');
            $Autoloader = new QualitusAutoloader();
            $Autoloader->register();
            $Autoloader->addNamespace('ILIAS\Plugin', '/Customizing/global/plugins');
            $DIC['qualitus.autoload'] = $Autoloader;
        }
        $DIC['qualitus.autoload']->addNamespace(self::PLUGIN_NS, realpath(dirname(__FILE__)));
 */
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
