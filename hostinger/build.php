<?php
// Generador de guía EPG a medida para tu lista de canales.
//
// Descarga guías XMLTV públicas (config.php), busca en ellas cada canal de tu lista
// (por nombre, país y siglas) y escribe guia.xml.gz con los MISMOS nombres de canal que
// usa tu servicio IPTV, para que el panel, la web player y cualquier app la enlacen sola.
//
// Uso:
//   Cron (recomendado):  php /ruta/a/epg/build.php
//   Por URL:             https://tudominio.com/epg/build.php?key=TU_CLAVE   (clave en config.php)
// Resultado: guia.xml.gz (la URL que pegas en el panel) y reporte.html (qué canales tienen guía).

declare(strict_types=1);

const EPG_DIR = __DIR__;
const LATAM = ['mx', 'co', 'ar', 'cl', 'pe', 'ec', 've', 'bo', 'py', 'uy', 'gt', 'hn', 'sv', 'ni', 'cr', 'pa', 'pr', 'do', 'cu', 'br'];

$cfg = require EPG_DIR . '/config.php';
$cli = PHP_SAPI === 'cli';
if (!$cli) {
    $key = (string) ($cfg['key'] ?? '');
    if (strlen($key) < 16 || !hash_equals($key, (string) ($_GET['key'] ?? ''))) {
        http_response_code(404);
        exit;
    }
    header('Content-Type: text/plain; charset=utf-8');
    ignore_user_abort(true);
}
@set_time_limit(0);
@ini_set('memory_limit', '768M');

if (!is_dir(EPG_DIR . '/cache')) {
    mkdir(EPG_DIR . '/cache', 0755, true);
}
$lock = fopen(EPG_DIR . '/cache/build.lock', 'c');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
    echo "Ya se está generando la guía.\n";
    exit;
}

function say(string $m): void {
    echo '[' . date('H:i:s') . "] $m\n";
    @ob_flush();
    @flush();
}

// ---------------------------------------------------------------- nombres

function lower_plain(string $s): string {
    $s = mb_strtolower($s, 'UTF-8');
    $s = strtr($s, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
        'à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u', 'ç' => 'c', 'â' => 'a', 'ê' => 'e',
        'ô' => 'o', 'ã' => 'a', 'õ' => 'o', 'ï' => 'i', 'ë' => 'e']);
    return $s;
}

const PREFIX_CC = ['mx' => 'mx', 'mex' => 'mx', 'cl' => 'cl', 'co' => 'co', 'col' => 'co', 'ar' => 'ar', 'arg' => 'ar',
    'pe' => 'pe', 'ec' => 'ec', 've' => 've', 'ven' => 've', 'bo' => 'bo', 'py' => 'py', 'uy' => 'uy', 'gt' => 'gt',
    'hn' => 'hn', 'sv' => 'sv', 'ni' => 'ni', 'cr' => 'cr', 'pa' => 'pa', 'pr' => 'pr', 'do' => 'do', 'rd' => 'do',
    'cu' => 'cu', 'br' => 'br', 'es' => 'es', 'us' => 'us', 'usa' => 'us', 'ca' => 'ca'];

const CC_WORDS = ['republica dominicana' => 'do', 'rep dom' => 'do', 'dominicana' => 'do', 'puerto rico' => 'pr',
    'costa rica' => 'cr', 'el salvador' => 'sv', 'estados unidos' => 'us', 'latin america' => 'la',
    'america latina' => 'la', 'latinoamerica' => 'la', 'centroamerica' => 'la', 'sudamerica' => 'la',
    'latam' => 'la', 'latino' => 'la', 'mexico' => 'mx', 'chile' => 'cl', 'colombia' => 'co',
    'argentina' => 'ar', 'peru' => 'pe', 'ecuador' => 'ec', 'venezuela' => 've', 'bolivia' => 'bo',
    'paraguay' => 'py', 'uruguay' => 'uy', 'guatemala' => 'gt', 'guate' => 'gt', 'honduras' => 'hn',
    'nicaragua' => 'ni', 'panama' => 'pa', 'cuba' => 'cu', 'brasil' => 'br', 'brazil' => 'br',
    'espana' => 'es', 'spain' => 'es', 'usa' => 'us', 'canada' => 'ca'];

const NUM_WORDS = ['uno' => '1', 'dos' => '2', 'tres' => '3', 'cuatro' => '4', 'cinco' => '5', 'seis' => '6',
    'siete' => '7', 'ocho' => '8', 'nueve' => '9', 'diez' => '10', 'once' => '11', 'doce' => '12', 'trece' => '13'];

