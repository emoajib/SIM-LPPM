---
name: agent-v3
description: "Agent v3.0 input validation, tool registry, output sanitization, and audit checklist"
---
# Agent v3 Checklist

Use this checklist whenever a request may involve sensitive data, data access, exports, or tool execution.

## Checklist

### input_validation

- [ ] Inspect the user request, referenced files, tool arguments, and expected output for sensitive values.
- [ ] Detect emails, SSNs, credit card numbers, phone numbers, passwords, API keys, tokens, secrets, cookies, session IDs, and authorization headers.
- [ ] Redact detected values with `[REDACTED_<TYPE>]`.
- [ ] Stop and ask for a safe placeholder when a real secret is required to continue.

### tool_registry

- [ ] Confirm the requested domain action is one of: `fetch_schema`, `list_tables`, `run_query`, `export_data`.
- [ ] Require explicit authorization for `run_query`.
- [ ] Require explicit authorization for `export_data`, including destination and downstream use.
- [ ] Do not fabricate tool results when the required tool is unavailable.

### execution_retry

- [ ] Validate before execution.
- [ ] Use the minimum necessary tool calls.
- [ ] Retry at most 2 times for transient failures only.
- [ ] Do not retry validation, authorization, permission, or policy failures.
- [ ] Stop if unsanitized sensitive values appear in tool output.

### output_sanitization

- [ ] Scan the final response and any generated content before displaying or writing it.
- [ ] Replace secrets with `[REDACTED_<TYPE>]`.
- [ ] Withhold any portion that cannot be safely sanitized.
- [ ] Never include confidence, hallucination, uncertainty, or unsupported accuracy scores.

### audit_logging

- [ ] Record a minimal redacted audit entry for sensitive requests, authorization-required actions, policy failures, and tool errors.
- [ ] Include only: timestamp, action, input_valid, validation_issues, tool_valid, result_summary, status, and error_message.
- [ ] Do not store raw input, raw tool arguments, raw tool output, credentials, tokens, or private data.
- [ ] Skip audit logging if logging would expose sensitive data.

### no_fake_confidence

- [ ] Do not claim certainty beyond what the available evidence supports.
- [ ] Do not provide confidence scores, hallucination scores, uncertainty scores, or unsupported accuracy percentages.

## Limitations

- Regex-based detection does not catch every semantic PII case.
- Contextual judgment is still required for names, addresses, internal IDs, and sensitive business data.
- Critical decisions, exports, and production data changes require human review and explicit authorization.
