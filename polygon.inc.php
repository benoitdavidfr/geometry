<?php
/*PhpDoc:
name:  polygon.inc.php
title: polygon.inc.php - définition d'un polygone
includes: [ geometry.inc.php ]
functions:
classes:
journal: |
  8/8/2018:
    - correction d'un bug dans filter()
  21/10/2017:
    - première version
*/
require_once __DIR__.'/geometry.inc.php';
/*PhpDoc: classes
name:  Polygon
title: Class Polygon extends Geometry - Définition d'un Polygon
methods:
doc: |
  protected $geom; // Pour un Polygon: [LineString]
*/
class Polygon extends Geometry {
  static $verbose = 0;

  /*PhpDoc: methods
  name:  lineStrings
  title: "function lineStrings(): array - retourne la liste des LineStrings composant le polygone"
  */
  function lineStrings(): array { return $this->geom; }
    
  /*PhpDoc: methods
  name:  __construct
  title: __construct($param) - construction à partir d'un WKT ou d'un [LineString] ou d'un [[[num,num]]]
  */
  function __construct($param) {
    // echo "Polygon::__construct(param=$param)\n";
    if (self::$verbose)
      echo "Polygon::__construct()\n";
    if (is_array($param)) {
      $this->geom = [];
      foreach ($param as $ls) {
        if (is_object($ls) and (get_class($ls)=='LineString'))
          $this->geom[] = $ls;
        elseif (is_array($ls))
          $this->geom[] = new LineString($ls);
        else
          throw new Exception("Parametre non reconnu dans Polygon::__construct()");
      }
      return;
    }
    elseif (is_string($param) and preg_match('!^(POLYGON\s*)?\(\(!', $param)) {
      $this->geom = [];
      $pattern = '!^(POLYGON\s*)?\(\(\s*([-\d.e]+)\s+([-\d.e]+)(\s+([-\d.e]+))?\s*,?!';
      while (1) {
        // echo "boucle de Polygon::__construct sur param=$param\n";
        $pointlist = [];
        while (preg_match($pattern, $param, $matches)) {
          // echo "matches="; print_r($matches);
          if (isset($matches[5]))
            $pointlist[] = new Point([$matches[2], $matches[3], $matches[5]]);
          else
            $pointlist[] = new Point([$matches[2], $matches[3]]);
          $param = preg_replace($pattern, '((', $param, 1);
        }
        if ($param=='(())') {
          $this->geom[] = new LineString($pointlist);
          return;
        } elseif (preg_match('!^\(\(\),\(!', $param)) {
          $this->geom[] = new LineString($pointlist);
          $param = preg_replace('!^\(\(\),\(!', '((', $param, 1);
        } else
          throw new Exception("Erreur dans Polygon::__construct(), Reste param=$param");
      }
    } else
      throw new Exception("Parametre non reconnu dans Polygon::__construct($param)");
  }
  
  static function test_new() {
    $pol = new Polygon('POLYGON((1 0,0 1,-1 0,0 -1,1 0))');
    echo "pol=$pol\n";
    echo "WKT:",$pol->wkt(),"\n";
    echo "GeoJSON:",json_encode($pol->geojson()),"\n";
  }
  
  /*PhpDoc: methods
  name:  addHole
  title: "function addHole(LineString $hole): void - aoute un trou au polygone"
  */
  function addHole(LineString $hole): void { $this->geom[] = $hole; }
  
  /*PhpDoc: methods
  name:  chgCoordSys
  title: "function chgCoordSys($src, $dest): Polygon - créée un nouveau Polygon en changeant le syst. de coord. de $src en $dest"
  */
  function chgCoordSys($src, $dest): Polygon {
    $lls = [];
    foreach ($this->geom as $ls)
      $lls[] = $ls->chgCoordSys($src, $dest);
    return new Polygon($lls);
  }
    
