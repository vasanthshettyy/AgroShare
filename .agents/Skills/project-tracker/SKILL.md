---
name: project-tracker
description: Use this skill at the start and end of every session to sync with the roadmap.
---

# AgroShare Project Tracker

## When to use
- Every time a new session starts.
- When the user asks "What's next?" or "Are we on track?"
- Before starting a new feature or module.

## Instructions
1. **Read Context First:** Immediately read `.context/roadmap.md` and `.context/logs.md`.
2. **Status Check:** Compare current file changes with the roadmap goals. 3. **Memory Update:** At the end of a task, suggest a concise summary for the user to append to `.context/logs.md`.
4. **Mind Map Preservation:** Do not suggest features that deviate from the Mind Map in `roadmap.md` without asking.
5. **Progress Report:** When asked for status, list completed items vs remaining items from the roadmap in a concise table.