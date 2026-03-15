---
name: vanilla-js-pro
description: Use this skill when building interactive UI elements, search bars, or handling API fetches.
---

# Vanilla JavaScript Architecture

## When to use
- Building the "Live Search" for tractor rentals.
- Handling form submissions without page reloads (AJAX/Fetch).
- Managing UI state (e.g., toggling modals or filters).
- Client-side form validation before PHP submission.

## Instructions
1. **ES6 Only:** Use `const`/`let`, arrow functions, and template literals.
2. **Fetch API:** Use `async/await` for all backend communication with PHP.
3. **Modular JS:** Keep JS files in `assets/js/` and use event delegation for better performance.
4. **No Dependencies:** Do not suggest npm packages or jQuery.
5. **Error Handling:** Always wrap `fetch()` calls in `try-catch` to catch network or runtime errors. Note that `fetch()` does not reject on HTTP errors (4xx/5xx); you MUST manually inspect `response.ok` or `response.status` to handle server-side failures and show user-friendly error messages.
6. **DOM Updates:** Template literals do not sanitize input and using them with `innerHTML` is unsafe. Use `textContent` for plain text, use `document.createElement()` plus `setAttribute()` and `appendChild()` for building dynamic DOM nodes, and if `innerHTML` must be used, explicitly sanitize data first (e.g., use sanitizers or allowlists).

## Example
```javascript
async function fetchResources() {
    try {
        const response = await fetch('includes/get_resources.php');
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        const data = await response.json();
        renderResources(data);
    } catch (error) {
        console.error('Fetch failed:', error);
        showToast('Failed to load resources. Please try again.');
    }
}
```