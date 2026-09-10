---
name: laravel-mcp-tool-development
description: Create, change, secure, or review Laravel MCP servers and tools in this project. Use for tool schemas, discovery/registration, authenticated read/write tools, structured MCP responses, or MCP tests. Do not use for RAG collectors or ordinary HTTP endpoints.
---

# Laravel MCP Tool Development

Expose existing application capabilities through MCP without creating a second, weaker business or authorization path.

## Establish Source Authority First

- Follow the root `AGENTS.md`.
- Read [the MCP source and implementation reference](references/mcp-tool-reference.md) before editing.
- Inspect root `composer.json`, `composer.lock`, Composer repository definitions, package discovery, the local `packages/assistant-ai-chat` tree, installed package code, application bindings/config, and tests to identify the source that actually runs.
- Never edit `vendor`. If runtime code exists only in `vendor`, change the owned package source or dependency through an explicitly authorized path.
- Do not implement against a test-only or stale server API. If referenced server/tool classes are missing from owned source, identify the mismatch before choosing the architecture.

## Tool Contract

- Read the installed `laravel/mcp` contracts and the nearest working tool before selecting method signatures, parameter schema, response type, registration, and test helpers.
- Give each tool one clear operation, stable snake-case name, precise description, bounded parameters, and a schema that matches the accepted payload.
- Separate read tools from mutating tools. A mutating tool requires explicit authorization, validated input, a cohesive application service, and a transaction when it performs dependent writes.
- Reuse the same Policies/Gates, ownership scopes, Form Request/custom Rule semantics, services, enums, and Resources used by HTTP. Do not reproduce weaker validation or bypass record visibility because the caller is MCP.
- Validate identifiers, enum values, translatable payloads, nested arrays, uniqueness, and upload limitations through existing domain primitives where their contract applies.
- Do not expose arbitrary model classes, columns, relations, filters, sort expressions, limits, or executable actions supplied by the model/client. Whitelist every selectable capability and enforce bounded result sizes.

## Responses, Errors, and Side Effects

- Return one consistent structured success/error envelope with stable machine-readable codes and safe localized/user-readable messages where the current server supports them.
- Distinguish unauthenticated, unauthorized, invalid input, not found, conflict, and internal failure. Do not return exception messages, SQL, filesystem paths, stack traces, credentials, or personal data not required by the tool contract.
- Keep controllers and tools thin. The tool adapts MCP input/output and delegates domain work; it does not become another service layer.
- Queue external effects after commit. Preserve idempotency for retried write tools and use an explicit idempotency key or domain uniqueness rule where duplicate execution has material impact.
- Register the tool in the authoritative server/provider and verify discovery. Do not leave a class that tests directly but the server never exposes.

## Verification

- Test discovery, schema, authentication, each permission/ownership branch, validation, not found/conflict errors, bounded listing/filter behavior, structured output, database effects, rollback, idempotent retry, and after-commit side effects as applicable.
- Prefer the Laravel MCP fake transporter/test API exposed by the installed version. Also prove that HTTP and MCP entry points share the same domain invariant when both exist.
- Finish with focused PHPUnit tests, Pint on only authorized changed PHP files, and EA/PhpStorm inspections when available.
