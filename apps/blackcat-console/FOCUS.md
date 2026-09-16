# Black Cat Focus Tracker

`focus.php` is the deliberately narrow project-tracking layer for Black Cat Console.

## Purpose

The tracker exists to reduce project creep rather than add more automation.

Each project has exactly these active fields:

- **Current milestone** — the outcome being worked toward now.
- **Next action** — one concrete action to do next.
- **Blocker** — one current reason the next action cannot proceed, when applicable.
- **Backlog** — ideas worth keeping that are not allowed to change current focus automatically.

## Rules

1. New ideas are parked in `BACKLOG.md`.
2. Parking an idea never changes the milestone or next action.
3. Only an explicit **Edit focus** action changes active work.
4. Marking the next action **Done** records the completion and leaves the next action as `Choose the next smallest action for this milestone`.
5. Blockers are explicit and visible on the project list.
6. The tracker shares the Console's private project directory and API-spend log, but it does not need to make an AI call to perform normal tracking.
7. AI may later recommend a next action, but it must not silently commit the recommendation.

## Current UI actions

- Create project
- Edit focus
- Mark next action done
- Mark/clear blocker
- Park idea in backlog
- Review recent project activity
- View monthly API spend already recorded by Black Cat Console

## Data

Per-project runtime data remains under the private Console data directory:

```text
data/projects/<project-id>/
  meta.json
  STATE.md
  BACKLOG.md
  AI_RULES.md
  activity.jsonl
```

The broader Console may maintain additional project-brain files in the same directory.

## Evidence status

**SOURCE IMPLEMENTED / LOCALLY PHP-LINTED / NOT YET DEPLOYMENT-VERIFIED.**

The first source implementation passed PHP 8.4 syntax checking in the development runtime. The real `blackcat-chatbot-01` deployment remains blocked on establishing a safe administrative shell path, so phone/browser behaviour has not yet been claimed as verified.
