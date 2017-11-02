<?php
/*PhpDoc:
name:  linestring.inc.php
title: linestring.inc.php - définition d'une ligne brisée
includes: [ geometry.inc.php ]
functions:
classes:
journal: |
  31/10/2017:
  - ajout de la méthode simlify() implémentant l'algo de Douglas & Peucker
  21/10/2017:
  - première version
*/
require_once 'geometry.inc.php';
/*PhpDoc: classes
name:  LineString
title: Class LineString extends Geometry - Définition d'une LineString
methods:
doc: |
  protected $geom; // Pour un LineString: [Point]
*/
class LineString extends Geometry {
  static $verbose = 0;
  
  /*PhpDoc: methods
  name:  __construct
  title: __construct($param) - construction à partir d'un WKT ou d'un [Point] ou d'un [[num0,num1]]
  */
  function __construct($param) {
    // echo "LineString::__construct(param=$param)\n";
    if (self::$verbose)
      echo "LineString::__construct()\n";
    if (is_array($param)) {
      $this->geom = [];
      foreach ($param as $no => $point) {
        if (is_object($point) and (get_class($point)=='Point')) {
          //echo "Ajout de l'objet point $point";
          $this->geom[] = $point;
        }
        elseif (is_array($point)) {
          //echo "Ajout du point $point[0] $point[1]\n";
          $this->geom[] = new Point($point);
        }
        else {
          echo "point no $no = "; var_dump($point);
          throw new Exception("Elt $no du parametre non reconnu dans LineString::__construct()");
        }
      }
      return;
    }
    if (!is_string($param) or !preg_match('!^(LINESTRING\s*)?\(!', $param))
      throw new Exception("Parametre '$param' non reconnu dans LineString::__construct()");
    $this->geom = [];
    $pattern = '!^(LINESTRING\s*)?\(\s*([-\d.e]+)\s+([-\d.e]+)(\s+([-\d.e]+))?\s*,?!i';
    while (preg_match($pattern, $param, $matches)) {
      // echo "matches="; print_r($matches);
      // echo "x=$matches[2], y=$matches[3]",(isset($matches[5])?",z=$matches[5]":''),"\n";
      if (isset($matches[5]))
        $this->geom[] = new Point([$matches[2], $matches[3], $matches[5]]);
      else
        $this->geom[] = new Point([$matches[2], $matches[3]]);
      $param = preg_replace($pattern, '(', $param, 1);
    }
    if ($param<>'()')
      throw new Exception("Erreur dans LineString::__construct(), Reste param=$param");
  }
    
  /*PhpDoc: methods
  name:  __toString
  title: function __toString() - affiche la liste des points entourées par des ()
  doc: |
    Si parent::$precision est défini alors les coordonnées sont arrondies avant l'affichage
  */
  function __toString():string { return '('.implode(',',$this->geom).')'; }
  
  /*PhpDoc: methods
  name:  toString
  title: function toString($nbdigits=null) - affiche la liste des points entourées par des ()
  doc: |
    Si le paramètre nbdigits est défini alors les coordonnées sont arrondies avant l'affichage
  */
  function toString($nbdigits=null) {
    $str = '';
    foreach ($this->geom as $pt)
      $str .= ($str?',':'').$pt->toString($nbdigits);
    return '('.$str.')';
  }
  
  /*PhpDoc: methods
  name:  points
  title: function points($i=null) - retourne la liste des points composant la ligne ou un point particulier
  doc: |
    Sans paramètre, renvoit la liste de points
    Avec comme paramètre un entier positif ou nul renvoit le ième point
    Avec un paramètre négatif renvoit un point à partir de la fin : -1 pour le dernier, ...
  */
  function points(int $i=null) {
    // echo "LineString::points(i=$i)<br>\n";
    if ($i===null)
      return $this->geom;
    elseif ($i >= 0) {
      // echo "geom[$i]=",$this->geom[$i],"\n";
      return $this->geom[$i];
    }
    else
      return $this->geom[count($this->geom)+$i];
  }
  
