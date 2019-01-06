<?php
/*PhpDoc:
name:  point.inc.php
title: point.inc.php - définition d'un point
includes: [ geometry.inc.php ]
functions:
classes:
journal: |
  23/12/2018:
  - modification de la signature de add, scalMult, pscal et pvect
  4/11/2018:
  - création d'une classe segment
  21/10/2017:
  - première version
*/
require_once __DIR__.'/geometry.inc.php';
/*PhpDoc: classes
name:  Point
title: Class Point extends Geometry - Définition d'un Point
methods:
doc: |
  protected $geom; // Pour un Point: [x, y {, z}]
                   // x, y et éventuellement z doivent être des nombres
  static $verbose = 0;
*/
class Point extends Geometry {
  static $verbose = 0;
  
  /*PhpDoc: methods
  name:  __construct
  title: __construct($param) - construction à partir d'un WKT ou d'un array [num, num {, num}]
  */
  function __construct($param) {
    if (self::$verbose)
      echo "Point::__construct(",(is_string($param) ? $param : ''),")\n";
    if (is_array($param)) {
      if ((count($param)==2) && is_numeric($param[0]) && is_numeric($param[1]))
        $this->geom = [ $param[0]+0, $param[1]+0 ];
      elseif ((count($param)==3) && is_numeric($param[0]) && is_numeric($param[1]) && is_numeric($param[2]))
        $this->geom = [ $param[0]+0, $param[1]+0, $param[2]+0 ];
      else {
        echo "param = "; var_dump($param);
        throw new Exception("Parametre array non reconnu dans Point::__construct()");
      }
    } 
    elseif (is_string($param)) {
      if (!preg_match('!^(POINT\s*\()?([-\d.e]+)\s+([-\d.e]+)(\s+([-\d.e]+))?\)?$!', $param, $matches))
        throw new Exception("Parametre wkt='$param' non reconnu dans Point::__construct()");
      elseif (isset($matches[4]))
        $this->geom = [$matches[2]+0, $matches[3]+0, $matches[5]+0];
      else
        $this->geom = [$matches[2]+0, $matches[3]+0];
    }
    else {
      echo "param = "; var_dump($param);
      throw new Exception("Parametre ni array ni string dans Point::__construct()");
    }
  }
    
  static function test_fromWkt() { // test de l'initialisation à partir du WKT
    $pt = new Point('POINT(15 20)');
    $pt = new Point('15 20');
    echo "pt=$pt\n";
    //print_r($pt->bbox());
    echo "bbox=",$pt->bbox(),"\n";
    $pt2 = new Point('POINT(15 20)');
    $pt3 = new Point('POINT(-1e-5 15 20)');
  //print_r($pt);
    echo "pt3=$pt3\n";
    echo ($pt2==$pt ? "pt2==pt" : 'pt2<>pt'),"\n";

    $pt = new Point('POINT(15 20 99)');
    $pt = Geometry::fromWkt('POINT(15 20 99)');
    echo "pt=$pt\n";
    echo "GeoJSON=",json_encode($pt->geojson()),"\n";
    echo "WKT:",$pt->wkt(),"\n";
  }
  
  static function test_fromGeoJSON() { // Test de l'initialisation à partir des coordonnées GeoJSON
    $pt = new Point([15,20]);
    $pt = new Point([15,20,30]);
    //$pt = new Point([15,20,30,50]);
    $pt = new Point([15,'20',30]);
    //$pt = new Point([15,'20','30xx']); // ereur
    $pt = new Point([15,'20']);
    echo "pt=$pt\n";
    echo "pt=",$pt->wkt(),"\n";
  }

  /*PhpDoc: methods
  name:  x
  title: function x() - accès à la première coordonnée
  */
  function x() { return $this->geom[0]; }
  
  /*PhpDoc: methods
  name:  y
  title: function y() - accès à la seconde coordonnée
  */
  function y() { return $this->geom[1]; }
  
  /*PhpDoc: methods
  name:  z
  title: function z() - accès à la troisième coordonnée ou null
  */
  function z() { return isset($this->geom[2]) ? $this->geom[2] : null; }
  
  /*PhpDoc: methods
  name:  isValid
  title: "function isValid(): bool - renvoie true car tous les objets point construits sont valides"
  */
  function isValid(): bool { return true; }

  /*PhpDoc: methods
  name:  round
  title: "function round(int $nbdigits): Point - arrondit un point avec le nb de chiffres indiqués"
  */
  function round(int $nbdigits): Point {
    if (!isset($this->geom[2]))
      return new Point([ round($this->geom[0],$nbdigits), round($this->geom[1], $nbdigits) ]);
    else
      return new Point([ round($this->geom[0],$nbdigits), round($this->geom[1], $nbdigits), $this->geom[2] ]);
  }
    
