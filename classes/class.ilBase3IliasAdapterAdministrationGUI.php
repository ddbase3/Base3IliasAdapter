<?php declare(strict_types=1);

use Base3\Api\IClassMap;
use Base3\Api\IDisplay;
use Base3\Translation\Api\ITranslation;
use Base3Ilias\Api\IBase3IliasSettings;

/**
 * Class ilBase3IliasAdapterAdministrationGUI
 * @author Daniel Dahme <dahme@qualitus.de>
 * @ilCtrl_isCalledBy ilBase3IliasAdapterAdministrationGUI: ilAdministrationGUI
 */
class ilBase3IliasAdapterAdministrationGUI extends ilObjectGUI {

	public function __construct($a_data, int $a_id, bool $a_call_by_reference = true, bool $a_prepare_output = true) {
		global $DIC;

		$this->type = 'adm';

		parent::__construct($a_data, $a_id, $a_call_by_reference, $a_prepare_output);

		$this->tpl = $DIC->ui()->mainTemplate();
		$this->toolbar = $DIC->toolbar();
		$this->lng->loadLanguageModule('administration');
	}

	public function executeCommand(): void {
		$this->tpl->addJavaScript('components/Base3/ClientStack/assetloader/assetloader.min.js');
		$this->tpl->addJavaScript('components/Base3/ClientStack/jqueryui/jquery-ui.js');
		$this->tpl->addCss('components/Base3/ClientStack/jqueryui/jquery-ui.css');

		$this->setTitleAndDescription();

		$display = $this->getDisplay(
			'tabcontroldisplay',
			[
				'tabs' => $this->getAdministrationConfig(),
				'active' => $this->ctrl->getCmd('view'),
				'empty_message' => $this->pluginTxt('base3_admin_empty'),
			]
		);

		$this->tpl->setContent($display instanceof IDisplay ? $display->getOutput() : '');
	}

	protected function setTitleAndDescription(): void {
		$this->tpl->setTitle($this->pluginTxt('base3_admin_page_title'));
		$this->tpl->setDescription($this->pluginTxt('base3_admin_page_description'));
	}

	protected function getAdministrationConfig(): array {
		$config = [];

		foreach($this->getSettings()->getAdministrationConfig() as $tab) {
			if(!is_array($tab)) {
				continue;
			}

			$tabName = isset($tab['name']) && is_scalar($tab['name'])
				? trim((string) $tab['name'])
				: '';

			if($tabName === '') {
				continue;
			}

			$tab['label'] = $this->translateAdministrationLabel(
				'base3_admin_tab_' . $tabName,
				isset($tab['label']) && is_scalar($tab['label']) ? (string) $tab['label'] : $tabName
			);

			$displays = [];
			foreach($tab['displays'] ?? [] as $display) {
				if(!is_array($display)) {
					continue;
				}

				$displayName = isset($display['name']) && is_scalar($display['name'])
					? trim((string) $display['name'])
					: '';

				if($displayName === '') {
					continue;
				}

				$display['label'] = $this->translateAdministrationLabel(
					'base3_admin_subtab_' . $displayName,
					isset($display['label']) && is_scalar($display['label'])
						? (string) $display['label']
						: $displayName
				);

				$displays[] = $display;
			}

			$tab['displays'] = $displays;
			$config[] = $tab;
		}

		return $config;
	}

	protected function pluginTxt(string $key, ?string $fallback = null): string {
		$text = ilBase3IliasAdapterPlugin::getInstance()->txt($key);

		if($fallback !== null && ($text === $key || trim($text) === '')) {
			return $fallback;
		}

		return $text;
	}


	protected function translateAdministrationLabel(string $key, string $fallback): string {
		return $this->getTranslation()->translate(
			'Administration',
			'administration',
			$key,
			$fallback
		);
	}

	protected function getDisplay(string $name, mixed $data = null): ?IDisplay {
		$instance = $this->getDisplayInstance($name);

		if(!$instance instanceof IDisplay) {
			return null;
		}

		$instance->setData($data);
		return $instance;
	}

	protected function getDisplayInstance(string $name): ?IDisplay {
		global $DIC;

		$classmap = $DIC[IClassMap::class];
		$instance = $classmap->getInstanceByInterfaceName(IDisplay::class, $name);

		return $instance instanceof IDisplay ? $instance : null;
	}

	protected function getSettings(): IBase3IliasSettings {
		global $DIC;

		return $DIC[IBase3IliasSettings::class];
	}


	protected function getTranslation(): ITranslation {
		global $DIC;

		return $DIC[ITranslation::class];
	}

}