  /*PhpDoc: methods
  name:  filter
  title: function filter($nbdigits) - renvoie un nouveau linestring filtré supprimant les points successifs identiques
  */
  function filter(int $nbdigits) {
    //    echo "LineString::filter(nbdigits=$nbdigits)<br>\n";
    //    echo "ls=$this<br>\n";
    $filtered = [];
    $ptprec = null;
    foreach ($this->geom as $pt) {
      // echo "pt=$pt<br>\n";
      $rounded = $pt->round($nbdigits);
      // echo "rounded=$rounded<br>\n";
      if (!$ptprec or ($rounded<>$ptprec)) {
        $filtered[] = $rounded;
        // echo "ajout de $rounded<br>\n";
      }
      $ptprec = $rounded;
    }
    return new LineString($filtered);
  }
  
  /*PhpDoc: methods
  name:  chgCoordSys
  title: function chgCoordSys($src, $dest) - créée un nouveau LineString en changeant le syst. de coord. de $src en $dest
  */
  function chgCoordSys($src, $dest) {
    $lsgeo = [];
    foreach ($this->geom as $pt)
      $lsgeo[] = $pt->chgCoordSys($src, $dest);
    return new LineString($lsgeo);
  }
  
  /*PhpDoc: methods
  name:  coordinates
  title: function coordinates() - renvoie un tableau de coordonnées
  */
  function coordinates() {
    $coordinates = [];
    foreach ($this->geom as $pt)
      $coordinates[] = $pt->coordinates();
    return $coordinates;
  }
  
  /*PhpDoc: methods
  name:  draw
  title: function draw($drawing, $stroke='black', $fill='transparent', $stroke_with=2) - dessine
  */
  function draw($drawing, $stroke='black', $fill='transparent', $stroke_with=2) {
    // echo "appel de LineString::draw()\n";
    return $drawing->drawLineString($this->geom, $stroke, $fill, $stroke_with);
  }
    
  /*PhpDoc: methods
  name:  isClosed
  title: function isClosed() - teste la fermeture de la polyligne
  */
  function isClosed() { return ($this->geom[0] == $this->geom[count($this->geom)-1]); }
  static function test_isClosed() {
    foreach ([
      'LINESTRING(0 0,100 100)',
      'LINESTRING(0 0,100 100,0 0)',
      ] as $lsstr) {
        $ls = new LineString($lsstr);
        echo $ls,($ls->isClosed()?" est fermée":" n'est pas fermée"),"\n";
    }
  }
  
  /*PhpDoc: methods
  name:  length
  title: function length() - renvoie la longueur de la polyligne
  */
  function length() {
    $length = 0;
    foreach ($this->geom as $p) {
      if (isset($prec)) {
        $v = substract($p, $prec);
        $length += $v->vectLength();
      }
      $prec = $p;
    }
    return $length;
  }
  static function test_length() {
    foreach ([
      'LINESTRING(0 0,100 100)',
      'LINESTRING(0 0,100 100,0 0)',
    ] as $lsstr) {
      $ls = new LineString($lsstr);
      echo "length($ls)=",$ls->length(),"\n";
    }
  }
  
  /*PhpDoc: methods
  name:  area
  title: function area() - renvoie la surface dans le système de coordonnées courant
  */
  function area():float {
    $area = 0.0;
    $n = count($this->geom);
    $pt0 = $this->geom[0];
    for ($i=1; $i<$n-1; $i++) {
      $area += Point::pvect(Point::substract($pt0,$this->geom[$i]), Point::substract($pt0,$this->geom[$i+1]));
    }
    return $area/2;
  }
  static function test_area() {
    foreach ([
      'LINESTRING(0 0,1 0,0 1,0 0)',
      'LINESTRING(0 0,1 0,1 1,0 1,0 0)',
    ] as $lsstr) {
      $ls = new LineString($lsstr);
      echo "area($ls)=",$ls->area(),"\n";
    }
  }
  
  /*PhpDoc: methods
  name:  distancePointPointList
  title: function distancePointPointList(Point $pt) - distance minimum d'une liste de points à un point
  doc : |
    Retourne la distance  et le no du point qui correspond à la distance minimum
  */
  function distancePointPointList(Point $pt) {
    for ($i=0; $i<count($this->geom); $i++) {
      $v = Point::substract($pt, $this->geom[$i]);
      $d = $v->vectLength();
      if (!isset($dist) or ($d < $dist)) {
        $dist = $d;
        $n = $i;
      }
    }
    return ['dist'=>$dist, 'n'=>$n];
  }
  static function test_distancePointPointList() {
    foreach ([
      'LINESTRING(0 0,1 1,1 0,0 1)',
      'LINESTRING(1 1,1 0,0 1)',
    ] as $lsstr) {
      $ls = new LineString($lsstr);
      echo "distancePointPointList($ls, (0,0))=";
      print_r($ls->distancePointPointList(new Point('POINT(0 0)')));
    }
  }
  