  /*PhpDoc: methods
  name:  filter
  title: "function filter(int $nbdigits): Point - synonyme de round()"
  */
  function filter(int $nbdigits): Point { return $this->round($nbdigits); }
    
  /*PhpDoc: methods
  name:  proj2D
  title: "function proj2D(): Point - projection 2D"
  */
  function proj2D(): Point { return new Point([$this->geom[0], $this->geom[1]]); }
    
  /*PhpDoc: methods
  name:  __toString
  title: "function __toString(): string - affichage des coordonnées séparées par un blanc"
  */
  function __toString(): string {
    return $this->geom[0].' '.$this->geom[1].(isset($this->geom[2])?' '.$this->geom[2]:'');
  }

  /*PhpDoc: methods
  name:  wkt
  title: "function wkt(): string - retourne la chaine WKT"
  */
  function wkt(): string { return strtoupper(get_called_class()).'('.$this.')'; }
  
  /*PhpDoc: methods
  name:  bbox
  title: "function bbox(): BBox - calcule la bbox"
  */
  function bbox(): BBox { return new BBox([$this]); }
  
  /*PhpDoc: methods
  name:  drawCircle
  title: "function drawCircle(Drawing $drawing, $r, $fill): void - dessine un cercle centré sur le point de rayon r dans la couleur indiquée"
  */
  function drawCircle(Drawing $drawing, $r, $fill): void { $drawing->drawCircle($pt, $r, $fill); }
  
  /*PhpDoc: methods
  name:  distance
  title: "function distance(Point $pt1): float - retourne la distance euclidienne entre 2 points"
  */
  function distance(Point $pt1): float {
    return sqrt(square($pt1->x() - $this->x()) + square($pt1->y() - $this->y()));
  }
  
  /*PhpDoc: methods
  name:  chgCoordSys
  title: "function chgCoordSys(string $src, string $dest): Point - crée un nouveau Point en passant du syst. de coord. $src en $dest"
  uses: [ 'coordsys.inc.php?Class CoordSys' ]
  doc: |
    Utilise CoordSys::chg($src, $dest, $x, $y) pour effectuer le chgt de syst. de coordonnées
  */
  function chgCoordSys(string $src, string $dest): Point {
    $c = CoordSys::chg($src, $dest, $this->geom[0], $this->geom[1]);
    if (isset($this->geom[2]))
      return new Point([$c[0], $c[1], $this->geom[2]]);
    else
      return new Point([$c[0], $c[1]]);
  }
  
  /*PhpDoc: methods
  name:  coordinates
  title: "function coordinates(): array - renvoie les coordonnées sous la forme [ number, number {, number} ]"
  */
  function coordinates(): array { return $this->geom; }
  
  /*PhpDoc: methods
  name:  length
  title: "function length(): float - renvoie la norme du vecteur"
  */
  function length(): float { return sqrt($this->pscal($this)); }
  static function test_length() {
    foreach ([
      'POINT(15 20)',
      'POINT(1 0)',
      'POINT(10 0)',
      'POINT(10 10)',
    ] as $pt) {
      $v = new Point($pt);
      echo "length($v)=",$v->length(),"\n";
    }
  }

  /*PhpDoc: methods
  name:  substract
  title: "static function substract(Point $p0, Point $p): Point - différence $p - $p0"
  */
  static function substract(Point $p0, Point $p): Point { return new Point([$p->x()-$p0->x(), $p->y()-$p0->y()]); }

  /*PhpDoc: methods
  name:  substract
  title: "function diff(Point $v): Point - différence $this - $v"
  */
  function diff(Point $v): Point { return new Point([$this->x() - $v->x(), $this->y() - $v->y()]); }

  /*PhpDoc: methods
  name:  add
  title: "static function add(Point $a, Point $b): Point - somme $a + $b"
  */
  //static function add(Point $a, Point $b): Point { return new Point([$a->x()+$b->x(), $a->y()+$b->y()]); }

  /*PhpDoc: methods
  name:  add
  title: "function add(Point $v): Point - somme $this + $v"
  */
  function add(Point $v): Point { return new Point([$this->x() + $v->x(), $this->y() + $v->y()]); }

  /*PhpDoc: methods
  name:  scalMult
  title: "static function scalMult(float $u, Point $v): Point - multiplication $u * $v"
  */
  //static function scalMult(float $u, Point $v): Point { return new Point([$u*$v->x(), $u*$v->y()]); }

