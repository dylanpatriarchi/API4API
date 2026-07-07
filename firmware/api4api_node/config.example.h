// -----------------------------------------------------------------------------
// API4API node configuration template.
//
// Copy this file to `config.h` and fill in the real values.
// `config.h` is git-ignored and must never be committed.
// -----------------------------------------------------------------------------
#ifndef API4API_CONFIG_H
#define API4API_CONFIG_H

// Wi-Fi credentials
#define WIFI_SSID     "your-wifi-ssid"
#define WIFI_PASSWORD "your-wifi-password"

// REST API base URL, e.g. "https://api.example.com"
#define SERVER_IP "https://api.example.com"

// Credentials protecting the over-the-air firmware update page
#define OTA_USERNAME "admin"
#define OTA_PASSWORD "change-me"

// Beehive this node is installed on (matches beehives.beehive_id in the API)
#define BEEHIVE_ID 1

// NTP / timezone
#define NTP_SERVER         "pool.ntp.org"
#define GMT_OFFSET_SEC     3600
#define DAYLIGHT_OFFSET_SEC 0

#endif  // API4API_CONFIG_H
