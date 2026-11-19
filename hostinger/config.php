<?php
// Configuración del generador de guía EPG (Hostinger / cualquier hosting con PHP).
// Edita solo este archivo. Después de cambiarlo, ejecuta build.php otra vez.
return [
    // Clave para ejecutar el generador por URL: https://tudominio.com/epg/build.php?key=ESTA_CLAVE
    // Pon una larga y al azar (mínimo 16 caracteres). Vacía = solo por cron (línea de comandos).
    'key' => '',

    // Opcional: tus líneas IPTV, para que la lista de canales se actualice sola cada día.
    // Puedes poner varias (una por servicio). Cada una puede ser:
    //   - el enlace M3U que te da el panel del servicio (get.php?username=...&password=...&type=m3u_plus)
    //   - o ['nombre' => 'Servicio 2', 'url' => 'http://servidor.com:80', 'user' => '...', 'pass' => '...']
    // 'nombre' solo sirve para ver cada servicio por separado en reporte.html.
    // Vacío = se usa canales.json (la lista que viene incluida).
    'lineas' => [
        // 'http://servidor1.com:80/get.php?username=USUARIO&password=CLAVE&type=m3u_plus',
        // ['nombre' => 'Servicio 2', 'm3u' => 'http://servidor2.com/get.php?username=USUARIO&password=CLAVE&type=m3u_plus'],
    ],

    // Con 'lineas', canales.json se deja de usar. Pon true para sumarlo también.
    'usar_canales_json' => false,

    // Archivo que genera (la URL que pegas en el panel: https://tudominio.com/epg/guia.xml.gz)
    'output' => 'guia.xml.gz',

    // Horas hacia atrás y días hacia adelante que se guardan en la guía.
    'past_hours' => 3,
    'days' => 4,

    // Grupos de canales que nunca tienen guía (no se buscan). Expresión regular.
    'skip_groups' => '/24\/7|EVENTOS|PASS|PPV|SERIES|LVBP|LIDOM|NETFLIX|GRAN HERMANO|ADULT|XXX|RADIO/i',

    // Guías XMLTV de donde se saca la programación, en orden de preferencia.
    // 'cc' = país de la guía (ayuda a elegir el canal correcto cuando hay nombres repetidos).
    'sources' => [
        ['url' => 'https://epgshare01.online/epgshare01/epg_ripper_MX1.xml.gz', 'cc' => 'mx'],
        ['url' => 'https://www.open-epg.com/files/mexico1.xml.gz', 'cc' => 'mx'],
        ['url' => 'https://www.open-epg.com/files/mexico2.xml.gz', 'cc' => 'mx'],
        ['url' => 'https://www.open-epg.com/files/mexico3.xml.gz', 'cc' => 'mx'],
        ['url' => 'https://epgshare01.online/epgshare01/epg_ripper_CO1.xml.gz', 'cc' => 'co'],
        ['url' => 'https://www.open-epg.com/files/colombia1.xml.gz', 'cc' => 'co'],
        ['url' => 'https://www.open-epg.com/files/colombia2.xml.gz', 'cc' => 'co'],
        ['url' => 'https://epgshare01.online/epgshare01/epg_ripper_AR1.xml.gz', 'cc' => 'ar'],
        ['url' => 'https://www.open-epg.com/files/argentina1.xml.gz', 'cc' => 'ar'],
        ['url' => 'https://www.open-epg.com/files/argentina2.xml.gz', 'cc' => 'ar'],
        ['url' => 'https://epgshare01.online/epgshare01/epg_ripper_CL1.xml.gz', 'cc' => 'cl'],
        ['url' => 'https://www.open-epg.com/files/chile1.xml.gz', 'cc' => 'cl'],
        ['url' => 'https://www.open-epg.com/files/chile2.xml.gz', 'cc' => 'cl'],
        ['url' => 'https://epgshare01.online/epgshare01/epg_ripper_PE1.xml.gz', 'cc' => 'pe'],
        ['url' => 'https://www.open-epg.com/files/peru1.xml.gz', 'cc' => 'pe'],
        ['url' => 'https://www.open-epg.com/files/peru2.xml.gz', 'cc' => 'pe'],
        ['url' => 'https://epgshare01.online/epgshare01/epg_ripper_EC1.xml.gz', 'cc' => 'ec'],
        ['url' => 'https://www.open-epg.com/files/ecuador1.xml.gz', 'cc' => 'ec'],
        ['url' => 'https://www.open-epg.com/files/venezuela1.xml.gz', 'cc' => 've'],
        ['url' => 'https://www.open-epg.com/files/venezuela2.xml.gz', 'cc' => 've'],
        ['url' => 'https://www.open-epg.com/files/bolivia1.xml.gz', 'cc' => 'bo'],
        ['url' => 'https://www.open-epg.com/files/paraguay1.xml.gz', 'cc' => 'py'],
        ['url' => 'https://epgshare01.online/epgshare01/epg_ripper_UY1.xml.gz', 'cc' => 'uy'],
        ['url' => 'https://www.open-epg.com/files/uruguay1.xml.gz', 'cc' => 'uy'],
        ['url' => 'https://epgshare01.online/epgshare01/epg_ripper_CR1.xml.gz', 'cc' => 'cr'],
        ['url' => 'https://www.open-epg.com/files/costarica1.xml.gz', 'cc' => 'cr'],
        ['url' => 'https://www.open-epg.com/files/costarica2.xml.gz', 'cc' => 'cr'],
        ['url' => 'https://www.open-epg.com/files/guatemala1.xml.gz', 'cc' => 'gt'],
        ['url' => 'https://www.open-epg.com/files/guatemala2.xml.gz', 'cc' => 'gt'],
        ['url' => 'https://www.open-epg.com/files/honduras1.xml.gz', 'cc' => 'hn'],
        ['url' => 'https://www.open-epg.com/files/honduras2.xml.gz', 'cc' => 'hn'],
        ['url' => 'https://epgshare01.online/epgshare01/epg_ripper_SV1.xml.gz', 'cc' => 'sv'],
        ['url' => 'https://www.open-epg.com/files/elsalvador1.xml.gz', 'cc' => 'sv'],
        ['url' => 'https://epgshare01.online/epgshare01/epg_ripper_PA1.xml.gz', 'cc' => 'pa'],
        ['url' => 'https://www.open-epg.com/files/panama1.xml.gz', 'cc' => 'pa'],
        ['url' => 'https://epgshare01.online/epgshare01/epg_ripper_DO1.xml.gz', 'cc' => 'do'],
        ['url' => 'https://www.open-epg.com/files/puertorico1.xml.gz', 'cc' => 'pr'],
        ['url' => 'https://www.open-epg.com/files/puertorico2.xml.gz', 'cc' => 'pr'],
        ['url' => 'https://epgshare01.online/epgshare01/epg_ripper_BR1.xml.gz', 'cc' => 'br'],
        ['url' => 'https://epgshare01.online/epgshare01/epg_ripper_BR2.xml.gz', 'cc' => 'br'],
        ['url' => 'https://www.open-epg.com/files/brazil1.xml.gz', 'cc' => 'br'],
        ['url' => 'https://www.open-epg.com/files/brazil2.xml.gz', 'cc' => 'br'],
        ['url' => 'https://epgshare01.online/epgshare01/epg_ripper_US2.xml.gz', 'cc' => 'us'],
        ['url' => 'https://epgshare01.online/epgshare01/epg_ripper_US_SPORTS1.xml.gz', 'cc' => 'us'],
        ['url' => 'https://www.open-epg.com/files/unitedstates1.xml.gz', 'cc' => 'us'],
        ['url' => 'https://www.open-epg.com/files/unitedstates2.xml.gz', 'cc' => 'us'],
        ['url' => 'https://www.open-epg.com/files/unitedstates3.xml.gz', 'cc' => 'us'],
        ['url' => 'https://www.open-epg.com/files/unitedstates4.xml.gz', 'cc' => 'us'],
        ['url' => 'https://epg.pw/xmltv/epg_US.xml.gz', 'cc' => 'us'],
        ['url' => 'https://epgshare01.online/epgshare01/epg_ripper_CA2.xml.gz', 'cc' => 'ca'],
        ['url' => 'https://www.open-epg.com/files/canada1.xml.gz', 'cc' => 'ca'],
        ['url' => 'https://epgshare01.online/epgshare01/epg_ripper_ES1.xml.gz', 'cc' => 'es'],
        ['url' => 'https://www.open-epg.com/files/spain1.xml.gz', 'cc' => 'es'],
        ['url' => 'https://www.open-epg.com/files/spain2.xml.gz', 'cc' => 'es'],
        ['url' => 'https://epgshare01.online/epgshare01/epg_ripper_PLEX1.xml.gz', 'cc' => 'us'],
        ['url' => 'https://epgshare01.online/epgshare01/epg_ripper_DISTROTV1.xml.gz', 'cc' => 'us'],
    ],
];
