<?php

use Base3\Api\IClassMap;
use Base3\Api\IContainer;
use Base3\Api\IPlugin;
use Base3\Api\IRequest;
use Base3\Core\PluginClassMap;
use Base3\Core\Request;
use Base3\Core\ServiceLocator;
use Base3\Hook\HookManager;
use Base3\Hook\IHookListener;
use Base3\Hook\IHookManager;
use Base3\ServiceSelector\Api\IServiceSelector;
use Base3\ServiceSelector\Standard\StandardServiceSelector;
use Base3Ilias\Base3IliasServiceLocator;
use Base3Ilias\IliasPsrContainer;
use ILIAS\DI\Container;

class ilBase3IliasAdapterUIHookGUI extends ilUIHookPluginGUI {

    public function __construct() {
        $this->base3IliasBootstrap();
    }

    public function getHTML(string $a_comp, string $a_part, array $a_par = array()): array {
        return ['mode' => ilUIHookPluginGUI::KEEP, 'html' => ''];
    }

    // Private methods

    private function base3IliasBootstrap() {

        if (!defined('DIR_ILIAS')) define('DIR_ILIAS', realpath(__DIR__ . '/../../../../../../../../..') . DIRECTORY_SEPARATOR);
        if (!defined('DIR_TMP')) define('DIR_TMP', '/srv/www/data/ilias10/base3/'); // TODO: in config auslagern
        if (!defined('DIR_SRC')) define('DIR_SRC', DIR_ILIAS . 'components/Base3/Base3Framework/src/');
        if (!defined('DIR_TEST')) define('DIR_TEST', DIR_ILIAS . 'components/Base3/Base3Framework/test/');
        if (!defined('DIR_PLUGIN')) define('DIR_PLUGIN', DIR_ILIAS . 'components/Base3/');
        if (!defined('DIR_LOCAL')) define('DIR_LOCAL', DIR_TMP);

        // Debug mode - 0: aus, 1: an, ggfs noch höhere Stufen?
        putenv('DEBUG=1');

        // error handling
        ini_set('display_errors', getenv('DEBUG') ? 1 : 0);
        ini_set('display_startup_errors', getenv('DEBUG') ? 1 : 0);
        error_reporting(getenv('DEBUG') ? E_ALL | E_STRICT : 0);

        // service locator
        $servicelocator = new Base3IliasServiceLocator();
        ServiceLocator::useInstance($servicelocator);
        $servicelocator
            ->set('servicelocator', $servicelocator, IContainer::SHARED)
            ->set(IRequest::class, Request::fromGlobals(), IContainer::SHARED)
            ->set(IContainer::class, 'servicelocator', IContainer::ALIAS)
            ->set(IHookManager::class, fn() => new HookManager, ServiceLocator::SHARED)
            ->set('classmap', new PluginClassMap($servicelocator), IContainer::SHARED)
            ->set(IClassMap::class, 'classmap', IContainer::ALIAS)
            ->set(IServiceSelector::class, StandardServiceSelector::getInstance(), IContainer::SHARED);

        // fill container with ILIAS services
        $servicelocator->setIliasContainer(new IliasPsrContainer($GLOBALS['DIC']));
        $servicelocator->set(\ILIAS\DI\Container::class, $GLOBALS['DIC'], IContainer::SHARED);

        // hooks
        $hookManager = $servicelocator->get(IHookManager::class);
        $listeners = $servicelocator->get(IClassMap::class)->getInstancesByInterface(IHookListener::class);
        foreach ($listeners as $listener) $hookManager->addHookListener($listener);
        $hookManager->dispatch('bootstrap.init');

        // plugins
        $plugins = $servicelocator->get(IClassMap::class)->getInstancesByInterface(IPlugin::class);
        foreach ($plugins as $plugin) $plugin->init();
        $hookManager->dispatch('bootstrap.start');

        // fill ilias container with base3 services
        $services = $servicelocator->getServiceList();
        foreach ($services as $service) {
            if (isset($GLOBALS['DIC'][$service])) continue;
            $GLOBALS['DIC'][$service] = $servicelocator->get($service);
        }

        // go
        $hookManager->dispatch('bootstrap.finish');
    }
}
