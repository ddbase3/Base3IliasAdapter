<?php declare(strict_types=1);

use Base3\Api\IClassMap;
use Base3\Api\IDisplay;

/**
 * Class ilBase3IliasAdapterAdministrationGUI
 * @author Daniel Dahme <dahme@qualitus.de>
 * @ilCtrl_isCalledBy ilBase3IliasAdapterAdministrationGUI: ilAdministrationGUI
 */
class ilBase3IliasAdapterAdministrationGUI extends ilObjectGUI {
	protected ilBase3IliasAdapterPlugin $plugin;

	protected array $displayConfig = [
		[
			'name' => 'base3',
			'label' => 'BASE3',
			'displays' => [
				[
					'name' => 'logadmindisplay',
					'label' => 'Log'
				], [
					'name' => 'servicesadmindisplay',
					'label' => 'Services'
				], [
					'name' => 'configurationadmindisplay',
					'label' => 'Configuration'
				], [
					'name' => 'jobsadmindisplay',
					'label' => 'Jobs'
				]
			]
		], [
			'name' => 'providers',
			'label' => 'Providers',
			'displays' => [
				[
					'name' => 'aiprovideradmindisplay',
					'label' => 'AI Providers'
				], [
					'name' => 'vectordbprovideradmindisplay',
					'label' => 'Vector DB Providers'
				]
			]
		], [
			'name' => 'agenttools',
			'label' => 'Agent Tools',
			'displays' => [
				[
					'name' => 'agenttoollogadmindisplay',
					'label' => 'Tool Log'
				], [
					'name' => 'agenttooltestadmindisplay',
					'label' => 'Tool Test'
				]
			]
		], [
			'name' => 'embedding',
			'label' => 'Embedding',
			'displays' => [
				[
					'name' => 'iliasembeddingprogressadmindisplay',
					'label' => 'Embedding Progress'
				], [
					'name' => 'iliasembeddingqueueadmindisplay',
					'label' => 'Embedding Queue'
				], [
					'name' => 'iliassourcekindenqueueadmindisplay',
					'label' => 'Source Kinds'
				], [
					'name' => 'iliasvectorpointsadmindisplay',
					'label' => 'Vector Points'
				], [
					'name' => 'iliasvectorstoreadmindisplay',
					'label' => 'Vector Store'
				]
			]
		], [
			'name' => 'reporting',
			'label' => 'Reporting',
			'displays' => [
				[
					'name' => 'datahawkschemadisplay',
					'label' => 'DB Schema',
					'data' => ['domain' => 'ilias']
				]
			]
		]
	];

	public function __construct($a_data, int $a_id, bool $a_call_by_reference = true, bool $a_prepare_output = true) {
		global $DIC;

		$this->type = 'adm';

		parent::__construct($a_data, $a_id, $a_call_by_reference, $a_prepare_output);

		$this->plugin = ilBase3IliasAdapterPlugin::getInstance();
		$this->tpl = $DIC->ui()->mainTemplate();
		$this->toolbar = $DIC->toolbar();
		$this->lng->loadLanguageModule('administration');
	}

	public function executeCommand(): void {

		$this->tpl->addJavaScript('components/Base3/ClientStack/assetloader/assetloader.min.js');
		$this->tpl->addJavaScript('components/Base3/ClientStack/jqueryui/jquery-ui.js');
		$this->tpl->addCss('components/Base3/ClientStack/jqueryui/jquery-ui.css');

		$default_display = $this->getDefaultDisplayName();
		$cmd = $this->ctrl->getCmd($default_display !== '' ? $default_display : 'view');

		$this->prepareBase3Output();

		$resolved = $this->resolveDisplayConfig($cmd);

		if ($resolved == null) {
			$this->tpl->setContent('');
			return;
		}

		$this->setSubTabs($resolved['tab'], $resolved['display']['name']);

		$display = $this->getDisplay($resolved['display']['name'], $resolved['display']['data'] ?? null);
		$html = $display == null ? '' : $display->getOutput();
		$this->tpl->setContent($html);
	}

	protected function prepareBase3Output(): void {
		$this->setTitleAndDescription();
		$this->setTabs();
	}

	protected function setTitleAndDescription(): void {
		$this->tpl->setTitle($this->txt('base3_admin_page_title', 'BASE3 Administration'));
		$this->tpl->setDescription(
			$this->txt(
				'base3_admin_page_description',
				'Central administration page for BASE3 tools, applications and extras.'
			)
		);
	}

	protected function setTabs(): void {
		foreach ($this->displayConfig as $tab) {
			if (empty($tab['displays']) || empty($tab['displays'][0]['name'])) {
				continue;
			}

			$this->tabs_gui->addTab(
				$tab['name'],
				$this->txt('base3_admin_tab_' . $tab['name'], $tab['label']),
				$this->ctrl->getLinkTarget($this, $tab['displays'][0]['name'])
			);
		}
	}

	protected function setSubTabs(array $tab, string $active_subtab): void {
		$this->tabs_gui->activateTab($tab['name']);

		foreach ($tab['displays'] as $display) {
			$this->tabs_gui->addSubTab(
				$display['name'],
				$this->txt('base3_admin_subtab_' . $display['name'], $display['label']),
				$this->ctrl->getLinkTarget($this, $display['name'])
			);
		}

		$this->tabs_gui->activateSubTab($active_subtab);
	}

	protected function getDefaultDisplayName(): string {
		$resolved = $this->getDefaultDisplayConfig();

		if ($resolved == null) {
			return '';
		}

		return $resolved['display']['name'];
	}

	protected function getDefaultDisplayConfig(): ?array {
		foreach ($this->displayConfig as $tab) {
			if (empty($tab['displays']) || empty($tab['displays'][0])) {
				continue;
			}

			return [
				'tab' => $tab,
				'display' => $tab['displays'][0]
			];
		}

		return null;
	}

	protected function resolveDisplayConfig(string $display_name): ?array {
		foreach ($this->displayConfig as $tab) {
			foreach ($tab['displays'] ?? [] as $display) {
				if (($display['name'] ?? '') !== $display_name) {
					continue;
				}

				return [
					'tab' => $tab,
					'display' => $display
				];
			}
		}

		return $this->getDefaultDisplayConfig();
	}

	protected function txt(string $key, string $fallback): string {
		$txt = $this->plugin->txt($key);

		if ($txt === $key || trim((string) $txt) === '') {
			return $fallback;
		}

		return $txt;
	}

	protected function getDisplay(string $name, mixed $data = null): ?IDisplay {
		global $DIC;
		$classmap = $DIC[IClassMap::class];
		$instances = $classmap->getInstances([
			'interface' => IDisplay::class,
			'name' => $name
		]);
		if (empty($instances)) return null;
		$instance = $instances[0];
		$instance->setData($data);
		return $instance;
	}
}
