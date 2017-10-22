<?php
/*PhpDoc:
name:  geometry.inc.php
title: geometry.inc.php - définition abstraite d'une géométrie WKT/GeoJSON, remplace geom2d
functions:
classes:
doc: |
  7 types de géométries sont définis:
  - 3 types géométriques élémentaires: Point, LineString et Polygon
  - 3 types de collections homogènes de géométries élémentaires: MultiPoint, MultiLineString et MultiPolygon
  - collection hétérogène de géométries élémentaires: GeometryCollection
  La classe Geometry est une une sur-classe abstraite de ces 7 types.
  Elle porte:
  - 2 méthodes de construction d'objet à partir d'un WKT ou d'un GeoJSON
  - la méthode générique geojson qui génère une représentation GeoJSON en Php en appellant la méthode coordinates
    cette méthode est surchargée pour GeometryCollection
  - la méthode wkt() qui frabrique une représentations WKT
  - la méthode bbox() qui fabrique le BBox
journal: |
  21/10/2017:
  - première version
*/
/*PhpDoc: classes
name:  Geometry
title: abstract class Geometry - Sur-classe abstraite des classes Point, LineString, Polygon, MultiGeom et GeometryCollection
methods:
doc: |
  Porte en variable de classe le paramètre precision qui définit le nombre de chiffres après la virgule à restituer en WKT par défaut.
  S'il est négatif, il indique le nbre de 0 à afficher comme derniers chiffres.
*/
abstract class Geometry {
  static $precision = null; // nombre de chiffres après la virgule, si null pas d'arrondi
  protected $geom; // La structure du stockage dépend de la sous-classe
  
  /*PhpDoc: methods
  name:  setParam
  title: static function setParam($param, $value=null) - définit un des paramètres
  */
  static function setParam($param, $value=null) {
    switch($param) {
      case 'precision': self::$precision = $value; break;
      default:
        throw new Exception("Parametre non reconnu dans Geometry::setParam()");  
    }
  }
    
  /*PhpDoc: methods
  name:  fromWkt
  title: static function fromWkt($wkt) - crée une géométrie à partir d'un WKT
  doc: |
    génère une erreur si le WKT ne correspond pas à une géométrie
  */
  static function fromWkt($wkt) {
    if (strncmp($wkt,'POINT',strlen('POINT'))==0)
      return new Point($wkt);
    elseif (strncmp($wkt,'LINESTRING',strlen('LINESTRING'))==0)
      return new LineString($wkt);
    elseif (strncmp($wkt,'POLYGON',strlen('POLYGON'))==0)
      return new Polygon($wkt);
    elseif (strncmp($wkt,'MULTIPOINT',strlen('MULTIPOINT'))==0)
      return new MultiPoint($wkt);
    elseif (strncmp($wkt,'MULTILINESTRING',strlen('MULTILINESTRING'))==0)
      return new MultiLineString($wkt);
    elseif (strncmp($wkt,'MULTIPOLYGON',strlen('MULTIPOLYGON'))==0)
      return new MultiPolygon($wkt);
    elseif (strncmp($wkt,'GEOMETRYCOLLECTION',strlen('GEOMETRYCOLLECTION'))==0)
      return new GeometryCollection($wkt);
    else
      throw new Exception("Parametre non reconnu dans Geometry::fromWkt()");  
  }
  
  /*PhpDoc: methods
  name:  fromGeoJSON
  title: static function fromGeoJSON($geometry) - crée une géométrie à partir d'une géométrue GeoJSON
  doc: |
    génère une erreur si le paramètre ne correspond pas à une géométrie GeoJSON
  */
  static function fromGeoJSON($geometry) {
    if (!is_array($geometry) or !isset($geometry['type'])) {
      echo "geometry ="; var_dump($geometry);
      throw new Exception("Le paramètre de Geometry::fromGeoJSON() n'est pas une géométrie GeoJSON");
    }
    if (in_array($geometry['type'], ['Point','LineString','Polygon','MultiPoint','MultiLineString','MultiPolygon'])
        and isset($geometry['coordinates'])) {
      return new $geometry['type']($geometry['coordinates']);
    }
    elseif (($geometry['type']=='GeometryCollection') and isset($geometry['geometries'])) {
      $coll = [];
      foreach ($geometry['geometries'] as $geometry)
        $coll[] = self::fromGeoJSON($geometry);
      return new GeometryCollection($coll);
    }
    else {
      echo "geometry ="; var_dump($geometry);
      throw new Exception("Le paramètre de Geometry::fromGeoJSON() n'est pas une géométrie GeoJSON");
    }
  }
  
