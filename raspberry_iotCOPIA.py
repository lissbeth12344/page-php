from AWSIoTPythonSDK.MQTTLib import AWSIoTMQTTClient
import json
import requests
import os
import time
import ssl


ENDPOINT = "a16qx4zp2qwzuc-ats.iot.us-east-1.amazonaws.com"
CERT_PATH = "/home/andrea/claves/aga/51bffd1ca8afd2d1899e043c2a8e73f53814a8c7cd64d4663b6c67730cf7e97a-certificate.pem.crt"
KEY_PATH = "/home/andrea/claves/aga/51bffd1ca8afd2d1899e043c2a8e73f53814a8c7cd64d4663b6c67730cf7e97a-private.pem.key"
ROOT_CA_PATH = "/home/andrea/claves/aga/AmazonRootCA1.pem"

def callback(client, userdata, message):
    try:
        data = json.loads(message.payload)
        print(f" NUEVO MENSAJE RECIBIDO")
        print(f"   Archivo: {data['archivo']}")
        print(f"   Bucket: {data['bucket']}")

        print(f"   Descargando...")
        response = requests.get(data['url'])

        if response.status_code == 200:
            os.makedirs("/home/andrea/descargas", exist_ok=True)

            filename = data['archivo'].split('/')[-1]
            filepath = f"/home/andrea/descargas/{filename}"

            with open(filepath, "wb") as f:
                f.write(response.content)

            print(f"  Archivo descargado: {filename}")
            print(f"  Ubicación: {filepath}")

            if filename.lower().endswith((".png", ".jpg", ".jpeg", ".gif", ".bmp")):
                os.system(f"zenity --info --text='Imagen recibida: {filename}' --width=300 --height=100 &")
                os.system(f"xdg-open '{filepath}' &")
                print("  Popup mostrado y visor abierto")

        else:
            print(f" Error al descargar: {response.status_code}")

    except Exception as e:
        print(f" Error al procesar mensaje: {e}")

mqtt_client = AWSIoTMQTTClient("raspberrypi")

mqtt_client.configureEndpoint(ENDPOINT, 8883)

mqtt_client.configureCredentials(ROOT_CA_PATH, KEY_PATH, CERT_PATH)

mqtt_client.configureOfflinePublishQueueing(-1)
mqtt_client.configureDrainingFrequency(2)
mqtt_client.configureConnectDisconnectTimeout(10)
mqtt_client.configureMQTTOperationTimeout(5)

mqtt_client.tls_insecure = True

try:
    print("Conectando a AWS IoT Core...")
    mqtt_client.connect()
    print("Conectado a AWS IoT Core")

    print(" Suscribiéndose al tópico: raspberry/descargar")
    mqtt_client.subscribe("raspberry/descargar", 1, callback)
    print("Suscripción exitosa!")
    print(" Esperando notificaciones... (presiona Ctrl+C para salir)\n")

    while True:
        time.sleep(1)

except KeyboardInterrupt:
    print(" Desconectando...")
    mqtt_client.disconnect()
    print(" Desconectado")
except Exception as e:
    print(f"Error: {e}")