  /*PhpDoc: methods
  name:  scalMult
  title: "function scalMult(float $u): Point - multiplication $u * $this"
  */
  function scalMult(float $u): Point { return new Point([$u * $this->x(), $u * $this->y()]); }

  /*PhpDoc: methods
  name:  pvect
  title: "static function pvect(Point $va, Point $vb): float - produit vectoriel"
  */
  //static function pvect(Point $va, Point $vb): float { return $va->x()*$vb->y() - $va->y()*$vb->x(); }

  /*PhpDoc: methods
  name:  pvect
  title: "function pvect(Point $v): float - produit vectoriel $this par $v"
  */
  function pvect(Point $v): float { return $this->x()*$v->y() - $this->y()*$v->x(); }

  /*PhpDoc: methods
  name:  pvect
  title: "function pscal(Point $v): float - produit scalaire $this par $v"
  */
  function pscal(Point $v): float { return $this->x()*$v->x() + $this->y()*$v->y(); }
  static function test_pscal() {
    foreach ([
      ['POINT(15 20)','POINT(20 15)'],
      ['POINT(1 0)','POINT(0 1)'],
      ['POINT(1 0)','POINT(0 3)'],
      ['POINT(4 0)','POINT(0 3)'],
      ['POINT(1 0)','POINT(1 0)'],
    ] as $lpts) {
      $v0 = new Point($lpts[0]);
      $v1 = new Point($lpts[1]);
      echo "($v0)->pvect($v1)=",$v0->pvect($v1),"\n";
      echo "($v0)->pscal($v1)=",$v0->pscal($v1),"\n";
    }
  }
  
  /*PhpDoc: methods
  name:  distancePointLine
  title: "function distancePointLine(Point $a, Point $b): float - distance signée du point courant à la droite définie par les 2 points"
  doc: |
    La distance est positive si le point est à gauche de la droite AB et négative s'il est à droite
    # Distance signee d'un point P a une droite orientee definie par 2 points A et B
    # la distance est positive si P est a gauche de la droite AB et negative si le point est a droite
    # Les parametres sont les 3 points P, A, B
    # La fonction retourne cette distance.
    # --------------------
    sub DistancePointDroite
    # --------------------
    { my @ab = (@_[4] - @_[2], @_[5] - @_[3]); # vecteur B-A
      my @ap = (@_[0] - @_[2], @_[1] - @_[3]); # vecteur P-A
      return pvect (@ab, @ap) / Norme(@ab);
    }
  */
  function distancePointLine(Point $a, Point $b): float {
    $ab = $b->diff($a);
    $ap = $this->diff($a);
    return $ab->pvect($ap) / $ab->length();
  }
  static function test_distancePointLine() {
    foreach ([
      ['POINT(1 0)','POINT(0 0)','POINT(1 1)'],
      ['POINT(1 0)','POINT(0 0)','POINT(0 2)'],
    ] as $lpts) {
      $p = new Point($lpts[0]);
      $a = new Point($lpts[1]);
      $b = new Point($lpts[2]);
      echo '(',$p,")->distancePointLine(",$a,',',$b,")->",$p->distancePointLine($a,$b),"\n";
    }
  }
  
  /*PhpDoc: methods
  name:  projPointOnLine
  title: "function projPointOnLine(Point $a, Point $b): float - projection du point sur la droite A,B, renvoie u"
  doc: |
    # Projection P' d'un point P sur une droite A,B
    # Les parametres sont les 3 points P, A, B
    # Renvoit u / P' = A + u * (B-A).
    # Le point projete est sur le segment ssi u est dans [0 .. 1].
    # -----------------------
    sub ProjectionPointDroite
    # -----------------------
    { my @ab = (@_[4] - @_[2], @_[5] - @_[3]); # vecteur B-A
      my @ap = (@_[0] - @_[2], @_[1] - @_[3]); # vecteur P-A
      return pscal(@ab, @ap)/(@ab[0]**2 + @ab[1]**2);
    }
  */
  function projPointOnLine(Point $a, Point $b): float {
    $ab = $b->diff($a);
    $ap = $this->diff($a);
    return $ab->pscal($ap) / $ab->pscal($ab);
  }
  static function test_projPointOnLine() {
    foreach ([
      ['POINT(1 0)','POINT(0 0)','POINT(1 1)'],
      ['POINT(1 0)','POINT(0 0)','POINT(0 2)'],
      ['POINT(1 1)','POINT(0 0)','POINT(0 2)'],
    ] as $lpts) {
      $p = new Point($lpts[0]);
      $a = new Point($lpts[1]);
      $b = new Point($lpts[2]);
      echo '(',$p,")->projPointOnLine(",$a,',',$b,")->",$p->projPointOnLine($a,$b),"\n";
    }
  }

