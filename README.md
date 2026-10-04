# Archive.org Embed

Plugin para DokuWiki que inserta un reproductor de Internet Archive dentro de una
página con una sola línea de sintaxis:

```
{{archiveorg>identificador}}
```

## Por qué existe

DokuWiki no trae reproductor para archive.org, y sin él las páginas que usan la
sintaxis `{{archiveorg>...}}` no muestran nada: el parser no reconoce el
componente, descarta la llamada y deja el identificador pelado como texto. Este
plugin implementa esa sintaxis.

## Requisitos

- DokuWiki **2020-07-29** ("Hogfather") o superior (usa la API de extensiones)
- PHP 7.2 o superior
- Sin librerías externas ni dependencias de CDN

## Instalación

```bash
git clone https://github.com/morezane/dokuwiki-plugin-archiveorg.git \
  lib/plugins/archiveorg
```

Después, active el plugin en **Administrador → Ajustes → Extensiones**. No hay
nada que configurar para que funcione.

## Uso

### Sintaxis

```
{{archiveorg>identificador}}
{{archiveorg>identificador|ancho}}
{{archiveorg>identificador|ancho|align}}
```

- **identificador**: lo que aparece en `archive.org/details/<identificador>`
- **ancho**: en píxeles. Si se omite, el reproductor toma el ancho del contenedor
- **align**: `left`, `center` o `right`. Por defecto `center`

### Ejemplos

```
{{archiveorg>choneturaz-pfr.morezane-2023}}
{{archiveorg>Achurado?|480|left}}
{{archiveorg>mascaras_201704/mascaras2.mov}}
```

### Interrogante final

Un identificador puede acabar en `?`, y el plugin lo quita antes de construir la
URL. Archive.org no admite `?` dentro de un identificador, así que sin esta
tolerancia las páginas que lo usarían se quedarían sin reproductor.

### Archivo suelto dentro de un ítem

La forma `{{archiveorg>ítem/archivo}}` reproduce un archivo concreto en lugar del
ítem completo. El reproductor de archive.org elige entonces el formato que el
navegador puede reproducir, así que un `.mov` original suele servirse como mp4 u
ogv sin que haya que cambiar nada en la página.

## Configuración

Los valores por defecto están en `conf/default.php` y se guardan en `local.php`.

| Ajuste | Por defecto | Qué hace |
|---|---|---|
| `width` | `0` | Ancho por defecto en píxeles. `0` = ancho completo del contenedor. |
| `align` | `center` | Alineación por defecto: `left`, `center` o `right`. |
| `ratio` | `16:9` | Proporción del reproductor. |
| `lazy` | `1` | Cargar el iframe sólo cuando entra en pantalla. |
| `showlink` | `1` | Mostrar el enlace "Ver en archive.org" debajo del reproductor. |

## Detalles de implementación

- **El CSS se inyecta a mano.** DokuWiki no carga el `style.css` de los plugins
  por su cuenta. El `action.php` lo añade, pero sólo en las páginas cuyo texto
  contiene `{{archiveorg>`, porque el encabezado se envía antes de analizar la
  página y no hay forma de saber si el reproductor va a aparecer.
- **Los identificadores se validan, no se escapan.** Archive.org sólo usa
  letras, dígitos, punto, guion y guion bajo. Un identificador con espacios se
  muestra como error legible en vez de convertirse en un iframe roto en
  silencio.
- **Sin barras adicionales.** En la forma `ítem/archivo` se rechaza cualquier
  barra extra, así nadie puede salirse del directorio del ítem con `../`.
- **El contenedor es responsivo sin JavaScript.** El iframe va absoluto dentro
  de una caja con `aspect-ratio`, así que el alto se calcula solo al cambiar el
  ancho de la ventana. Hay un respaldo con `padding-bottom` para navegadores sin
  `aspect-ratio`.

## Problemas conocidos

- Si edita los archivos del plugin directamente dentro del contenedor, utilice la
  ruta persistente `/config/dokuwiki/lib/plugins/archiveorg`, no
  `/app/www/public/lib/plugins/`. En un contenedor recreado se pierde lo que se
  haya escrito en la segunda ruta.
- La sintaxis no tiene caché propia: cada render recalcula. Es intencional,
  DokuWiki cachea la página completa.

## Licencia

GPL 2.0 o posterior. Ver el archivo [LICENSE](LICENSE).