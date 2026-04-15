#!/usr/bin/env bash
#
# Seeds the Solr demo cores with documents so the dashboard widgets have
# realistic data to render. Idempotent — documents are keyed by unique `id`
# and overwritten on re-runs.
#
# Seeds both the English and German cores by default; pass a single core to
# target just one.
#
# Usage (from inside the ddev web container):
#   bash Tests/Acceptance/Fixtures/solr_seed.sh [core_en|core_de|...] [http://solr:8983]

set -euo pipefail

SOLR_BASE="${2:-http://solr:8983}"

if [ -n "${1:-}" ]; then
    CORES=("$1")
else
    CORES=(core_en core_de)
fi

for CORE in "${CORES[@]}"; do
  UPDATE_URL="${SOLR_BASE}/solr/${CORE}/update?commit=true"
  echo "Seeding Solr core '${CORE}' at ${SOLR_BASE}..."

# Build a JSON array:
#   pages        × 120
#   tt_content   × 310
#   tt_news      × 45
#   sys_file     × 80
#   ext_news     × 20
#
# Field choices follow EXT:solr's default schema (dynamic fields, type, title).

python3 - "$UPDATE_URL" <<'PY'
import json
import sys
import urllib.request

update_url = sys.argv[1]
docs = []
counts = {
    "pages": 120,
    "tt_content": 310,
    "tt_news": 45,
    "sys_file_metadata": 80,
    "news": 20,
}
for type_, count in counts.items():
    for i in range(1, count + 1):
        docs.append({
            "id": f"demo/{type_}/{i}",
            "appKey": "EXT:solr",
            "type": type_,
            "title": f"{type_.replace('_', ' ').title()} #{i}",
            "content": f"Demo content for {type_} record number {i}.",
            "siteHash": "demo",
        })

payload = json.dumps(docs).encode("utf-8")
req = urllib.request.Request(
    update_url,
    data=payload,
    headers={"Content-Type": "application/json"},
    method="POST",
)
with urllib.request.urlopen(req, timeout=10) as resp:
    print(f"HTTP {resp.status}: posted {len(docs)} documents")
PY

done

echo "Done."
