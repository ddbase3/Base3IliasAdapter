<?php declare(strict_types=1);

use Base3Ilias\Base3\Base3IliasRuntime;
use ILIAS\DI\Container;

/**
 * @ilCtrl_IsCalledBy ilBase3IliasAdapterAjaxGUI: ilUIPluginRouterGUI
 */
class ilBase3IliasAdapterAjaxGUI {

	protected Container $dic;
	protected ilCtrl $ctrl;

	public function __construct() {
		$this->dic = $GLOBALS['DIC'];
		$this->ctrl = $this->dic->ctrl();

		Base3IliasRuntime::bootOnce(false, true);
	}

	public function executeCommand(): void {
		$cmd = $this->ctrl->getCmd('dispatch');

		if (!in_array($cmd, ['dispatch'], true)) {
			$cmd = 'dispatch';
		}

		$this->$cmd();
	}

	protected function dispatch(): void {
		echo Base3IliasRuntime::dispatch();
		exit;
	}
}
