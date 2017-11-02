<?php
/*PhpDoc:
name:  point.inc.php
title: point.inc.php - définition d'un point
includes: [ geometry.inc.php ]
functions:
classes:
journal: |
  21/10/2017:
  - première version
*/
require_once 'geometry.inc.php';
/*PhpDoc: classes
name:  Point
title: Class Point extends Geometry - Définition d'un Point
methods:
doc: |
  protected $geom; // Pour un Point: [x, y {, z}]
  x, y et éventuellement z doivent être des nombres
*/
class Point extends Geometry {
  static $verbose = 0;
  
  /*PhpDoc: methods
  name:  __construct
  title: __construct($param) - construction à partir d'un WKT ou d'un [num, num {, num}]
  */
  function __construct($param) {
    if (self::$verbose)
      echo "Point::__construct()\n";
    if (is_array($param)) {
      if ((count($param)==2) and is_numeric($param[0]) and is_numeric($param[1])) {
        $this->geom = [$param[0]+0, $param[1]+0];
      }
      elseif ((count($param)==3) and is_numeric($param[0]) and is_numeric($param[1]) and is_numeric($param[2])) {
        $this->geom = [$param[0]+0, $param[1]+0, $param[2]+0];
      }
      else {
        echo "param = "; var_dump($param);
        throw new Exception("Parametre array non reconnu dans Point::__construct()");
      }
    } 
    elseif (is_string($param)) {
      if (!preg_match('!^(POINT\s*\()?([-\d.e]+)\s+([-\d.e]+)(\s+([-\d.e]+))?\)?$!', $param, $matches))
        throw new Exception("Parametre string='$param' non reconnu dans Point::__construct()");
      elseif (isset($matches[4]))
        $this->geom = [$matches[2]+0, $matches[3]+0, $matches[5]+0];
      else
        $this->geom = [$matches[2]+0, $matches[3]+0];
    }
    else
      throw new Exception("Parametre ni array ni string dans Point::__construct()");
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
  name:  round
  title: function round($nbdigits) - arrondit un point avec le nb de chiffres indiqués
  */
  function round($nbdigits) {
    return new Point([ round($this->geom[0],$nbdigits), round($this->geom[1],$nbdigits) ]);
  }
    
  /*PhpDoc: methods
  name:  toString
  title: function toString($nbdigits=null) - affichage des coordonnées séparées par un blanc
  doc: |
    Si nbdigits est défini alors les coordonnées sont arrondies avant l'affichage.
    Si nbdigits n'est pas défini alors parent::$precision est utilisé.
    Si parent::$precision n'est pas défini alors l'ensemble des chiffres sont affichés.
  */
  function toString($nbdigits=null) {
    if ($nbdigits === null)
      $nbdigits = parent::$precision;
    if ($nbdigits === null)
      return $this->geom[0].' '.$this->geom[1].(isset($this->geom[2])?' '.$this->geom[2]:'');
    else
      return round($this->geom[0],$nbdigits).' '.round($this->geom[1],$nbdigits)
              .(isset($this->geom[2])?' '.$this->geom[2]:'');
  }
    
  /*PhpDoc: methods
  name:  __toString
  title: function __toString() - affichage des coordonnées séparées par un blanc
  doc: |
    Si parent::$precision est défini alors les coordonnées sont arrondies avant l'affichage
  */
  function __toString() { return $this->toString(); }

  /*PhpDoc: methods
  name:  wkt
  title: function wkt($nbdigits=null) - retourne la chaine WKT en précisant éventuellement le nbre de chiffres significatifs
  */
  function wkt(int $nbdigits=null):string { return strtoupper(get_called_class()).'('.$this->toString($nbdigits).')'; }
  
  /*PhpDoc: methods
  name:  bbox
  title: function bbox() - calcule la bbox
  */
  function bbox():BBox {
    $bbox = new BBox;
    return $bbox->bound($this);
  }
  
  /*PhpDoc: methods
  name:  drawCircle
  title: function drawCircle(Drawing $drawing, $r, $fill) - dessine un cercle centré sur le point de rayon r dans la couleur indiquée
  */
  function drawCircle(Drawing $drawing, $r, $fill) { $drawing->drawCircle($pt, $r, $fill); }
  
  /*PhpDoc: methods
  name:  distance
  title: function distance() - retourne la distance euclidienne entre 2 points
  */
  function distance(Point $pt1) { return sqrt(square($pt1->x() - $this->x()) + square($pt1->y() - $this->y())); }
  
  /*PhpDoc: methods
  name:  chgCoordSys
  title: function chgCoordSys($src, $dest) - crée un nouveau Point en changeant le syst. de coord. de $src en $dest
  uses: [ 'coordsys.inc.php?Class CoordSys' ]
  doc: |
    Utilise CoordSys::chg($src, $dest, $x, $y) pour effectuer le chgt de syst. de coordonnées
  */
  function chgCoordSys($src, $dest) {
    $c = CoordSys::chg($src, $dest, $this->geom[0], $this->geom[1]);
    if (isset($this->gom[2]))
      return new Point([$c[0], $c[1], $this->gom[2]]);
    else
      return new Point([$c[0], $c[1]]);
  }
  
  /*PhpDoc: methods
  name:  coordinates
  title: function coordinates() - renvoie un tableau de coordonnées
  */
  function coordinates() { return $this->geom; }
  
  /*PhpDoc: methods
  name:  vectLength
  title: function vectLength() - renvoie la norme du vecteur
  */
  function vectLength() { return sqrt(Point::pscal($this,$this)); }
  static function test_vectLength() {
    foreach ([
      'POINT(15 20)',
      'POINT(1 0)',
      'POINT(10 0)',
      'POINT(10 10)',
    ] as $pt) {
      $v = new Point($pt);
      echo "vectLength($v)=",$v->vectLength(),"\n";
    }
  }

  /*PhpDoc: methods
  name:  substract
  title: static function substract(Point $p0, Point $p) - différence $p - $p0
  */
  static function substract(Point $p0, Point $p) { return new Point([$p->x()-$p0->x(), $p->y()-$p0->y()]); }

  /*PhpDoc: methods
  name:  add
  title: static function add(Point $a, Point $b) - somme $a + $b
  */
  static function add(Point $a, Point $b) { return new Point([$a->x()+$b->x(), $a->y()+$b->y()]); }

  /*PhpDoc: methods
  name:  scalMult
  title: static function scalMult($u, Point $v) - multiplication $u * $v
  */
  static function scalMult($u, Point $v) { return new Point([$u*$v->x(), $u*$v->y()]); }

  /*PhpDoc: methods
  name:  pvect
  title: static function pvect(Point $va, Point $vb) - produit vectoriel
  */
  static function pvect(Point $va, Point $vb) { return $va->x()*$vb->y() - $va->y()*$vb->x(); }

  /*PhpDoc: methods
  name:  pvect
  title: function pscal(Point $va, Point $vb) - produit scalaire
  */
  static function pscal(Point $va, Point $vb) { return $va->x()*$vb->x() + $va->y()*$vb->y(); }
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
      echo "pvect($v0,$v1)=",Point::pvect($v0,$v1),"\n";
      echo "pscal($v0,$v1)=",Point::pscal($v0,$v1),"\n";
    }
  }
  
  /*PhpDoc: methods
  name:  distancePointLine
  title: function distancePointLine(Point $a, Point $b) - distance signée du point courant à la droite définie par les 2 points
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
  function distancePointLine(Point $a, Point $b) {
    $ab = Point::substract($a, $b);
    $ap = Point::substract($a, $this);
    return Point::pvect($ab, $ap) / $ab->vectLength();
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
  title: function projPointOnLine(Point $a, Point $b) - projection du point sur la droite A,B, renvoie u
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
  function projPointOnLine(Point $a, Point $b) {
    $ab = Point::substract($a, $b);
    $ap = Point::substract($a, $this);
    return Point::pscal($ab, $ap) / ($ab->x()*$ab->x() + $ab->y()*$ab->y());
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
  name:  interSegSeg
  title: static function interSegSeg(array $a, array $b) - intersection entre 2 segments a et b
  doc: |
    Chaque segment en paramètre est défini comme un tableau de 2 points
    Si les segments ne s'intersectent pas alors retourne null
    S'il s'intersectent, retourne le pt ainsi que les abscisses u et v
    Si les 2 segments sont parallèles, alors retourne null même s'ils sont partiellement confondus
  journal: |
    9/1/2017
      Utilisation de tableaux de points comme paramètre
    29/12/2016
      Modif pour optimisation
  */
  static function interSegSeg(array $a, array $b) {
    if (max($a[0]->x(),$a[1]->x()) < min($b[0]->x(),$b[1]->x())) return null;
    if (max($b[0]->x(),$b[1]->x()) < min($a[0]->x(),$a[1]->x())) return null;
    if (max($a[0]->y(),$a[1]->y()) < min($b[0]->y(),$b[1]->y())) return null;
    if (max($b[0]->y(),$b[1]->y()) < min($a[0]->y(),$a[1]->y())) return null;
    
    $va = Point::substract($a[0], $a[1]); // vecteur correspondant au segment a
    $vb = Point::substract($b[0], $b[1]); // vecteur correspondant au segment b
    $ab = Point::substract($a[0], $b[0]); // vecteur b0 - a0
    $pab = Point::pvect($va, $vb);
    if ($pab == 0)
      return null; // droites parallèles, éventuellement confondues
    $u = Point::pvect($ab, $vb) / $pab;
    $v = Point::pvect($ab, $va) / $pab;
    if (($u >= 0) and ($u < 1) and ($v >= 0) and ($v < 1))
      return [ 'pt'=>new Point([$a[0]->x()+$u*$va->x(), $a[0]->y()+$u*$va->y() ]),
               'u'=>$u, 'v'=>$v
             ];
    else
      return null;
  }
  static function test_interSegSeg() {
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
      echo "interSegSeg(",$a0,',',$a1,',',$b0,',',$b1,")->"; print_r(Point::interSegSeg([$a0,$a1],[$b0,$b1])); echo "\n";
    }
  }
};

