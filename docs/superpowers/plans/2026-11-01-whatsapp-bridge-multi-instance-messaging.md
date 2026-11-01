# WhatsApp Bridge Multi-Instance and Typed Messaging Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add independently configurable WhatsApp bridge instances and typed text/buttons/poll/media messaging across the Laravel Filament plugin and Node bridge service.

**Architecture:** The plugin stores encrypted instance records and resolves one by explicit option or default. The Node service exposes session listing/creation/deletion plus one validated typed send endpoint, while retaining the current text endpoint as an alias. Filament adds instance lifecycle controls and type-specific test-message fields.

**Tech Stack:** PHP 8.3, Laravel 12/13, Filament 5, Orchestra Testbench, PHPUnit, Node.js 18+, Express, whatsapp-web.js.

**Spec:** `docs/superpowers/specs/2026-11-01-whatsapp-bridge-multi-instance-messaging-design.md`

## Global Constraints

- Preserve existing single-instance settings and `sendMessage($to, $message, $options)` behavior.
- Encrypt bridge API tokens at rest and never expose them in API responses or logs.
- Keep Meta and Twilio provider behavior unchanged.
- No commits are created without explicit user approval.
- Typed messages are `text`, `buttons`, `poll`, and `media`; media is URL-based in this phase.

## Review Focus

- Legacy installations with no instance rows still resolve the existing bridge configuration: repository compatibility tests.
- Explicit instance selection overrides the default without mutating settings: provider HTTP tests.
- Invalid typed payloads return structured errors and never fall back to plain text: Node API tests.
- Missing/unknown sessions produce safe disconnected/error states: session lifecycle tests.
- Sensitive tokens stay encrypted/masked through CRUD, logs, and list responses: repository and Node response tests.

### Task 1: Instance persistence and repository resolution (plugin)

**Files:**
- Create: `database/migrations/create_whatsapp_bridge_instances_table.php`
- Create: `src/Settings/WhatsappBridgeInstanceRepository.php`
- Modify: `src/Settings/WhatsappSettingsRepository.php`
- Modify: `config/whatsapp-bridge-settings.php`
- Test: `tests/WhatsappBridgeInstanceRepositoryTest.php`

**Interfaces:** `all(): array`, `find(string|int $id): ?array`, `default(): ?array`, `save(array $data, string|int|null $id = null): array`, `delete(string|int $id): bool`; Bridge config remains backward-compatible.

- [ ] Write failing tests for legacy fallback, encrypted token persistence, default selection, update/delete, and refusal to delete the final default.
- [ ] Run the focused test and confirm failure.
- [ ] Implement migration/repository with encrypted token, enabled/default flags, and legacy fallback.
- [ ] Re-run focused tests expecting pass.

### Task 2: Typed message contract and instance-aware Bridge provider (plugin)

**Files:**
- Create: `src/Contracts/WhatsappMessage.php`
- Modify: `src/Services/WhatsappBridge.php`
- Modify: `src/Contracts/WhatsappProviderInterface.php`
- Test: `tests/WhatsappBridgeTypedMessageTest.php`
- Modify: `tests/WhatsappBridgeTest.php`

**Interfaces:** `sendMessageType(string $to, string $type, array $payload = [], array $options = []): bool`; convenience `sendText`, `sendButtons`, `sendPoll`, `sendMedia`; instance from `instance_id`/`instance` option, else default.

- [ ] Write failing HTTP tests for canonical payloads and selected session URLs for all four types.
- [ ] Add tests for legacy text fallback and structured failures.
- [ ] Run focused tests and confirm failure.
- [ ] Implement validation/normalization and endpoint calls; legacy fallback only for text.
- [ ] Run focused provider tests and existing bridge tests expecting pass.

### Task 3: Filament instance management and typed send form (plugin)

**Files:**
- Modify: `src/Filament/Pages/WhatsappSettingsPage.php`
- Modify: `resources/lang/en/messages.php`
- Modify: `resources/lang/ar/messages.php`
- Test: `tests/WhatsappSettingsPageTest.php`

**Interfaces:** page actions `saveInstance`, `deleteInstance`, `setDefaultInstance`, `connectInstance`, `refreshInstanceStatus`, `disconnectInstance`; send-test instance/type selectors with conditional fields.

- [ ] Write failing page tests for CRUD/default actions and type-specific state.
- [ ] Run focused page tests and confirm failure.
- [ ] Implement Instances tab and selected/default connection UI.
- [ ] Implement typed send action using Task 2 methods.
- [ ] Add English/Arabic labels and validation.
- [ ] Run page tests and Pint.

### Task 4: Node bridge session management and typed API

**Files:**
- Modify: bridge repository `index.js`
- Modify: bridge `package.json` if test scripts are absent
- Create: bridge test file following its existing convention

**Interfaces:** authenticated `GET /api/:secret/sessions`, `POST /api/:secret/sessions`, `DELETE /api/:secret/sessions/:session`, `POST /api/:secret/send/:session`; retain `send-message` alias.

- [ ] Add mocked client tests for session CRUD and all message types.
- [ ] Add tests for invalid payloads, unknown sessions, auth failures, and token leakage.
- [ ] Run Node tests and confirm failures.
- [ ] Implement route handlers, validation, and whatsapp-web.js dispatch helpers with `{ code, message, field }` errors.
- [ ] Run Node tests and lint.

### Task 5: Integration verification and documentation

**Files:**
- Modify: `README.md`
- Modify: `CHANGELOG.md`

- [ ] Document instance configuration, typed PHP examples, API payloads, and migration compatibility.
- [ ] Run `php vendor/bin/phpunit`, `php vendor/bin/pint --test`, and bridge npm test/lint commands.
- [ ] Review both repository diffs for credentials/generated artifacts and report environment-dependent checks.
