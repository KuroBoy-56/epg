# Guía EPG a medida para tu lista — instalación en Hostinger

Este generador arma **una sola guía XMLTV** con los **mismos nombres de canal que usa tu servicio IPTV**.
Así, el panel, la web player y cualquier app (TiviMate, Smarters…) le ponen la guía sola a cada canal.

- Sale de 56 guías públicas de América y España (epgshare01, open-epg, epg.pw).
- Corre en cualquier hosting con PHP 7.4 o más nuevo. No usa Node, GitHub ni VPS.
- Usa unos 40 MB de memoria y tarda alrededor de 1 minuto.

## 1. Subir los archivos

1. hPanel → **Administrador de archivos** → entra a `public_html`.
2. Crea la carpeta `epg` y sube adentro **todo el contenido** de esta carpeta: `build.php`, `config.php`, `alias.php`, `manual.json`, `canales.json`, `index.php`, `.htaccess`, la carpeta `cache` y `guia.xml.gz`.
   Los archivos que empiezan con punto (`.htaccess`) a veces están ocultos: actívalos en «Mostrar archivos ocultos».
3. Abre en el navegador `https://TUDOMINIO.com/epg/guia.xml.gz`. Si se descarga un archivo, la guía ya está en línea.

## 2. Pegar la URL en tu panel

1. **Primero** sube al panel el archivo `includes/epg_engine.php` nuevo (está en `subir-al-panel/includes/`). Sin él, el panel confunde canales que se llaman parecido en países distintos, como «[IZZI] ESPN» y «[Flow AR] ESPN».
2. Panel → **Guía EPG**. Si eres distribuidor, es tu página **Guía EPG**.
3. En la lista de guías **borra todo** y deja solo esta línea:
   `https://TUDOMINIO.com/epg/guia.xml.gz`
4. Pulsa **Guardar** y luego **Actualizar ahora**.

Ya está: las apps usan la guía del servidor IPTV si la tiene y, si no, esta. El panel la vuelve a leer solo cada 12 horas.

## 3. Que se actualice sola (cron)

hPanel → **Avanzado → Cron Jobs**:

- **Tipo:** PHP
- **Comando / archivo:** `domains/TUDOMINIO.com/public_html/epg/build.php`
  Si te pide el comando completo, usa: `/usr/bin/php /home/TU_USUARIO/domains/TUDOMINIO.com/public_html/epg/build.php`
- **Horario:** todos los días a las 3:00 → minuto `0`, hora `3`, día `*`, mes `*`, día de la semana `*`

Se recomienda una vez al día porque las guías públicas solo traen 2 o 3 días por adelantado. Si de todos modos lo quieres cada 2 días, usa día `*/2`.

Para probarlo sin esperar al cron, pon una clave en `config.php` (`'key' => 'una-clave-larga-al-azar'`) y abre:
`https://TUDOMINIO.com/epg/build.php?key=una-clave-larga-al-azar`

## 4. Ver qué canales tienen guía

Abre `https://TUDOMINIO.com/epg/reporte.html`. Ahí ves:

- el total de canales con guía y el porcentaje por grupo (México, Colombia, USA…);
- la lista de los que quedaron **sin guía**;
- con qué canal de qué guía se emparejó cada uno de los tuyos, para revisarlo.

Los canales 24/7, de eventos, PPV y de pases deportivos no se buscan, porque no tienen programación fija. Se cambia en `config.php`, en `skip_groups`.

## 5. Si tu proveedor agrega canales

El generador trae tu lista de hoy en `canales.json`. Para que se actualice sola cada día, pon tu línea en `config.php`:

```php
'xtream' => [
    'url' => 'http://tu-servidor.com:80',
    'user' => 'tu_usuario',
    'pass' => 'tu_contraseña',
],
```

`config.php` no se puede abrir desde el navegador (lo bloquea `.htaccess`), así que tus datos quedan solo en el hosting.

## 6. Corregir un canal a mano

Si un canal sale con la guía equivocada o sin guía, y lo encuentras en alguna de las guías, agrégalo en `manual.json`:

```json
{
  "CL | CANAL 13 OPC.1": "Canal.13.(Chile).cl",
  "MX | CANAL RARO": null
}
```

- A la izquierda va el **nombre exacto** del canal en tu lista; a la derecha, el **id del canal** en la guía (en el reporte, columna «Canal en la guía»).
- `null` significa «este canal nunca lleva guía».

## 7. Canales locales de EE. UU.

Las guías nombran las estaciones locales por sus siglas (por ejemplo, «CBS 2 CHICAGO» aparece como WBBM). La tabla está en `alias.php`. Si falta una ciudad, agrégala ahí con sus siglas.

## Problemas

- **«No se pudo descargar» en el reporte (al final):** esa guía pública no respondió ese día; se usa la copia del día anterior. Si falla siempre, quítala de `config.php`.
- **La guía no cambia en la app:** en el panel pulsa **Guía EPG → Actualizar ahora**. Las apps guardan la guía unos minutos.
- **Error de memoria o tiempo en el cron:** Hostinger permite lo que necesita (40 MB y 1–2 minutos). Si tu plan es muy limitado, quita de `config.php` las guías de países que no usas.
