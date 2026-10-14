# Base3IliasAdapter Privacy and Data Processing

## Scope

Base3IliasAdapter embeds BASE3 in ILIAS. This document describes the entry points visible in this repository and their privacy/security boundaries. It is not a legal privacy notice or a claim of conformity with GDPR or accessibility legislation. Storage and runtime details provided by the separately deployed ILIAS host and BASE3 plugins must be verified for each installation.

## Data and purpose

The administration GUI obtains display definitions from the registered `IBase3IliasSettings` service and renders them through `TabControlDisplay`. These definitions can contain a display name, settings group/name and UI labels. Administration displays themselves can expose further operational or user-related data according to their own service contracts.

The AJAX GUI receives a display name and, for generic displays, a URL-safe, base64-encoded JSON `base3_display_data` parameter. Its maximum encoded length is 1,048,576 characters. The Chatbot configuration display is special: the AJAX handler retrieves its configuration identity from `IBase3IliasSettings`, rather than accepting the displayed group/name from the submitted parameter. The existing `Base3IliasChatbotConfigService` supplies the FileManager URLs for that identity.

The `fileManager` command retrieves the configured chatbot group/name, reads the ILIAS user ID, and delegates file operations to `Base3IliasChatbotConfigService::handleFileManager()`. That service uses `Base3IliasFileManagerHttpService` and `Base3IliasManagedFileStorageService` from the separate Base3Ilias module. Operations include file listing, downloads, uploads, file or folder creation, copy, move, and deletion. File contents, file names, metadata and upload-session information can therefore constitute personal data. See the Base3Ilias module and the active ILIAS storage backend for actual locations, handling, and deletion semantics.

## Requests, cookies and recipients

The administration loads its initial active display and later tab changes through the same browser AJAX request. `TabControlDisplay` uses `fetch()` with `credentials: "same-origin"` and `cache: "no-store"`. Requests are sent to the configured ILIAS/BASE3 endpoint. The adapter does not add an external recipient or create a separate cookie for this initial request; it relies on the host's existing browser and server authentication context.

The repository does not establish whether the deployed ILIAS host, other plugins, proxies, loggers, or browser libraries transmit data to external recipients. That requires deployment-specific verification.

## Persistence, retention and deletion

The GUI components do not implement a new persistent database or file store for tab navigation. Chatbot settings and managed files are handled by the configured BASE3 services and ILIAS storage modules. `Base3IliasFileUploadService` may hold temporary chunk-upload sessions while transfers complete; its lifecycle belongs to the Base3Ilias module and deployment storage configuration.

This adapter does not specify application or proxy access-log retention, the retention of chatbot files or messages, or deletion schedules. These settings must be documented and enforced by the actual deployment and the storage owners; do not infer them from this adapter alone.

## Security and access-control considerations

`ilBase3IliasAdapterAjaxGUI` enters through the ILIAS UI plugin router and uses the runtime's configured display and storage services. In this repository the AJAX GUI itself does not perform a separate per-command permission check before `dispatch()` or `fileManager()`. The effective authorization provided by ILIAS routing and the configured host services requires verification. In particular, `fileManager` operations must be restricted to appropriately authorized users and must not rely on the `Developer` role merely to initialize the browser UI.

Changing the initially selected administration display to load via AJAX reuses the already present endpoint. It must not widen access to that endpoint or change which users are authorized to view or modify a chatbot configuration.

## Accessibility

The host should verify the tab control, AJAX loading indicators, error messages, focus order, keyboard operation and FileManager against WCAG 2.2 AA and applicable deployment rules. The source-level change does not by itself establish conformance. Manual screen-reader and keyboard checks remain necessary.

## Known verification gaps

The provided adapter sources do not establish the full ILIAS router access policy, host log retention, storage retention, external recipients, international transfers, or end-to-end browser accessibility. These items need a deployment review using the actual ILIAS version and enabled plugins.
