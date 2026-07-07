# API4API Node Firmware

ESP32 firmware that reads the hive sensors and pushes measurements to the
API4API REST backend.

## Sensors

| Sensor   | Quantity                         |
| -------- | -------------------------------- |
| HX711    | Hive weight (load cell)          |
| DS18B20  | External temperature             |
| SHT21    | Internal temperature & humidity  |
| INMP441  | Hive noise (dominant FFT peak)   |

## Configuration

1. Copy `api4api_node/config.example.h` to `api4api_node/config.h`.
2. Fill in your Wi-Fi credentials, the API base URL, the OTA login and the
   beehive ID. `config.h` is git-ignored and must never be committed.

## Build & flash

Open `api4api_node/api4api_node.ino` in the Arduino IDE (or `arduino-cli`),
select an ESP32 board and upload. Required libraries: `ArduinoJson`, `HX711`,
`arduinoFFT`, `OneWire`, `DallasTemperature`, `SHT2x`.

Over-the-air updates are served from the board's IP at `/` (login) once it is
online.
