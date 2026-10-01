<?php declare(strict_types=1);

use Base3\Api\IClassMap;
use Base3\Api\IDisplay;
use Base3\Api\IRequest;
use Base3Ilias\Api\IBase3IliasSettings;
use Base3Ilias\Base3\Base3IliasChatbotConfigService;
use Base3Ilias\Base3\Base3IliasRuntime;
use ILIAS\DI\Container;

/**
 * @ilCtrl_IsCalledBy ilBase3IliasAdapterAjaxGUI: ilUIPluginRouterGUI
 */
class ilBase3IliasAdapterAjaxGUI {

	private const DISPLAY_DATA_PARAMETER = 'base3_display_data';
	private const MAX_DISPLAY_DATA_LENGTH = 1048576;
	private const CHATBOT_CONFIG_DISPLAY = 'chatbotconfigdisplay';

	protected Container $dic;
	protected ilCtrl $ctrl;

	public function __construct() {
		$this->dic = $GLOBALS['DIC'];
		$this->ctrl = $this->dic->ctrl();

		Base3IliasRuntime::bootOnce(false, true);
	}

	public function executeCommand(): void {
		$cmd = $this->ctrl->getCmd('dispatch');

		if(!in_array($cmd, ['dispatch', 'fileManager'], true)) {
			$cmd = 'dispatch';
		}

		$this->$cmd();
	}

	protected function dispatch(): void {
		$request = Base3IliasRuntime::getServiceLocator()->get(IRequest::class);
		$out = trim((string) $request->request('out', 'html'));
		$this->setResponseContentType($out);

		$encodedData = $request->request(self::DISPLAY_DATA_PARAMETER, null);

		if(!is_string($encodedData) || trim($encodedData) === '') {
			echo Base3IliasRuntime::dispatch();
			exit;
		}

		$data = $this->decodeDisplayData($encodedData);
		if(!$data['valid']) {
			$this->sendError(400, ilBase3IliasAdapterPlugin::getInstance()->txt('ajax_invalid_display_data'));
		}

		$name = trim((string) $request->request('name', ''));

		$classmap = Base3IliasRuntime::getServiceLocator()->get(IClassMap::class);
		$display = $classmap->getInstanceByInterfaceName(IDisplay::class, $name);

		if(!$display instanceof IDisplay) {
			$this->sendError(404, ilBase3IliasAdapterPlugin::getInstance()->txt('ajax_display_not_found'));
		}

		if($name === self::CHATBOT_CONFIG_DISPLAY) {
			$this->configureChatbotDisplay($display);
		} else {
			$display->setData($data['value']);
		}

		echo $display->getOutput($out !== '' ? $out : 'html', true);
		exit;
	}

	protected function fileManager(): void {
		$data = $this->getConfiguredDisplayData(self::CHATBOT_CONFIG_DISPLAY);
		$group = $this->requireDisplayString($data, 'group');
		$name = $this->requireDisplayString($data, 'name');
		$query = $this->dic->http()->request()->getQueryParams();
		$action = trim((string)($query['fm_action'] ?? ''));

		$this->getChatbotConfigService()->handleFileManager(
			$action,
			$group,
			$name,
			(int)$this->dic->user()->getId()
		);
	}

	private function configureChatbotDisplay(IDisplay $display): void {
		$data = $this->getConfiguredDisplayData(self::CHATBOT_CONFIG_DISPLAY);

		$this->getChatbotConfigService()->configureDisplay(
			$display,
			$this->requireDisplayString($data, 'group'),
			$this->requireDisplayString($data, 'name'),
			$this->requireDisplayString($data, 'title'),
			$this->requireDisplayString($data, 'description'),
			$this->requireDisplayString($data, 'submit_label'),
			$this->ctrl->getLinkTargetByClass(
				['ilUIPluginRouterGUI', self::class],
				'fileManager'
			)
		);
	}

	private function getChatbotConfigService(): Base3IliasChatbotConfigService {
		return Base3IliasRuntime::getServiceLocator()->get(Base3IliasChatbotConfigService::class);
	}

	/**
	 * @return array<string,mixed>
	 */
	private function getConfiguredDisplayData(string $displayName): array {
		$settings = Base3IliasRuntime::getServiceLocator()->get(IBase3IliasSettings::class);
		$match = null;

		foreach($settings->getAdministrationConfig() as $tab) {
			if(!is_array($tab)) {
				continue;
			}

			foreach($tab['displays'] ?? [] as $display) {
				if(!is_array($display) || trim((string)($display['name'] ?? '')) !== $displayName) {
					continue;
				}

				if($match !== null) {
					throw new RuntimeException('Administration display configuration is ambiguous: ' . $displayName);
				}

				if(!isset($display['data']) || !is_array($display['data'])) {
					throw new RuntimeException('Administration display data is missing: ' . $displayName);
				}

				$match = $display['data'];
			}
		}

		if($match === null) {
			throw new RuntimeException('Administration display configuration not found: ' . $displayName);
		}

		return $match;
	}

	private function requireDisplayString(array $data, string $key): string {
		if(!array_key_exists($key, $data) || !is_scalar($data[$key])) {
			throw new RuntimeException('Administration display value is missing: ' . $key);
		}

		$value = trim((string)$data[$key]);
		if($value === '') {
			throw new RuntimeException('Administration display value is empty: ' . $key);
		}

		return $value;
	}

	/**
	 * @return array{valid: bool, value: mixed}
	 */
	private function decodeDisplayData(string $encodedData): array {
		$encodedData = trim($encodedData);

		if($encodedData === '' || strlen($encodedData) > self::MAX_DISPLAY_DATA_LENGTH) {
			return [
				'valid' => false,
				'value' => null,
			];
		}

		$padding = strlen($encodedData) % 4;
		if($padding > 0) {
			$encodedData .= str_repeat('=', 4 - $padding);
		}

		$json = base64_decode(strtr($encodedData, '-_', '+/'), true);
		if(!is_string($json)) {
			return [
				'valid' => false,
				'value' => null,
			];
		}

		$value = json_decode($json, true);
		if(json_last_error() !== JSON_ERROR_NONE) {
			return [
				'valid' => false,
				'value' => null,
			];
		}

		return [
			'valid' => true,
			'value' => $value,
		];
	}

	private function setResponseContentType(string $out): void {
		if(headers_sent()) {
			return;
		}

		$contentType = match(strtolower(trim($out))) {
			'json' => 'application/json; charset=utf-8',
			'xml' => 'application/xml; charset=utf-8',
			'php', 'text', 'txt' => 'text/plain; charset=utf-8',
			default => ''
		};

		if($contentType !== '') {
			header('Content-Type: ' . $contentType);
		}
	}

	private function sendError(int $statusCode, string $message): never {
		http_response_code($statusCode);

		if(!headers_sent()) {
			header('Content-Type: text/plain; charset=utf-8');
		}

		echo $message;
		exit;
	}
}
