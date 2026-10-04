<?php

if (!defined('DOKU_INC')) die();

use dokuwiki\Extension\ActionPlugin;
use dokuwiki\Extension\Event;
use dokuwiki\Extension\EventHandler;

/**
 * Plugin Archive.org Embed - acción
 *
 * DokuWiki no carga el style.css de los plugins por su cuenta, así que hay que
 * inyectarlo a mano. Y sólo se inyecta en las páginas que de verdad llevan un
 * reproductor: el encabezado se envía antes de analizar el texto de la página,
 * así que lo único disponible es el texto wiki en crudo.
 *
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
 */
class action_plugin_archiveorg extends ActionPlugin
{
    /** Texto sintáctico que dispara la carga de los estilos */
    protected const SYNTAX = '{{archiveorg>';

    /** @inheritdoc */
    public function register(EventHandler $controller)
    {
        $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'handleAssets');
    }

    /**
     * Añade la hoja de estilos cuando la página lleva un reproductor.
     *
     * @param Event $event
     * @return void
     */
    public function handleAssets(Event $event)
    {
        global $ID;

        if (!is_array($event->data)) return;
        if (!$this->needsStyles($ID)) return;

        $seed = @filemtime(DOKU_PLUGIN . 'archiveorg/style.css');
        if (!$seed) $seed = time();

        $event->data['link'][] = [
            'rel' => 'stylesheet',
            'href' => DOKU_BASE . 'lib/plugins/archiveorg/style.css?' . $seed,
        ];
    }

    /**
     * ¿Esta página lleva algún reproductor?
     *
     * @param string $id
     * @return bool
     */
    protected function needsStyles($id)
    {
        if (!$id) return false;

        $text = rawWiki($id);
        if ($text === false || $text === '') return false;

        return strpos($text, self::SYNTAX) !== false;
    }
}