  /*PhpDoc: methods
  name:  area
  title: function distancePointLineString(Point $pt) - distance minimum de la ligne brisée au point pt
  doc : |
    Retourne la distance et le point qui correspond à la distance minimum
  */
  function distancePointLineString(Point $pt) {
    $p0 = $this->geom[0];
    $p0pt = Point::substract($p0,$pt);
    $dmin = $p0pt->vectLength();
    $resPt = $p0;
    for($i=1; $i<count($this->geom); $i++) {
      $a = $this->geom[$i-1];
      $b = $this->geom[$i];
      $u = $pt->projPointOnLine($a, $b);
// Si le point projeté est sur le segment, on considère la distance
      if (($u > 0) and ($u < 1)) {
        $distPointToLine = $pt->distancePointLine($a, $b);
        if ($distPointToLine < $dmin) {
          $dmin = $distPointToLine;
          $resPt = Point::add($a, Point::scalMult($u, Point::substract($a,$b)));
        }
      }
      $bp = Point::substract($b, $pt);
      $dist = $bp->vectLength();
      if ($dist < $dmin) {
        $dmin = $dist;
        $resPt = $b;
      }
    }
    return ['dmin'=>$dmin, 'pt'=>$resPt];
  }
  static function test_distancePointLineString() {
    $p0 = new Point('POINT(0 0)');
    foreach ([
      'LINESTRING(0 0,1 1,1 0,0 1)',
      'LINESTRING(1 1,1 0,0 1)',
    ] as $lsstr) {
      $ls = new LineString($lsstr);
      echo "${ls}->distancePointLineString((0,0))=";
      print_r($ls->distancePointLineString($p0));
    }
  }
  
  /*PhpDoc: methods
  name:  simplify
  title: function simplify(float $distTreshold) - simplifie la géométrie de la ligne brisée
  doc : |
    Algorithme de Douglas & Peucker
    Ne modifie pas l'objet courant
    Retourne un nouvel objet LineString simplifié
    ou null si la ligne est fermée et que la distance max est inférieur au seuil
  */
  function simplify(float $distTreshold) {
    if (count($this->points()) < 3)
      return $this;
    // cas d'une ligne ouverte
    if (!$this->isClosed()) {
      $pt0 = $this->points(0);
      $ptn = $this->points(-1);
      $distmax = 0; // distance max
      $nptmax = -1; // num du point pour la distance max
      foreach($this->points() as $n => $pt) {
        $dist = abs($pt->distancePointLine($pt0, $ptn));
        if ($dist > $distmax) {
          $distmax = $dist;
          $nptmax = $n;
        }
      }
      if ($distmax < $distTreshold)
        return new LineString([$pt0,$ptn]);
      $ls1 = new LineString(array_slice($this->points(), 0, $nptmax));
      $ls1 = $ls1->simplify($distTreshold);
      $ls2 = new LineString(array_slice($this->points(), $nptmax));
      $ls2 = $ls2->simplify($distTreshold);
      $ls = new LineString(array_merge($ls1->points(),array_slice($ls2->points(),1)));
      return $ls;
    }
    // cas d'une ligne fermée **** A FAIRE ****
    else {
      $pt0 = $this->points(0);
      $distmax = 0; // distance max
      $nptmax = -1; // num du point pour la distance max
      foreach($this->points() as $n => $pt) {
        $dist = $pt->distance($pt0);
        if ($dist > $distmax) {
          $distmax = $dist;
          $nptmax = $n;
        }
      }
      if ($distmax < $distTreshold)
        return null;
      $ls1 = new LineString(array_slice($this->points(), 0, $nptmax));
      $ls1 = $ls1->simplify($distTreshold);
      $ls2 = new LineString(array_slice($this->points(), $nptmax));
      $ls2 = $ls2->simplify($distTreshold);
      $ls = new LineString(array_merge($ls1->points(),array_slice($ls2->points(),1)));
      return $ls;
    }
  }
  