const SUFFIX_CC = ['arg' => 'ar', 'ar' => 'ar', 'col' => 'co', 'co' => 'co', 'pe' => 'pe', 'cl' => 'cl', 'chi' => 'cl',
    'mx' => 'mx', 'mex' => 'mx', 'ven' => 've', 've' => 've', 'bo' => 'bo', 'py' => 'py', 'uy' => 'uy', 'ec' => 'ec',
    'gt' => 'gt', 'hn' => 'hn', 'cr' => 'cr', 'pa' => 'pa', 'pr' => 'pr', 'rd' => 'do', 'br' => 'br', 'bra' => 'br',
    'usa' => 'us', 'us' => 'us', 'sv' => 'sv', 'ni' => 'ni'];

// abreviaturas que las listas usan distinto
const SYNONYMS = ['dsports' => 'directv sports', 'chv' => 'chilevision', 'natgeo' => 'national geographic',
    'nat geo' => 'national geographic', 'stgo' => 'santiago', 'disc' => 'discovery', 'hist' => 'history',
    'tnt sport' => 'tnt sports', 'espn premium' => 'espn premium', 'fox sport' => 'fox sports'];

const NOISE = ['canal', 'channel', 'tv', 'television', 'tele', 'de', 'del', 'la', 'el', 'los', 'las', 'the',
    'hd', 'fhd', 'uhd', 'sd', '4k', '8k', 'hevc', 'h265', 'h264', 'fps', 'backup', 'bkp', 'alt', 'vip',
    'east', 'este', 'feed', 'envivo', 'vivo', 'en', 'oficial', 'official', 'senal', 'network'];

