#!/usr/bin/env bash
set -euo pipefail

# scripts/simulate-webhook.sh
# Sends a correctly SIGNED Stripe event to the local webhook endpoint (like `stripe trigger`).
#
# Usage:   ./scripts/simulate-webhook.sh <event_type> <order_id> [event_id]
# Example: ./scripts/simulate-webhook.sh checkout.session.completed 6
#          ./scripts/simulate-webhook.sh checkout.session.completed 6 evt_demo_1   # run twice: 2nd is a duplicate
# Env:     APP_URL (default http://localhost:8080), STRIPE_WEBHOOK_SECRET (default whsec_local_demo_secret)

EVENT="${1:?event type required, e.g. checkout.session.completed}"
ORDER="${2:?order id required}"
EVENT_ID="${3:-evt_cli_$(date +%s)_$RANDOM}"
APP_URL="${APP_URL:-http://localhost:8080}"
SECRET="${STRIPE_WEBHOOK_SECRET:-whsec_local_demo_secret}"
TS="$(date +%s)"

case "$EVENT" in
    checkout.session.*) OBJECT="{\"id\":\"cs_cli_$ORDER\",\"object\":\"checkout.session\",\"client_reference_id\":\"$ORDER\",\"payment_status\":\"paid\",\"payment_intent\":\"pi_cli_$ORDER\",\"metadata\":{\"order_id\":\"$ORDER\"}}" ;;
    charge.refunded)    OBJECT="{\"id\":\"ch_cli_$ORDER\",\"object\":\"charge\",\"refunded\":true,\"metadata\":{\"order_id\":\"$ORDER\"}}" ;;
    *)                  OBJECT="{\"id\":\"pi_cli_$ORDER\",\"object\":\"payment_intent\",\"metadata\":{\"order_id\":\"$ORDER\"}}" ;;
esac

PAYLOAD="{\"id\":\"$EVENT_ID\",\"object\":\"event\",\"type\":\"$EVENT\",\"created\":$TS,\"livemode\":false,\"data\":{\"object\":$OBJECT}}"
SIG="$(printf '%s.%s' "$TS" "$PAYLOAD" | openssl dgst -sha256 -hmac "$SECRET" | sed 's/^.* //')"

echo "==> POST $APP_URL/index.php?route=webhook/stripe  ($EVENT, order $ORDER)"
curl -sS -w '\nHTTP %{http_code}\n' -X POST "$APP_URL/index.php?route=webhook/stripe" \
    -H "Content-Type: application/json" \
    -H "Stripe-Signature: t=$TS,v1=$SIG" \
    -d "$PAYLOAD"
