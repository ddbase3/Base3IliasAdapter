<?php declare(strict_types=1);

use Base3\Base3Ilias\PageComponent\AbstractPageComponentConfigGUI;
use UiFoundation\Api\IAdminDisplay;

/**
 * Base3Ilias Adapter - ILIAS component settings configuration GUI.
 *
 * This class is called by ILIAS' Component Settings GUI and renders a tab-based
 * configuration UI:
 * - A static "General" tab with explanatory information.
 * - One dynamic tab per ClientStack admin display (IAdminDisplay) discovered via class map.
 *
 * @ilCtrl_isCalledBy ilBase3IliasAdapterConfigGUI: ilObjComponentSettingsGUI
 */
class ilBase3IliasAdapterConfigGUI extends AbstractPageComponentConfigGUI {
	protected const TAB_GENERAL = 'general';

	/**
	 * Execute command routed by ilPluginConfigGUI::executeCommand().
	 *
	 * Notes on stability:
	 * - We only allow known tab commands (whitelist).
	 * - Unknown commands fall back to "general".
	 * - Missing/invalid display instances are handled gracefully.
	 *
	 * @throws ilCtrlException
	 */
	public function performCommand(string $cmd): void {
		$this->init();

		$displays = $this->collectAdminDisplays();
		$this->buildTabs($displays);

		$allowedTabs = array_merge([self::TAB_GENERAL], array_keys($displays));
		$cmd = in_array($cmd, $allowedTabs, true) ? $cmd : self::TAB_GENERAL;

		$this->tabs->activateTab($cmd);

		if ($cmd === self::TAB_GENERAL) {
			$this->renderGeneralTab();
			return;
		}

		$display = $displays[$cmd] ?? null;
		if (!$display instanceof IAdminDisplay) {
			$this->tpl->setContent($this->renderErrorBox(
				$this->txt('error_tab_not_found_title'),
				$this->txt('error_tab_not_found_message')
			));
			return;
		}

		// Admin displays are responsible for generating their own HTML output.
		// We keep this adapter thin and predictable.
		$this->tpl->setContent((string) $display->getOutput());
	}

	/**
	 * Discover all admin displays via the class map.
	 *
	 * We key them by $display->getName() (technical name) so command routing stays stable.
	 *
	 * @return array<string, IAdminDisplay> key = tab name / command, value = display instance
	 */
	protected function collectAdminDisplays(): array {
		$result = [];

		$instances = $this->classmap->getInstances(['interface' => IAdminDisplay::class]);
		foreach ($instances as $instance) {
			if (!$instance instanceof IAdminDisplay) {
				continue;
			}

			$tabName = trim((string) $instance->getName());
			if ($tabName === '' || $tabName === self::TAB_GENERAL) {
				// Skip empty names and prevent collisions with reserved tabs.
				continue;
			}

			$result[$tabName] = $instance;
		}

		ksort($result); // Stable ordering of dynamic tabs.
		return $result;
	}

	/**
	 * Build all tabs (General + dynamic admin display tabs).
	 *
	 * @param array<string, IAdminDisplay> $displays
	 */
	protected function buildTabs(array $displays): void {
		$this->tabs->addTab(
			self::TAB_GENERAL,
			$this->txt('tab_general'),
			$this->ctrl->getLinkTarget($this, self::TAB_GENERAL)
		);

		foreach ($displays as $tabName => $_display) {
			$this->tabs->addTab(
				$tabName,
				$this->txt('tab_' . $tabName),
				$this->ctrl->getLinkTarget($this, $tabName)
			);
		}
	}