// "CL | CANAL 13 OPC.1 (720p)" -> ['t' => ['13'], 'k' => '13', 'cc' => 'cl', 'call' => null]
function canon(string $raw): array {
    $s = lower_plain(html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    $cc = null;
    $call = null;
    if (preg_match('/^\s*([kw][a-z]{2,3})(?:-(?:tv|dt|cd|ld|lp|ca)\d*)?\b/', $s, $m)) {
        $call = strtoupper($m[1]);
    }
    // prefijos: "MX |", "[Flow AR]", "USA l", "DEP |"
    for ($i = 0; $i < 3; $i++) {
        if (preg_match('/^\s*([a-z]{2,4})\s*[|:]\s*/', $s, $m)) {
            $cc = $cc ?? (PREFIX_CC[$m[1]] ?? null);
            $s = substr($s, strlen($m[0]));
        } elseif (preg_match('/^\s*usa\s+l\s+/', $s, $m)) {
            $cc = $cc ?? 'us';
            $s = substr($s, strlen($m[0]));
        } elseif (preg_match('/^\s*\[?([^\]|]{1,14})\]\s*/', $s, $m)) {
            if (preg_match('/\b([a-z]{2})$/', trim($m[1]), $c)) {
                $cc = $cc ?? (PREFIX_CC[$c[1]] ?? null);
            }
            $s = substr($s, strlen($m[0]));
        } else {
            break;
        }
    }
    // paréntesis: país o siglas, luego se quitan
    $paren = '';
    $s = preg_replace_callback('/[\(\[]([^\)\]]*)[\)\]]/', function ($m) use (&$cc, &$call, &$paren) {
        foreach (CC_WORDS as $w => $c) {
            if (preg_match('/\b' . $w . '\b/', $m[1])) {
                $cc = $cc ?? $c;
                return ' ';
            }
        }
        if (preg_match('/\b(pacific|pacifico|west|oeste)\b/', $m[1])) {
            return ' pacific ';
        }
        if (preg_match('/^\s*([kw][a-z]{2,3})(?:-(?:tv|dt|cd|ld))?\s*$/', $m[1], $c)) {
            $call = $call ?? strtoupper($c[1]);
        } elseif (!preg_match('/antes|antiguo|solo|\d{3,4}p|24\/7|encoder|^\s*[a-z]?\s*$/', $m[1])) {
            $paren .= ' ' . $m[1];
        }
        return ' ';
    }, $s);
    $s = preg_replace('/\bnot\s*24\/7\b|\bgeo-?blocked\b|\b\d{3,4}p\b|\b\d{2}fps\b/', ' ', $s);
    $s = preg_replace('/\bopc(?:ion)?\.?\s*#?\s*\d+\b|\bop\.?\s*\d+\b|#\s*\d+|\bopc\b/', ' ', $s);
    $s = str_replace(['&', "'", '´', '’'], '', $s);
    $s = str_replace('+', ' plus ', $s);
    $s = preg_replace('/\b(pacifico|west|oeste)\b/', 'pacific', $s);
    $s = preg_replace('/[^a-z0-9]+/', ' ', $s);
    $s = ' ' . trim($s) . ' ';
    foreach (CC_WORDS as $w => $c) {
        if (strpos($s, " $w ") !== false && trim(str_replace(" $w ", ' ', $s)) !== '') {
            $cc = $cc ?? $c;
            $s = str_replace(" $w ", ' ', $s);
        }
    }
    $tokens = canon_tokens($s);
    if (count($tokens) > 1 && isset(SUFFIX_CC[end($tokens)])) {
        $cc = $cc ?? SUFFIX_CC[end($tokens)];
        array_pop($tokens);
    }
    $alt = [];
    if (trim($paren) !== '') {
        $p = canon_tokens(preg_replace('/[^a-z0-9]+/', ' ', str_replace('+', ' plus ', $paren)));
        if ($p) {
            $alt = array_merge($tokens, $p);
        }
    }
    return ['t' => $tokens, 'k' => implode('', $tokens), 'cc' => $cc, 'call' => $call, 'alt' => $alt];
}

function canon_tokens(string $s): array {
    $s = ' ' . trim($s) . ' ';
    foreach (SYNONYMS as $a => $b) {
        $s = str_replace(" $a ", " $b ", $s);
    }
    $tokens = [];
    foreach (explode(' ', trim($s)) as $t) {
        if ($t === '' || in_array($t, NOISE, true)) {
            continue;
        }
        $tokens[] = NUM_WORDS[$t] ?? $t;
    }
    return $tokens;
}

function bag(array $t): string {
    sort($t);
    return implode(' ', $t);
}

// qué tan bien encaja el país de la guía (cs) con el del canal (cp)
function cc_pref(?string $cp, ?string $cs): int {
    if ($cp !== null && $cp === $cs) {
        return 100;
    }
    $latam = $cp !== null && in_array($cp, LATAM, true);
    if ($cp === null || $cp === 'la') {
        if ($cs === 'la') return 90;
        if ($cs !== null && in_array($cs, LATAM, true)) return 70;
        if ($cs === null) return 50;
        return $cs === 'us' ? 40 : 15;
    }
    if ($latam) {
        if ($cs === 'la') return 85;
        if ($cs === null) return 50;
        if (in_array($cs, LATAM, true)) return 45;
        return $cs === 'us' ? 25 : 10;
    }
    if ($cp === 'us') {
        if ($cs === null) return 50;
        if ($cs === 'ca') return 45;
        return $cs === 'la' ? 30 : 10;
    }
    return $cs === null ? 40 : 10;
}

function group_cc(string $g): ?string {
    $g = lower_plain($g);
    if (preg_match('/\b(usa|telemundo|univision|unimas|galavision)\b/', $g)) return 'us';
    if (strpos($g, 'republica dominicana') !== false || strpos($g, 'rep dom') !== false) return 'do';
    foreach (CC_WORDS as $w => $c) {
        if ($c !== 'la' && preg_match('/\b' . $w . '\b/', $g)) return $c;
    }
    if (preg_match('/\b(izzi|vix|liga mx)\b/', $g)) return 'mx';
    if (preg_match('/\bflow\b/', $g)) return 'ar';
    if (preg_match('/\bdgo\b/', $g)) return 'co';
    return 'la';
}

// ---------------------------------------------------------------- lista de canales

// Canales de todas tus líneas IPTV (config.php › 'lineas') más canales.json.
// Cada línea se guarda en cache/linea_N.json: si un día su servidor no responde, se usa la copia anterior.
function load_channels(array $cfg): array {
    $lines = $cfg['lineas'] ?? [];
    $old = $cfg['xtream'] ?? []; // configuración vieja de una sola línea
    if (!empty($old['url']) && !empty($old['user'])) {
        $lines[] = $old;
    }
    $all = [];
    foreach (array_values($lines) as $i => $line) {
        $line = is_string($line) ? ['m3u' => $line] : (array) $line;
        $label = trim((string) ($line['nombre'] ?? '')) ?: 'Línea ' . ($i + 1);
        $cache = EPG_DIR . '/cache/linea_' . ($i + 1) . '.json';
        $list = line_channels($line);
        if ($list) {
            file_put_contents($cache, json_encode($list, JSON_UNESCAPED_UNICODE), LOCK_EX);
            say("$label: " . count($list) . ' canales en vivo');
        } else {
            $list = json_decode((string) @file_get_contents($cache), true) ?: [];
            say("$label: no respondió; se usa la copia anterior (" . count($list) . ' canales)');
        }
        foreach ($list as $ch) {
            $ch['group'] = count($lines) > 1 ? $label . ' › ' . $ch['group'] : $ch['group'];
            $all[] = $ch;
        }
    }
    if (!$lines || !empty($cfg['usar_canales_json'])) {
        foreach (json_decode((string) @file_get_contents(EPG_DIR . '/canales.json'), true) ?: [] as $ch) {
            $all[] = $ch;
        }
    }
    // el mismo nombre en dos líneas es el mismo canal
    $seen = [];
    $out = [];
    foreach ($all as $ch) {
        $k = mb_strtolower(trim((string) ($ch['name'] ?? '')), 'UTF-8');
        if ($k !== '' && !isset($seen[$k])) {
            $seen[$k] = true;
            $out[] = $ch;
        }
    }
    return $out;
}

// Una línea: enlace M3U (get.php?username=…&password=…), o url + user + pass, o solo un enlace .m3u
function line_channels(array $line): array {
    $url = trim((string) ($line['m3u'] ?? ''));
    $user = (string) ($line['user'] ?? '');
    $pass = (string) ($line['pass'] ?? '');
    $base = rtrim((string) ($line['url'] ?? ''), '/');
    if ($url !== '' && preg_match('#^(https?://[^/?\#]+)/get\.php\?#i', $url, $m)) {
        parse_str((string) parse_url($url, PHP_URL_QUERY), $q);
        if (!empty($q['username'])) {
            [$base, $user, $pass] = [$m[1], (string) $q['username'], (string) ($q['password'] ?? '')];
        }
    }
    if ($base !== '' && $user !== '') {
        $api = $base . '/player_api.php?username=' . rawurlencode($user) . '&password=' . rawurlencode($pass);
        $cats = json_decode((string) http_get($api . '&action=get_live_categories', 60), true);
        $streams = json_decode((string) http_get($api . '&action=get_live_streams', 180), true);
        if (is_array($cats) && is_array($streams) && $streams) {
            $names = [];
            foreach ($cats as $c) {
                $names[(string) ($c['category_id'] ?? '')] = (string) ($c['category_name'] ?? '');
            }
            $list = [];
            foreach ($streams as $st) {
                $list[] = ['name' => (string) ($st['name'] ?? ''), 'epg' => (string) ($st['epg_channel_id'] ?? ''),
                    'group' => $names[(string) ($st['category_id'] ?? '')] ?? ''];
            }
            return $list;
        }
        if ($url === '') {
            return [];
        }
    }
    return $url !== '' ? m3u_channels($url) : [];
}

// Lee una lista M3U sin guardarla entera: solo los canales en vivo (no películas ni series).
function m3u_channels(string $url): array {
    $tmp = EPG_DIR . '/cache/lista.m3u.part';
    $fp = fopen($tmp, 'w');
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_FILE => $fp, CURLOPT_FOLLOWLOCATION => true, CURLOPT_MAXREDIRS => 5,
        CURLOPT_CONNECTTIMEOUT => 20, CURLOPT_TIMEOUT => 600, CURLOPT_USERAGENT => 'Mozilla/5.0 EPG-Builder']);
    curl_exec($ch);
    $ok = !curl_errno($ch) && (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE) === 200;
    curl_close($ch);
    fclose($fp);
    $list = [];
    if ($ok && ($in = fopen($tmp, 'r'))) {
        $inf = null;
        while (($row = fgets($in)) !== false) {
            $row = trim($row);
            if (strncmp($row, '#EXTINF', 7) === 0) {
                $inf = $row;
            } elseif ($inf !== null && $row !== '' && $row[0] !== '#') {
                $u = strtolower($row);
                if (strpos($u, '/movie/') === false && strpos($u, '/series/') === false
                    && !preg_match('/\.(mp4|mkv|avi|mov)(\?|$)/', $u)) {
                    preg_match('/tvg-id="([^"]*)"/', $inf, $id);
                    preg_match('/group-title="([^"]*)"/', $inf, $g);
                    preg_match('/tvg-name="([^"]*)"/', $inf, $tn);
                    $name = trim(substr($inf, strrpos($inf, ',') + 1)) ?: ($tn[1] ?? '');
                    $list[] = ['name' => $name, 'epg' => $id[1] ?? '', 'group' => $g[1] ?? ''];
                }
                $inf = null;
            }
        }
        fclose($in);
    }
    @unlink($tmp); // la lista lleva tus datos en cada enlace: no se guarda
    return $list;
}

