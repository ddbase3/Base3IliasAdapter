# Base3IliasAdapter

Base3IliasAdapter is the central ILIAS integration plugin for the BASE3 ecosystem. It connects the BASE3 Framework with ILIAS, makes BASE3 components and BASE3 plugins available inside the ILIAS `components` directory, provides ILIAS service adapters, and registers the services required by BASE3 in the ILIAS container.

The key words "MUST", "MUST NOT", "REQUIRED", "SHALL", "SHALL NOT", "SHOULD",
"SHOULD NOT", "RECOMMENDED", "MAY", and "OPTIONAL"
in this document are to be interpreted as described in
[RFC 2119](https://www.ietf.org/rfc/rfc2119.txt).

**Table of Contents**

* [Requirements](#requirements)
* [Installation](#installation)
* [Specifications](#specifications)
* [Other Information](#other-information)
    * [Correlations](#correlations)
    * [Bugs](#bugs)
    * [License](#license)

## Requirements

*  [![Minimum ILIAS Version](https://img.shields.io/badge/Minimum_ILIAS-10.0-orange.svg)](https://ilias.de/) [![Maximum ILIAS Version](https://img.shields.io/badge/Maximum_ILIAS-12.999-orange.svg)](https://ilias.de/)
*  ![Plugin Slot](https://img.shields.io/badge/Slot-UIHook-blue)
*  [![Minimum PHP Version](https://img.shields.io/badge/Minimum_PHP-8.1-blue.svg)](https://php.net/) [![Maximum PHP Version](https://img.shields.io/badge/Maximum_PHP-8.4-blue.svg)](https://php.net/)

## Installation

Before installing the plugin ensure all requirements are given.
The files MUST be saved in the following directory:

```
<ILIAS>/public/Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Base3IliasAdapter
```

Correct file and folder permissions MUST be ensured by the responsible system administrator.
The plugin's files and folder SHOULD NOT be created as root.

## Specifications

Base3IliasAdapter is required for running the BASE3 ecosystem inside ILIAS. It integrates the BASE3 Framework, the required ILIAS service adapters, dependent BASE3 components, and BASE3 plugins located in the ILIAS `components` directory.

The plugin fills the ILIAS container with the services required by BASE3 and acts as the central adapter layer between ILIAS and BASE3-based extensions.

## Other Information

This plugin is the central Base3IliasAdapter UIHook plugin. Other BASE3-based ILIAS plugins are only runnable in connection with the BASE3 Framework, the BaseIlias integration, the corresponding dependent components, and this Base3IliasAdapter plugin.

### Correlations

### Bugs

### License

GPL v3.0
