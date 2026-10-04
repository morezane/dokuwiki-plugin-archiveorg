<?php

use dokuwiki\Extension\SyntaxPlugin;

/**
 * Plugin Archive.org Embed - componente de sintaxis
 *
 * Escribe un reproductor de Internet Archive con:
 *
 *   {{archiveorg>identificador}}
 *   {{archiveorg>identificador|640}}
 *   {{archiveorg>identificador|640|center}}
 *
 * El identificador es el que aparece en la URL del ítem, por ejemplo
 * choneturaz-pfr.morezane-2023 en archive.org/details/choneturaz-pfr.morezane-2023
 *
 * @license GPL 2 (http://www.gnu.org/licenses/gp.html)
 */
class syntax_plugin_archiveorg_archiveorg extends SyntaxPlugin
{
    /**
     * Caracteres admitidos en un identificador de archive.org o en un nombre
     * de archivo dentro del ítem.
     *
     * Archive.org sólo usa letras, dígitos, punto, guion y guion bajo. Al
     * validar en vez de escapar, un identificador raro se muestra como error
     * legible en lugar de convertirse en un iframe silenciosamente roto.
     */
    protected const ID_PATTERN = '/^[A-Za-z0-9][A-Za-z0-9._-]*$/';

    /** @inheritdoc */
    public function getType()
    {
        return 'substition';
    }

    /** @inheritdoc */
    public function getPType()
    {
        return 'normal';
    }

    /** @inheritdoc */
    public function getSort()
    {
        return 300;
    }

    /** @inheritdoc */
    public function connectTo($mode)
    {
        // El nombre del modo lleva plugin y componente, que es lo que el
        // parser le devuelve a Handler::plugin() para volver a cargar esta clase
        $this->Lexer->addSpecialPattern(
            '\{\{archiveorg>[^}]+\}\}',
            $mode,
            'plugin_archiveorg_archiveorg'
        );
    }

    /**
     * @inheritdoc
     * @return array
     */
    public function handle($match, $state, $pos, Doku_Handler $handler)
    {
        // "{{archiveorg>x}}" -> "x": quita el prefijo y la llave de cierre
        $inner = substr($match, strlen('{{archiveorg>'), -2);
        $parts = preg_split('/\|/', $inner, -1, PREG_SPLIT_NO_EMPTY);
        $parts = array_map('trim', $parts);

        $raw = array_shift($parts);

        /*
         * El wiki trae muchas entradas con un interrogante al final
         * ("Achurado?"), que el autor usó como marca de "id aproximado".
         * Archive.org no admite '?' en un identificador, así que se quita en
         * lugar de rechazar la página entera.
         */
        $raw = rtrim($raw, '?');
        $raw = trim($raw);

        // "item/archivo" apunta a un archivo suelto dentro de un ítem
        $segments = explode('/', $raw);
        $item = $segments[0];
        $file = $segments[1] ?? null;

        if (!$this->isValidSegment($item) || ($file !== null && !$this->isValidSegment($file))) {
            $html = '<div class="archiveorg-error">' .
                hsc($this->getLang('badid')) . ' <code>' . hsc($raw) . '</code></div>';
            return [$state, $html];
        }

        $width = $this->resolveWidth($parts[0] ?? null);
        $align = $this->resolveAlign($parts[1] ?? null);

        return [$state, $this->renderWidget($item, $file, $width, $align)];
    }

    /**
     * Valida un segmento: un identificador de archive.org o un nombre de
     * archivo. Se rechaza cualquier barra adicional para que nadie pueda
     * escapar del directorio del ítem con "../".
     *
     * @param string $segment
     * @return bool
     */
    protected function isValidSegment($segment)
    {
        return (bool)preg_match(self::ID_PATTERN, $segment);
    }

    /**
     * Ancho en píxeles, o null para tomar el del contenedor.
     *
     * @param string|null $value
     * @return int|null
     */
    protected function resolveWidth($value)
    {
        if ($value === null || $value === '') {
            $conf = (int)$this->getConf('width');
            return $conf > 0 ? $conf : null;
        }

        $px = (int)$value;
        if ($px <= 0) return null;

        // Sin tope alto: un ancho del 100% del contenedor tiene que poder
        // escribirse como "100%", y 20000 es un tope holgado que no estorba.
        return min($px, 20000);
    }

    /**
     * Alineación horizontal válida.
     *
     * @param string|null $value
     * @return string
     */
    protected function resolveAlign($value)
    {
        $allowed = ['left', 'center', 'right'];
        if ($value !== null && in_array($value, $allowed, true)) return $value;

        $conf = (string)$this->getConf('align');
        return in_array($conf, $allowed, true) ? $conf : 'center';
    }

    /**
     * Construye el marcado del reproductor.
     *
     * @param string $item
     * @param string|null $file
     * @param int|null $width
     * @param string $align
     * @return string
     */
    protected function renderWidget($item, $file, $width, $align)
    {
        // El embed de archive.org acepta tanto un ítem como un archivo suelto
        // dentro del ítem, y el reproductor elige el formato que el navegador
        // aguanta (normalmente mp4 u ogv en vez del original).
        $embed = 'https://archive.org/embed/' . rawurlencode($item);
        if ($file !== null) $embed .= '/' . rawurlencode($file);

        $page = 'https://archive.org/details/' . rawurlencode($item);
        $label = $item . ($file !== null ? '/' . $file : '') . ' — ' . $this->getLang('title');

        $lazy = (int)$this->getConf('lazy') ? ' loading="lazy"' : '';

        $html = '<div class="archiveorg-wrapper archiveorg-align-' . $align . '"' .
            ($width ? ' style="max-width:' . $width . 'px"' : '') . '>';
        $html .= '<div class="archiveorg-frame" style="--archiveorg-ratio:' .
            hsc((string)$this->getConf('ratio')) . '">';
        $html .= '<iframe src="' . hsc($embed) . '"' .
            ' title="' . hsc($label) . '"' .
            ' allowfullscreen="allowfullscreen"' .
            ' frameborder="0"' .
            $lazy .
            ' allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; fullscreen"' .
            ' referrerpolicy="no-referrer-when-downgrade"></iframe>';
        $html .= '</div>';

        if ((int)$this->getConf('showlink')) {
            $html .= '<p class="archiveorg-link">' .
                '<a href="' . hsc($page) . '" class="urlextern" rel="ugc nofollow">' .
                hsc($this->getLang('open')) . '</a></p>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * @inheritdoc
     * @return bool
     */
    public function render($format, Doku_Renderer $renderer, $data)
    {
        // El HTML ya viene construido y escapado: no tiene sentido que otro
        // renderizador intente interpretarlo
        if ($format !== 'xhtml') return false;

        $renderer->doc .= $data[1];
        return true;
    }
}