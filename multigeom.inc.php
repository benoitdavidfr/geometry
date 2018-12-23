<?php
/*PhpDoc:
name:  multigeom.inc.php
title: multigeom.inc.php - définition d'une collection de géométries élémentaires
includes: [ geometry.inc.php ]
classes:
doc: |
journal: |
  8/8/2018:
    ajout proj2D()
  22/10/2017:
    création
*/
require_once __DIR__.'/geometry.inc.php';

/*PhpDoc: classes
name:  MultiGeom
title: abstract class MultiGeom extends Geometry - Liste de géométries élémentaires homogènes
methods:
*/
abstract class MultiGeom extends Geometry {
  /*PhpDoc: methods
  name:  __toString
  title: function __toString() - génère une chaine de caractère correspondant au WKT sans l'entete
  */
  function __toString() {
    $str = '';
    foreach($this->geom as $elt)
      $str .= ($str?',':'').$elt;
    return '('.$str.')';
  }
  
  /*PhpDoc: methods
  name:  coordinates
  title: function coordinates() - renvoie un tableau de coordonnées
  */
  function coordinates() {
    $coordinates = [];
    foreach ($this->geom as $elt)
      $coordinates[] = $elt->coordinates();
    return $coordinates;
  }
  
  /*PhpDoc: methods
  name:  isValid
  title: "function isValid(): bool - renvoie booléen"
  */
  function isValid(): bool {
    if (count($this->geom) == 0)
      return false;
    foreach ($this->geom as $elt) {
      if (!$elt->isValid())
        return false;
    }
    return true;
  }
  
  /*PhpDoc: methods
  name:  proj2D
  title: "function proj2D(): MultiGeom - renvoie un nouveau 2D"
  */
  function proj2D(): MultiGeom {
    $coll = [];
    foreach ($this->geom as $elt) {
      $coll[] = $elt->proj2D();
    }
    $class = get_called_class();
    return new $class ($coll);
  }

  /*PhpDoc: methods
  name:  filter
  title: "function filter(int $nbdigits): MultiGeom - renvoie une nouvelle collection dont chaque élément est filtré supprimant les points successifs identiques"
  doc: |
    Les coordonnées sont arrondies avec $nbdigits chiffres significatifs
    Un filtre sans arrondi n'a pas de sens.
  */
  function filter(int $nbdigits): MultiGeom {
    $coll = [];
    foreach ($this->geom as $elt) {
      $filtered = $elt->filter($nbdigits);
      if ($filtered->isValid())
        $coll[] = $filtered;
    }
    $class = get_called_class();
    return new $class ($coll);
  }
  
  /*PhpDoc: methods
  name:  chgCoordSys
  title: "function chgCoordSys(string $src, string $dest): MultiGeom - créée un nouveau MultiGeom en changeant le syst. de coord. de $src en $dest"
  */
  function chgCoordSys(string $src, string $dest): MultiGeom {
    $coll = [];
    foreach ($this->geom as $elt)
      $coll[] = $elt->chgCoordSys($src, $dest);
    $class = get_called_class();
    return new $class ($coll);
  }
};


if (basename(__FILE__)<>basename($_SERVER['PHP_SELF'])) return;
echo "<html><head><meta charset='UTF-8'><title>multigeom</title></head><body><pre>";
