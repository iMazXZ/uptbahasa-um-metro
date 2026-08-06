<?php
/**
 * HTML Purifier configuration for Laravel (mews/purifier).
 * - Profile "post" untuk konten artikel/tabel/gambar/iframe aman.
 * - Doctype diset ke XHTML 1.0 Transitional (HTML5 tidak didukung oleh core).
 * - Elemen HTML5 tetap diaktifkan via custom_definition.
 */

return [
    'encoding'         => 'UTF-8',
    'finalize'         => true,
    'ignoreNonStrings' => false,

    // Pastikan folder ini writable: storage/app/purifier
    'cachePath'     => storage_path('app/purifier'),
    'cacheFileMode' => 0755,

    'settings' => [

        // Biarkan default seperti bawaan paket (jangan dihapus)
        'default' => [
            'HTML.Doctype'             => 'HTML 4.01 Transitional',
            'HTML.Allowed'             => 'div,b,strong,i,em,u,a[href|title],ul,ol,li,p[style],br,span[style],img[width|height|alt|src]',
            'CSS.AllowedProperties'    => 'font,font-size,font-weight,font-style,font-family,text-decoration,padding-left,color,background-color,text-align',
            'AutoFormat.AutoParagraph' => true,
            'AutoFormat.RemoveEmpty'   => true,
        ],

        /**
         * Profil utama untuk render body postingan
         * Panggil dengan: clean($html, 'post')
         */
        'post' => [
            // Gunakan doctype yang didukung purifier
            'HTML.Doctype'             => 'XHTML 1.0 Transitional',
            'Core.EscapeInvalidTags'   => true,

            // Skema URL aman
            'URI.AllowedSchemes'       => [
                'http'   => true,
                'https'  => true,
                'mailto' => true,
            ],

            // Iframe aman (YouTube/Vimeo)
            'HTML.SafeIframe'          => true,
            'URI.SafeIframeRegexp'     => '%^(https?:)?//(www\.youtube\.com/embed/|player\.vimeo\.com/video/)%',
            'Attr.AllowedFrameTargets' => ['_blank'],
            'Attr.AllowedRel'          => ['nofollow', 'noopener', 'noreferrer'],

            // Tag yang diizinkan
            'HTML.Allowed' => implode(',', [
                // Headings & teks
                'h1','h2','h3','h4','h5','h6',
                'p[style]','br','hr','blockquote',
                'b','strong','i','em','u','s','mark','sub','sup','code','kbd','pre',

                // Link
                'a[href|title|target|rel]',

                // List
                'ul','ol','li','dl','dt','dd',

                // Gambar/figure
                'img[src|alt|title|width|height|loading|decoding]',
                'figure','figcaption',

                // Tabel
                'table[summary]','thead','tbody','tfoot','tr',
                'th[scope|colspan|rowspan|abbr]','td[colspan|rowspan]',
                'caption','colgroup','col[span]',

                // Kontainer
                'span[style]','div',

                // Iframe (dibatasi oleh SafeIframeRegexp)
                'iframe[width|height|src|frameborder|allowfullscreen]',
            ]),

            // CSS yang diizinkan
            'CSS.AllowedProperties' => implode(',', [
                'text-align','text-decoration','font-style','font-weight',
                'color','background-color',
                'border','border-width','border-style','border-color',
                'border-collapse','border-spacing',
                'caption-side','empty-cells',
                'vertical-align',
                'padding','padding-left','padding-right','padding-top','padding-bottom',
                'margin','margin-left','margin-right','margin-top','margin-bottom',
                'list-style-type',
                'white-space',
            ]),

            // Auto-format berguna
            'AutoFormat.AutoParagraph'              => true,
            'AutoFormat.RemoveEmpty'                => true,
            'AutoFormat.RemoveEmpty.RemoveNbsp'     => true,
            'AutoFormat.Linkify'                    => true,
            'AutoFormat.DisplayLinkURI'             => false,

            // Alt default untuk img
            'Attr.DefaultImageAlt'                  => 'Image',
        ],

        // Profil contoh bawaan (opsional)
        'test' => [
            'Attr.EnableID' => true,
        ],

        'youtube' => [
            'HTML.SafeIframe'      => true,
            'URI.SafeIframeRegexp' => "%^(http://|https://|//)(www.youtube.com/embed/|player.vimeo.com/video/)%"
        ],

        /**
         * Definisi HTML5 agar dikenali purifier (meski doctype bukan HTML5)
         */
        'custom_definition' => [
            'id'    => 'html5-definitions',
            'rev'   => 1,
            'debug' => false,

            'elements' => [
                // Sectioning
                ['section', 'Block', 'Flow', 'Common'],
                ['nav',     'Block', 'Flow', 'Common'],
                ['article', 'Block', 'Flow', 'Common'],
                ['aside',   'Block', 'Flow', 'Common'],
                ['header',  'Block', 'Flow', 'Common'],
                ['footer',  'Block', 'Flow', 'Common'],

                // Grouping
                ['address',    'Block', 'Flow', 'Common'],
                ['hgroup',     'Block', 'Required: h1 | h2 | h3 | h4 | h5 | h6', 'Common'],
                ['figure',     'Block', 'Optional: (figcaption, Flow) | (Flow, figcaption) | Flow', 'Common'],
                ['figcaption', 'Inline','Flow', 'Common'],

                // Media
                ['video', 'Block', 'Optional: (source, Flow) | (Flow, source) | Flow', 'Common', [
                    'src'      => 'URI',
                    'type'     => 'Text',
                    'width'    => 'Length',
                    'height'   => 'Length',
                    'poster'   => 'URI',
                    'preload'  => 'Enum#auto,metadata,none',
                    'controls' => 'Bool',
                ]],
                ['source', 'Block', 'Flow', 'Common', [
                    'src'  => 'URI',
                    'type' => 'Text',
                ]],

                // Text-level
                ['s',    'Inline', 'Inline', 'Common'],
                ['var',  'Inline', 'Inline', 'Common'],
                ['sub',  'Inline', 'Inline', 'Common'],
                ['sup',  'Inline', 'Inline', 'Common'],
                ['mark', 'Inline', 'Inline', 'Common'],
                ['wbr',  'Inline', 'Empty',  'Core'],

                // Edit
                ['ins', 'Block', 'Flow', 'Common', ['cite' => 'URI', 'datetime' => 'CDATA']],
                ['del', 'Block', 'Flow', 'Common', ['cite' => 'URI', 'datetime' => 'CDATA']],
            ],

            'attributes' => [
                ['iframe', 'allowfullscreen', 'Bool'],
                ['table',  'height',          'Text'],
                ['td',     'border',          'Text'],
                ['th',     'border',          'Text'],
                ['tr',     'width',           'Text'],
                ['tr',     'height',          'Text'],
                ['tr',     'border',          'Text'],

                // Tambahan agar img loading/decoding tidak error
                ['img', 'loading',  'Enum#lazy,eager'],
                ['img', 'decoding', 'Enum#sync,async,auto'],
            ],

        ],

        'custom_attributes' => [
            ['a', 'target', 'Enum#_blank,_self,_target,_top'],
        ],

        'custom_elements' => [
            ['u', 'Inline', 'Inline', 'Common'],
        ],

        'summary' => [
            'HTML.Allowed' => 'p,br,strong,em,a[href|title|target],ul,ol,li,h2,h3,blockquote,code,img[src|alt|title|width|height]',
        ],
    ],
];
