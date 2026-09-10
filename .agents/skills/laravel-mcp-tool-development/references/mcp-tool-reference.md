# MCP Tool Reference

## Current Repository Sources

- Root `composer.json` requires `wakebtech/assistant-ai-chat` by version and does not currently declare the checked-in `packages/assistant-ai-chat` directory as a Composer path repository.
- `packages/assistant-ai-chat/src/Mcp/Tools` contains package-development examples.
- `packages/assistant-ai-chat/src/Console/Commands/McpGenerator.php` generates MCP JSON metadata from forms and/or Postman; that generator is distinct from a Laravel MCP server with executable tools.
- `tests/Feature/McpLegalToolsTest.php` currently references `WakebLegalMcpServer` and several legal tools not present in the checked-in package source or the installed package tree observed when this reference was created.

Therefore, never copy the current MCP test as a complete architecture blindly. Re-check the live tree on every MCP task and resolve whether source files are missing, generated, ignored, supplied by another branch/package version, or intentionally planned.

## Implementation References After Authority Is Resolved

- Inspect the installed `laravel/mcp` Tool contract and testing helpers.
- Inspect the authoritative server's tool registration list and authentication setup.
- For an HTTP-equivalent write, read the existing Form Request, Policy, controller, service, Resource, and feature tests.
- For list/get tools, reuse the same visibility scope, filters, eager loading, ordering, Resource shape, and bounded pagination.
- For write tools, reuse the application service and its transaction; never port controller logic into the MCP class.

If making the checked-in package authoritative requires changing Composer repository/dependency configuration, treat that as a separate dependency decision and obtain any required authorization before changing it.