  /*PhpDoc: methods
  name:  value
  title: function value()
  */
  function value() { return $this->geom; }
  
  /*PhpDoc: methods
  name:  geojson
  title: function geojson() - retourne un tableau Php qui encodé en JSON correspondra à la geometry GeoJSON
  */
  function geojson():array { return [ 'type'=>get_called_class(), 'coordinates'=>$this->coordinates() ]; }
  
  /*PhpDoc: methods
  name:  wkt
  title: function wkt($nbdigits=null) - retourne la chaine WKT en précisant éventuellement le nbre de chiffres significatifs
  */
  function wkt(int $nbdigits=null):string { return strtoupper(get_called_class()).$this->toString($nbdigits); }

  /*PhpDoc: methods
  name:  bbox
  title: function bbox() - calcule la bbox
  */
  function bbox():BBox {
    $bbox = new BBox;
    foreach ($this->geom as $geom)
      $bbox->union($geom->bbox());
    return $bbox;
  }
};

require_once __DIR__.'/point.inc.php';
require_once __DIR__.'/coordsys.inc.php';
require_once __DIR__.'/bbox.inc.php';
require_once __DIR__.'/linestring.inc.php';
require_once __DIR__.'/polygon.inc.php';
require_once __DIR__.'/multigeom.inc.php';
require_once __DIR__.'/multipoint.inc.php';
require_once __DIR__.'/multilinestring.inc.php';
require_once __DIR__.'/multipolygon.inc.php';
require_once __DIR__.'/geomcoll.inc.php';


if (basename(__FILE__)<>basename($_SERVER['PHP_SELF'])) return;

echo "<html><head><meta charset='UTF-8'><title>geometry</title></head><body><pre>";

if (1) { // Test Geometry::fromGeoJSON(); et Geometry::fromWkt()
  foreach ([
    [ 'type'=>'Point',
      'coordinates'=>[5, 4],
    ],
    [ 'type'=>'LineString',
      'coordinates'=>[[5, 4],[15, 14]],
    ],
    [ 'type'=>'Polygon',
      'coordinates'=>[
        [[5, 4],[15, 14],[15, 18],[5, 4]],
        [[7, 8],[10, 12],[9, 8],[5, 4]],
      ],
    ],
    [ 'type'=>'MultiPoint',
      'coordinates'=>[[5, 4],[15, 14]],
    ],
    [ 'type'=>'MultiLineString',
      'coordinates'=>[
        [[5, 4],[15, 14],[15, 18],[5, 4]],
        [[7, 8],[10, 12],[9, 8],[5, 4]],
      ],
    ],
    [ 'type'=>'MultiPolygon',
      'coordinates'=>[
        [
          [[5, 4],[15, 14],[15, 18],[5, 4]],
          [[7, 8],[10, 12],[9, 8],[5, 4]],
        ]
      ],
    ],
    [ 'type'=>'GeometryCollection',
      'geometries'=>[
        [ 'type'=>'Point',
          'coordinates'=>[5, 4],
        ],
        [ 'type'=>'LineString',
          'coordinates'=>[[5, 4],[15, 14]],
        ],
        [ 'type'=>'Polygon',
          'coordinates'=>[
            [[5, 4],[15, 14],[15, 18],[5, 4]],
            [[7, 8],[10, 12],[9, 8],[5, 4]],
          ],
        ],
      ],
    ],
  ] as $geojson) {
    $gc = Geometry::fromGeoJSON($geojson);
    echo "wkt=",$gc->wkt(),"\n";
    $gc2 = Geometry::fromWkt($gc->wkt());
    echo "GeoJSON=",json_encode($gc2->geojson()),"\n";
  }
}


