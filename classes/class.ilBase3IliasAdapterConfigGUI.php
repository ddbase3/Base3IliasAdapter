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
			$this->tpl->setContent($this->renderErrorBox('Tab not found', 'The requested admin display tab is not available.'));
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
	protected function renderGeneralTab(): void {
		$title = 'Base3Ilias Adapter';
		$html = '<h2>' . $this->escape($title) . '</h2>';
		$this->tpl->setContent($html);
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
