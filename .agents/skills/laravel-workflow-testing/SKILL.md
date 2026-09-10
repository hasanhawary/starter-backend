---
name: laravel-workflow-testing
description: Design, add, or review focused PHPUnit coverage for Laravel status, step, approval, assignment, deadline, command, or Strategy-based workflows in this project. Use when workflow behavior changes or is insufficiently covered; do not activate for trivial non-workflow unit tests.
---

# Laravel Workflow Testing

Prove the workflow's public and domain invariants without coupling tests to incidental implementation details.

## Authority and Test Design

- Follow the root `AGENTS.md`, and the implementation skill for the target workflow.
- Read [the workflow test matrix](references/workflow-test-matrix.md) and inspect the closest live workflow, its service, Factory/Context/strategies or Steps, Request, Policy, Resource buttons, logs, notifications, commands, factories, and existing tests.
- Build a compact transition matrix before writing tests: source state, target state/action, actor/permission, required payload, writes, side effects, and expected next actions.
- Prefer feature tests for HTTP contracts and service integration. Use unit tests for pure Factory mappings, enum helpers, registries, and isolated rules. Do not mock Eloquent chains merely to avoid testing real persistence.

## Required Coverage by Risk

- Test every changed Factory mapping and unknown/invalid selector behavior. Invalid user input must fail validation before Factory resolution.
- For each changed transition, cover an allowed source state and actor, a forbidden source state, a forbidden actor, and meaningful dynamic validation branches.
- Assert the complete atomic result: main state, related writes, active/inactive pivots, lifecycle logs, timestamps, assignments, and counters that belong to the transition.
- Force a failure after an earlier write when practical and assert rollback. Cover row locking or duplicate-action protection where concurrent requests could race.
- Verify resource buttons/next actions agree with transition policy; do not allow the API to advertise a transition that the service rejects or hide one it accepts.
- For notifications, jobs, events, mail, or external I/O, assert dispatch only after a successful commit and no dispatch after rollback. Test scheduled/system actors separately from authenticated actors.
- For commands and schedules, cover idempotent re-runs, date boundaries, overlap/deduplication mechanisms, batch iteration, and the same service entry point used by HTTP when applicable.

## Fixtures and Assertions

- Use existing factories and factory states before manual model construction. Add the smallest useful factory/state when setup is repeated or encodes a real domain state.
- Create only the permissions and relations required by the scenario, clear Spatie's permission cache when needed, and authenticate with the established Sanctum test style.
- Assert public response envelopes, validation keys, authorization status, database state, and observable side effects. Avoid assertions on private method calls or exact SQL.
- Preserve bilingual/translatable payload shapes and enum backing values in fixtures. Use stable dates or time freezing for deadline workflows.
- A regression test should fail for the reported behavior, not merely match newly written class names or comments.

## Verification

- Run the smallest affected PHPUnit file or filter first, then the related module suite when the change crosses several transitions.
- Do not delete or weaken existing tests to make a change pass. Document environment-dependent tests that cannot run.
- Format changed PHP tests with Pint and run EA/PhpStorm inspections on them when available, following the backend quality gate.
