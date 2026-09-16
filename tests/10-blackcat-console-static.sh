#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
APP="$ROOT/apps/blackcat-console/index.php"

[[ -f "$APP" ]] || { echo "FAIL: missing $APP"; exit 1; }

if command -v php >/dev/null 2>&1; then
  php -l "$APP"
else
  echo "WARN: php not installed on this test host; syntax check skipped"
fi

# Repository source must reference server-side secret names, never embed a live OpenAI key.
if grep -Eq 'sk-(proj-)?[A-Za-z0-9_-]{20,}' "$APP"; then
  echo "FAIL: possible hard-coded API key found in Black Cat Console source"
  exit 1
fi

grep -q 'BLACKCAT_CONSOLE_PASSWORD' "$APP" || { echo "FAIL: console password gate missing"; exit 1; }
grep -q 'OPENAI_API_KEY' "$APP" || { echo "FAIL: OpenAI server-side configuration hook missing"; exit 1; }
grep -q 'store.*false' "$APP" || { echo "FAIL: OpenAI request storage should be explicitly disabled in MVP"; exit 1; }
grep -q 'portable-trusted-linux' "$APP" || { echo "FAIL: initial PTL project seed missing"; exit 1; }

# Spend visibility is part of the control plane: preserve raw usage and pricing metadata.
grep -q 'api-usage.jsonl' "$APP" || { echo "FAIL: append-only API usage file missing"; exit 1; }
grep -q 'cached_input_tokens' "$APP" || { echo "FAIL: cached token accounting missing"; exit 1; }
grep -q 'reasoning_tokens' "$APP" || { echo "FAIL: reasoning token visibility missing"; exit 1; }
grep -q 'estimated_cost_usd' "$APP" || { echo "FAIL: estimated cost accounting missing"; exit 1; }
grep -q 'BLACKCAT_MONTHLY_API_BUDGET' "$APP" || { echo "FAIL: monthly budget control missing"; exit 1; }
grep -q 'PRICING_VERSION' "$APP" || { echo "FAIL: pricing-version metadata missing"; exit 1; }

# Browser clients may read summaries but never raw API keys.
if grep -Eq "OPENAI_API_KEY[^\n]*(localStorage|sessionStorage|document\.|window\.)" "$APP"; then
  echo "FAIL: API key appears to be exposed to browser-side state"
  exit 1
fi

echo "PASS: Black Cat Console static checks"
