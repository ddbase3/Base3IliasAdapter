<?php declare(strict_types=1);

use Base3\Api\IClassMap;
use Base3Ilias\Api\IModeInfoProvider;
use Base3Ilias\Base3\Base3IliasRuntime;
use ILIAS\GlobalScreen\Scope\Layout\Builder\StandardPageBuilder;
use ILIAS\GlobalScreen\Scope\Layout\Factory\PageBuilderModification;
use ILIAS\GlobalScreen\Scope\Layout\Provider\AbstractModificationPluginProvider;
use ILIAS\GlobalScreen\Scope\Layout\Provider\PagePart\PagePartProvider;
use ILIAS\GlobalScreen\ScreenContext\Stack\CalledContexts;
use ILIAS\GlobalScreen\ScreenContext\Stack\ContextCollection;
use ILIAS\UI\Component\Layout\Page\Page;
use ILIAS\UI\Component\MainControls\ModeInfo;

/** Bridges one BASE3 mode notice to ILIAS's existing layout modification slot. */
final class ilBase3IliasAdapterModeInfoProvider extends AbstractModificationPluginProvider {

	public function isInterestedInContexts(): ContextCollection {
		return $this->context_collection->internal()->external();
	}

	public function getPageBuilderDecorator(CalledContexts $contexts): ?PageBuilderModification {
		Base3IliasRuntime::bootOnce(false, true);
		$classMap = Base3IliasRuntime::getServiceLocator()->get(IClassMap::class);
		$notice = null;
		foreach ($classMap->getInstancesByInterface(IModeInfoProvider::class) as $provider) {
			$candidate = $provider->getModeInfo();
			if ($candidate === null) continue;
			if ($notice !== null) throw new RuntimeException('More than one BASE3 mode notice is active.');
			$notice = $candidate;
		}
		if (!$notice instanceof ModeInfo) return null;
		return $this->factory->page()->withModification(
			static fn(PagePartProvider $parts): Page => (new StandardPageBuilder())->build($parts)->withModeInfo($notice)
		)->withLowPriority();
	}
}
