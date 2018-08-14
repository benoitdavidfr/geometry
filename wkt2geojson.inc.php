<?php
/*PhpDoc:
name:  wkt2geojson.inc.php
title: wkt2geojson.inc.php - transforme un WKT en GeoJSON
classes:
doc: |
  Implémentation performance de la transformation WKT en GeoJSON
journal: |
  14/8/2018:
    ajout du paramètre shift pour générer les objets de l'autre côté de l'anti-méridien
  12/8/2018
  - première version
*/
/*PhpDoc: classes
name:  Wkt2GeoJson
title: class Wkt2GeoJson - classe statique portant la méthode convert(string $wkt): array
methods:
doc: |
  La méthode convert() ne crée pas de copie du $wkt afin d'optimiser la gestion mémoire.
  De plus, elle utilise ni preg_match() ni preg_replace().
  Les fichiers polygon.wkt.txt et multipolygon.wkt.txt sont utilisés pour le test unitaire
*/
class Wkt2GeoJson {
  // génère à la volée un GeoJSON à partir d'un WKT
  // shift vaut 0 -360.0 ou +360.0 pour générer les objets de l'autre côté de l'anti-méridien
  static function convert(string $wkt, float $shift): array {
    //echo "wkt=$wkt<br>\n";
    if (substr($wkt, 0, 6)=='POINT(') {
      $pos = 6;
      return ['type'=>'Point', 'coordinates'=> self::parseListPoints($wkt, $pos, $shift)[0]];
    }
    elseif (substr($wkt, 0, 11)=='LINESTRING(') {
      $pos = 11;
      return ['type'=>'LineString', 'coordinates'=> self::parseListPoints($wkt, $pos, $shift)];
    }
    elseif (substr($wkt, 0, 16)=='MULTILINESTRING(') {
      $pos = 16;
      return ['type'=>'MultiLineString', 'coordinates'=> self::parsePolygon($wkt, $pos, $shift)];
    }
    elseif (substr($wkt, 0, 8)=='POLYGON(') {
      $pos = 8;
      return ['type'=>'Polygon', 'coordinates'=> self::parsePolygon($wkt, $pos, $shift)];
    }
    elseif (substr($wkt, 0, 13)=='MULTIPOLYGON(') {
      $pos = 13;
      return ['type'=>'MultiPolygon', 'coordinates'=> self::parseMultiPolygon($wkt, $pos, $shift)];
    }
    else
      die("erreur Wkt2GeoJson::convert(), wkt=$wkt");
  }
  
  // traite le MultiPolygon
  private static function parseMultiPolygon(string $wkt, int &$pos, float $shift): array {
    //echo "parseMultiPolygon($wkt)<br>\n";
    $geom = [];
    $n = 0;
    while ($pos < strlen($wkt)) {
      if (substr($wkt, $pos, 1) == '(') {
        $pos++;
        $geom[$n] = self::parsePolygon($wkt, $pos, $shift);
        $n++;
        //echo "left parseMultiPolygon 1=$wkt<br>\n";
        if (substr($wkt, $pos, 1) == ',') {
          $pos++;
          //echo "left parseMultiPolygon 2=$wkt<br>\n";
        }
      }
      elseif (substr($wkt,$pos,1) == ')') {
        $pos++;
        return $geom;
      }
      else {
        echo "left parseMultiPolygon 3=",substr($wkt,$pos),"<br>\n";
        die("Erreur Wkt2GeoJson::parseMultiPolygon ligne ".__LINE__);
      }
    }
    return $geom;
  }
  
  // consomme une liste de points entourée d'une paire de parenthèses + parenthèse fermante
  private static function parsePolygon(string $wkt, int &$pos, float $shift): array {
    $geom = [];
    $n = 0;
    while($pos < strlen($wkt)) {
      if (substr($wkt, $pos, 1) == '(') {
        $pos++;
        $geom[$n++] = self::parseListPoints($wkt, $pos, $shift);
      }
      elseif (substr($wkt, $pos, 3) == '),(') {
        $pos += 3;
        $geom[$n++] = self::parseListPoints($wkt, $pos, $shift);
      }
      elseif (substr($wkt, $pos, 2) == '))') {
        $pos += 2;
        return $geom;
      }
      else {
        echo "left parsePolygon 2 sur ",substr($wkt, $pos),"<br>\n";
        die("ligne ".__LINE__);
      }
    }
    echo "left parsePolygon 3=$wkt, pos=$pos<br>\n";
    die("ligne ".__LINE__);
  }
  
