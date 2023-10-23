#include <Arduino.h>
#include <ArduinoJson.h>
#include <WiFi.h>
#include <WiFiClient.h>
#include <WebServer.h>
#include <ESPmDNS.h>
#include <WiFiUdp.h>
#include <ArduinoOTA.h>
#include <Update.h>
#include <HTTPClient.h>
#include "time.h"
#include <EEPROM.h>

#include "HX711.h"
#include <driver/i2s.h>
#include "arduinoFFT.h"
#include <OneWire.h>
#include <DallasTemperature.h>
#include "SHT2x.h"

#define dataPin  17
#define clockPin 16
SHT2x sht;
#define ONE_WIRE_BUS 18
OneWire oneWire(ONE_WIRE_BUS);
DallasTemperature sensors(&oneWire);
const int LOADCELL_DOUT_PIN = 27;
const int LOADCELL_SCK_PIN = 26;
HX711 scale;
arduinoFFT FFT = arduinoFFT(); /* Create FFT object */
#define I2S_WS 15
#define I2S_SD 13
#define I2S_SCK 2
#define I2S_PORT I2S_NUM_0
const uint16_t samples = 64; //This value MUST ALWAYS be a power of 2
const double samplingFrequency = 22050; //Hz, must be less than 10000 due to ADC
unsigned int sampling_period_us;
unsigned long microseconds;
double vReal[samples];
double vImag[samples];
#define SCL_INDEX 0x00
#define SCL_TIME 0x01
#define SCL_FREQUENCY 0x02
#define SCL_PLOT 0x03

//dato da salvare
struct KeyValue {
  char sensorType[20];        // Chiave (stringa di max 10 caratteri)
  char valueType[20];        // Chiave (stringa di max 10 caratteri)
  float value;           // Valore (intero)
  unsigned long time;  // Timestamp (unsigned long)
};
const int bufferSize = 25;  // Dimensione massima del buffer
const int bufferStartAddress = 0;  // Indirizzo di partenza nella EEPROM per il buffer
KeyValue buffer[bufferSize];  // Buffer per i dati json
int bufferIndex = 0;  // Indice corrente del buffer

#define SERVER_IP ""

String host = "";
#define ssid ""
#define password  ""

unsigned long previousMillis = 0;
unsigned long interval = 30000;

const char* ntpServer = "pool.ntp.org";
const long  gmtOffset_sec = 3600;   //Replace with your GMT offset (seconds)
const int   daylightOffset_sec = 0;  //Replace with your daylight offset (seconds)

DynamicJsonDocument doc(1024);
unsigned long secondsAtNow;

//params inmp441 (mic)
const int ARRAY_SIZE = 500;
double MajorPeakThreshold = 600000;
double lastFreq=0;
double lastGain=0;
int maxMinuteForSavingInmp441 = 1;
double deltaForSavingInmp441 = 500;
unsigned long _secondsAtSavingInmp441=0;

//params hx711 (loadcell)
double lastAverage = 0;
int maxMinuteForSavingHx711 = 30;
double deltaForSavingHx711 = 1;
unsigned long _secondsAtSavingHx711=0;

//params ds18b20 (temperature)
int maxMinuteForSavingDs18b20 = 10;
double deltaForSavingDs18b20 = 1;
unsigned long _secondsAtSavingDs18b20=0;
double lastTempC=0;

//param sht12
int maxMinuteForSavingSht12 = 10;
double deltaForSavingSht12_Temp_c = 1;
unsigned long _secondsAtSavingSht12_Temp_c=0;
double lastTemp_c=0;
double deltaForSavingSht12_Humidity = 3;
unsigned long _secondsAtSavingSht12_Humidity=0;
double lastHumidity=0;

const int numReadings = 500;
int readings[numReadings]; // array per i valori letti
int pointer = 0;            // indice corrente dell'array
int total = 0;            // somma corrente dei valori letti
int average = 0;  

