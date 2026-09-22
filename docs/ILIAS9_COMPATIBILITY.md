# ILIAS 9 compatibility

## Scope

ILIAS 10 and newer use the native ILIAS component layout. BASE3 packages live below:

```text
<ILIAS>/components/Base3/
```

The `Base3IliasAdapter/lib` directory stays empty in that setup.

ILIAS 9 has no equivalent BASE3 component location in this integration. For the remaining ILIAS 9 support period, the adapter therefore carries the BASE3 packages below its own `lib` directory:

```text
<ILIAS>/Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Base3IliasAdapter/
└── lib/
    ├── Base3Framework/
    ├── Base3Ilias/
    └── <other BASE3 plugins>/
```

This compatibility path is intentionally isolated in `class.ilBase3IliasAdapterIlias9Compatibility.php` so it can be removed when ILIAS 9 support ends.


## Required packages in `lib`

The ILIAS 9 `lib` directory is the BASE3 plugin root for that installation. It must contain every BASE3 package required by the final runtime composition.

`Base3Framework` and `Base3Ilias` are mandatory. The current Base3Ilias source also depends on `UiFoundation`, so a complete ILIAS 9 runtime needs at least:

```text
lib/
├── Base3Framework/
├── Base3Ilias/
└── UiFoundation/
```

Additional feature, implementation, and foundation plugins belong beside them as required by the project. Missing required contracts should be fixed by adding the owning package to `lib`, not by adding fallback classes to the adapter.

## Layout detection

The adapter does not select the path from a configured version number.

It locates the ILIAS root by walking upwards until `ilias.ini.php` is found and then inspects the actual plugin location.

ILIAS 9 layout:

```text
Customizing/global/plugins/...
```

ILIAS 10+ layout:

```text
public/Customizing/global/plugins/...
```

Only the ILIAS 9 layout activates the compatibility bootstrap. For the ILIAS 10+ layout the compatibility class returns without changing constants, autoloading, or runtime startup.

## Directory constants in ILIAS 9

Before the BASE3 autoloader is registered, the adapter defines the BASE3 directories for the local `lib` layout:

```text
DIR_ILIAS      = <ILIAS>/
DIR_COMPONENTS = <ILIAS>/components/
DIR_BASE3      = <Base3IliasAdapter>/lib/
DIR_FRAMEWORK  = <Base3IliasAdapter>/lib/Base3Framework/
DIR_SRC        = <Base3IliasAdapter>/lib/Base3Framework/src/
DIR_TEST       = <Base3IliasAdapter>/lib/Base3Framework/test/
DIR_PLUGIN     = <Base3IliasAdapter>/lib/
```

`DIR_COMPONENTS` intentionally keeps its normal ILIAS meaning even though the directory does not exist in ILIAS 9. `Base3IliasClassMap` now skips the optional host-component scan when that directory is absent. BASE3 plugin discovery itself continues through `DIR_PLUGIN`.

## Autoloading

The existing BASE3 autoloader is used without changes to Base3Framework.

Before `Autoloader::register()` is called, the compatibility bootstrap registers the existing Base3Ilias component-class namespace:

```text
Base3\Base3Ilias\ -> <Base3IliasAdapter>/lib/Base3Ilias/classes/
```

The normal BASE3 registration then adds:

```text
Base3\       -> DIR_SRC
<Plugin>\    -> DIR_PLUGIN/<Plugin>/src
```

This makes the framework, Base3Ilias runtime classes, Base3Ilias component helper classes, and all additional BASE3 plugins below `lib` available through the existing loader.

After that the adapter starts `Base3IliasRuntime::bootOnce()`. The runtime creates the BASE3 service locator and publishes its services, including `Base3\Api\IClassMap`, into the ILIAS DIC. This is the part that is missing when the ILIAS 9 configuration UI is opened without the compatibility bootstrap.

## Base3Ilias paths

Base3Ilias templates now use `DIR_BASE3 . 'Base3Ilias'` instead of rebuilding that path from `DIR_COMPONENTS`.

For ILIAS 10+ both expressions point to the same component directory. For ILIAS 9 `DIR_BASE3` points to the adapter `lib` directory, so the same Base3Ilias code can load templates from the local package.

## Asset resolution

`Base3IliasAssetResolver` receives `ISystemService` from the BASE3 container.

For ILIAS 10 and newer the existing deployed URL remains unchanged:

```text
plugin/Foo/assets/js/app.js
-> ./components/Base3/Foo/js/app.js
```

For ILIAS 9 assets are not deployed into a public component directory. They stay below the adapter `lib` directory and are addressed directly:

```text
plugin/Foo/assets/js/app.js
-> ./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Base3IliasAdapter/lib/Foo/assets/js/app.js
```

The ILIAS 9 base URL is derived from the real `DIR_BASE3` path relative to `DIR_ILIAS`, not from a duplicated hardcoded adapter path. Cache-busting continues to use the source file hash in both layouts.

The remaining direct BASE3 asset references in the adapter administration GUI and Base3Ilias page-component GUI now use `IAssetResolver` as well.

## System versions

`Base3IliasSystemService` provides:

```text
host system name       ILIAS
host system version    ILIAS_VERSION_NUMERIC
embedded system name   BASE3
embedded system version Base3Framework/VERSION
```

The version reads are defensive and return an empty string if the respective version source is unavailable.

## Removing ILIAS 9 support later

The temporary adapter path can be removed in a small, explicit change:

1. Remove `classes/class.ilBase3IliasAdapterIlias9Compatibility.php`.
2. Remove the `bootIlias9Compatibility()` call and method from `class.ilBase3IliasAdapterPlugin.php`.
3. Remove the adapter `lib` contents from ILIAS 10+ packages as already intended.
4. Remove the ILIAS 9 direct-asset branch from `Base3IliasAssetResolver` if no older host layout remains supported.
5. Delete this document.

The `DIR_BASE3` template-path corrections and the missing-`DIR_COMPONENTS` guard in Base3Ilias are general portability fixes and do not need to be reverted.