	/**
	 * Tab: General
	 *
	 * This intentionally contains no "real" configuration yet. It explains what Base3Ilias
	 * (and this adapter) are supposed to do, so admins understand the moving parts.
	 */
	public function renderGeneralTab(): void {
		$html = ''
			. '<div class="base3ilias-general">'
			. '<h2>' . $this->escape($this->txt('general_title')) . '</h2>'

			. '<p class="lead">'
			. $this->escape($this->txt('general_lead'))
			. '</p>'

			. '<div class="grid">'
			. '  <div class="card">'
			. '    <h3>' . $this->escape($this->txt('general_ecosystem_title')) . '</h3>'
			. '    <ul>'
			. $this->renderListItem('general_ecosystem_plugins_label', 'general_ecosystem_plugins_text')
			. $this->renderListItem('general_ecosystem_modular_label', 'general_ecosystem_modular_text')
			. $this->renderListItem('general_ecosystem_architecture_label', 'general_ecosystem_architecture_text')
			. '    </ul>'
			. '  </div>'

			. '  <div class="card">'
			. '    <h3>' . $this->escape($this->txt('general_technical_title')) . '</h3>'
			. '    <ul>'
			. $this->renderListItem('general_technical_di_label', 'general_technical_di_text')
			. $this->renderListItem('general_technical_workers_label', 'general_technical_workers_text')
			. $this->renderListItem('general_technical_microservices_label', 'general_technical_microservices_text')
			. $this->renderListItem('general_technical_replaceable_label', 'general_technical_replaceable_text')
			. '    </ul>'
			. '  </div>'

			. '  <div class="card">'
			. '    <h3>' . $this->escape($this->txt('general_ai_title')) . '</h3>'
			. '    <p>' . $this->escape($this->txt('general_ai_intro')) . '</p>'
			. '    <ul>'
			. $this->renderListItem('general_ai_flows_label', 'general_ai_flows_text')
			. $this->renderListItem('general_ai_chatbot_label', 'general_ai_chatbot_text')
			. $this->renderListItem('general_ai_dashboards_label', 'general_ai_dashboards_text')
			. '    </ul>'
			. '  </div>'

			. '  <div class="card">'
			. '    <h3>' . $this->escape($this->txt('general_reporting_title')) . '</h3>'
			. '    <ul>'
			. $this->renderListItem('general_reporting_displays_label', 'general_reporting_displays_text')
			. $this->renderListItem('general_reporting_queries_label', 'general_reporting_queries_text')
			. $this->renderListItem('general_reporting_assets_label', 'general_reporting_assets_text')
			. '    </ul>'
			. '  </div>'
			. '</div>'

			. '<div class="note">'
			. '  <img src="Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Base3IliasAdapter/assets/logo.svg" '
			. '       alt="BASE3" '
			. '       style="height:1.5em;margin-right:6px;float:right;">'
			. '  <strong>' . $this->escape($this->txt('general_credentials_label')) . ':</strong> '
			. $this->escape($this->txt('general_credentials_text'))
			. '</div>'

			. '<style>'
			. '.base3ilias-general{background:#fff;border:1px solid #d6d6d6;padding:16px;border-radius:4px;max-width:100%;font-family:Arial,sans-serif;color:#333;}'
			. '.base3ilias-general .lead{margin-top:6px;margin-bottom:14px;font-size:14px;color:#444;line-height:1.5;}'
			. '.base3ilias-general .mono{font-family:Consolas,monospace;}'
			. '.base3ilias-general .grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin-top:10px;}'
			. '@media(max-width:900px){.base3ilias-general .grid{grid-template-columns:1fr;}}'
			. '.base3ilias-general .card{border:1px solid #ddd;border-radius:6px;background:#fff;padding:12px;}'
			. '.base3ilias-general .card h3{margin:0 0 8px 0;font-size:14px;}'
			. '.base3ilias-general ul,.base3ilias-general ol{margin:0;padding-left:18px;color:#444;font-size:13px;line-height:1.45;}'
			. '.base3ilias-general p{margin:0 0 10px 0;color:#444;font-size:13px;line-height:1.45;}'
			. '.base3ilias-general .note{margin-top:12px;border:1px solid #e6e6e6;background:#fafafa;border-radius:6px;padding:10px;font-size:12px;color:#555;line-height:1.4;}'
			. '</style>';

		$this->tpl->setContent($html);
	}

	protected function renderListItem(string $labelKey, string $textKey): string {
		return '<li><strong>'
			. $this->escape($this->txt($labelKey))
			. ':</strong> '
			. $this->escape($this->txt($textKey))
			. '</li>';
	}

	/**
	 * Minimal HTML escaping helper.
	 *
	 * We keep this local to avoid relying on external helpers that may not exist in every context.
	 */
	protected function escape(string $value): string {
		return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
	}

	/**
	 * Simple, safe error box renderer (no external UI dependency).
	 */
	protected function renderErrorBox(string $headline, string $message): string {
		return ''
			. '<div class="alert alert-danger" role="alert">'
			. '<strong>' . $this->escape($headline) . '</strong><br>'
			. $this->escape($message)
			. '</div>';
	}
}
