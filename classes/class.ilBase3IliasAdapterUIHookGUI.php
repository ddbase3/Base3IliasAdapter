<?php declare(strict_types=1);

use Base3Ilias\Base3\Base3IliasRuntime;

class ilBase3IliasAdapterUIHookGUI extends ilUIHookPluginGUI {

	public function __construct() {
		Base3IliasRuntime::bootOnce(false, true);
	}

	public function getHTML(string $a_comp, string $a_part, array $a_par = []): array {
		return [
			'mode' => ilUIHookPluginGUI::KEEP,
			'html' => ''
		];
	}
}
