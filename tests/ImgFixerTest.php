<?php
/**
 * Copyright 2017 Bitban Technologies, S.L.
 * Todos los derechos reservados.
 */
namespace Bitban\Utils\ImgDimensions\Tests;

use Bitban\Utils\ImgDimensions\ImgFixer;
use PHPUnit\Framework\TestCase;

class ImgFixerTest extends TestCase
{
    public function testImgSrcList()
    {
        $fixer = new ImgFixer();
        $html = file_get_contents(__DIR__ . '/resources/images.html');
        $imgSrcList = $fixer->getImgSrcListFromHtml($html);
        $this->assertCount(3, $imgSrcList);
    }

    public function testFetchDimensions()
    {
        $fixer = new ImgFixer();
        $dimensions = $fixer->fetchDimensions([
            "http://www.risasinmas.com/wp-content/uploads/2013/09/perrete-disfrazado.jpg?foo",
            "http://images.eldiario.es/clm/Foto-Smart-Dog_EDIIMA20160606_0306_18.jpg",
            "http://2.bp.blogspot.com/-HPvC7gFayFI/UJFHE-W0O0I/AAAAAAAAAGo/7TON-Fp6X00/s1600/IMG_3284.JPG"
        ]);
        $this->assertCount(3, $dimensions);
        $this->assertSame(600, $dimensions["http://www.risasinmas.com/wp-content/uploads/2013/09/perrete-disfrazado.jpg?foo"][0]);
        $this->assertSame(829, $dimensions["http://www.risasinmas.com/wp-content/uploads/2013/09/perrete-disfrazado.jpg?foo"][1]);
        $this->assertSame(643, $dimensions["http://images.eldiario.es/clm/Foto-Smart-Dog_EDIIMA20160606_0306_18.jpg"][0]);
        $this->assertSame(362, $dimensions["http://images.eldiario.es/clm/Foto-Smart-Dog_EDIIMA20160606_0306_18.jpg"][1]);
        $this->assertSame(1600, $dimensions["http://2.bp.blogspot.com/-HPvC7gFayFI/UJFHE-W0O0I/AAAAAAAAAGo/7TON-Fp6X00/s1600/IMG_3284.JPG"][0]);
        $this->assertSame(1068, $dimensions["http://2.bp.blogspot.com/-HPvC7gFayFI/UJFHE-W0O0I/AAAAAAAAAGo/7TON-Fp6X00/s1600/IMG_3284.JPG"][1]);
    }

    public function testFixDimensions()
    {
        $fixer = new ImgFixer();
        $html = file_get_contents(__DIR__ . '/resources/images.html');
        $dimensions = [
            "http://www.risasinmas.com/wp-content/uploads/2013/09/perrete-disfrazado.jpg?foo" => [1920, 1080],
            "http://images.eldiario.es/clm/Foto-Smart-Dog_EDIIMA20160606_0306_18.jpg" => [800, 600],
            "http://2.bp.blogspot.com/-HPvC7gFayFI/UJFHE-W0O0I/AAAAAAAAAGo/7TON-Fp6X00/s1600/IMG_3284.JPG" => [640, 480]
        ];
        $fixedHtml = $fixer->fixDimensions($html, $dimensions);

        $dom = new \DOMDocument();
        @$dom->loadHtml($fixedHtml);
        $tags = $dom->getElementsByTagName("img");
        foreach ($tags as $tag) {
            if (array_key_exists($tag->getAttribute("src"), $dimensions)) {
                $this->assertSame($dimensions[$tag->getAttribute("src")][0], intval($tag->getAttribute("data-src-width")));
                $this->assertSame($dimensions[$tag->getAttribute("src")][1], intval($tag->getAttribute("data-src-height")));
            }
        }
    }
}
