import sys
from pathlib import Path
import unicodedata
import re
from bs4 import BeautifulSoup

# --- Funciones base ---
def normalizar(texto):
    if not texto:
        return ""
    texto = texto.strip().lower()
    texto = unicodedata.normalize("NFD", texto)
    texto = "".join(c for c in texto if unicodedata.category(c) != "Mn")
    texto = texto.replace("’", "'").replace("‘", "'").replace("`", "'")
    return " ".join(texto.split())

def construir_mapa_tabla(soup):
    mapping = {}
    for tr in soup.find_all("tr"):
        celdas = tr.find_all(["td", "th"])
        if len(celdas) >= 2:
            etiqueta = celdas[0].get_text(" ", strip=True)
            valor = celdas[1].get_text(" ", strip=True)
            mapping[normalizar(etiqueta)] = valor
    for b in soup.find_all("b"):
        etiqueta = b.get_text(" ", strip=True).replace(":", "")
        td_padre = b.find_parent("td")
        if td_padre:
            siguiente = td_padre.find_next_sibling("td")
            if siguiente:
                mapping.setdefault(normalizar(etiqueta), siguiente.get_text(" ", strip=True))
    return mapping

def extraer_info_doc(ruta_doc):
    html = Path(ruta_doc).read_text(encoding="utf-8", errors="replace")
    soup = BeautifulSoup(html, "html.parser")
    mapping = construir_mapa_tabla(soup)

    def key_lookup(clave):
        return mapping.get(normalizar(clave), "Nul")

    variantes_titol = ["titol de l'avis", "titol de l'avís", "titol de l avis", "titol", "titol del avis"]
    titol_avis = next((key_lookup(v) for v in variantes_titol if key_lookup(v) != "Nul"), "Nul")

    campos_interes = [
        "Servei afectat", "Descripció", "Afectació a l'usuari", "Canal de notificació",
        "Data d'inici", "Hora d'inici", "Data de finalització", "Hora de finalització",
        "Procediment", "El canvi/intervenció el realitza producció (AESC)"
    ]
    extraidos = {campo: key_lookup(campo) for campo in campos_interes}

    # Ajustar Procediment según El canvi/intervenció
    canvi_aesc = extraidos.get("El canvi/intervenció el realitza producció (AESC)", "").strip().lower()
    procediment = extraidos.get("Procediment", "")
    pasos = [p.strip() for p in re.split(r'\n|paso\s*\d+[:.]', procediment, flags=re.IGNORECASE) if p.strip()]

    if pasos:
        if "sí" in canvi_aesc or "si" in canvi_aesc:
            pasos_seleccionados = pasos[:2]
        else:
            pasos_seleccionados = [pasos[0]]
            if len(pasos) > 2:
                pasos_seleccionados.extend(pasos[2:4])
        extraidos["Procediment"] = "\n".join(pasos_seleccionados)
    else:
        extraidos["Procediment"] = "Nul"

    desc_area = soup.find(id="descriptionArea")
    if desc_area:
        extraidos["Descripció"] = desc_area.get_text(" ", strip=True)

    titulo_html = soup.title.string.strip() if soup.title else "Sin título HTML"

    return {
        "nombre": Path(ruta_doc).stem,
        "titol_avis": titol_avis,
        "titulo_html": titulo_html,
        "campos": extraidos
    }

# --- Función principal ---
def main():
    if len(sys.argv) != 3:
        print("Uso: python process_doc.py <ruta_doc_en_uploads> <ruta_salida_txt_en_procesados>")
        sys.exit(1)

    ruta_doc = Path(sys.argv[1])
    salida_txt = Path(sys.argv[2])

    if not ruta_doc.exists():
        print(f"No se encontró el archivo: {ruta_doc}")
        sys.exit(2)

    info = extraer_info_doc(ruta_doc)

    with open(salida_txt, "w", encoding="utf-8") as f:
        f.write(f"=== {info['nombre']} ===\n")
        f.write(f"Títol de l’avís: {info['titol_avis']}\n")
        f.write(f"(Títol HTML: {info['titulo_html']})\n\n")
        for campo, valor in info["campos"].items():
            f.write(f"{campo}: {valor}\n")
        f.write("\n")

    print(f"Procesado: {ruta_doc.name} -> {salida_txt}")

if __name__ == "__main__":
    main()
