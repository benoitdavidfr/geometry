<?php
/*PhpDoc:
name:  bbox.inc.php
title: bbox.inc.php - définition d'une boite englobante (BBox)
functions:
classes:
doc: |
  Fonctions de gestion des boites englobantes
doc: |
journal: |
  21/10/2017:
  - première version
*/

/*PhpDoc: functions
name:  square
title: function square($x) { return $x*$x; }
*/
if (!function_exists('square')) {
  function square($x) { return $x*$x; }
}

/*PhpDoc: classes
name:  BBox
title: class BBox - Boite englobante
methods:
doc: |
  Une boite englobante (traduction de bounding box) est définie par les coordonnées min et max d'un ensemble quelconque de points.
  La méthode principale est bound(Point) qui ajoute un point à la boite.
  On a de plus 2 méthodes intéressantes:
  - le calcul de la taille de la boite
  - le calcul de la distance minimum entre les points des 2 boites.
  Une boite peut ne contenir aucun point ; dans ce cas min et max contiennent la valeur null. On dit qu'elle est indéterminée.
  Si une boite n'est pas indéterminée alors min et max contiennent chacun un point.
*/
class BBox {
  private $min=null; // null ou Point
  private $max=null; // null ou Point
  
  /*PhpDoc: methods
  name:  __construct
  title: function __construct($param=null) - initialise une boite en fonction du paramètre
  doc: |
    Sans paramètre la boite est initialisée indéterminée
    Le paramètre optionnel est une chaine de la forme "nombre,nombre" ou "nombre,nombre,nombre,nombre"
  */
  function __construct(string $param=null) {
    if ($param===null)
      return;
    $coord = explode(',', $param);
    //var_dump($coord);
    if ((count($coord)>=2) and is_numeric($coord[0]) and is_numeric($coord[1]))
      $this->bound(new Point([$coord[0]+0, $coord[1]+0]));
    if ((count($coord)>=4) and is_numeric($coord[2]) and is_numeric($coord[3]))
      $this->bound(new Point([$coord[2]+0, $coord[3]+0]));
  }
  
  // Renvoie vrai ssi la Bbox est indéterminée
  function undetermined() { return $this->min===null; }
  
  /*PhpDoc: methods
  name:  __toString
  title: function __toString() - affiche les 2 points entourées de []
  */
  function __toString() { return ($this->undetermined() ? '[]' : '['.$this->min.','.$this->max.']'); }
  
  /*PhpDoc: methods
  name:  min
  title: function min() - renvoie le point min
  */
  function min() { return $this->min; }
  
  /*PhpDoc: methods
  name:  max
  title: function max() - renvoie le point max
  */
  function max() { return $this->max; }
  
  /*PhpDoc: methods
  name:  bound
  title: function bound(Point $pt) - agrandit la boite pour contenir le point et la renvoie
  */
  function bound(Point $pt): BBox {
    if ($this->undetermined()) {
      $this->min = $pt;
      $this->max = $pt;
    } else {
      $this->min = new Point([ min($this->min->x(), $pt->x()), min($this->min->y(), $pt->y())]);
      $this->max = new Point([ max($this->max->x(), $pt->x()), max($this->max->y(), $pt->y())]);
    }
    return $this;
  }
  
  /*PhpDoc: methods
  name:  union
  title: function union(BBox $bbox) - Agrandit la boite courante pour contenir la boite en paramètre et la renvoit
  */
  function union(BBox $bbox) {
    if ($bbox->min===null)
      return $this;
    $this->bound($bbox->min);
    $this->bound($bbox->max);
    return $this;
  }
  
  /*PhpDoc: methods
  name:  size
  title: function size() - longueur de la diagonale
  */
  function size():float { return ($this->undetermined() ? null : $this->min->distance($this->max)); }
  
  /*PhpDoc: methods
  name:  area
  title: function area() - surface
  */
  function area():float {
    return ($this->undetermined() ? null
              : ($this->max->x() - $this->min->x()) * ($this->max->y() - $this->min->y()));
  }
  
  /*PhpDoc: methods
  name:  mindist
  title: function mindist($r1) - calcul du minimum les distances entre les points de 2 boites
  */
  function mindist(Bbox $r1):float {
    $debug = 0;
    if (($this->max->x() >= $r1->min->x()) and ($this->min->x() <= $r1->max->x())) { // Les X s'intersectent
      if (($this->max->y() >= $r1->min->y()) and ($this->min->y() <= $r1->max->y())) { // Les Y s'intersectent
        if ($debug) echo "cas 1 : les 2 rectangles s'intersectent\n";
        return 0;                                                                    // cas 1 : les 2 rectangles s'intersectent
      } else {                                                                          // Les Y ne s'intersectent pas
        if ($debug) echo "cas 3 : X s'intersectent mais pas les Y \n";
        return min (abs($this->max->y() - $r1->min->y()), abs($r1->max->y() - $this->min->y())); // cas 3: X s'intersectent mais pas les Y 
      }
    } elseif (($this->max->y() >= $r1->min->y()) and ($this->min->y() <= $r1->max->y())) { // Les Y s'intersectent
        if ($debug) echo "cas 2: Y s'intersectent mais pas les X\n";
        return min (abs($this->max->x() - $r1->min->x()), abs($r1->max->x() - $this->min->x()));  // cas 2: Y s'intersectent mais pas les X
    } elseif ($this->min->x() < $r1->min->x()) {
      if ($this->min->y() < $r1->min->y()) { // cas 4a : r1 au NE de this
        if ($debug) echo "cas 4a : r1 au NE de this\n";
        return $this->max->distance($r1->min);
      } else { // cas 4b : r1 au SE de this
        if ($debug) echo "cas 4b : r1 au SE de this\n";
        return sqrt(square($r1->min->x() - $this->max->x()) + square($this->min->y() - $r1->max->y()));
      }
    } else {
      if ($this->min->y() < $r1->min->y()) { // cas 4d : r1 au NW de this
        if ($debug) echo "cas 4d : r1 au NW de this\n";
        return sqrt(square($r1->max->x() - $this->min->x()) + square($this->max->y() - $r1->min->y()));
      } else { // cas 4c : r1 au SW de this
        if ($debug) echo "cas 4c : r1 au SW de this\n";
        return $this->min->distance($r1->max);
      }
    }
  }
  
