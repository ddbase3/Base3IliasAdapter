# Base3IliasAdapter Privacy and Data Processing

## Scope

Base3IliasAdapter embeds BASE3 in ILIAS. This document describes the entry points visible in this repository and their privacy/security boundaries. It is not a legal privacy notice or a claim of conformity with GDPR or accessibility legislation. Storage and runtime details provided by the separately deployed ILIAS host and BASE3 plugins must be verified for each installation.

## Data and purpose

The administration GUI receives display definitions from the registered `IBase3IliasSettings` service and renders them via `TabControlDisplay`. These definitions may include settings identities and UI labels. Administration displays can expose additional operational or user data according to their own contracts.

The AJAX GUI is a generic BASE3 entry point. It receives a display name and, for display requests, an optional URL-safe Base64-encoded `base3_display_data` parameter (up to 1,048,576 encoded characters), then resolves the `IDisplay` via `IClassMap`, passes the decoded data through `setData()` and returns the output. The adapter does not inspect or authorize a feature-specific configuration identity and does not process uploads, downloads or other file operations.

FileManager access for the ILIAS administration now uses a separately discovered `Base3IliasFileManagerOutput` in the Base3Ilias component, which checks the available BASE3 `IUsermanager` administrator permission and confirms the requested storage owner against the project-provided administration configuration. The FileManager HTTP service and ILIAS managed storage remain outside this repository. Details of file persistence, uploaded content, deletion and temporary upload sessions belong to Base3Ilias and the active storage backend.

`base3_display_data` is client-supplied data. It must not be treated by a consuming display as proof of authorization or of a trusted storage owner. Feature services continue to own their own authorization and data validation.

## Requests, cookies and recipients

The administration renders the initially selected display normally, and `TabControlDisplay` uses `fetch()` with `credentials: "same-origin"` and `cache: "no-store"` for subsequent tabs. Requests are sent to the configured ILIAS/BASE3 endpoint. The adapter does not add an external recipient or create a separate cookie; it relies on the host's browser and server authentication context.

The repository does not establish whether the deployed ILIAS host, other plugins, proxies, loggers, or browser libraries transmit data to external recipients. That requires deployment-specific verification.

## Persistence, retention and deletion

The GUI components do not implement a new persistent database or file store for tab navigation. Chatbot settings and managed files are handled by the configured BASE3 services and the ILIAS storage integration. `Base3IliasFileUploadService` may hold temporary chunk-upload sessions while transfers complete; its lifecycle belongs to Base3Ilias and deployment storage configuration.

This adapter does not specify application or proxy access-log retention, the retention of chatbot files or messages, or deletion schedules. These settings must be documented and enforced by the actual deployment and the storage owners; do not infer them from this adapter alone.

## Security and access-control considerations

The AJAX GUI dispatches BASE3 displays without feature-specific permission logic. The effective authorization of normal displays depends on their own contracts, the BASE3 runtime and the host router, which require deployment testing. The adapter neither determines a FileManager storage owner nor grants FileManager access.

The Base3Ilias FileManager output separately requires `IUsermanager::can(Permission::for('system', 'admin'))`, a current BASE3 user ID, and an owner present in server-provided administration settings with resources enabled. It must not trust `fm_group` and `fm_name` alone. This is the existing BASE3 administrator permission, not a new role model. Other host-specific Chatbot integrations remain responsible for their own command-level restrictions.

## Accessibility

The host should verify the tab control, AJAX loading indicators, error messages, focus order, keyboard operation and FileManager against WCAG 2.2 AA and applicable deployment rules. The source-level change does not by itself establish conformance. Manual screen-reader and keyboard checks remain necessary.

## Known verification gaps

The provided adapter sources do not establish the full ILIAS router access policy, host log retention, storage retention, external recipients, international transfers, or end-to-end browser accessibility. These items need a deployment review using the actual ILIAS version and enabled plugins.