//webserver per l'update del firmware da pagina web
WebServer server(80);
const char* loginIndex =
 "<form name='loginForm'>"
    "<table width='20%' bgcolor='A09F9F' align='center'>"
        "<tr>"
            "<td colspan=2>"
                "<center><font size=4><b>ESP32 Login Page</b></font></center>"
                "<br>"
            "</td>"
            "<br>"
            "<br>"
        "</tr>"
        "<tr>"
             "<td>Username:</td>"
             "<td><input type='text' size=25 name='userid'><br></td>"
        "</tr>"
        "<br>"
        "<br>"
        "<tr>"
            "<td>Password:</td>"
            "<td><input type='Password' size=25 name='pwd'><br></td>"
            "<br>"
            "<br>"
        "</tr>"
        "<tr>"
            "<td><input type='submit' onclick='check(this.form)' value='Login'></td>"
        "</tr>"
    "</table>"
"</form>"
"<script>"
    "function check(form)"
    "{"
    "if(form.userid.value=='admin' && form.pwd.value=='Franchetti2023%')"
    "{"
    "window.open('/serverIndex')"
    "}"
    "else"
    "{"
    " alert('Error Password or Username')/*displays error message*/"
    "}"
    "}"
"</script>";

/*
 * Server Index Page
 */

const char* serverIndex =
"<script src='https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js'></script>"
"<form method='POST' action='#' enctype='multipart/form-data' id='upload_form'>"
   "<input type='file' name='update'>"
        "<input type='submit' value='Update'>"
    "</form>"
 "<div id='prg'>progress: 0%</div>"
 "<script>"
  "$('form').submit(function(e){"
  "e.preventDefault();"
  "var form = $('#upload_form')[0];"
  "var data = new FormData(form);"
  " $.ajax({"
  "url: '/update',"
  "type: 'POST',"
  "data: data,"
  "contentType: false,"
  "processData:false,"
  "xhr: function() {"
  "var xhr = new window.XMLHttpRequest();"
  "xhr.upload.addEventListener('progress', function(evt) {"
  "if (evt.lengthComputable) {"
  "var per = evt.loaded / evt.total;"
  "$('#prg').html('progress: ' + Math.round(per*100) + '%');"
  "}"
  "}, false);"
  "return xhr;"
  "},"
  "success:function(d, s) {"
  "console.log('success!')"
 "},"
 "error: function (a, b, c) {"
 "}"
 "});"
 "});"
 "</script>";

//pulisce la eeprom
void freeEEPROM(){
  EEPROM.write(0,0);
  EEPROM.write(1,0);
  EEPROM.commit();
}

//salva il buffer nella eeprom
void saveBufferToEEPROM() {
  EEPROM.write(0,bufferIndex % 256);
  EEPROM.write(1,bufferIndex / 256);
  
  // Salva il buffer nella EEPROM
  for (int i = 0; i < bufferIndex; i++) {
    EEPROM.put(2 + (i * sizeof(KeyValue)), buffer[i]);
  }

  // Scrivi i dati nella EEPROM
  EEPROM.commit();
  Serial.print("saved ");
  Serial.print(bufferIndex);
  Serial.println(" data on EEPROM");
}

// Leggi i dati dalla EEPROM e ripopola il buffer
void readDataFromEEPROM() {
  bufferIndex = EEPROM.read(0) + (265*EEPROM.read(1));
  for (int i = 0; i < bufferSize; i++) {
    EEPROM.get(2 + (i * sizeof(KeyValue)), buffer[i]);
    // Puoi anche aggiungere qui ulteriori logiche o operazioni sui dati letti
  }

  Serial.print("found ");
  Serial.print(bufferIndex);
  Serial.println(" data on EEPROM");
}


/**
 * Wait for WiFi connection, and, if not connected, reboot
 */