function http_get(string $url, int $timeout) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_MAXREDIRS => 5,
        CURLOPT_CONNECTTIMEOUT => 15, CURLOPT_TIMEOUT => $timeout, CURLOPT_USERAGENT => 'Mozilla/5.0 EPG-Builder']);
    $body = curl_exec($ch);
    $ok = !curl_errno($ch) && (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE) === 200;
    curl_close($ch);
    return $ok ? $body : false;
}

function download(string $url, string $file): bool {
    $local = getenv('EPG_LOCAL_DIR'); // solo para pruebas sin internet
    if ($local) {
        return @copy(rtrim($local, '/') . '/' . basename(parse_url($url, PHP_URL_PATH)), $file);
    }
    for ($try = 0; $try < 2; $try++) {
        $fp = fopen($file . '.part', 'w');
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_FILE => $fp, CURLOPT_FOLLOWLOCATION => true, CURLOPT_MAXREDIRS => 5,
            CURLOPT_CONNECTTIMEOUT => 20, CURLOPT_TIMEOUT => 600, CURLOPT_USERAGENT => 'Mozilla/5.0 EPG-Builder']);
        curl_exec($ch);
        $ok = !curl_errno($ch) && (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE) === 200;
        curl_close($ch);
        fclose($fp);
        if ($ok && filesize($file . '.part') > 500) {
            return rename($file . '.part', $file);
        }
        @unlink($file . '.part');
        sleep(3);
    }
    // si falla, sirve la copia anterior (menos de 3 días)
    return is_file($file) && time() - filemtime($file) < 3 * 86400;
}

