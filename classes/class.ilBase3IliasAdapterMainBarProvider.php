<?php declare(strict_types=1);

use ILIAS\GlobalScreen\Helper\BasicAccessCheckClosuresSingleton;
use ILIAS\GlobalScreen\Scope\MainMenu\Provider\AbstractStaticMainMenuPluginProvider;
use ILIAS\MainMenu\Provider\StandardTopItemsProvider;

class ilBase3IliasAdapterMainBarProvider extends AbstractStaticMainMenuPluginProvider

{
	/**
	 * @return array
	 */
	public function getStaticTopItems(): array {
		return [];
	}

	/**
	 * @return array
	 */
	public function getStaticSubItems(): array {
		$access_helper = BasicAccessCheckClosuresSingleton::getInstance();

		if (!$access_helper->isUserLoggedIn()() || !$access_helper->hasAdministrationAccess()()) {
			return [];
		}

		return [
			$this->mainmenu->link($this->id()->identifier('base3_administration'))
				->withTitle($this->getMenuTitle())
				->withAction($this->getAdministrationLink())
				->withParent(StandardTopItemsProvider::getInstance()->getAdministrationIdentification())
				->withPosition(850)
		];
	}

	/**
	 * @return string
	 */
	protected function getAdministrationLink(): string {
		$this->dic->ctrl()->setParameterByClass('ilAdministrationGUI', 'ref_id', (string) SYSTEM_FOLDER_ID);

		return $this->dic->ctrl()->getLinkTargetByClass(
			[
				'ilAdministrationGUI',
				'ilBase3IliasAdapterAdministrationGUI'
			],
			'view'
		);
	}

	/**
	 * @return string
	 */
	protected function getMenuTitle(): string {
		$txt = $this->plugin->txt('base3_admin_menu_title');

		if ($txt === 'base3_admin_menu_title' || trim((string) $txt) === '') {
			return 'BASE3';
		}

		return $txt;
	}
}