void waitForWiFiConnectOrReboot(bool printOnSerial=true) {
  uint32_t notConnectedCounter = 0;
  bool manageReconnection=false;
  if (WiFi.status() != WL_CONNECTED)
    if(printOnSerial) 
      Serial.print("Wifi connecting");
  while (WiFi.status() != WL_CONNECTED) {
      vTaskDelay (1000); // Check again in about 250ms
      //delay(1000);
      if(printOnSerial) 
        Serial.print(".");
      notConnectedCounter++;
      if(notConnectedCounter > 30) { // Reset board if not connected after 5s
          if(printOnSerial)
            Serial.println("Resetting due to Wifi not connecting...");
          ESP.restart();
      }
      manageReconnection=true;
  }
  if (manageReconnection)
  {
    if(printOnSerial) {
      // Print wifi IP addess
      Serial.print("\nConnected! IP address: ");
      Serial.println(WiFi.localIP());
      Serial.print("\nConnected! MAC address: ");
      Serial.println(WiFi.macAddress());
    }
    //uint64_t chipid = ESP.getEfuseMac(); // The chip ID is essentially its MAC address(length: 6 bytes).
    //uint16_t chip = (uint16_t)(chipid >> 32);
    //char ssid2[23];
    //snprintf(ssid2, 23, "%04X%08X", chip, (uint32_t)chipid);
    if(printOnSerial) {
      // Print wifi IP addess
      //Serial.print("\nConnected! ID: ");
      //Serial.println(ssid);
    }
    host = "API4API_" + WiFi.macAddress();
  }
}

void setup() {
  Serial.begin(115200);
  unsigned long unconnectedTimes = 0;

  //evita di loggare i dati wifi
  esp_log_level_set("wifi", ESP_LOG_NONE); 
  WiFi.setAutoReconnect(true);
  WiFi.begin(ssid, password);
  waitForWiFiConnectOrReboot(true);

  //sync con ntpserver
  configTime(0, 0, ntpServer);
  
  //setup inmp441 (mic)
  i2s_install();
  i2s_setpin();
  i2s_start(I2S_PORT);
  delay(500);
  sampling_period_us = round(1000000*(1.0/samplingFrequency));

  //setup hx711 (loadcell)
  scale.begin(LOADCELL_DOUT_PIN, LOADCELL_SCK_PIN);
  scale.set_scale(2280.f);                      // this value is obtained by calibrating the scale with known weights; see the README for details

  //setup ds18b20 (temperature)
  sensors.begin();
  
  //setup sht21 (temperature, humidity)
  sht.begin(dataPin, clockPin);
  sht.read();
  float temp_c = sht.getTemperature();
  float humidity = sht.getHumidity();
  int sensorValue = humidity;
  for (int i = 0; i < numReadings; i++) {
    readings[i] = sensorValue;
    total += sensorValue;
  }
  
  /*return index page which is stored in serverIndex */
  server.on("/", HTTP_GET, []() {
    server.sendHeader("Connection", "close");
    server.send(200, "text/html", loginIndex);
  });
  server.on("/serverIndex", HTTP_GET, []() {
    server.sendHeader("Connection", "close");
    server.send(200, "text/html", serverIndex);
  });
  /*handling uploading firmware file */
  server.on("/update", HTTP_POST, []() {
    server.sendHeader("Connection", "close");
    server.send(200, "text/plain", (Update.hasError()) ? "FAIL" : "OK");
    ESP.restart();
  }, []() {
    HTTPUpload& upload = server.upload();
    if (upload.status == UPLOAD_FILE_START) {
      Serial.printf("Update: %s\n", upload.filename.c_str());
      if (!Update.begin(UPDATE_SIZE_UNKNOWN)) { //start with max available size
        Update.printError(Serial);
      }
    } else if (upload.status == UPLOAD_FILE_WRITE) {
      /* flashing firmware to ESP*/
      if (Update.write(upload.buf, upload.currentSize) != upload.currentSize) {
        Update.printError(Serial);
      }
    } else if (upload.status == UPLOAD_FILE_END) {
      if (Update.end(true)) { //true to set the size to the current progress
        Serial.printf("Update Success: %u\nRebooting...\n", upload.totalSize);
      } else {
        Update.printError(Serial);
      }
    }
  });
  server.begin();
  
  EEPROM.begin(512);
  readDataFromEEPROM();

  //inizializza il json con value a 0
  doc["value"] = 0.0f;

  ArduinoOTA
    .onStart([]() {
      String type;
      if (ArduinoOTA.getCommand() == U_FLASH)
        type = "sketch";
      else // U_SPIFFS
        type = "filesystem";

      // NOTE: if updating SPIFFS this would be the place to unmount SPIFFS using SPIFFS.end()
      Serial.println("Start updating " + type);
    })
    .onEnd([]() {
      Serial.println("\nEnd");
    })
    .onProgress([](unsigned int progress, unsigned int total) {
      Serial.printf("Progress: %u%%\r", (progress / (total / 100)));
    })
    .onError([](ota_error_t error) {
      Serial.printf("Error[%u]: ", error);
      if (error == OTA_AUTH_ERROR) Serial.println("Auth Failed");
      else if (error == OTA_BEGIN_ERROR) Serial.println("Begin Failed");
      else if (error == OTA_CONNECT_ERROR) Serial.println("Connect Failed");
      else if (error == OTA_RECEIVE_ERROR) Serial.println("Receive Failed");
      else if (error == OTA_END_ERROR) Serial.println("End Failed");
    });

  ArduinoOTA.begin();
}

