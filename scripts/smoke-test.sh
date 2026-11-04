#!/usr/bin/env bash
set -uo pipefail

# scripts/smoke-test.sh
# Black-box checks against a running stack: health, pagination, security headers and blocked directories.
# Usage: ./scripts/smoke-test.sh [base_url]

BASE="${1:-http://localhost:8080}"
FAILS=0

check() { # name expected_status url [extra curl args]
    local name="$1" expected="$2" url="$3"; shift 3
    local code
    code="$(curl -s -o /dev/null -w '%{http_code}' "$@" "$url")"
    if [ "$code" = "$expected" ]; then echo "  [PASS] $name ($code)"; else echo "  [FAIL] $name (expected $expected, got $code)"; FAILS=$((FAILS+1)); fi
}

contains() { # name url needle
    if curl -s "$2" | grep -q "$3"; then echo "  [PASS] $1"; else echo "  [FAIL] $1"; FAILS=$((FAILS+1)); fi
}

echo "Smoke testing $BASE"
check "health endpoint"                 200 "$BASE/index.php?route=health"
check "storefront page 1"               200 "$BASE/index.php?route=home&page=1"
check "storefront page 2"               200 "$BASE/index.php?route=home&page=2"
contains "pagination rendered"          "$BASE/index.php?route=home" 'class="pagination"'
check "config/ is not downloadable"     403 "$BASE/config/config.example.php"
check "database/ is not downloadable"   403 "$BASE/database/schema.sql"
check "app/ is not downloadable"        403 "$BASE/app/Core/Auth.php"
check "webhook rejects GET"             405 "$BASE/index.php?route=webhook/stripe"
check "webhook rejects bad signature"   400 "$BASE/index.php?route=webhook/stripe" -X POST -H 'Stripe-Signature: t=1,v1=bad' -d '{}'
if curl -sI "$BASE/index.php" | grep -qi '^X-Content-Type-Options: nosniff'; then echo "  [PASS] security headers"; else echo "  [FAIL] security headers"; FAILS=$((FAILS+1)); fi

[ "$FAILS" -eq 0 ] && echo "All smoke tests passed." || { echo "$FAILS check(s) failed."; exit 1; }
