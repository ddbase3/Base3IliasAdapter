<?php declare(strict_types=1);

/**
 * Class ilBase3IliasAdapterAdministrationGUI
 * @author Daniel Dahme <dahme@qualitus.de>
 * @ilCtrl_isCalledBy ilBase3IliasAdapterAdministrationGUI: ilAdministrationGUI
 */
class ilBase3IliasAdapterAdministrationGUI extends ilObjectGUI {
	protected ilBase3IliasAdapterPlugin $plugin;

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
		$cmd = $this->ctrl->getCmd('view');

		$this->prepareBase3Output();

		switch ($cmd) {
			case 'applications':
				$this->applicationsObject();
				break;

			case 'view':
			default:
				$this->viewObject();
				break;
		}
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
		$this->tabs_gui->addTab(
			'view',
			$this->txt('base3_admin_tab_tools', 'Tools'),
			$this->ctrl->getLinkTarget($this, 'view')
		);

		$this->tabs_gui->addTab(
			'applications',
			$this->txt('base3_admin_tab_applications', 'Applications'),
			$this->ctrl->getLinkTarget($this, 'applications')
		);
	}

	public function viewObject(): void {
		$this->tabs_gui->activateTab('view');
		$this->tpl->setContent($this->buildToolsHtml());
	}

	public function applicationsObject(): void {
		$this->tabs_gui->activateTab('applications');
		$this->tpl->setContent($this->buildApplicationsHtml());
	}

	protected function buildToolsHtml(): string {
		$html = [];
		$html[] = '<div class="container-fluid">';
		$html[] = '<div class="row">';
		$html[] = '<div class="col-xs-12">';

		$html[] = '<div class="panel panel-default">';
		$html[] = '<div class="panel-heading">';
		$html[] = '<h3 class="panel-title">' . $this->escape($this->txt('base3_admin_tools_title', 'BASE3 Tools')) . '</h3>';
		$html[] = '</div>';
		$html[] = '<div class="panel-body">';
		$html[] = '<p>' . $this->escape($this->txt(
			'base3_admin_tools_intro',
			'Future BASE3-provided tools can be listed and configured here.'
		)) . '</p>';
		$html[] = '<ul class="list-group">';
		$html[] = '<li class="list-group-item">' . $this->escape($this->txt('base3_admin_example_tools_item_1', 'Example Tool 1')) . '</li>';
		$html[] = '<li class="list-group-item">' . $this->escape($this->txt('base3_admin_example_tools_item_2', 'Example Tool 2')) . '</li>';
		$html[] = '</ul>';
		$html[] = '</div>';
		$html[] = '</div>';

		$html[] = '</div>';
		$html[] = '</div>';
		$html[] = '</div>';

		return implode('', $html);
	}

	protected function buildApplicationsHtml(): string {
		$html = [];
		$html[] = '<div class="container-fluid">';
		$html[] = '<div class="row">';
		$html[] = '<div class="col-xs-12">';

		$html[] = '<div class="panel panel-default">';
		$html[] = '<div class="panel-heading">';
		$html[] = '<h3 class="panel-title">' . $this->escape($this->txt('base3_admin_applications_title', 'BASE3 Applications')) . '</h3>';
		$html[] = '</div>';
		$html[] = '<div class="panel-body">';
		$html[] = '<p>' . $this->escape($this->txt(
			'base3_admin_applications_intro',
			'Future BASE3-provided applications and extras can be listed and configured here.'
		)) . '</p>';
		$html[] = '<ul class="list-group">';
		$html[] = '<li class="list-group-item">' . $this->escape($this->txt('base3_admin_example_applications_item_1', 'Example Application 1')) . '</li>';
		$html[] = '<li class="list-group-item">' . $this->escape($this->txt('base3_admin_example_applications_item_2', 'Example Application 2')) . '</li>';
		$html[] = '</ul>';
		$html[] = '</div>';
		$html[] = '</div>';

		$html[] = '</div>';
		$html[] = '</div>';
		$html[] = '</div>';

		return implode('', $html);
	}

	protected function txt(string $key, string $fallback): string {
		$txt = $this->plugin->txt($key);

		if ($txt === $key || trim((string) $txt) === '') {
			return $fallback;
		}

		return $txt;
	}

	protected function escape(string $value): string {
		return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
	}
}
