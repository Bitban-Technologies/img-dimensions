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
        $this->assertCount(4, $imgSrcList);
    }

    public function testFetchDimensions()
    {
        $fixer = new ImgFixer();
        $fixer->setBaseUrl("https://i.pinimg.com");
        $dimensions = $fixer->fetchDimensions([
            "http://www.risasinmas.com/wp-content/uploads/2013/09/perrete-disfrazado.jpg?foo",
            "http://images.eldiario.es/clm/Foto-Smart-Dog_EDIIMA20160606_0306_18.jpg",
            "http://2.bp.blogspot.com/-HPvC7gFayFI/UJFHE-W0O0I/AAAAAAAAAGo/7TON-Fp6X00/s1600/IMG_3284.JPG",
            "/originals/1a/c4/98/1ac498f952cbf4107fa460660cff0630.jpg"
        ]);
        $this->assertCount(4, $dimensions);
        $this->assertSame(600, $dimensions["http://www.risasinmas.com/wp-content/uploads/2013/09/perrete-disfrazado.jpg?foo"][0]);
        $this->assertSame(829, $dimensions["http://www.risasinmas.com/wp-content/uploads/2013/09/perrete-disfrazado.jpg?foo"][1]);
        $this->assertSame(643, $dimensions["http://images.eldiario.es/clm/Foto-Smart-Dog_EDIIMA20160606_0306_18.jpg"][0]);
        $this->assertSame(362, $dimensions["http://images.eldiario.es/clm/Foto-Smart-Dog_EDIIMA20160606_0306_18.jpg"][1]);
        $this->assertSame(1600, $dimensions["http://2.bp.blogspot.com/-HPvC7gFayFI/UJFHE-W0O0I/AAAAAAAAAGo/7TON-Fp6X00/s1600/IMG_3284.JPG"][0]);
        $this->assertSame(1068, $dimensions["http://2.bp.blogspot.com/-HPvC7gFayFI/UJFHE-W0O0I/AAAAAAAAAGo/7TON-Fp6X00/s1600/IMG_3284.JPG"][1]);
        $this->assertSame(500, $dimensions["/originals/1a/c4/98/1ac498f952cbf4107fa460660cff0630.jpg"][0]);
        $this->assertSame(750, $dimensions["/originals/1a/c4/98/1ac498f952cbf4107fa460660cff0630.jpg"][1]);
    }

    public function testFetchDimensionsError()
    {
        $this->expectException(\Exception::class);
        $fixer = new ImgFixer();
        $dimensions = $fixer->fetchDimensions([
            "/originals/1a/c4/98/1ac498f952cbf4107fa460660cff0630.jpg"
        ]);
        $this->assertSame(500, $dimensions["/originals/1a/c4/98/1ac498f952cbf4107fa460660cff0630.jpg"][0]);
        $this->assertSame(750, $dimensions["/originals/1a/c4/98/1ac498f952cbf4107fa460660cff0630.jpg"][1]);
    }

    public function testFixDimensions()
    {
        $fixer = new ImgFixer();
        $html = file_get_contents(__DIR__ . '/resources/images.html');
        $dimensions = [
            "http://www.risasinmas.com/wp-content/uploads/2013/09/perrete-disfrazado.jpg?foo" => [1920, 1080],
            "http://images.eldiario.es/clm/Foto-Smart-Dog_EDIIMA20160606_0306_18.jpg" => [800, 600],
            "http://2.bp.blogspot.com/-HPvC7gFayFI/UJFHE-W0O0I/AAAAAAAAAGo/7TON-Fp6X00/s1600/IMG_3284.JPG" => [640, 480],
            "https://i.pinimg.com/originals/1a/c4/98/1ac498f952cbf4107fa460660cff0630.jpg" => [0, 0]
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
