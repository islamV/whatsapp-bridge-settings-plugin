# WhatsApp Bridge Multi-Instance and Typed Messaging Design

## Intent

Let Laravel/Filament administrators manage multiple WhatsApp connections and send more than plain text through the linked `whatsapp-bridge` service. An instance may use a distinct bridge URL/API token and a distinct WhatsApp session name. Existing single-instance configuration and text sending must continue to work.

## Goals

- Store multiple named Bridge instances with URL, encrypted token, session name, sender label, timeout, enabled/default state.
- Manage instance lifecycle from Filament: create/edit/delete, select default, start QR pairing, refresh status, disconnect, and show connected phone.
- Add a stable typed message contract shared by the Node bridge and PHP plugin: `text`, `buttons`, `poll`, `media`, with room for later types.
- Expose typed PHP methods plus a generic message method while retaining `sendMessage($to, $message, $options)`.
- Keep legacy `/api/:secret/send-message/:session` behavior valid for text payloads.
- Keep provider selection and Meta/Twilio behavior unchanged.

## Non-goals

- Group/broadcast campaign scheduling, inbound webhook processing, contact synchronization, or message history.
- Replacing WhatsApp Web authentication or adding a second WhatsApp library.
- Making Meta/Twilio support Bridge-only interactive message types in this phase.

## Constraints

- The plugin targets Laravel 12/13 and Filament 5 with PHP 8.3+.
- Tokens remain encrypted at rest.
- Existing settings migration and legacy flat columns must remain readable.
- The Node bridge currently has session-scoped QR/status/close/send endpoints and in-memory session metadata backed by LocalAuth files.
- No commit is created without explicit user approval.

## Approaches considered

1. **Coordinated API evolution (chosen):** update the Node bridge and plugin together with a typed message envelope and instance management. This is the only option that can be verified end to end and keeps the contract explicit.
2. **Plugin-only generic adapter:** faster, but the bridge would not understand the new message types and behavior could not be verified.
3. **Minimal session-name extension:** smaller, but cannot manage separate bridge URLs/tokens or provide a complete admin lifecycle.

## Design

### Instance model and compatibility

Add a `whatsapp_bridge_instances` table keyed by UUID/id, with `name`, `api_base_url`, encrypted `api_token`, `session_name`, `sender`, `timeout`, `enabled`, `is_default`, and timestamps. The repository will read the new table first and transparently expose the legacy single Bridge configuration as one synthetic/default instance when no rows exist. Saving the first instance will preserve the legacy columns for existing consumers; deleting the final instance is refused or leaves the legacy default intact.

The provider resolves an instance by an explicit `instance`/`instance_id` option, then the configured default instance. All QR/status/disconnect/send calls use that instance’s URL/token/session. The existing public methods continue to work without an instance option.

### Bridge API

Keep the current session endpoints and add authenticated endpoints under the same URL scheme:

- `GET /api/:secret/sessions` — list known sessions and status/phone.
- `POST /api/:secret/sessions` — create or initialize a named session.
- `DELETE /api/:secret/sessions/:session` — logout and destroy a session.
- `POST /api/:secret/send/:session` — typed message envelope.

The existing `POST /send-message/:session` remains a text compatibility alias. Session listing includes only safe metadata; no tokens or browser internals.

### Message contract

The canonical request is:

```json
{
  "to": "201000000000",
  "type": "text|buttons|poll|media",
  "text": "optional text",
  "buttons": [{"id":"yes","title":"Yes"}],
  "poll": {"name":"Choose one","options":["A","B"],"selectableCount":1},
  "media": {"url":"https://…","caption":"optional","filename":"optional","mimetype":"optional"},
  "options": {}
}
```

The Node service validates each type, resolves the recipient, and maps the envelope to the corresponding `whatsapp-web.js` message API. Media is URL-based in this phase; base64 uploads are deferred. The PHP plugin provides `sendText`, `sendButtons`, `sendPoll`, `sendMedia`, and `sendMessageType` methods, while `sendMessage` maps to `type=text`.

### Filament flow

Add an Instances tab with a table/repeater of named instances and row actions for edit, QR/connect, refresh, disconnect, set default, and delete. The send-test action gains an instance selector and message-type selector; fields change by type and submit the canonical envelope. The existing Bridge tab remains available for the legacy/default instance during migration.

### Validation and errors

Use shared type-specific validation in PHP and Node. Return structured errors with `code`, `message`, and optional `field`. The PHP provider logs masked phone numbers and instance names, never tokens. A failed typed request must not silently fall back to legacy text; only a text request may use the compatibility endpoint.

## Verification

- Unit tests for repository migration/legacy fallback, encryption, instance selection, and typed payload construction.
- HTTP tests for text compatibility, each typed message, status/QR/disconnect per instance, and structured errors.
- Node tests for payload validation, session listing, and `whatsapp-web.js` dispatch mocks.
- Filament page tests for instance CRUD and type-specific send forms.
- Run the plugin test suite, Pint, and the bridge Node test/lint commands before claiming completion.