if (basename(__FILE__)<>basename($_SERVER['PHP_SELF'])) return;
echo "<html><head><meta charset='UTF-8'><title>point</title></head><body><pre>";

//Point::test_interSegSeg();
//Point::test_projPointOnLine();
//Point::test_distancePointLine();
//Point::test_vectLength();
//Point::test_pscal();


if (1) { // initialisation à partir du WKT
  $pt = new Point('POINT(15 20)');
  $pt = new Point('15 20');
  echo "pt=$pt\n";
  //print_r($pt->bbox());
  echo "bbox=",$pt->bbox(),"\n";
  $pt2 = new Point('POINT(15 20)');
  $pt3 = new Point('POINT(-e-5 15 20)');
//print_r($pt);
  echo "pt3=$pt3\n";
  echo ($pt2==$pt ? "pt2==pt" : 'pt2<>pt'),"\n";

  $pt = new Point('POINT(15 20 99)');
  $pt = Geometry::fromWkt('POINT(15 20 99)');
  echo "pt=$pt\n";
  echo "GeoJSON=",json_encode($pt->geojson()),"\n";
  echo "WKT:",$pt->wkt(),"\n";
}

if (1) { // Initialisation à partir des coordonnées GeoJSON
  $pt = new Point([15,20]);
  $pt = new Point([15,20,30]);
  //$pt = new Point([15,20,30,50]);
  $pt = new Point([15,'20',30]);
  //$pt = new Point([15,'20','30xx']); // ereur
  $pt = new Point([15,'20']);
  echo "pt=$pt\n";
  echo "pt=",$pt->wkt(),"\n";
}