  /*PhpDoc: methods
  name:  inters
  title: "abstract function inters(Geometry $geom): bool - teste l'intersection entre les 2 géommétries"
  */
  //abstract function inters(Geometry $geom): bool;
};

class Segment {
  private $pts; // 2 points
  
  function __construct(Point $pt0, Point $pt1) { $this->pts = [$pt0, $pt1]; }
  
  /*PhpDoc: methods
  name:  intersSeg
  title: "function intersSeg(Segment $seg): ?array - intersection entre 2 segments"
  doc: |
    Si les segments ne s'intersectent pas alors retourne []
    S'ils s'intersectent alors retourne le pt ainsi que les abscisses u et v
    Si les 2 segments sont parallèles, alors retourne [] même s'ils sont partiellement confondus
  journal: |
    4/11/2018
      Création d'une classe Segement
    9/1/2017
      Utilisation de tableaux de points comme paramètre
    29/12/2016
      Modif pour optimisation
  */
  function inters(Segment $seg): array {
    $a = $this->pts;
    $b = $seg->pts;
    if (max($a[0]->x(),$a[1]->x()) < min($b[0]->x(),$b[1]->x())) return [];
    if (max($b[0]->x(),$b[1]->x()) < min($a[0]->x(),$a[1]->x())) return [];
    if (max($a[0]->y(),$a[1]->y()) < min($b[0]->y(),$b[1]->y())) return [];
    if (max($b[0]->y(),$b[1]->y()) < min($a[0]->y(),$a[1]->y())) return [];
    
    $va = $a[1]->diff($a[0]); // vecteur correspondant au segment a
    $vb = $b[1]->diff($b[0]); // vecteur correspondant au segment b
    $ab = $b[0]->diff($a[0]); // vecteur b0 - a0
    $pab = $va->pvect($vb);
    if ($pab == 0)
      return []; // droites parallèles, éventuellement confondues
    $u = $ab->pvect($vb) / $pab;
    $v = $ab->pvect($va) / $pab;
    if (($u >= 0) and ($u < 1) and ($v >= 0) and ($v < 1))
      return [ 'pt'=>new Point([$a[0]->x()+$u*$va->x(), $a[0]->y()+$u*$va->y() ]),
               'u'=>$u, 'v'=>$v
             ];
    else
      return [];
  }
  static function test_inters() {
    foreach ([
      ['POINT(0 0)','POINT(10 0)','POINT(0 -5)','POINT(10 5)'],
      ['POINT(0 0)','POINT(10 0)','POINT(0 0)','POINT(10 5)'],
      ['POINT(0 0)','POINT(10 0)','POINT(0 -5)','POINT(10 -5)'],
      ['POINT(0 0)','POINT(10 0)','POINT(0 -5)','POINT(20 0)'],
    ] as $lpts) {
      $a0 = new Point($lpts[0]);
      $a1 = new Point($lpts[1]);
      $b0 = new Point($lpts[2]);
      $b1 = new Point($lpts[3]);
      echo "inters(",$a0,',',$a1,',',$b0,',',$b1,")->";
      $a = new Segment($a0, $a1);
      $b = new Segment($b0, $b1);
      print_r($a->inters($b)); echo "\n";
    }
  }

};

if (basename(__FILE__)<>basename($_SERVER['PHP_SELF'])) return;
echo "<html><head><meta charset='UTF-8'><title>point</title></head><body><pre>";
require_once __DIR__.'/inc.php';

if (!isset($_GET['test'])) {
  echo <<<EOT
</pre>
<h2>Test de la classe Point</h2>
<ul>
  <li><a href='?class=Point&amp;test=test_fromWkt'>initialisation par WKT</a>
  <li><a href='?class=Point&amp;test=test_fromGeoJSON'>initialisation par GeoJSON</a>
  <li><a href='?class=Point&amp;test=test_projPointOnLine'>test_projPointOnLine</a>
  <li><a href='?class=Point&amp;test=test_distancePointLine'>test_distancePointLine</a>
  <li><a href='?class=Point&amp;test=test_length'>test_length</a>
  <li><a href='?class=Point&amp;test=test_pscal'>test_pscal</a>
</ul>\n
<h2>Test de la classe Segment</h2>
<ul>
  <li><a href='?class=Segment&amp;test=test_inters'>test_inters</a>
</ul>\n
EOT;
  die();
}
else {
  $class = $_GET['class'];
  $test = $_GET['test'];
  $class::$test();
}
