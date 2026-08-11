#!/usr/bin/env bash
set -euo pipefail

# Helper script to run on an already provisioned server to deploy the stack and pull model
# Usage: sudo bash deploy-ollama.sh

PROJECT_DIR="/home/deployer/taxi-booking-system/aws-cheapest-deployment-options"
MODEL_ID="mistral/mistral-7b-instruct"

if [ ! -d "$PROJECT_DIR" ]; then
  echo "Project directory not found: $PROJECT_DIR"
  exit 1
fi

cd "$PROJECT_DIR"

# Apply safe compose edits if needed
cp docker-compose.yml docker-compose.yml.orig || true
sed -i 's/- "8080:80"/- "80:80"/' docker-compose.yml || true
sed -i 's/11434:11434/127.0.0.1:11434:11434/' docker-compose.yml || true
# rudimentary removal of mysql ports block if present
awk "/mysql:/, /volumes:/ { if (\$0 ~ /ports:/) { inports=1; print \"  # ports removed for mysql (bound to Docker network only)\"; next } if (inports && \$0 ~ /-/) { next } if (inports && \$0 ~ /[^[:space:]]/) { inports=0 } } { print }" docker-compose.yml > docker-compose.tmp && mv docker-compose.tmp docker-compose.yml || true

# Start services
sudo docker compose up -d --build

# Wait for ollama
for i in $(seq 1 60); do
  if sudo docker compose ps --services --filter "status=running" | grep -q "ollama"; then
    echo "ollama running"
    break
  fi
  echo "waiting for ollama... ($i)"
  sleep 2
done

# Pull model into ollama
for i in 1 2 3 4 5; do
  if sudo docker compose exec -T ollama ollama pull "$MODEL_ID"; then
    echo "pulled $MODEL_ID"
    break
  else
    echo "pull failed, retry in 10s ($i)"
    sleep 10
  fi
done

# Restart chatbot to pick up MODEL_NAME env from .env
sudo docker compose up -d --no-deps --build chatbot || true
sudo docker compose restart chatbot || true

echo "Deployment script complete. Check containers: sudo docker compose ps"