---
description: "Run Agent v3.0 local safety self-check"
agent: agent-v3
---
Run a local self-check for the Agent v3.0 Kilo configuration. Do not write an audit log during this self-check; simulate audit entries only.

## Steps

1. Read these files:
   - `AGENTS.md`
   - `.kilo/agent/agent-v3.md`
   - `.kilo/skills/agent-v3/SKILL.md`

2. Validate frontmatter for the agent, skill, and command:
   - Each file must start with `---`.
   - Each file must contain a matching closing `---`.
   - YAML must parse successfully.
   - `.kilo/agent/agent-v3.md` must include `description`, `mode: all`, and `hidden: false`.
   - `.kilo/skills/agent-v3/SKILL.md` must include `name: agent-v3` and `description`.
   - `.kilo/command/agent-v3-selfcheck.md` must include `description`.

3. Check for credential literals in the agent prompt, skill, and command:
   - Reject obvious secret-looking values such as long random tokens, private key blocks, bearer headers, password assignments, and API key assignments.
   - Placeholder text such as `<EMAIL>`, `API_KEY_VALUE`, and `[REDACTED_API_KEY]` is allowed.
   - If a placeholder resembles a real secret, replace it with a safer placeholder and rerun the check.

4. Simulate these scenarios and verify that displayed results do not contain original sensitive values:
   - Clean input: `list_tables` with no sensitive values.
   - Sensitive input: request containing `<EMAIL>` and `API_KEY_VALUE`; expected output must use `[REDACTED_EMAIL]` and `[REDACTED_API_KEY]`.
   - Valid tool without authorization: `list_tables`; expected result is allowed.
   - Valid tool requiring authorization: `run_query`; expected result is blocked until explicit authorization is provided.
   - Invalid tool: `delete_database`; expected result is rejected as outside the tool registry.

5. Confirm compatibility with `AGENTS.md`:
   - The Agent v3.0 rules must not remove the mandatory Laravel workflow.
   - The self-check must not modify `.env`, `composer.lock`, application code, or deployment files.
   - The self-check must not commit or push changes.

6. Report a concise result:
   - `frontmatter: pass | fail`
   - `credential_scan: pass | fail`
   - `scenario_simulation: pass | fail`
   - `agentic_workflow_compatibility: pass | fail`
   - Any failures must include the file path and a sanitized reason only.