//restituisce l'epoch time sincronizzato con l'ntp
unsigned long getTime() {
  time_t now;
  struct tm timeinfo;
  if (!getLocalTime(&timeinfo)) {
    //Serial.println("Failed to obtain time");
    return(0);
  }
  time(&now);
  return now;
}

void loop() {
  // post inmp441 (mic)
  double* freqGain = getMicValues();
  if ((((secondsAtNow - _secondsAtSavingInmp441)/60) > maxMinuteForSavingInmp441) || (freqGain[1]>MajorPeakThreshold))
  {
    doc["link"]="";
    doc["sensorType"]="INMP441";
    doc["valueType"]="MajorPeakFrequency";
    doc["value"]=freqGain[0];
    doc["timestampUtc"]=getTime();
    bufferingJson(doc);
    
    doc["link"]="";
    doc["sensorType"]="INMP441";
    doc["valueType"]="MajorPeakGain";
    doc["value"]=freqGain[1];
    doc["timestampUtc"]=getTime();
    bufferingJson(doc);
    
    _secondsAtSavingInmp441 = secondsAtNow;
    lastFreq=freqGain[0];
    lastGain=freqGain[1];
  }

  // post hx711 (loadcell)
  if (scale.is_ready()) {
    double average = scale.get_units(10);
    if ((((secondsAtNow - _secondsAtSavingHx711)/60) > maxMinuteForSavingHx711) || abs(average-lastAverage)>deltaForSavingHx711)
    {
      doc["link"]="";
      doc["sensorType"]="HX711";
      doc["valueType"]="weigth";
      doc["value"]=average;
      doc["timestampUtc"]=getTime();
      bufferingJson(doc);
      _secondsAtSavingHx711=secondsAtNow;
      lastAverage=average;
    }
  }

  sensors.requestTemperatures(); // Send the command to get temperatures
  float tempC = sensors.getTempCByIndex(0);
  if(tempC != DEVICE_DISCONNECTED_C) 
  {
    //Serial.println(tempC);
    if ((((secondsAtNow - _secondsAtSavingDs18b20)/60) > maxMinuteForSavingDs18b20) || abs(tempC-lastTempC)>deltaForSavingDs18b20)
    {
      doc["link"]="";
      doc["sensorType"]="DS18B20";
      doc["valueType"]="temperature";
      doc["value"]=tempC;
      doc["timestampUtc"]=getTime();
      bufferingJson(doc);
      _secondsAtSavingDs18b20=secondsAtNow;
      lastTempC = tempC;
    }
  } 

  sht.read();
  float temp_c = sht.getTemperature();
  float humidity = sht.getHumidity();

  if ((((secondsAtNow - _secondsAtSavingSht12_Temp_c)/60) > maxMinuteForSavingSht12) || abs(temp_c-lastTemp_c)>deltaForSavingSht12_Temp_c)
  {
    doc["link"]="";
    doc["sensorType"]="SHT12";
    doc["valueType"]="temperature";
    doc["value"]=temp_c;
    doc["timestampUtc"]=getTime();
    bufferingJson(doc);
    _secondsAtSavingSht12_Temp_c=secondsAtNow;
    lastTemp_c = temp_c;
  }
  
  int sensorValue = humidity;
  total = total - readings[pointer] + sensorValue;
  readings[pointer] = sensorValue;
  average = total / numReadings;
  pointer++;
  if (pointer >= numReadings)
    pointer = 0;
  if ((((secondsAtNow - _secondsAtSavingSht12_Humidity)/60) > maxMinuteForSavingSht12) || abs(average-lastHumidity)>deltaForSavingSht12_Humidity)
  {
    doc["link"]="";
    doc["sensorType"]="SHT12";
    doc["valueType"]="humidity";
    doc["value"]=humidity;
    doc["timestampUtc"]=getTime();
    bufferingJson(doc);
    _secondsAtSavingSht12_Humidity=secondsAtNow;
    lastHumidity = average;
  }

  //ripristina la connessione wifi qualora sia giù
  waitForWiFiConnectOrReboot(true);

  //se c'è connessione svuota il buffer ed esegue le post
  if (WiFi.status() == WL_CONNECTED) 
  {
    int retry=5;
    while(bufferIndex>0 && retry>0)
    {
      KeyValue newData;
      newData = buffer[bufferIndex-1];
      doc["sensorType"] = String(newData.sensorType);
      doc["valueType"] = String(newData.valueType);
      doc["value"] = newData.value;
      doc["timestampUtc"] = newData.time;
      if (postJson(doc))
      {
        retry=5;
        bufferIndex--;
      }
      else
      {
        retry--;
        delay(1000);
      }
    }
    if (bufferIndex<=0)
      freeEEPROM();
    server.handleClient();
    ArduinoOTA.handle();
  } 
  else
  {
    //se non c'è connessione carica il buffer in eeprom
    saveBufferToEEPROM();
  }
}