  /*PhpDoc: methods
  name:  __toString
  title: "function __toString(): string - affiche la liste des LineString entourée par des ()"
  */
  function __toString(): string { return '('.implode(',',$this->geom).')'; }
  
  /*PhpDoc: methods
  name:  toString
  title: "function toString($nbdigits=null): string - affiche la liste des LineString entourée par des () en précisant évent. le nbre de chiffres significatifs"
  */
  function toString(int $nbdigits=null): string {
    $str = '';
    foreach ($this->geom as $ls)
      $str .= ($str?',':'').$ls->toString($nbdigits);
    return '('.$str.')';
  }
  
  /*PhpDoc: methods
  name:  bbox
  title: "function bbox(): BBox - calcul du rectangle englobant"
  */
  function bbox(): BBox { return $this->geom[0]->bbox(); }
  
  /*PhpDoc: methods
  name:  isValid
  title: "function isValid(): bool - renvoie vrai ssi l'objet est correct"
  */
  function isValid(): bool {
    if (count($this->geom) == 0)
      return false;
    foreach ($this->geom as $ls) {
      if (!$ls->isValid())
        return false;
      if (count($ls->points()) < 4)
        return false;
    }
    return true;
  }
  
  /*PhpDoc: methods
  name:  proj2D
  title: "function proj2D(): Polygon - renvoie un nouveau 2D"
  */
  function proj2D(): Polygon {
    $proj = [];
    foreach ($this->geom as $ls) {
      $proj[] = $ls->proj2D();
    }
    return new Polygon($proj);
  }

  /*PhpDoc: methods
  name:  filter
  title: "function filter($nbdigits): Polygon - filtre la géométrie en supprimant les points intermédiaires successifs identiques"
  */
  function filter(int $nbdigits): Polygon {
    //echo "Polygon::filter($nbdigits)\n";
    $result = [];
    foreach ($this->geom as $ls) {
      $filtered = $ls->filter($nbdigits);
      if (count($filtered->points()) >= 4)
        $result[] = $filtered;
    }
    return new Polygon($result);
  }
  
  // A SUPPRIMER, redondant avec isValid() et faux
  //function isDegenerated(): bool { return (count($this->geom[0]) < 4); }
  
  /*PhpDoc: methods
  name:  coordinates
  title: "function coordinates(): array - renvoie les coordonnées comme [ [ [ num ] ] ]"
  */
  function coordinates(): array {
    $coordinates = [];
    foreach ($this->geom as $ls)
      $coordinates[] = $ls->coordinates();
    return $coordinates;
  }
  
  /*PhpDoc: methods
  name:  draw
  title: function draw($drawing, $stroke='black', $fill='transparent', $stroke_with=2) - dessine
  */
  function draw($drawing, $stroke='black', $fill='transparent', $stroke_with=2) {
    return $drawing->drawPolygon($this->geom, $stroke, $fill, $stroke_with);
  }
  
  /*PhpDoc: methods
  name:  area
  title: "function area($options=[]): float - renvoie la surface dans le système de coordonnées courant"
  doc: |
    Par défaut, l'extérieur et les intérieurs tournent dans des sens différents.
    La surface est positive si l'extérieur tourne dans le sens trigonométrique, <0 sinon.
    Si l'option 'noDirection' vaut true alors les sens ne sont pas pris en compte
  */
  function area(array $options=[]): float {
    $noDirection = (isset($options['noDirection']) and ($options['noDirection']));
    foreach ($this->geom as $ring)
      if (!isset($area))
        $area = ($noDirection ? abs($ring->area()) : $ring->area());
      else
        $area += ($noDirection ? -abs($ring->area()) : $ring->area());
    return $area;
  }
  static function test_area() {
    foreach ([
      'POLYGON((0 0,1 0,0 1,0 0))' => "triangle unité",
      'POLYGON((0 0,1 0,1 1,0 1,0 0))'=>"carré unité",
      'POLYGON((0 0,10 0,10 10,0 10,0 0))'=>"carré 10",
      'POLYGON((0 0,10 0,10 10,0 10,0 0),(2 2,2 8,8 8,8 2,2 2))'=>"carré troué bien orienté",
      'POLYGON((0 0,0 10,10 10,10 0,0 0),(2 2,2 8,8 8,8 2,2 2))'=>"carré troué mal orienté",
    ] as $polstr=>$title) {
      echo "<h3>$title</h3>";
      $pol = new Polygon($polstr);
      echo "area($pol)=",$pol->area();
      echo ", noDirection->",$pol->area(['noDirection'=>true]),"\n";
    }
  }
  
