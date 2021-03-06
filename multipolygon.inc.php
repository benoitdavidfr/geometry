<?php
/*PhpDoc:
name:  multipolygon.inc.php
title: multipolygon.inc.php - définition d'une collection de polygones
includes: [ geometry.inc.php ]
classes:
doc: |
journal: |
  14/10/2018:
    ajout chgCoordSys()
  8/8/2018:
    suppression filter() déjà définie sur MultiGeom
  22/10/2017:
    création
*/
require_once __DIR__.'/multigeom.inc.php';

/*PhpDoc: classes
name:  MultiPolygon
title: class MultiPolygon extends MultiGeom - Liste de polygones
methods:
*/
class MultiPolygon extends MultiGeom {
  static $verbose = 0;
  
  /*PhpDoc: methods
  name:  __construct
  title: function __construct($param) - initialise un MultiPolygon à partir d'un WKT ou de [Polygon] ou de [[[[num,num]]]]
  */
  function __construct($param) {
    if (self::$verbose)
      echo "MultiPolygon::__construct()\n";
    if (is_array($param)) {
      $this->geom = [];
      foreach ($param as $elt) {
        if (is_object($elt) and (get_class($elt)=='Polygon'))
          $this->geom[] = $elt;
        elseif (is_array($elt))
          $this->geom[] = new Polygon($elt);
        else
          throw new Exception("Parametre non reconnu dans MultiPolygon::__construct()");
      }
    }
    elseif (is_string($param)) {
      $this->geom = [];
      $ring = '\([-0-9. ,]+\),?';
      $pattern = "!^MULTIPOLYGON\s*\((\(($ring)*\)),?!";
      while (preg_match($pattern, $param, $matches)) {
        $this->geom[] = new Polygon("POLYGON$matches[1]");
        $param = preg_replace($pattern, 'MULTIPOLYGON(', $param, 1);
      }
      if ($param<>'MULTIPOLYGON()')
        throw new Exception("Parametre '$param' non reconnu dans MultiPolygon::__construct()");
    }
    else
      throw new Exception("Parametre non reconnu dans MultiPolygon::__construct()");
  }
  
  // Test de prise en compte d'un MULTIPOLYGON
  static function test_new() {
    $geomstr = <<<EOT
MULTIPOLYGON (((153042 6799129,153043 6799174,153063 6799199),(1 1,2 2)),((154613 6803109.5,154568 6803119,154538.9 6803145)))
EOT;

    $mp = new MultiPolygon($geomstr);
    echo "multipolygon=$mp\n";
    echo "wkt=",$mp->wkt(),"\n";
    echo "GeoJSON:",json_encode($mp->geojson()),"\n";
  }
  
  /*PhpDoc: methods
  name:  wkt
  title: function wkt() - génère une chaine de caractère correspondant au WKT avec l'entete
  */
  function wkt(int $nbdigits=null):string { return 'MULTIPOLYGON'.$this; }
  
  /* Déjà défini sur MultiGeom
  function filter(int $nbdigits): MultiPolygon {
    $geom = [];
    foreach ($this->geom as $polygon) {
      $filteredPolygon = $polygon->filter($nbdigits);
      $geom[] = $filteredPolygon;
    }
    return new MultiPolygon($geom);
  }*/
  
  function area(): float {
    $area = 0.0;
    foreach($this->geom as $polygon)
      $area += $polygon->area();
    return $area;
  }
  
  /*PhpDoc: methods
  name:  inters
  title: "function inters(Geometry $geom): bool - teste l'intersection entre les 2 polygones ou multi-polygones"
  */
  function inters(Geometry $geom): bool {
    if (get_class($geom) == 'Polygon') {
      foreach($this->geom as $polygon) {
        if ($polygon->inters($geom)) // intersection entre 2 polygones
          return true;
      }
      return false;
    }
    elseif (get_class($geom) == 'MultiPolygon') {
      foreach($this->geom as $pol0) {
        foreach ($geom as $pol1) {
          if ($pol0->inters($pol1)) // intersection entre 2 polygones
            return true;
        }
      }
      return false;
    }
    else
      throw new Exception("Erreur d'appel de MultiPolygon::inters() avec un objet de ".get_class($geom));
  }
};


if (basename(__FILE__)<>basename($_SERVER['PHP_SELF'])) return;
echo "<html><head><meta charset='UTF-8'><title>MultiPolygon</title></head><body><pre>";
require_once __DIR__.'/inc.php';

if (!isset($_GET['test'])) {
  echo <<<EOT
</pre>
<h2>Test de la classe MultiPolygon</h2>
<ul>
  <li><a href='?test=test_new'>test_new</a>
</ul>\n
EOT;
  die();
}
else {
  $test = $_GET['test'];
  MultiPolygon::$test();
}

