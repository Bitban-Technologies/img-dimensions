<?php
/**
 * Copyright 2017 Bitban Technologies, S.L.
 * Todos los derechos reservados.
 */
namespace Bitban\Utils\ImgDimensions;

use DOMDocument;
use GuzzleHttp\Client;
use function GuzzleHttp\Promise\unwrap;
use GuzzleHttp\Psr7\Response;

class ImgFixer
{
    /** @var string */
    private $widthCustomAttribute;
    /** @var string */
    private $heightCustomAttribute;
    /** @var string */
    private $baseUrl;

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
     * @param string $baseUrl
     * @return ImgFixer
     */
    public function setBaseUrl(string $baseUrl): self
    {
        $this->baseUrl = $baseUrl;
        return $this;
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
     * @throws \Exception Si alguna de las URLs es relativa y no se ha indicado una URL base llamando a setBaseUrl()
     */
    public function fetchDimensions(array $urls): array
    {
        $client = new Client([
            "base_uri" => $this->baseUrl,
            "http_errors" => false]
        );
        $promises = []; // URLs
        $inliners = []; // Imágenes inline

        $tempFiles = [];
        foreach ($urls as $url) {

            if (0 === strpos($url, "data:")) {
                $inliners[] = $url;
                continue;
            }

            $tmpFile = tempnam(sys_get_temp_dir(), __CLASS__);
            $promises[$url] = $client->getAsync($url, ["sink" => $tmpFile]);
            $tempFiles[$url] = $tmpFile;
        }

        $dimensions = [];

        // Procesamos las imágenes que nos hemos descargado
        $results = unwrap($promises);
        foreach ($results as $src => $result) {
            /** @var Response $result */
            if (200 !== $result->getStatusCode()) {
                // TODO ¿Apuntamos la URL errónea en algún sitio?
                continue;
            }
            $size = getimagesize($tempFiles[$src]);
            $dimensions[$src] = [$size[0], $size[1]];
        }

        // Añadimos las imágenes inline
        foreach ($inliners as $data) {
            $size = getimagesize($data);
            $dimensions[$data] = [$size[0], $size[1]];
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
            $html = preg_replace("#(src=['\"]" . preg_quote($src) . "['\"])#", "$1 data-src-width=\"$width\" data-src-height=\"$height\"", $html);
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
