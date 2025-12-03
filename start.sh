#!/bin/bash

# Laravel Filament SEO Platform - Start Script
# This script kills any existing processes on port 8003 and starts the application

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
PORT=8003
HOST=127.0.0.1
APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

echo -e "${BLUE}================================================${NC}"
echo -e "${BLUE}  Laravel Filament SEO Platform - Startup${NC}"
echo -e "${BLUE}================================================${NC}\n"

# Change to application directory
cd "$APP_DIR"

# Step 1: Check for existing processes on port 8003
echo -e "${YELLOW}[1/6] Checking for existing processes on port ${PORT}...${NC}"
if lsof -ti:$PORT > /dev/null 2>&1; then
    echo -e "${YELLOW}      Found existing process(es) on port ${PORT}${NC}"

    # Get PIDs
    PIDS=$(lsof -ti:$PORT)

    for PID in $PIDS; do
        PROCESS_NAME=$(ps -p $PID -o comm= 2>/dev/null || echo "unknown")
        echo -e "${YELLOW}      Killing process: PID=$PID ($PROCESS_NAME)${NC}"
        kill -9 $PID 2>/dev/null || true
    done

    # Wait a moment for ports to be released
    sleep 1
    echo -e "${GREEN}      ✓ Port ${PORT} cleared${NC}"
else
    echo -e "${GREEN}      ✓ Port ${PORT} is available${NC}"
fi

# Step 2: Check dependencies
echo -e "\n${YELLOW}[2/6] Checking dependencies...${NC}"

# Check MySQL
if command -v mysql &> /dev/null; then
    if mysql -u root -e "SELECT 1" > /dev/null 2>&1; then
        echo -e "${GREEN}      ✓ MySQL is running${NC}"
    else
        echo -e "${RED}      ✗ MySQL is not accessible${NC}"
        echo -e "${YELLOW}      Please start MySQL or check credentials${NC}"
    fi
else
    echo -e "${YELLOW}      ⚠ MySQL command not found (may be running in Docker)${NC}"
fi

# Check Redis
if command -v redis-cli &> /dev/null; then
    if redis-cli ping > /dev/null 2>&1; then
        echo -e "${GREEN}      ✓ Redis is running${NC}"
    else
        echo -e "${YELLOW}      ⚠ Redis is not responding${NC}"
        echo -e "${YELLOW}      Queue jobs will not work. Start Redis with: redis-server${NC}"
    fi
else
    echo -e "${YELLOW}      ⚠ Redis CLI not found (may be running in Docker)${NC}"
fi

# Check PHP
if command -v php &> /dev/null; then
    PHP_VERSION=$(php -v | head -n 1 | cut -d ' ' -f 2)
    echo -e "${GREEN}      ✓ PHP ${PHP_VERSION}${NC}"
else
    echo -e "${RED}      ✗ PHP not found${NC}"
    exit 1
fi

# Step 3: Check environment
echo -e "\n${YELLOW}[3/6] Checking environment...${NC}"
if [ ! -f .env ]; then
    echo -e "${RED}      ✗ .env file not found${NC}"
    echo -e "${YELLOW}      Creating from .env.example...${NC}"
    cp .env.example .env
    php artisan key:generate
fi
echo -e "${GREEN}      ✓ Environment file exists${NC}"

# Verify database connection
if php artisan db:show > /dev/null 2>&1; then
    echo -e "${GREEN}      ✓ Database connection successful${NC}"
else
    echo -e "${YELLOW}      ⚠ Database connection failed - check .env settings${NC}"
fi

# Step 4: Clear caches
echo -e "\n${YELLOW}[4/6] Clearing application caches...${NC}"
php artisan optimize:clear > /dev/null 2>&1 && echo -e "${GREEN}      ✓ Caches cleared${NC}"

# Step 5: Run migrations (if needed)
echo -e "\n${YELLOW}[5/6] Checking database migrations...${NC}"
if php artisan migrate:status > /dev/null 2>&1; then
    PENDING=$(php artisan migrate:status --pending 2>/dev/null | grep -c "Pending" || echo "0")
    if [ "$PENDING" -gt 0 ]; then
        echo -e "${YELLOW}      Found pending migrations. Running...${NC}"
        php artisan migrate --force
    fi
    echo -e "${GREEN}      ✓ Database is up to date${NC}"
else
    echo -e "${YELLOW}      ⚠ Could not check migrations${NC}"
fi

# Step 6: Start the queue worker
echo -e "\n${YELLOW}[6/7] Starting queue worker...${NC}"
php artisan queue:work --verbose --tries=3 --timeout=90 > storage/logs/queue.log 2>&1 &
QUEUE_PID=$!
echo $QUEUE_PID > storage/app/queue.pid

# Wait a moment and check if queue worker started
sleep 1
if ps -p $QUEUE_PID > /dev/null 2>&1; then
    echo -e "${GREEN}      ✓ Queue worker started (PID: $QUEUE_PID)${NC}"
else
    echo -e "${YELLOW}      ⚠ Queue worker may not have started${NC}"
fi

# Step 7: Start the server
echo -e "\n${YELLOW}[7/7] Starting Laravel development server...${NC}"
echo -e "${BLUE}      Server will start on: http://${HOST}:${PORT}${NC}"
echo -e "${BLUE}      Admin panel: http://${HOST}:${PORT}/admin/login${NC}\n"

# Start server in background and capture PID
php artisan serve --host=$HOST --port=$PORT > storage/logs/server.log 2>&1 &
SERVER_PID=$!

# Save PID to file for stop script
echo $SERVER_PID > storage/app/server.pid

# Wait a moment and check if server started
sleep 2

if ps -p $SERVER_PID > /dev/null 2>&1; then
    echo -e "${GREEN}================================================${NC}"
    echo -e "${GREEN}  ✓ Server started successfully!${NC}"
    echo -e "${GREEN}================================================${NC}\n"

    echo -e "${BLUE}Access your application:${NC}"
    echo -e "  → Homepage:     ${GREEN}http://${HOST}:${PORT}/${NC}"
    echo -e "  → Admin Panel:  ${GREEN}http://${HOST}:${PORT}/admin/login${NC}\n"

    echo -e "${BLUE}Admin Credentials:${NC}"
    echo -e "  Email:    ${GREEN}admin@example.com${NC}"
    echo -e "  Password: ${GREEN}password${NC}\n"

    echo -e "${YELLOW}Server PID: ${SERVER_PID}${NC}"
    echo -e "${YELLOW}Queue Worker PID: ${QUEUE_PID}${NC}"
    echo -e "${YELLOW}Log files:${NC}"
    echo -e "  - Server: ${YELLOW}storage/logs/server.log${NC}"
    echo -e "  - Queue:  ${YELLOW}storage/logs/queue.log${NC}\n"

    echo -e "${BLUE}Monitor queue jobs:${NC} ${GREEN}tail -f storage/logs/queue.log${NC}"
    echo -e "${BLUE}To stop all services:${NC} ${GREEN}./stop.sh${NC}\n"

    # Show last few log lines
    echo -e "${BLUE}Server output:${NC}"
    tail -n 3 storage/logs/server.log

else
    echo -e "${RED}================================================${NC}"
    echo -e "${RED}  ✗ Failed to start server${NC}"
    echo -e "${RED}================================================${NC}\n"
    echo -e "${YELLOW}Check logs for details: storage/logs/server.log${NC}\n"
    exit 1
fi
