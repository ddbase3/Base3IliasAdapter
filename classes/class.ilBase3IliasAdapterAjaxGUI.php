<?php declare(strict_types=1);

use Base3\Api\IClassMap;
use Base3\Api\IDisplay;
use Base3\Api\IRequest;
use Base3Ilias\Base3\Base3IliasRuntime;
use ILIAS\DI\Container;

/**
 * @ilCtrl_IsCalledBy ilBase3IliasAdapterAjaxGUI: ilUIPluginRouterGUI
 */
class ilBase3IliasAdapterAjaxGUI {

	private const DISPLAY_DATA_PARAMETER = 'base3_display_data';
	private const MAX_DISPLAY_DATA_LENGTH = 1048576;

	protected Container $dic;
	protected ilCtrl $ctrl;

	public function __construct() {
		$this->dic = $GLOBALS['DIC'];
		$this->ctrl = $this->dic->ctrl();

		Base3IliasRuntime::bootOnce(false, true);
	}

	public function executeCommand(): void {
		$cmd = $this->ctrl->getCmd('dispatch');

		if(!in_array($cmd, ['dispatch'], true)) {
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

		$display->setData($data['value']);
		echo $display->getOutput($out !== '' ? $out : 'html', true);
		exit;
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
