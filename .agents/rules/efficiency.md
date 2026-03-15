# Efficiency & Silence Rules
- **Direct Action:** Do not ask "Would you like me to do X?" If a task is clear, execute it.
- **No Small Talk:** Skip greetings, apologies, and concluding summaries unless specifically asked.
- **Terminal Policy:** Execute safe read-only commands (`ls`, `cat`, `grep`, `git status`) without asking. Do NOT access excluded sensitive paths (e.g., .env, secrets/, private keys) or files likely to contain PII/API keys/auth tokens. Explicit user confirmation is required before running read-only commands on any files outside standard code/config directories.
- **Minimal Explanation:** Only explain code if the logic is non-obvious or complex. 
- **Tool Preference:** Use `grep` or `find` to locate code instead of reading every file.