function xml_time(string $v): int {
    if (!preg_match('/^(\d{14})\s*([+-]\d{4})?/', trim($v), $m)) {
        return 0;
    }
    $d = DateTime::createFromFormat('YmdHis O', $m[1] . ' ' . ($m[2] ?? '+0000'));
    return $d ? $d->getTimestamp() : 0;
}

function open_xml(string $file): ?XMLReader {
    $r = new XMLReader();
    $gz = substr(file_get_contents($file, false, null, 0, 2), 0, 2) === "\x1f\x8b";
    return @$r->open(($gz ? 'compress.zlib://' : '') . $file, null, LIBXML_NONET | LIBXML_COMPACT | LIBXML_PARSEHUGE) ? $r : null;
}

// ---------------------------------------------------------------- 1. índice de las guías

$from = time() - (int) $cfg['past_hours'] * 3600;
$to = time() + (int) $cfg['days'] * 86400;
$channels = load_channels($cfg);
if (!$channels) {
    say('No hay canales (canales.json vacío).');
    exit(1);
}
say('Canales en tu lista: ' . count($channels));

$SC = [];        // canales de las guías
$byKey = [];     // clave compacta -> [ids]
$byTok = [];     // palabra -> [ids]
$byCall = [];    // siglas -> [ids]
$byXid = [];     // id xmltv en minúsculas -> [ids]
$byBag = [];     // palabras ordenadas -> [ids]
$files = [];
$log = [];
foreach ($cfg['sources'] as $si => $src) {
    $file = EPG_DIR . '/cache/src_' . $si . '.xml.gz';
    if (!download($src['url'], $file)) {
        $log[] = [$src['url'], 'no se pudo descargar', 0];
        say('✗ ' . $src['url']);
        continue;
    }
    $r = open_xml($file);
    if (!$r) {
        $log[] = [$src['url'], 'archivo no válido', 0];
        continue;
    }
    $files[$si] = $file;
    $local = [];
    $n = 0;
    while (@$r->read()) {
        if ($r->nodeType !== XMLReader::ELEMENT) {
            continue;
        }
        if ($r->name === 'channel') {
            $xid = (string) $r->getAttribute('id');
            $node = @simplexml_load_string($r->readOuterXml());
            if ($xid === '' || !$node) {
                continue;
            }
            $names = [];
            foreach ($node->{'display-name'} as $dn) {
                $names[] = trim((string) $dn);
            }
            $cc = $src['cc'] ?? null;
            if (preg_match('/\.([a-z]{2})(?:[a-z_]*\d*)?$/i', $xid, $m) && isset(PREFIX_CC[strtolower($m[1])])) {
                $cc = PREFIX_CC[strtolower($m[1])];
            }
            if (!ctype_digit($xid)) {
                $names[] = str_replace('.', ' ', preg_replace('/\.[a-z_]{2,}\d*$/i', '', $xid));
            }
            $id = count($SC);
            $SC[$id] = ['s' => $si, 'x' => $xid, 'cc' => $cc, 'n' => 0, 'label' => $names[0] ?? $xid,
                'icon' => isset($node->icon) ? (string) $node->icon['src'] : ''];
            $local[$xid] = $id;
            $byXid[strtolower($xid)][] = $id;
            $seen = [];
            foreach ($names as $name) {
                $name = preg_replace('/\.[a-z]{2}\d?$/i', '', $name);
                $c = canon($name);
                if ($c['cc'] !== null && ($cc === null || $cc === ($src['cc'] ?? null))) {
                    $SC[$id]['cc'] = $c['cc'];
                }
                if ($c['call']) {
                    $byCall[$c['call']][] = $id;
                }
                foreach ([$c['t'], $c['alt']] as $tk) {
                    $k = implode('', $tk);
                    if ($k === '' || isset($seen[$k])) {
                        continue;
                    }
                    $seen[$k] = true;
                    $byKey[$k][] = $id;
                    $byBag[bag($tk)][] = $id;
                    $SC[$id]['keys'][] = $tk;
                    foreach (array_unique($tk) as $t) {
                        $byTok[$t][] = $id;
                    }
                }
            }
        } elseif ($r->name === 'programme') {
            $id = $local[(string) $r->getAttribute('channel')] ?? null;
            if ($id !== null) {
                $s = xml_time((string) $r->getAttribute('start'));
                $e = xml_time((string) $r->getAttribute('stop'));
                if ($e > $from && $s < $to) {
                    $SC[$id]['n']++;
                    $n++;
                }
            }
            $r->next();
        }
    }
    $r->close();
    $log[] = [$src['url'], 'ok (' . round(filesize($file) / 1048576, 1) . ' MB)', $n];
    say('✓ ' . basename($src['url']) . ': ' . count($local) . " canales, $n programas");
}

