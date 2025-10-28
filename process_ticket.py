from selenium import webdriver
from selenium.webdriver.edge.service import Service
from selenium.webdriver.edge.options import Options
from selenium.webdriver.common.by import By
import mysql.connector
from pathlib import Path
import re
import time

# === CONFIGURACIÓN ===
CARPETA_PROCESADOS = Path(__file__).parent / "procesados"
CARPETA_PROCESADOS.mkdir(exist_ok=True)

EDGE_DRIVER_PATH = r"C:\xampp\htdocs\d\drivers\msedgedriver.exe"

# === CREDENCIALES JIRA (solo si es login directo) ===
USER_JIRA = "1803438@uab.cat"
PASS_JIRA = "2005@rZ9tawcBank9Y5R9FLGn!"

# === Conexión a la base de datos ===
conn = mysql.connector.connect(
    host="localhost",
    user="marc",
    password="Y3CF7B9xoRWz!D@u",
    database="web_procesador"
)
cursor = conn.cursor(dictionary=True)
cursor.execute("SELECT * FROM archivos WHERE status='pendiente'")
tickets = cursor.fetchall()

# === Configurar Edge ===
edge_options = Options()
# ⚠️ Deja visible el navegador para hacer login manual
# edge_options.add_argument("--headless")
edge_options.add_argument("--disable-gpu")
edge_options.add_argument("--no-sandbox")
edge_options.add_argument("--disable-dev-shm-usage")

service = Service(EDGE_DRIVER_PATH)
driver = webdriver.Edge(service=service, options=edge_options)

# === LOGIN EN JIRA / TIQUETS ===
print("Iniciando sesión en Jira...")
driver.get("https://tiquets.uab.cat/login.jsp")
time.sleep(5)

try:
    # Si existe formulario Atlassian
    if driver.find_elements(By.ID, "username"):
        driver.find_element(By.ID, "username").send_keys(USER_JIRA)
        driver.find_element(By.ID, "login-submit").click()
        time.sleep(2)
        driver.find_element(By.ID, "password").send_keys(PASS_JIRA)
        driver.find_element(By.ID, "login-submit").click()
        time.sleep(5)
        print("Inicio de sesión completado automáticamente.\n")
    else:
        print("Formulario de login no encontrado. Es posible que requiera login SSO (manual).")
        print("Por favor, inicia sesión manualmente en la ventana de Edge y luego presiona Enter aquí.")
        input("Pulsa Enter cuando hayas iniciado sesión...")
except Exception as e:
    print(f"Error al intentar hacer login: {e}")

# === PROCESAR TICKETS ===
for ticket in tickets:
    url = ticket["url"]
    id_ticket = ticket["id"]
    nombre_archivo = ticket["filename"] or f"ticket_{id_ticket}"
    output_file = CARPETA_PROCESADOS / f"{nombre_archivo}.txt"
    relative_path = f"procesados/{nombre_archivo}.txt"

    print(f"Procesando {url}...")

    try:
        cursor.execute("UPDATE archivos SET status='procesando' WHERE id=%s", (id_ticket,))
        conn.commit()

        driver.get(url)
        time.sleep(6)

        texto = driver.find_element(By.TAG_NAME, "body").text
        titulo = driver.title or "Sin título"

        texto = texto.encode('utf-8', errors='ignore').decode('utf-8')
        titulo = titulo.encode('utf-8', errors='ignore').decode('utf-8')

        caso = re.search(r"TASQ-\d+", texto)
        servicio = re.search(r"Servei afectat:(.*)", texto)
        descripcion = re.search(r"Descripció:(.*)", texto)

        contenido = f"""
Ticket: {caso.group(0) if caso else 'No encontrado'}
Título: {titulo}
Servicio afectado: {servicio.group(1).strip() if servicio else 'No encontrado'}
Descripción: {descripcion.group(1).strip() if descripcion else 'No encontrada'}

URL: {url}
"""

        output_file.write_text(contenido, encoding="utf-8")

        cursor.execute(
            "UPDATE archivos SET status='procesado', procesado_path=%s, error_msg=NULL WHERE id=%s",
            (relative_path, id_ticket)
        )
        conn.commit()

    except Exception as e:
        cursor.execute(
            "UPDATE archivos SET status='error', error_msg=%s WHERE id=%s",
            (str(e), id_ticket)
        )
        conn.commit()
        print(f"Error procesando {url}: {e}")

driver.quit()
cursor.close()
conn.close()
print("Procesamiento completado.")