  /*PhpDoc: methods
  name:  pointInPolygon
  title: "pointInPolygon(Point $pt): bool - teste si un point pt est dans le polygone"
  */
  function pointInPolygon(Point $pt): bool {
    $c = false;
    foreach ($this->geom as $ring)
      if ($ring->pointInPolygon($pt))
        $c = !$c;
    return $c;
  }
  static function test_pointInPolygon() {
    $p0 = new Point('POINT(0 0)');
    foreach ([
      'POLYGON((1 0,0 1,-1 0,0 -1))',
      'POLYGON((1 1,-1 1,-1 -1,1 -1))',
      'POLYGON((1 1,-1 1,-1 -1,1 -1,1 1))',
      'POLYGON((1 1,2 1,2 2,1 2))',
    ] as $polstr) {
      $pol = new Polygon($polstr);
      echo "${pol}->pointInPolygon(($p0))=",($pol->pointInPolygon($p0)?'true':'false'),"\n";
    }
  }

  /*PhpDoc: methods
  name:  segs
  title: "segs(): array - liste des segments"
  */
  function segs(): array {
    $segs = [];
    foreach ($this->lineStrings() as $ls)
      $segs = array_merge($segs, $ls->segs());
    return $segs;
  }
  
  /*PhpDoc: methods
  name:  inters
  title: "function inters(Geometry $geom): bool - teste l'intersection entre les 2 polygones ou multi-polygones"
  */
  function inters(Geometry $geom): bool {
    if (get_class($geom) == 'Polygon') {
      // Si les boites ne s'intersectent pas alors les polygones non plus
      if ($this->bbox()->inters($geom->bbox()) == 0)
        return false;
      // si un point de $geom est dans $this alors il y a intersection
      foreach($geom->lineStrings() as $ls) {
        foreach ($ls->points() as $pt) {
          if ($this->pointInPolygon($pt))
            return true;
        }
      }
      // Si un point de $this est dans $geom alors il y a intersection
      foreach ($this->lineStrings() as $ls) {
        foreach ($ls->points() as $pt) {
          if ($geom->pointInPolygon($pt))
            return true;
        }
      }
      // Si 2 segments s'intersectent alors il y a intersection
      foreach ($this->segs() as $seg0) {
        foreach($geom->segs() as $seg1) {
          if ($seg0->inters($seg1))
            return true;
        }
      }
      // Sinon il n'y a pas intersection
      return false;
    }
    elseif (get_class($geom) == 'MultiPolygon') {
      return $geom->inters($this);
    }
    else
      throw new Exception("Erreur d'appel de Polygon::inters() avec un objet de ".get_class($geom));
  }
};


if (basename(__FILE__)<>basename($_SERVER['PHP_SELF'])) return;
echo "<html><head><meta charset='UTF-8'><title>polygon</title></head><body><pre>";
require_once __DIR__.'/inc.php';

if (!isset($_GET['test'])) {
  echo <<<EOT
</pre>
<h2>Test de la classe Polygon</h2>
<ul>
  <li><a href='?test=test_new'>test_new</a>
  <li><a href='?test=test_pointInPolygon'>test_pointInPolygon</a>
  <li><a href='?test=test_area'>test_area</a>
</ul>\n
EOT;
  die();
}
else {
  $test = $_GET['test'];
  Polygon::$test();
}