void bufferingJson(DynamicJsonDocument docu){
  KeyValue newData;
  snprintf(newData.sensorType, sizeof(newData.sensorType), doc["sensorType"]);
  snprintf(newData.valueType, sizeof(newData.valueType), doc["valueType"]);
  newData.value = doc["value"];
  newData.time = doc["timestampUtc"];

  Serial.print("value: ");
  Serial.println(newData.value);
  Serial.print("valueType: ");
  Serial.println(newData.valueType);
  Serial.print("sensorType: ");
  Serial.println(newData.sensorType);
  Serial.print("timestamp: ");
  Serial.println(newData.time);
  
  // Aggiungi la nuova coppia chiave-valore al buffer
  buffer[bufferIndex] = newData;
  bufferIndex++;

  if (bufferIndex >= bufferSize) {
    bufferIndex=bufferSize-1;
  }
}

bool postJson(DynamicJsonDocument docu){
  bool res = false;
  HTTPClient http;
    
  Serial.print("[HTTP] begin... to: ");  
  Serial.println(SERVER_IP "/RealtimeDatas"); 
  if (http.begin(SERVER_IP "/RealtimeDatas")) 
  {
    http.addHeader("content-type", "application/json");
    http.addHeader("cache-control", "no-cache");
    //http.addHeader("x-apikey", "xxxxxxxxxxxxxxxxxxxxxxxxxxxxx");

    //genera il json
    String output;
    docu["id_beehive"] = 2;
    docu["beehive"]["id"] = 2;
    docu["beehive"]["espMacAddres"] = WiFi.macAddress();
    serializeJson(docu, output);
    
    Serial.print("[HTTP] POST: ");
    Serial.println(output);

    int httpCode = http.POST(output); 
    
    if (httpCode > 0) {
      Serial.printf("[HTTP] POST... code: %d\n", httpCode);

      if (httpCode == HTTP_CODE_CREATED) {
        const String& payload = http.getString();
        Serial.println("received payload:\n<<");
        Serial.println(payload);
        Serial.println(">>");
        res = true;
      }
    } else {
      Serial.printf("[HTTP] POST... failed, error: %s\n", http.errorToString(httpCode).c_str());
    }

    http.end();
  }
  return res;
}


