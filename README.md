# API4API 🐝

**Smart-beekeeping telemetry platform.** Hives instrumented with an ESP32 node
stream weight, temperature, humidity and acoustic data to a REST API that stores
the readings and raises alerts when a metric crosses a configurable threshold.

The project was carried out in collaboration with Prof. Eng. Francesco Adriani to
raise awareness about the beekeeping crisis: giving beekeepers continuous,
remote insight into hive health so problems are caught early.

[![CI](https://github.com/dylanpatriarchi/API4API/actions/workflows/ci.yml/badge.svg)](https://github.com/dylanpatriarchi/API4API/actions/workflows/ci.yml)

---

## Architecture

```
                            ┌───────────────────────────────────────┐
                            │              BEEHIVE NODE             │
                            │            (ESP32 firmware)           │
                            │                                       │
   ┌──────────┐  weight     │  ┌─────────┐                          │
   │  HX711   ├────────────▶│  │         │  buffer (RAM)            │
   ├──────────┤  temp       │  │ sample  │  └─▶ EEPROM fallback     │
   │ DS18B20  ├────────────▶│  │   +     │        when offline      │
   ├──────────┤  temp/hum   │  │ filter  │                          │
   │  SHT21   ├────────────▶│  │   +     │                          │
   ├──────────┤  noise/FFT  │  │  FFT    │                          │
   │ INMP441  ├────────────▶│  └────┬────┘                          │
   └──────────┘             │       │  HTTPS POST (JSON)            │
                            └───────┼───────────────────────────────┘
                                    │
                                    ▼
   ┌────────────────────────────────────────────────────────────────────────┐
   │                          REST API  (PHP 8, PDO)                          │
   │                                                                          │
   │   public/index.php  ──▶  Router  ──▶  Controllers  ──▶  Database (PDO)   │
   │   (front controller)                     │                               │
   │                                          ▼                               │
   │                                    Mailer (alerts)                       │
   │                                                                          │
   │   Resources:  /esp   /beehives   /measurements   /thresholds            │
   └───────────────────────────────────┬────────────────────────────────────┘
                                        │
                        ┌───────────────┴───────────────┐
                        ▼                               ▼
             ┌────────────────────┐          ┌────────────────────┐
             │   MySQL / MariaDB  │          │   E-mail alerts     │
             │  esp · beehives ·  │          │  (threshold breach) │
             │ measurements ·     │          └────────────────────┘
             │ thresholds         │
             └────────────────────┘
```

**Data flow:** the ESP32 samples every sensor, keeps a reading only when it
changed meaningfully (or a max interval elapsed), buffers it, and POSTs it to the
API when connectivity is available — falling back to EEPROM when offline so no
reading is lost. On ingestion the API compares each value against the stored
thresholds and e-mails an alert on breach.

---

## Repository layout

```
API4API/
├── backend/                     REST API (PHP 8)
│   ├── public/                  Web root — front controller + .htaccess
│   │   └── index.php
│   ├── src/
│   │   ├── Core/                Config, Database, Router, Request, Response
│   │   ├── Controllers/         One controller per resource
│   │   └── Support/             Mailer
│   ├── routes/api.php           Route table
│   ├── config/config.example.php
│   ├── database/schema.sql      Reference schema
│   └── composer.json
├── firmware/
│   └── api4api_node/            ESP32 Arduino sketch + config.example.h
└── .github/workflows/ci.yml     Continuous integration
```

---

## API reference

Base URL: the deployed `backend/public/` directory. All bodies are JSON.

| Method   | Endpoint               | Description                          |
| -------- | ---------------------- | ------------------------------------ |
| `GET`    | `/esp`                 | List ESP boards                      |
| `GET`    | `/esp/{id}`            | Get one ESP board                    |
| `POST`   | `/esp`                 | Register an ESP board                |
| `PUT`    | `/esp/{id}`            | Update an ESP board                  |
| `DELETE` | `/esp/{id}`            | Delete an ESP board                  |
| `GET`    | `/beehives`            | List beehives                        |
| `GET`    | `/beehives/{id}`       | Get one beehive                      |
| `POST`   | `/beehives`            | Create a beehive                     |
| `PUT`    | `/beehives/{id}`       | Update a beehive                     |
| `DELETE` | `/beehives/{id}`       | Delete a beehive                     |
| `GET`    | `/measurements`        | List measurements (newest first)     |
| `GET`    | `/measurements/{id}`   | Get one measurement                  |
| `POST`   | `/measurements`        | Ingest a measurement (raises alerts) |
| `GET`    | `/thresholds`          | List alert thresholds                |
| `GET`    | `/thresholds/{metric}` | Get one threshold                    |
| `PUT`    | `/thresholds/{metric}` | Update a threshold                   |

Responses are wrapped in a `data` envelope; errors return `{ "error": "..." }`
with the matching HTTP status.

**Example — ingest a measurement:**

```bash
curl -X POST https://api.example.com/measurements \
  -H 'Content-Type: application/json' \
  -d '{
        "weight": 42.5,
        "temperature": 34.2,
        "humidity": 61.0,
        "noise_level": 512000,
        "beehive_id": 1,
        "recorded_at": "2026-07-07 10:30:00"
      }'
```

---

## Getting started (backend)

Requirements: PHP 8.1+, MySQL/MariaDB, and [Composer](https://getcomposer.org).

```bash
cd backend
composer install                                   # optional: a PSR-4 fallback
                                                   # loader ships for shared hosting
cp config/config.example.php config/config.php     # then edit the credentials
mysql -u <user> -p <database> < database/schema.sql
```

Point your web server's document root at `backend/public/`. The bundled
`.htaccess` routes every request through the front controller. For local
development:

```bash
php -S localhost:8000 -t backend/public
```

Configuration is read from `config/config.php`, which in turn honours
environment variables (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`,
`ALERT_RECIPIENT`, …) — handy for containerized or CI setups.

## Getting started (firmware)

See [`firmware/README.md`](firmware/README.md). In short: copy
`config.example.h` to `config.h`, fill in your Wi-Fi / API / OTA settings, then
flash `api4api_node.ino` to an ESP32.

---

## Development

```bash
cd backend
composer lint        # PSR-12 check (PHP_CodeSniffer)
composer lint:fix    # auto-fix style
```

CI runs on every push and pull request: it lints the PHP against PSR-12,
validates `composer.json`, syntax-checks every PHP file across PHP 8.1–8.3, and
compiles the ESP32 firmware.

## License

Released under the [MIT License](LICENSE).
