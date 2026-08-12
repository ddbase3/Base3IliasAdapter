<?php declare(strict_types=1);

use Base3\Api\IClassMap;
use Base3Ilias\Api\IMetaBarItemProvider;
use Base3Ilias\Base3\Base3IliasRuntime;
use ILIAS\GlobalScreen\Scope\MetaBar\Provider\AbstractStaticMetaBarPluginProvider;

class ilBase3IliasAdapterMetaBarProvider extends AbstractStaticMetaBarPluginProvider

{
	/**
	 * @return array
	 */
	public function getMetaBarItems(): array {
		Base3IliasRuntime::bootOnce(false, true);

		$classmap = Base3IliasRuntime::getServiceLocator()->get(IClassMap::class);
		$providers = $classmap->getInstancesByInterface(IMetaBarItemProvider::class);
		$items = [];

		foreach ($providers as $provider) {
			$items[] = $provider->getMetaBarItem(
				$this->meta_bar,
				$this->id()->identifier($provider::getName())
			);
		}

		return $items;
	}
}
