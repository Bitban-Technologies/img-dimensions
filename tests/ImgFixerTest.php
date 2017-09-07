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
    private function getDimensions()
    {
        return [
            "http://www.risasinmas.com/wp-content/uploads/2013/09/perrete-disfrazado.jpg?foo" => [600, 829],
            "http://images.eldiario.es/clm/Foto-Smart-Dog_EDIIMA20160606_0306_18.jpg" => [643, 362],
            "http://2.bp.blogspot.com/-HPvC7gFayFI/UJFHE-W0O0I/AAAAAAAAAGo/7TON-Fp6X00/s1600/IMG_3284.JPG" => [1600, 1068],
            "/originals/1a/c4/98/1ac498f952cbf4107fa460660cff0630.jpg" => [500, 750],
            "data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" => [1, 1],
            "1ac498f952cbf4107fa460660cff0630.jpg" => [500, 750]
        ];
    }

    private function checkFixedHtml(string $fixedHtml, array $dimensions)
    {
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

    public function testImgSrcList()
    {
        $fixer = new ImgFixer();
        $html = file_get_contents(__DIR__ . '/resources/images.html');
        $imgSrcList = $fixer->getImgSrcListFromHtml($html);
        $this->assertCount(6, $imgSrcList);
        $this->assertSame(array_keys($this->getDimensions()), $imgSrcList);
    }

    public function testFetchDimensions()
    {
        $fixer = new ImgFixer();
        $fixer->setBaseUrl("https://i.pinimg.com/originals/1a/c4/98/foo.html");
        $urls = [
            "http://www.risasinmas.com/wp-content/uploads/2013/09/perrete-disfrazado.jpg?foo",
            "http://images.eldiario.es/clm/Foto-Smart-Dog_EDIIMA20160606_0306_18.jpg",
            "http://2.bp.blogspot.com/-HPvC7gFayFI/UJFHE-W0O0I/AAAAAAAAAGo/7TON-Fp6X00/s1600/IMG_3284.JPG",
            "/originals/1a/c4/98/1ac498f952cbf4107fa460660cff0630.jpg",
            "data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7",
            "1ac498f952cbf4107fa460660cff0630.jpg",
        ];

        $dimensions = $fixer->fetchDimensions($urls);
        $expectedDimensions = $this->getDimensions();

        $this->assertCount(6, $dimensions);

        foreach ($urls as $url) {
            $this->assertSame($expectedDimensions[$url][0], $dimensions[$url][0]);
            $this->assertSame($expectedDimensions[$url][1], $dimensions[$url][1]);
        }
    }

    public function testFetchDimensionsError()
    {
        $this->expectException(\Exception::class);
        $fixer = new ImgFixer();
        $dimensions = $fixer->fetchDimensions([
            "/originals/1a/c4/98/1ac498f952cbf4107fa460660cff0630.jpg"
        ]);
    }

    public function testFixDimensions()
    {
        $fixer = new ImgFixer();
        $html = file_get_contents(__DIR__ . '/resources/images.html');
        $fixedHtml = $fixer->fixDimensions($html, $this->getDimensions());
        $this->checkFixedHtml($fixedHtml, $this->getDimensions());
    }

    public function testCompleteFlow()
    {
        $fixer = new ImgFixer();
        $fixer->setBaseUrl("https://i.pinimg.com/originals/1a/c4/98/foo.html");
        $html = file_get_contents(__DIR__ . '/resources/images.html');
        $fixedHtml = $fixer->fix($html);
        $this->checkFixedHtml($fixedHtml, $this->getDimensions());
    }

    public function testInvalidUrl()
    {
        $fixer = new ImgFixer();
        $dimensions = $fixer->fetchDimensions([
            "http://2.bp.blogspot.com/-HPvC7gFayFI/UJFHE-W0O0I/AAAAAAAAAGo/7TON-Fp6X00/s1600/IMG_3284.JPG",
            "http://example.com/foo/bar.jpeg"
        ]);

        $this->assertCount(1, $dimensions);
    }
}
