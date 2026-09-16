# Black Cat Browser Execution Architecture

## Purpose

Black Cat needs browser execution for tasks that are best completed on real websites rather than through a dedicated API. Browser execution is a tool available to the Black Cat reasoning layer; it is not itself the reasoning layer.

## Selected browser engine

Use **Playwright with Chromium** for the first Black Cat-owned browser worker.

Reasons:

- mature browser automation;
- first-class Chromium support on Linux;
- headless and headed modes;
- mobile/device emulation;
- screenshots, downloads, traces and isolated browser contexts;
- avoids coupling Black Cat to one website's private UI implementation.

Puppeteer is a valid alternative, but Playwright is the preferred first implementation.

## Important OpenAI distinction

Do not assume the OpenAI API is inherently a lower-reasoning version of ChatGPT. Current flagship API models support high reasoning effort and computer-use tools. ChatGPT may still produce a different experience because the ChatGPT product adds its own agentic harness, tools, memory, Work/Codex orchestration and browser environment around the underlying models.

Black Cat therefore separates:

1. **Reasoning model** — e.g. OpenAI Responses API, local model or another provider.
2. **Browser executor** — Black Cat-controlled Playwright/Chromium worker.
3. **ChatGPT Work handoff** — optional user-directed handoff when the user specifically wants ChatGPT's product-level Work/browser environment.

Black Cat must not depend on automating the `chatgpt.com` user interface as a substitute for an API. UI-driving a subscription web session is brittle, difficult to secure and unnecessary for access to the strongest API reasoning models.

## Routing model

```text
User / Project Console
        |
        v
Black Cat task planner
        |
        +--> local/simple operation
        |
        +--> API reasoning (Sol/Astra, chosen effort)
        |        |
        |        +--> structured tool request
        |                  |
        |                  v
        |          Black Cat Browser Worker
        |          Playwright + Chromium
        |
        +--> OpenAI computer-use tool when a hosted visual computer is preferable
        |
        +--> explicit ChatGPT Work handoff when the product harness itself is desired
```

## Browser-worker security boundary

The browser worker must be treated as an untrusted-content execution boundary.

Initial requirements:

- run under a dedicated unprivileged OS account;
- never run Chromium as root;
- use a separate browser context/profile per project or identity;
- default to headless mode;
- use an allowlist or explicit per-task approval for destination domains;
- keep secrets outside prompts and outside repository files;
- do not reveal passwords/session cookies to the model;
- record requested domains and consequential actions in an audit log;
- require confirmation for destructive, financial, account-changing, publishing or irreversible actions;
- quarantine downloads before another component opens or executes them;
- impose job timeouts and memory/concurrency limits;
- allow the user to terminate a browser job immediately.

## Privacy model

Before a remote model is given browser evidence, Black Cat should be able to show what will leave the local machine, for example:

```text
Destination model: GPT-6 Astra
Website: example.com
Sending externally:
- current task text
- screenshot of current browser viewport
- selected DOM/accessibility information
Not sending:
- stored password
- session cookie value
- unrelated tabs
```

A later local-model mode may keep both reasoning and browser execution entirely local.

## Initial implementation stages

### B1 — Browser health worker

Install Node.js, Playwright and Chromium and prove that an unprivileged worker can open an approved public page, return title/URL, capture a screenshot and exit cleanly.

### B2 — Structured actions

Expose a small local-only tool interface for actions such as navigate, inspect, click, type, select, upload, download, screenshot and extract text. Do not expose arbitrary shell execution through the browser interface.

### B3 — Black Cat planner integration

Allow the project AI to request structured browser actions. Validate every action against policy before execution and feed bounded observations back to the reasoning model.

### B4 — Signed-in sessions and takeover

Add user-controlled persistent sessions only after a secure login/takeover design exists. Credentials must not be typed by the model or stored in project prompts.

### B5 — Local USB migration

Reuse the same browser-worker protocol on Black Cat USB so local browsing can continue without the remote Black Cat server.

## Current status

**ARCHITECTURE ACCEPTED / BROWSER WORKER NOT YET INSTALLED OR VERIFIED.**

The remote Console deployment is also not yet complete because administrative shell access to `blackcat-chatbot-01` has not been established through the currently available control connection. No server has been rebuilt or reset to bypass this boundary.
