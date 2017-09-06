<?php
/**
 * Copyright 2017 Bitban Technologies, S.L.
 * Todos los derechos reservados.
 */
namespace Bitban\Utils\ImgDimensions;

use DOMDocument;
use GuzzleHttp\Client;
use function GuzzleHttp\Promise\unwrap;

class ImgFixer
{
    /** @var string */
    private $widthCustomAttribute;
    /** @var string */
    private $heightCustomAttribute;

    /**
     * @param string $widthCustomAttribute
     * @param string $heightCustomAttribute
     */
    public function __construct(
        string $widthCustomAttribute = "data-src-width",
        string $heightCustomAttribute = "data-src-height"
    ) {
        $this->widthCustomAttribute = $widthCustomAttribute;
        $this->heightCustomAttribute = $heightCustomAttribute;
    }

    /**
     * A partir de un HTML, obtiene la lista de URLs de imágenes cuyas medidas se desconocen
     *
     * @param string $html
     * @return string[]
     */
    public function getImgSrcListFromHtml($html): array
    {
        $doc = new DOMDocument();
        @$doc->loadHTML($html);

        $tags = $doc->getElementsByTagName("img");

        $missing = [];
        foreach ($tags as $tag) {
            if (is_numeric($tag->getAttribute("width"))
                && is_numeric($tag->getAttribute("height")))
            {
                continue;
            }

            if (is_numeric($tag->getAttribute($this->widthCustomAttribute))
                && is_numeric($tag->getAttribute($this->heightCustomAttribute)))
            {
                continue;
            }

            // No tenemos las medidas
            $missing[] = $tag->getAttribute("src");
        }
        return $missing;
    }

    /**
     * A partir de una lista de URLs de imágenes, las descarga y obtiene sus medidas
     *
     * @param string[] $urls
     * @return int[][] Array asociativo. Para cada URL devueve un array con width y height
     */
    public function fetchDimensions(array $urls): array
    {
        $client = new Client();
        $promises = [];
        $tempFiles = [];
        foreach ($urls as $src) {
            $tmpFile = tempnam(sys_get_temp_dir(), __CLASS__);
            $promises[$src] = $client->getAsync($src, ["sink" => $tmpFile]);
            $tempFiles[$src] = $tmpFile;
        }

        $results = unwrap($promises);

        $dimensions = [];
        foreach ($results as $src => $result) {
            $size = getimagesize($tempFiles[$src]);
            $dimensions[$src] = [$size[0], $size[1]];
        }

        return $dimensions;
    }

    /**
     * Modifica un HTML añadiendo/modificando las medidas de las imágenes cuyas URL se indican
     *
     * @param string $html
     * @param int[] $dimensions Array asociativo con las medidas (width, height) asociadas a cada URL
     * @return string
     */
    public function fixDimensions(string $html, array $dimensions): string
    {
        foreach ($dimensions as $src => $dimension) {
            $width = $dimensions[$src][0];
            $height = $dimensions[$src][1];
            $html = preg_replace("#(src=['\"]" . $src . "['\"])#", "$1 data-src-width=\"$width\" data-src-height=\"$height\"", $html);
        }

        return $html;
    }

    /**
     * Modifica un HTML añadiendo las medidas de las imágenes que no las tienen
     *
     * @param string $html
     * @return string
     */
    public function fix($html)
    {
        $missing = $this->getImgSrcListFromHtml($html);
        $dimensions = $this->fetchDimensions($missing);
        return $this->fixDimensions($html, $dimensions);
    }
}