// ---------------------------------------------------------------- 2. emparejar

$alias = require EPG_DIR . '/alias.php';
$manual = json_decode((string) @file_get_contents(EPG_DIR . '/manual.json'), true) ?: [];
$skip = (string) ($cfg['skip_groups'] ?? '/^$/');

function best(array $ids, ?string $cp, array $SC, int $min = 0): ?int {
    $best = null;
    $bs = -1;
    foreach (array_unique($ids) as $id) {
        if ($SC[$id]['n'] < 3) {
            continue;
        }
        $p = cc_pref($cp, $SC[$id]['cc']);
        if ($p < $min) {
            continue;
        }
        $score = $p * 1000 + min($SC[$id]['n'], 300) - $SC[$id]['s'];
        if ($score > $bs) {
            $bs = $score;
            $best = $id;
        }
    }
    return $best;
}

// Un canal de un país solo toma la guía de otro país si el canal es regional (sale en 3 países o más).
function min_pref(array $ids, ?string $cp, string $k, array $SC): int {
    if (strlen($k) < 2) {
        return 100; // "Canal 5": solo el del mismo país
    }
    if ($cp === null || !in_array($cp, LATAM, true)) {
        return 40;
    }
    $ccs = [];
    foreach ($ids as $id) {
        if ($SC[$id]['n'] >= 3 && $SC[$id]['cc'] !== null) {
            $ccs[$SC[$id]['cc']] = true;
        }
    }
    return count($ccs) >= 3 ? 40 : 50;
}

function fuzzy(array $c, ?string $cp, array $SC, array $byTok): ?int {
    $tok = $c['t'];
    if (!$tok || strlen($c['k']) < 4) {
        return null;
    }
    $rare = null;
    foreach ($tok as $t) {
        if (isset($byTok[$t]) && ($rare === null || count($byTok[$t]) < count($byTok[$rare]))) {
            $rare = $t;
        }
    }
    if ($rare === null || count($byTok[$rare]) > 3000) {
        return null;
    }
    preg_match_all('/\d+/', $c['k'], $dp);
    $okExtra = ['nacional', 'america', 'americas', 'central', 'satelital'];
    $cand = [];
    foreach (array_unique($byTok[$rare]) as $id) {
        if ($SC[$id]['n'] < 3 || cc_pref($cp, $SC[$id]['cc']) < 45) {
            continue;
        }
        foreach ($SC[$id]['keys'] ?? [] as $st) {
            $sk = implode('', $st);
            preg_match_all('/\d+/', $sk, $ds);
            if ($dp[0] !== $ds[0]) {
                continue; // "canal 13" nunca es "canal 11"
            }
            $q = 0;
            $extraS = array_diff($st, $tok);
            $extraP = array_diff($tok, $st);
            if (!$extraP && count($extraS) <= 1 && (!$extraS || in_array(reset($extraS), $okExtra, true))) {
                $q = 3;
            } elseif (!$extraS && count($extraP) === 1 && in_array(reset($extraP), $okExtra, true)) {
                $q = 3;
            } elseif (strlen($c['k']) >= 6 && strlen($sk) >= 6) {
                $d = levenshtein($c['k'], $sk);
                if ($d / max(strlen($c['k']), strlen($sk)) <= 0.12) {
                    $q = 2;
                }
            }
            if ($q) {
                $cand[] = [$q, $id];
            }
        }
    }
    if (!$cand) {
        return null;
    }
    $top = max(array_column($cand, 0));
    return best(array_column(array_filter($cand, fn($x) => $x[0] === $top), 1), $cp, $SC, 45);
}

