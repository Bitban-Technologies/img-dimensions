# Img Dimensions

[![PHP 7.1](https://img.shields.io/badge/php-7.1-blue.svg)](http://php.net/manual/es/migration71.php) 
[![Latest Stable Version](https://poser.pugx.org/bitban/img-dimensions/v/stable)](https://packagist.org/packages/bitban/img-dimensions)
[![License](https://poser.pugx.org/bitban/img-dimensions/license)](https://packagist.org/packages/bitban/img-dimensions)
[![Build Status](https://travis-ci.org/bitban/img-dimensions.svg?branch=master)](https://travis-ci.org/bitban/img-dimensions)
[![Coverage Status](https://coveralls.io/repos/github/bitban/img-dimensions/badge.svg?branch=master)](https://coveralls.io/github/bitban/img-dimensions?branch=master)

Librería que permite modificar un HTML añadiendo las medidas de las imágenes que no las tienen.

## Uso

```php
<?php
$html = "...";
$fixer = new \Bitban\Utils\ImgDimensions\ImgFixer();
$fixedHtml = $fixer->fix($html);
```

Las dimensiones se añaden en dos atributos arbitrarios que se pueden indicar en el constructor de la clase `ImgFixer`. Los valores por defecto son `data-src-width` y `data-src-height` para ancho y alto respectivamente.

El proceso consta de tres pasos:

* A partir de un HTML, obtener el listado de imágenes cuyas dimensiones se desconocen.
* Calcular las dimensiones de un listado de URLs de imágenes.
* A partir de un HTML y una lista de dimensiones de imágenes, añadir al HTML las dimensiones dadas.

Se han hecho públicos todos los métodos, para que cualquiera de los tres pasos pueda ser invocado de forma independiente por si resulta de utilidad.

La descarga de imágenes para calcular sus dimensiones se realiza con el [cliente HTTP Guzzle](http://docs.guzzlephp.org/en/stable/), haciendo descarga paralela.
