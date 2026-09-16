# Black Cat Console — Prototype Deployment

## Intended host

Use the existing `blackcat-chatbot-01` Debian droplet. Do not create a new droplet for this MVP and do not deploy the Console onto `ptl-builder-01`.

## Required runtime

- HTTPS reverse proxy (Nginx/Caddy/Apache is acceptable)
- PHP 8.1 or later
- PHP cURL extension
- writable private project-data directory
- server-side environment variables

## Required secret

`BLACKCAT_CONSOLE_PASSWORD`

The application intentionally refuses to operate if this variable is missing.

## Optional AI configuration

`OPENAI_API_KEY`

`BLACKCAT_COACH_MODEL` defaults to `gpt-5.6-terra`.

`BLACKCAT_CHAT_MODEL` defaults to `gpt-5.6-sol`.

If `OPENAI_API_KEY` is missing, the prompt-coaching screen still works in a basic local fallback mode, but approved prompts cannot be sent to OpenAI.

## Security requirements before public exposure

1. Serve the Console over HTTPS only.
2. Keep all secrets outside Git and outside the public web root.
3. Configure the web server root to `apps/blackcat-console/`, not the repository root.
4. Deny direct HTTP access to `data/` at the web-server layer as well as through the included Apache `.htaccess` fallback.
5. Restrict the initial deployment to the owner; there is no public registration path.
6. Do not expose DigitalOcean/GitHub administrative credentials to browser JavaScript.
7. Treat interaction history as private project information.

## First validation sequence

1. Run `php -l apps/blackcat-console/index.php`.
2. Start the app without `BLACKCAT_CONSOLE_PASSWORD` and confirm it displays **Setup locked**.
3. Configure the password and confirm unauthenticated API calls fail.
4. Log in and confirm the seeded **USB / Black Cat Linux** project appears.
5. Open it and confirm project-brain files display.
6. Speak/type a request and run **Improve my request** with no OpenAI key. Confirm local fallback coaching works.
7. Configure an OpenAI API key server-side and repeat the coach action.
8. Confirm the API key never appears in page source, browser network request payloads, logs or Git.
9. Send an approved prompt and confirm the result is written to project `history.jsonl` with token usage metadata.
10. Test on iPhone Safari and desktop before describing the Console as operational.

## Current evidence status

**IMPLEMENTED IN SOURCE / NOT YET DEPLOYED OR DEVICE-VERIFIED.**

Repository commits alone do not prove that the Console is installed on `blackcat-chatbot-01`, reachable over HTTPS, or functioning correctly on an iPhone. Those are separate validation gates.