function alias_call(array $ch, array $alias): ?string {
    $n = lower_plain($ch['name']);
    if (strpos($n, 'news') !== false || strpos($n, 'sports') !== false) {
        return null;
    }
    $n = trim(preg_replace('/\s+/', ' ', preg_replace('/\b(hd|fhd|sd|tv)\b|[^a-z ]/', ' ', $n)));
    foreach ($alias as $net => $cities) {
        if (strpos($n, $net . ' ') === 0) {
            $city = trim(substr($n, strlen($net)));
            return $cities[$city] ?? null;
        }
    }
    return null;
}

$pick = [];      // id canal de la guía -> [nombres de tu lista]
$stats = [];     // grupo -> [total, con guía]
$miss = [];
$pairs = [];
foreach ($channels as $ch) {
    $name = trim((string) ($ch['name'] ?? ''));
    $group = (string) ($ch['group'] ?? '');
    if ($name === '' || preg_match($skip, $group)) {
        continue;
    }
    $stats[$group] = $stats[$group] ?? [0, 0];
    $stats[$group][0]++;
    $c = canon($name);
    $cp = $c['cc'] ?? group_cc($group);
    $id = null;
    $how = '';
    if (array_key_exists($name, $manual)) {
        $id = $manual[$name] === null ? null : best($byXid[strtolower((string) $manual[$name])] ?? [], null, $SC);
        $how = 'manual';
    } else {
        $epg = strtolower(trim((string) ($ch['epg'] ?? '')));
        if ($epg !== '' && isset($byXid[$epg])) {
            [$id, $how] = [best($byXid[$epg], $cp, $SC), 'id'];
        }
        $call = $c['call'] ?? alias_call($ch, $alias);
        if ($id === null && $call && isset($byCall[$call])) {
            [$id, $how] = [best($byCall[$call], 'us', $SC), 'siglas'];
        }
        if ($id === null && $c['k'] !== '' && isset($byKey[$c['k']])) {
            [$id, $how] = [best($byKey[$c['k']], $cp, $SC, min_pref($byKey[$c['k']], $cp, $c['k'], $SC)), 'nombre'];
        }
        if ($id === null && count($c['t']) > 1 && isset($byBag[bag($c['t'])])) {
            $ids = $byBag[bag($c['t'])];
            [$id, $how] = [best($ids, $cp, $SC, min_pref($ids, $cp, $c['k'], $SC)), 'nombre'];
        }
        // "ESPN 1" = "ESPN"
        if ($id === null && count($c['t']) > 1 && end($c['t']) === '1') {
            $k1 = substr($c['k'], 0, -1);
            if (isset($byKey[$k1])) {
                [$id, $how] = [best($byKey[$k1], $cp, $SC, 45), 'nombre'];
            }
        }
        if ($id === null) {
            [$id, $how] = [fuzzy($c, $cp, $SC, $byTok), 'parecido'];
        }
    }
    if ($id === null) {
        $miss[$group][] = $name;
        continue;
    }
    $stats[$group][1]++;
    $pick[$id][] = $name;
    $pairs[] = [$group, $name, $SC[$id]['label'], basename($cfg['sources'][$SC[$id]['s']]['url']), $how];
}
$total = array_sum(array_column($stats, 0));
$hits = array_sum(array_column($stats, 1));
say("Canales con guía: $hits de $total (" . ($total ? round(100 * $hits / $total) : 0) . '%)');

// ---------------------------------------------------------------- 3. escribir la guía