  // consomme une liste de points sans parenthèses - version a optimiser
  private static function parseListPointsNotOptimized(string &$wkt): array {
    $points = [];
    $pattern = '!^(-?\d+(\.\d+)?) (-?\d+(\.\d+)?),?!';
    while(preg_match($pattern, $wkt, $matches)) {
      //echo "matches="; print_r($matches); echo "<br>";
      $points[] = [$matches[1], $matches[3]];
      $wkt = preg_replace($pattern, '', $wkt, 1);
    }
    //echo "left parseListPoints=$wkt<br>\n";
    return $points;
  }
  
  // consomme une liste de points sans parenthèses - version optimisée
  // à la fin pos pointe sur la ')'
  private static function parseListPoints(string $wkt, int &$pos, float $shift): array {
    $points = [];
    while ($pos < strlen($wkt)) {
      $len = strcspn($wkt, ' ', $pos);
      $lng = substr($wkt, $pos, $len);
      //echo "len=$len, wkt='$wkt', pos=$pos, x=$x\n"; die();
      $pos += $len+1;
      $len = strcspn($wkt, '),', $pos);
      $lat = substr($wkt, $pos, $len);
      $pos += $len;
      //echo "len=$len, wkt='$wkt', pos=$pos, x=$x, y=$y\n"; //die();
      $points[] = [(float)$lng + $shift, (float)$lat];
      //print_r($points);
      //echo 'fin=',substr($wkt, $pos, 1),"\n"; //die();
      if (substr($wkt, $pos, 1)==')')
        return $points;
      $pos++;
    }
    return $points;
  }
};


if (basename(__FILE__)<>basename($_SERVER['PHP_SELF'])) return;


// Test unitaire de la classe Wkt2GeoJson

$wkts = [
  //'POINT(-59.696 -51.966)',
  //'LINESTRING(141 -2.6,142.735 -3.289,144.584 -3.861,145.273 -4.374,145.83 -4.876,145.982 -5.466,147.648 -6.084,147.891 -6.614,146.971 -6.722,147.192 -7.388,148.085 -8.044,148.734 -9.105,149.307 -9.071,149.267 -9.514,150.039 -9.684,149.739 -9.873,150.802 -10.294,150.691 -10.583,150.028 -10.652,149.782 -10.393,148.923 -10.281,147.913 -10.13,147.135 -9.492,146.568 -8.943,146.048 -8.067,144.744 -7.63,143.897 -7.915,143.286 -8.245,143.414 -8.983,142.628 -9.327,142.068 -9.16,141.034 -9.118,140.143 -8.297,139.128 -8.096,138.881 -8.381,137.614 -8.412,138.039 -7.598,138.669 -7.32,138.408 -6.233,137.928 -5.393,135.989 -4.547,135.165 -4.463,133.663 -3.539,133.368 -4.025,132.984 -4.113,132.757 -3.746,132.754 -3.312,131.99 -2.821,133.067 -2.46,133.78 -2.48,133.696 -2.215,132.232 -2.213,131.836 -1.617,130.943 -1.433,130.52 -0.938,131.868 -0.695,132.38 -0.37,133.986 -0.78,134.143 -1.152,134.423 -2.769,135.458 -3.368,136.293 -2.307,137.441 -1.704,138.33 -1.703,139.185 -2.051,139.927 -2.409,141 -2.6)',
  //'POLYGON((162.119 -10.483,162.399 -10.826,161.7 -10.82,161.32 -10.205,161.917 -10.447,162.119 -10.483))',
  //'MULTIPOLYGON(((162.119 -10.483,162.399 -10.826,161.7 -10.82,161.32 -10.205,161.917 -10.447,162.119 -10.483)),((162.119 -10.483,162.399 -10.826,161.7 -10.82,161.32 -10.205,161.917 -10.447,162.119 -10.483)))',
  'polygon.wkt.txt',
  'multipolygon.wkt.txt',
];
header('Content-type: application/json');
//header('Content-type: text/plain');
echo "[\n";
$nb = 0;
foreach ($wkts as $wkt) {
  if (is_file($wkt))
    $wkt = file_get_contents($wkt);
  $geojson = Wkt2GeoJson::convert($wkt);
  if ($nb++<>0)
    echo ",\n";
  echo '  ',json_encode($geojson, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
}
echo "\n]\n";
