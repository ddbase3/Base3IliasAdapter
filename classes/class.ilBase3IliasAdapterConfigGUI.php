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

		$html = ''
			. '<div class="base3ilias-general">'
			. '<h2>' . $this->escape($title) . '</h2>'

			. '<p class="lead">'
			. 'Das Projekt <strong>Base3Ilias</strong> verbindet <strong>ILIAS</strong> mit dem <strong>BASE3 Framework</strong> '
			. 'und erweitert ILIAS um moderne technische Bausteine, ohne dass dafür ein separates externes System betrieben werden muss.'
			. '</p>'

			. '<div class="grid">'
			. '  <div class="card">'
			. '    <h3>BASE3 EcoSystem in ILIAS</h3>'
			. '    <ul>'
			. '      <li><strong>Plugins laufen vollständig in ILIAS:</strong> Services, Routing und UI werden direkt im ILIAS-Kontext ausgeführt.</li>'
			. '      <li><strong>Modular & erweiterbar:</strong> BASE3-Plugins ergänzen Funktionen sauber, ohne Core-Patches.</li>'
			. '      <li><strong>Stabile Architektur:</strong> klare Interfaces, ClassMap-Discovery und nachvollziehbare Service-Strukturen.</li>'
			. '    </ul>'
			. '  </div>'

			. '  <div class="card">'
			. '    <h3>Technische Mittel, die ILIAS so nicht bietet</h3>'
			. '    <ul>'
			. '      <li><strong>Dependency Injection + Auto-Wiring:</strong> Services werden konsistent aufgelöst, getestet und wiederverwendet.</li>'
			. '      <li><strong>Worker / Jobs:</strong> Hintergrundprozesse für Queue-Verarbeitung, Sync, Cleanup und periodische Aufgaben.</li>'
			. '      <li><strong>Logging & Observability:</strong> strukturierte Scopes, Live-Tail und aussagekräftige Betriebsdaten.</li>'
			. '      <li><strong>Microservice-Konnektoren:</strong> saubere Anbindung externer Systeme (optional) – ohne ILIAS zu verbiegen.</li>'
			. '    </ul>'
			. '  </div>'

			. '  <div class="card">'
			. '    <h3>KI-Komponenten & Wissensarbeit</h3>'
			. '    <p>'
			. '      BASE3 ergänzt ILIAS um eine robuste Grundlage für KI-Use-Cases – mit klaren Pipelines statt „Magie“.'
			. '    </p>'
			. '    <ul>'
			. '      <li><strong>Agent Flows:</strong> modulare Verarbeitungsschritte (Extractor → Parser → Chunker → Embedding → VectorStore).</li>'
			. '      <li><strong>Chatbot / RAG:</strong> Retrieval über Vektor-Datenbank, Filter (z.B. Subtree/ACL) und nachvollziehbare Treffer.</li>'
			. '      <li><strong>Transparenz statt Blackbox:</strong> Admin-Dashboards zeigen Fortschritt, Mengen, Fehler und letzte Aktionen.</li>'
			. '    </ul>'
			. '  </div>'

			. '  <div class="card">'
			. '    <h3>Reporting & Daten-Tools</h3>'
			. '    <ul>'
			. '      <li><strong>Reporting-Displays:</strong> konfigurierbare Tabellen/Ansichten mit konsistenter Datenquelle.</li>'
			. '      <li><strong>Strukturierte Query-Layer:</strong> einheitliche Datenabfragen (inkl. Join-Auflösung) für wiederverwendbare Reports.</li>'
			. '      <li><strong>Frontend-Assets:</strong> ClientStack bündelt JS/CSS sauber und kontrolliert (ohne Wildwuchs).</li>'
			. '    </ul>'
			. '  </div>'
			. '</div>'

			. '<div class="note">'
			. '  <img src="Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Base3IliasAdapter/assets/logo.svg" '
			. '       alt="BASE3" '
			. '       style="height:1.5em;margin-right:6px;float:right;">'
			. '  <strong>Credentials:</strong> Dieses Projekt ist Teil des '
			. '  <strong>BASE3 EcoSystem</strong> (GPL v3.0), '
			. '  entwickelt und gepflegt von <strong>Daniel Dahme</strong>. '
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