$out = EPG_DIR . '/' . $cfg['output'];
$tmp = $out . '.tmp';
$gz = gzopen($tmp, 'wb6');
$esc = fn($s) => htmlspecialchars((string) $s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
gzwrite($gz, "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<tv generator-info-name=\"EPG a medida\" date=\"" . date('YmdHis O') . "\">\n");
$outId = [];
$bySrc = [];
foreach ($pick as $id => $names) {
    $xid = $SC[$id]['x'];
    $oid = isset($outId[$xid]) ? $xid . '-' . $SC[$id]['s'] : $xid;
    $outId[$oid] = true;
    $bySrc[$SC[$id]['s']][$xid] = $oid;
    $x = '  <channel id="' . $esc($oid) . '">';
    foreach (array_unique($names) as $n) {
        $x .= "\n    <display-name>" . $esc($n) . '</display-name>';
    }
    if ($SC[$id]['icon'] !== '') {
        $x .= "\n    <icon src=\"" . $esc($SC[$id]['icon']) . '"/>';
    }
    gzwrite($gz, $x . "\n  </channel>\n");
}
$programs = 0;
foreach ($bySrc as $si => $map) {
    $r = open_xml($files[$si]);
    if (!$r) {
        continue;
    }
    while (@$r->read()) {
        if ($r->nodeType !== XMLReader::ELEMENT || $r->name !== 'programme') {
            continue;
        }
        $oid = $map[(string) $r->getAttribute('channel')] ?? null;
        if ($oid !== null) {
            $s = xml_time((string) $r->getAttribute('start'));
            $e = xml_time((string) $r->getAttribute('stop'));
            if ($s && $e > $from && $s < $to) {
                $xml = preg_replace('/\schannel="[^"]*"/', ' channel="' . $esc($oid) . '"', $r->readOuterXml(), 1);
                gzwrite($gz, '  ' . $xml . "\n");
                $programs++;
            }
        }
        $r->next();
    }
    $r->close();
}
gzwrite($gz, "</tv>\n");
gzclose($gz);
if ($programs > 0) {
    rename($tmp, $out);
    say("Guía escrita: $out (" . round(filesize($out) / 1048576, 1) . " MB, $programs programas)");
} else {
    @unlink($tmp);
    say('No salió ningún programa: se conserva la guía anterior.');
}

// ---------------------------------------------------------------- 4. reporte

uasort($stats, fn($a, $b) => $b[0] <=> $a[0]);
$h = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$html = '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
    . '<title>Reporte EPG</title><style>body{font:14px system-ui,sans-serif;margin:16px;background:#111;color:#eee}'
    . 'table{border-collapse:collapse;width:100%;max-width:900px}td,th{border-bottom:1px solid #333;padding:4px 6px;text-align:left}'
    . 'details{margin:4px 0}.ok{color:#6c6}.no{color:#e77}small{color:#999}</style>'
    . '<h1>Reporte de la guía EPG</h1><p>Generada: ' . date('Y-m-d H:i') . " · <b>$hits de $total</b> canales con guía ("
    . ($total ? round(100 * $hits / $total) : 0) . "%) · $programs programas</p>"
    . '<h2>Por grupo</h2><table><tr><th>Grupo</th><th>Con guía</th><th>Total</th></tr>';
foreach ($stats as $g => [$t, $k]) {
    $html .= '<tr><td>' . $h($g) . '</td><td class="' . ($k ? 'ok' : 'no') . "\">$k</td><td>$t</td></tr>";
}
$html .= '</table><h2>Sin guía</h2><p><small>Para forzar uno, agrégalo en manual.json: "NOMBRE EXACTO": "id del canal en la guía".</small></p>';
foreach ($miss as $g => $list) {
    $html .= '<details><summary>' . $h($g) . ' (' . count($list) . ')</summary>' . $h(implode(' · ', $list)) . '</details>';
}
$html .= '<h2>Emparejados</h2><table><tr><th>Tu canal</th><th>Canal en la guía</th><th>Fuente</th><th>Cómo</th></tr>';
foreach ($pairs as [$g, $n, $l, $f, $how]) {
    $html .= '<tr><td>' . $h($n) . '</td><td>' . $h($l) . '</td><td><small>' . $h($f) . '</small></td><td><small>' . $h($how) . '</small></td></tr>';
}
$html .= '</table><h2>Fuentes</h2><table>';
foreach ($log as [$u, $st, $n]) {
    $html .= '<tr><td><small>' . $h($u) . '</small></td><td>' . $h($st) . "</td><td>$n</td></tr>";
}
file_put_contents(EPG_DIR . '/reporte.html', $html . '</table>', LOCK_EX);
say('Reporte: reporte.html');
flock($lock, LOCK_UN);