//metodi per estrarre frequenza dominante ed intensità dal microfono
void PrintVector(double *vData, uint16_t bufferSize, uint8_t scaleType)
{
  for (uint16_t i = 0; i < bufferSize; i++)
  {
    double abscissa;
    /* Print abscissa value */
    switch (scaleType)
    {
      case SCL_INDEX:
        abscissa = (i * 1.0);
  break;
      case SCL_TIME:
        abscissa = ((i * 1.0) / samplingFrequency);
  break;
      case SCL_FREQUENCY:
        abscissa = ((i * 1.0 * samplingFrequency) / samples);
  break;
    }
    Serial.print(abscissa, 6);
    if(scaleType==SCL_FREQUENCY)
      Serial.print("Hz");
    Serial.print(" ");
    Serial.println(vData[i], 4);
  }
  Serial.println();
}

void i2s_install(){
  const i2s_config_t i2s_config = {
    .mode = i2s_mode_t(I2S_MODE_MASTER | I2S_MODE_RX),
    .sample_rate = 44100,
    .bits_per_sample = i2s_bits_per_sample_t(16),
    .channel_format = I2S_CHANNEL_FMT_ONLY_LEFT,
    .communication_format = i2s_comm_format_t(I2S_COMM_FORMAT_I2S | I2S_COMM_FORMAT_I2S_MSB),
    .intr_alloc_flags = 0, // default interrupt priority
    .dma_buf_count = 8,
    .dma_buf_len = 64,
    .use_apll = false
  };

  i2s_driver_install(I2S_PORT, &i2s_config, 0, NULL);
}

void i2s_setpin(){
  const i2s_pin_config_t pin_config = {
    .bck_io_num = I2S_SCK,
    .ws_io_num = I2S_WS,
    .data_out_num = -1,
    .data_in_num = I2S_SD
  };

  i2s_set_pin(I2S_PORT, &pin_config);
}

int16_t sBuffer[ARRAY_SIZE];
double* getMicValues()
{
  /*SAMPLING*/
  microseconds = micros();
  for(int i=0; i<samples; i++)
  {
    //int bytes=0;
    //while (bytes == 0)
    {
      //int32_t sample = 0; 
      //bytes = i2s_read(I2S_PORT, (char*)&sample, portMAX_DELAY);
      size_t bytesIn = 0;
      esp_err_t result = i2s_read(I2S_PORT, sBuffer, sizeof(sBuffer), &bytesIn, portMAX_DELAY); // no timeout
      if (result == ESP_OK && bytesIn > 0)
      {
      //if(bytes > 0){
        vReal[i] = bytesIn;
        vImag[i] = 0;
        while(micros() - microseconds < sampling_period_us){
          //empty loop
        }
        microseconds += sampling_period_us;
      }
    }
  }
  /* Print the results of the sampling according to time */
  //Serial.println("Data:");
  //PrintVector(vReal, samples, SCL_TIME);
  FFT.Windowing(vReal, samples, FFT_WIN_TYP_HAMMING, FFT_FORWARD);  /* Weigh data */
  //Serial.println("Weighed data:");
  //PrintVector(vReal, samples, SCL_TIME);
  FFT.Compute(vReal, vImag, samples, FFT_FORWARD); /* Compute FFT */
  //Serial.println("Computed Real values:");
  //PrintVector(vReal, samples, SCL_INDEX);
  //Serial.println("Computed Imaginary values:");
  //PrintVector(vImag, samples, SCL_INDEX);
  FFT.ComplexToMagnitude(vReal, vImag, samples); /* Compute magnitudes */
  //Serial.println("Computed magnitudes:");
  //PrintVector(vReal, (samples >> 1), SCL_FREQUENCY);
  static double freqGain[2];
  double x = 0;
  double v = 0;
  FFT.MajorPeak(vReal, samples, samplingFrequency, &x, &v);
  freqGain[0] = x;
  freqGain[1] = v;
  //Serial.print(x, 6);
  //Serial.print("Hz, ");
  //Serial.println(v, 6);
  return freqGain;
}