  /*PhpDoc: methods
  name:  inters
  title: function inters($bbox1) - calcul du rapport de l'intersection des 2 boites sur le maximum des surfaces des 2 boites
  doc: |
    Permet d'estimer si 2 boites correspondent
  */
  function inters($bbox1):float {
    // echo "$this -> BBox::inters($bbox1)<br>\n";
    $xmin = max($bbox1->min()->x(),$this->min()->x());
    $ymin = max($bbox1->min()->y(),$this->min()->y());
    $xmax = min($bbox1->max()->x(),$this->max()->x());
    $ymax = min($bbox1->max()->y(),$this->max()->y());
    if (($xmax < $xmin) or ($ymax < $ymin))
      return 0;
    // echo "area=",$this->area(),", ",$bbox1->area(),"<br>\n";
    return (($xmax-$xmin)*($ymax-$ymin)) / max($bbox1->area(), $this->area());
  }
  
  /*PhpDoc: methods
  name:  asPolygon
  title: "function asPolygon(): Polygon - renvoie un Polygon"
  */
  function asPolygon(): Polygon {
    $ls = [
      $this->min(),
      new Point([$this->min()->x(), $this->max()->y()]),
      $this->max(),
      new Point([$this->max()->x(), $this->min()->y()]),
      $this->min(),
    ];
    $ls = new LineString($ls);
    return new Polygon([$ls]);
  }
  
  /*PhpDoc: methods
  name:  asArray
  title: function asArray() - renvoie [xmin, ymin, xmax, ymax] ou null
  */
  function asArray(): array {
    if ($this->undetermined())
      return null;
    else
      return [$this->min()->x(), $this->min()->y(), $this->max()->x(), $this->max()->y()];
  }
  
  /*PhpDoc: methods
  name:  chgCoordSys
  title: function chgCoordSys($src, $dest) - crée un nouveau BBox en changeant le syst. de coord. de $src en $dest
  */
  function chgCoordSys(string $src, string $dest) {
    if ($this->min===null)
      return $this;
    $min = $this->min->chgCoordSys($src, $dest);
    $max = $this->max->chgCoordSys($src, $dest);
    return new Bbox($min, $max);
  }
  
  /*PhpDoc: methods
  name:  pointInBBox
  title: function pointInBBox(Point $pt) - test si un point est dans un BBox
  doc: |
    Par convention les points sur une des frontières Ouest ou Sud appartiennent alors que les autres n'appartiennent pas
  */
  function pointInBBox(Point $pt) {
    return (($pt->x() >= $this->min->x()) and ($pt->y() >= $this->min->y())
        and ($pt->x() < $this->max->x()) and ($pt->y() < $this->max->y()));
  }
  
  /*PhpDoc: methods
  name:  edges
  title: function edges() - retourne les 4 côtés sous la forme de couple de points
  */
  function edges(): array {
    $xmin = $this->min->x();
    $ymin = $this->min->y();
    $xmax = $this->max->x();
    $ymax = $this->max->y();
    return [
      'S' => [ $this->min(), new Point(['x'=>$xmax, 'y'=>$ymin]) ],
      'E' => [ new Point(['x'=>$xmax, 'y'=>$ymin]), $this->max() ],
      'N' => [ $this->max(), new Point(['x'=>$xmin, 'y'=>$ymax]) ],
      'W' => [ new Point(['x'=>$xmin, 'y'=>$ymax]), $this->min() ],
    ];
  }
  
  /*PhpDoc: methods
  name:  corner
  title: "function corner($no) - retourne un des 4 coins : 0=>SW, 1=>SE, ..."
  */
  function corner($no): Point {
    switch($no) {
      case 0 : return $this->min(); // coin SW
      case 1 : return new Point(['x'=>$this->max->x(), 'y'=>$this->min->y()]); // coin SE
      case 2 : return $this->max(); // coin NE
      case 3 : return new Point(['x'=>$this->min->x(), 'y'=>$this->max->y()]); // coin NW
      default:
        throw new Exception("BBox::corner(no=$no), no incorrect");
    }
  }
};


if (basename(__FILE__)<>basename($_SERVER['PHP_SELF'])) return;
echo "<html><head><meta charset='UTF-8'><title>bbox</title></head><body><pre>";
