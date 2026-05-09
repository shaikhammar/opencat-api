# opencat-api

REST API service for the [OpenCAT Framework](https://github.com/shaikhammar/opencat-framework). Exposes all framework capabilities (segmentation, TM, MT, QA, terminology, full workflow) over HTTP so any tech stack can build CAT tooling against it.

**Stack:** Laravel 13 · PHP 8.4 · Sanctum token auth · Laravel Queue · SQLite (default) · Docker

---

## Requirements

- PHP 8.4+
- Composer 2
- SQLite (default) or MySQL/PostgreSQL

---

## Local setup

```bash
# 1. Copy env
cp .env.example .env

# 2. Install dependencies (opencat/* packages are pulled from Packagist)
composer install

# 3. Generate app key and run migrations
php artisan key:generate
php artisan migrate

# 4. Start the server + queue worker
php artisan serve &
php artisan queue:work &
```

The API is available at `http://localhost:8000/api`.

---

## Docker

```bash
cp .env.example .env
# Fill in APP_KEY (php artisan key:generate prints it)

docker compose up -d
```

API at `http://localhost:8000/api`.

---

## Authentication

All endpoints (except `POST /api/auth/tokens`) require a Bearer token.

### Create a token

```bash
curl -X POST http://localhost:8000/api/auth/tokens \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"secret","name":"my-script","abilities":["*"]}'
```

Response:

```json
{
  "data": {
    "tokenId": 1,
    "token": "1|abc123...",
    "abilities": ["*"]
  }
}
```

Use the token as `Authorization: Bearer 1|abc123...` on all subsequent requests.

---

## Quickstart: process a file

```bash
TOKEN="1|abc123..."

# 1. Create a project
curl -X POST http://localhost:8000/api/projects \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name":"My Project","sourceLang":"en","targetLangs":["fr"]}'
# → {"data":{"id":1,...}}

# 2. Upload a file
curl -X POST http://localhost:8000/api/files \
  -H "Authorization: Bearer $TOKEN" \
  -F "file=@document.docx"
# → {"data":{"fileId":1,...}}

# 3. Run the workflow (sync for files < 5 MB)
curl -X POST http://localhost:8000/api/projects/1/process \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"fileId":1,"targetLang":"fr"}'
# → {"data":{"xliffFileId":2,"matchStats":{...},"qaIssues":[],...}}

# 4. Download the output XLIFF
curl -O http://localhost:8000/api/files/2/download \
  -H "Authorization: Bearer $TOKEN"
```

---

## Async processing

Files over `ASYNC_THRESHOLD_MB` (default 5 MB) return HTTP 202:

```json
{"data":{"jobId":5,"statusUrl":"/api/jobs/5"}}
```

Poll for completion:

```bash
curl http://localhost:8000/api/jobs/5 -H "Authorization: Bearer $TOKEN"
# → {"data":{"jobId":5,"status":"completed","progress":100,"result":{...}}}
```

---

## Endpoint reference

| Method | Path | Description |
|--------|------|-------------|
| POST | `/api/auth/tokens` | Create API token |
| GET | `/api/auth/tokens` | List tokens |
| DELETE | `/api/auth/tokens/{id}` | Revoke token |
| POST | `/api/files` | Upload file |
| GET | `/api/files/{id}` | File metadata |
| GET | `/api/files/{id}/download` | Download file |
| DELETE | `/api/files/{id}` | Delete file |
| GET | `/api/projects` | List projects |
| POST | `/api/projects` | Create project |
| GET | `/api/projects/{id}` | Get project |
| PATCH | `/api/projects/{id}` | Update project |
| DELETE | `/api/projects/{id}` | Delete project |
| POST | `/api/projects/{id}/process` | Run full workflow |
| POST | `/api/extract` | Extract segments from file |
| POST | `/api/segment` | Segment plain text |
| POST | `/api/tm/lookup` | TM lookup |
| POST | `/api/tm/import` | Import TMX |
| POST | `/api/tm/segments` | Add/update TM segment |
| POST | `/api/mt/translate` | MT translate |
| POST | `/api/qa/run` | QA check XLIFF |
| POST | `/api/terminology/recognize` | Find terms in text |
| POST | `/api/terminology/import` | Import TBX glossary |
| GET | `/api/jobs/{id}` | Poll async job |
| DELETE | `/api/jobs/{id}` | Cancel job |

---

## Configuration

| Env var | Default | Description |
|---------|---------|-------------|
| `MAX_UPLOAD_MB` | `50` | Maximum file upload size |
| `ASYNC_THRESHOLD_MB` | `5` | Files above this size go async |
| `FILE_RETENTION_HOURS` | `24` | Hours before uploaded files expire |
| `DEEPL_API_KEY` | — | Global DeepL key (override per-project) |
| `GOOGLE_TRANSLATE_API_KEY` | — | Global Google Translate key |
| `QUEUE_CONNECTION` | `database` | Set to `redis` for production |
| `FILESYSTEM_DISK` | `local` | Set to `s3` for S3-compatible storage |

---

## Token abilities (scopes)

| Ability | Grants |
|---------|--------|
| `process` | File extraction, segmentation, workflow |
| `tm:read` | TM lookup |
| `tm:write` | TM import, segment update |
| `mt` | MT translate |
| `qa` | QA run |
| `terminology:read` | Term recognition |
| `terminology:write` | TBX import |
| `projects` | Project CRUD |
| `*` | All abilities |

---

## OpenAPI spec

Generate with Scribe:

```bash
php artisan scribe:generate
```

Spec available at `http://localhost:8000/docs` (HTML) and `http://localhost:8000/docs.json` (OpenAPI 3.1 JSON).

---

## Framework packages

All `opencat/*` packages are available via Composer:

```bash
composer require opencat/workflow opencat/translation-memory opencat/qa
```

See the [OpenCAT Framework](https://github.com/shaikhammar/opencat-framework) for the full package list and documentation.

---

## License

MIT
