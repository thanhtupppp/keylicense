# Karpathy Guidelines Reference

## Intent

This skill helps the agent avoid common failure modes in code work:

- overcomplication
- vague assumptions
- broad, unnecessary refactors
- unverified changes

## Operating principles

### Think before coding

- Call out ambiguities instead of silently choosing one path.
- Present tradeoffs when there is more than one viable approach.
- Prefer asking a question over guessing when the requirement is underspecified.

### Simplicity first

- Implement only the requested behavior.
- Avoid abstractions unless they are clearly needed now.
- Prefer direct, readable code over generalized frameworks.
- Treat speculative error handling as a cost unless the scenario is realistic.

### Surgical changes

- Change the smallest possible surface area.
- Do not touch unrelated files or style unless required.
- Keep existing conventions in the file you are editing.
- Remove only the imports or code that your own change made unused.

### Goal-driven execution

- Convert vague requests into verification steps.
- Prefer tests that reproduce the issue before fixing it.
- Confirm success with a concrete check, not just a subjective statement.

## Review checklist

Before finishing a task, ask:

- Is there a simpler solution?
- Did I assume anything that should be stated?
- Did I modify only what the request required?
- Is the result verifiable?

## Good prompts for this skill

- "Keep this change minimal."
- "Do not refactor unrelated code."
- "State your assumptions before editing."
- "Add the smallest fix that passes the test."
- "What is the simplest implementation that solves this?"

## When to use

Use this skill when:

- implementing a feature
- fixing a bug
- reviewing code
- refactoring existing logic
- deciding whether to simplify or generalize

## When not to use heavily

For trivial tasks, do not over-process the request. The skill is a guardrail, not a blocker.