  /*PhpDoc: methods
  name:  pointInPolygon
  title: pointInPolygon(Point $pt) - teste si un point pt est dans le polygone
  doc: |
    Code de référence en C:
    int pnpoly(int npol, float *xp, float *yp, float x, float y)
    { int i, j, c = 0;
      for (i = 0, j = npol-1; i < npol; j = i++) {
        if ((((yp[i]<=y) && (y<yp[j])) ||
             ((yp[j]<=y) && (y<yp[i]))) &&
            (x < (xp[j] - xp[i]) * (y - yp[i]) / (yp[j] - yp[i]) + xp[i]))
          c = !c;
      }
      return c;
    }
  */
  function pointInPolygon(Point $pt) {
    $c = false;
    $j = count($this->geom) - 1;
    for($i=0; $i<count($this->geom); $i++) {
      if (((($this->geom[$i]->y() <= $pt->y()) and ($pt->y() < $this->geom[$j]->y()))
          or (($this->geom[$j]->y() <= $pt->y()) and ($pt->y() < $this->geom[$i]->y())))
        and (($pt->x() - $this->geom[$i]->x()) < ($this->geom[$j]->x() - $this->geom[$i]->x())
                 * ($pt->y() - $this->geom[$i]->y()) / ($this->geom[$j]->y() - $this->geom[$i]->y()))) {
        $c = !$c;
      }
      $j = $i;
    }
    return $c;
  }
  static function test_pointInPolygon() {
    $p0 = new Point('POINT(0 0)');
    foreach ([
      'LINESTRING(1 0,0 1,-1 0,0 -1)',
      'LINESTRING(1 1,-1 1,-1 -1,1 -1)',
      'LINESTRING(1 1,-1 1,-1 -1,1 -1,1 1)',
      'LINESTRING(1 1,2 1,2 2,1 2)',
    ] as $lsstr) {
      $ls = new LineString($lsstr);
      echo "${ls}->pointInPolygon(($p0))=",($ls->pointInPolygon($p0)?'true':'false'),"\n";
    }
  }
};


if (basename(__FILE__)<>basename($_SERVER['PHP_SELF'])) return;
echo "<html><head><meta charset='UTF-8'><title>linestring</title></head><body><pre>";


//LineString::test_pointInPolygon();
//LineString::test_distancePointLineString();
//LineString::test_distancePointPointList();
//LineString::test_area();
//LineString::test_length();
//LineString::test_isClosed();

if (1) {
  foreach ([
    'LINESTRING(30 30,200 200)',
    [[10,10],[20,20]],
    [[10,10],[20,20,60]],
    [new Point([0,0]), new Point([5,10])],
  ] as $param) {
    $ls = new LineString($param);
    if (is_array($param)) {
      echo "new LineString("; print_r($param); echo " -> $ls\n";
      //print_r($ls);
    }
    else
      echo "new LineString($param) -> $ls\n";
    echo "WKT:",$ls->wkt(),"\n";
  }
}

if (0) {
  echo "Test de la distance entre 2 rectangles\n";
  $ls0 = new LineString('LINESTRING(0 0,100 100)');
  $r0 = $ls0->bbox();
  echo "r0=$r0\n";
  foreach ([
      'LINESTRING(30 30,200 200)',  // cas 1 : X & Y intersectent -> 0
      'LINESTRING(130 70,200 200)', // cas 2 : Y intersectent mais pas X
      'LINESTRING(70 -100,200 -50)', // cas 3 : X intersectent mais pas Y
      'LINESTRING(170 170,200 250)', // cas 4a : r1 au NE de r0
      'LINESTRING(101 101,200 250)', // cas 4a : r1 au NE de r0
      'LINESTRING(170 -170,200 -250)', // cas 4b : r1 au SE de r0
      'LINESTRING(101 -1,101 -100)', // cas 4b : r1 au SE de r0
      'LINESTRING(-170 -170,-200 -250)', // cas 4c : r1 au SW de r0
      'LINESTRING(-101 -170,-1 -1)', // cas 4c : r1 au SW de r0
      'LINESTRING(-170 170,-200 250)', // cas 4d : r1 au NW de r0
      'LINESTRING(-170 170,-1 101)', // cas 4d : r1 au NW de r0
    ] as $lsstr) {
      $ls1 = new LineString($lsstr);
      $r1 = $ls1->bbox();
      echo "r1=$r1 -> d=",$r0->mindist($r1),"\n";
  }
}