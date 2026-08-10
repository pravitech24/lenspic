#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")"

if [[ ! -d .venv ]]; then
  python3 -m venv .venv
fi

source .venv/bin/activate
pip install -r requirements.txt
exec uvicorn main:app --reload --host "${UVICORN_HOST:-127.0.0.1}" --port "${UVICORN_PORT:-8001}"