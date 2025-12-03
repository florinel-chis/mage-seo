#!/bin/bash

# Laravel Filament SEO Platform - Stop Script
# This script gracefully stops the application server and queue workers

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
PORT=8003
APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

echo -e "${BLUE}================================================${NC}"
echo -e "${BLUE}  Laravel Filament SEO Platform - Shutdown${NC}"
echo -e "${BLUE}================================================${NC}\n"

# Change to application directory
cd "$APP_DIR"

STOPPED_SOMETHING=false

# Step 1: Stop server using PID file
echo -e "${YELLOW}[1/3] Checking for server PID file...${NC}"
if [ -f storage/app/server.pid ]; then
    SERVER_PID=$(cat storage/app/server.pid)

    if ps -p $SERVER_PID > /dev/null 2>&1; then
        echo -e "${YELLOW}      Stopping server (PID: ${SERVER_PID})...${NC}"
        kill $SERVER_PID 2>/dev/null

        # Wait for graceful shutdown
        sleep 1

        # Force kill if still running
        if ps -p $SERVER_PID > /dev/null 2>&1; then
            echo -e "${YELLOW}      Force stopping server...${NC}"
            kill -9 $SERVER_PID 2>/dev/null
        fi

        echo -e "${GREEN}      ✓ Server stopped${NC}"
        STOPPED_SOMETHING=true
    else
        echo -e "${YELLOW}      PID file exists but process not running${NC}"
    fi

    # Clean up PID file
    rm -f storage/app/server.pid
else
    echo -e "${YELLOW}      No PID file found${NC}"
fi

# Step 2: Stop any processes on port 8003 (backup method)
echo -e "\n${YELLOW}[2/3] Checking for processes on port ${PORT}...${NC}"
if lsof -ti:$PORT > /dev/null 2>&1; then
    PIDS=$(lsof -ti:$PORT)

    for PID in $PIDS; do
        PROCESS_NAME=$(ps -p $PID -o comm= 2>/dev/null || echo "unknown")
        echo -e "${YELLOW}      Stopping process: PID=$PID ($PROCESS_NAME)${NC}"
        kill $PID 2>/dev/null

        # Wait a moment
        sleep 1

        # Force kill if still running
        if ps -p $PID > /dev/null 2>&1; then
            echo -e "${YELLOW}      Force stopping PID=$PID...${NC}"
            kill -9 $PID 2>/dev/null
        fi

        STOPPED_SOMETHING=true
    done

    echo -e "${GREEN}      ✓ All processes on port ${PORT} stopped${NC}"
else
    echo -e "${GREEN}      ✓ No processes running on port ${PORT}${NC}"
fi

# Step 3: Stop queue workers (if any)
echo -e "\n${YELLOW}[3/3] Checking for queue workers...${NC}"
QUEUE_PIDS=$(pgrep -f "artisan queue:work" || true)

if [ -n "$QUEUE_PIDS" ]; then
    echo -e "${YELLOW}      Found queue workers, stopping...${NC}"

    # Signal Laravel queue workers to stop gracefully
    php artisan queue:restart > /dev/null 2>&1 || true

    # Wait a moment for graceful shutdown
    sleep 2

    # Check if any workers are still running
    REMAINING=$(pgrep -f "artisan queue:work" || true)

    if [ -n "$REMAINING" ]; then
        echo -e "${YELLOW}      Force stopping remaining workers...${NC}"
        for PID in $REMAINING; do
            kill -9 $PID 2>/dev/null || true
        done
    fi

    echo -e "${GREEN}      ✓ Queue workers stopped${NC}"
    STOPPED_SOMETHING=true
else
    echo -e "${GREEN}      ✓ No queue workers running${NC}"
fi

# Summary
echo -e "\n${BLUE}================================================${NC}"
if [ "$STOPPED_SOMETHING" = true ]; then
    echo -e "${GREEN}  ✓ Application stopped successfully${NC}"
else
    echo -e "${YELLOW}  No running processes found${NC}"
fi
echo -e "${BLUE}================================================${NC}\n"

# Show port status
if lsof -ti:$PORT > /dev/null 2>&1; then
    echo -e "${RED}Warning: Port ${PORT} is still in use${NC}"
    echo -e "${YELLOW}Run 'lsof -ti:${PORT}' to identify the process${NC}\n"
else
    echo -e "${GREEN}Port ${PORT} is now free${NC}\n"
fi

echo -e "${BLUE}To start the server again, run:${NC} ${GREEN}./start.sh${NC}\n"
