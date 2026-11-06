<?php declare(strict_types=1);

use Base3\Api\IAssetResolver;
use Base3\Api\IClassMap;
use Base3\Api\IDisplay;
use Base3\Authorization\Api\IAuthorizationService;
use Base3\Authorization\Dto\AuthorizationRequest;
use Base3\Usermanager\Permission;

/**
 * Shared BASE3 settings under the existing native ILIAS administration Tools entry.
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
		global $DIC;

		$authorization = $DIC[IAuthorizationService::class];
		if (!$authorization->decide(new AuthorizationRequest(
			$authorization->getDefaultAuthority(), Permission::for('system', 'admin')
		))->isAllowed()) {
			http_response_code(403);
			$this->tpl->setContent('<p role="alert">' . htmlspecialchars(
				ilBase3IliasAdapterPlugin::getInstance()->txt('base3_admin_no_access'),
				ENT_QUOTES, 'UTF-8'
			) . '</p>');
			return;
		}

		$assetResolver = $DIC[IAssetResolver::class];
		$this->tpl->addJavaScript($assetResolver->resolve('plugin/ClientStack/assets/assetloader/assetloader.min.js'));
		$this->tpl->addJavaScript($assetResolver->resolve('plugin/ClientStack/assets/jqueryui/jquery-ui.js'));
		$this->tpl->addCss($assetResolver->resolve('plugin/ClientStack/assets/jqueryui/jquery-ui.css'));

		$this->tpl->setTitle(ilBase3IliasAdapterPlugin::getInstance()->txt('base3_admin_page_title'));
		$this->tpl->setDescription(ilBase3IliasAdapterPlugin::getInstance()->txt('base3_admin_page_description'));

		$display = $DIC[IClassMap::class]->getInstanceByInterfaceName(IDisplay::class, 'settingsdisplay');
		if (!$display instanceof IDisplay) {
			http_response_code(503);
			$this->tpl->setContent('');
			return;
		}
		$display->setData(null);
		$this->tpl->setContent($display->getOutput('html'));
	}
}
