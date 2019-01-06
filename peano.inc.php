<?php
/*PhpDoc:
name:  peano.inc.php
title: peano.inc.php - définition d'une clé de Péano permettant de construire un index spatial
functions:
classes:
doc: |
  Une clé de Péano est une chaine identifiant un noeud d'un QuadTree
  La classe Peano porte les différentes méthodes, notamment
    - g4() qui produit les 4 Péano à partir d'un rectangle englobant
journal: |
  6/1/2019:
  - première version
*/

// vrai ssi les 2 intervalles s'intersectent
function intervalIntersects(array $i1, array $i2): bool {
  return (($i2[0] < $i1[1]) && ($i2[1] > $i1[0]));
}

// vrai ssi les 2 rectangles s'intesectent
function bboxIntersects(array $b1, array $b2): bool {
  return intervalIntersects([$b1['W'],$b1['E']], [$b2['W'],$b2['E']])
      && intervalIntersects([$b1['S'],$b1['N']], [$b2['S'],$b2['N']]);
}

/*PhpDoc: classes
name: Peano
title: class Peano
doc: |
  Un objet correspond à une clé de Péano
  Chaque clé est codée commue une chaine avec un caractère 1 à 4 par niveau
  Les quadrants sont numérotés 1 à 4 de la manière suivante:
        Lat
        ^
      3 | 4
     ---+---> lon
      1 | 2
*/
class Peano {
  const LonMax = 180.0;
  const LatMax = 90.0;
  private $str; // une chaine composée de n caractères '1','2','3' ou '4'
  
  // Crée un Péano soit en définissant sa chaine soit un point et le niveau souhaité
  function __construct($prm1='', int $level=-1) {
    if (is_string($prm1)) {
      for($i=0; $i<strlen($prm1); $i++)
        if (!in_array(substr($prm1, $i, 1), ['1','2','3','4']))
          throw new Exception("Erreur, clé de Péano $prm1 incorrecte");
      $this->str = $prm1;
    }
    elseif (is_array($prm1) && ($level >= 0)) {
      $pt = $prm1;
      $peano = ''; // la clé de Péano initiale
      $halfSize = [self::LonMax, self::LatMax]; // la demi-taille initiale en Long Lat
      $center = [0, 0]; // le point central en Lon Lat
      for($i=0; $i < $level; $i++) {
        $p = ($pt[1] < $center[1]) ? (($pt[0] < $center[0]) ?'1' : '2') : (($pt[0] < $center[0]) ? '3' : '4'); 
        $peano .= $p; 
        // demi-côté du niveau suivant
        $halfSize[0] /= 2; $halfSize[1] /= 2;
        // centre du niveau suivant
        switch($p) {
          case '1': $center = [ $center[0] - $halfSize[0], $center[1] - $halfSize[1] ]; break;
          case '2': $center = [ $center[0] + $halfSize[0], $center[1] - $halfSize[1] ]; break;
          case '3': $center = [ $center[0] - $halfSize[0], $center[1] + $halfSize[1] ]; break;
          case '4': $center = [ $center[0] + $halfSize[0], $center[1] + $halfSize[1] ]; break;
        }
        //echo "itération $i, center=",json_encode($center),", peano=$peano\n";
      }
      $this->str = $peano;
    }
    else
      throw new Exception("Erreur des types des paramètres de création d'un Péano");
  }
    
  function __toString(): string { return $this->str; }
  
  // génère le mbr en coord géo. correspondant à la clé
  function mbr(): array {
    $sw = [- self::LonMax, - self::LatMax]; // le point SW en Lon Lat
    $size = [self::LonMax*2, self::LatMax*2]; // la taille initiale en Long Lat
    for($i=0; $i < strlen($this->str); $i++) {
      $size[0] /= 2;
      $size[1] /= 2;
      switch(substr($this->str, $i, 1)) {
        case '1': break;
        case '2': $sw[0] += $size[0]; break;
        case '3': $sw[1] += $size[1]; break;
        case '4': $sw[0] += $size[0]; $sw[1] += $size[1]; break;
      }
    }
    return ['W'=> $sw[0], 'S'=> $sw[1], 'E'=> $sw[0]+$size[0], 'N'=> $sw[1]+$size[1] ];
  }
  static function test_mbr() { // Test unitaire 
    foreach (['', '1', '2', '3', '4', '1324', '13'] as $str) {
      $p = new Peano($str);
      echo "p=",$p,", mbr=",json_encode($p->mbr()),"\n";
    }
  }
  
  function lastDigit(): string {
    if (strlen($this->str)==0)
      throw new Exception("Erreur de lastDigit sur le Peano Monde");
    else
      return substr($this->str, -1);
  }
    
  function parent(): Peano {
    if (strlen($this->str)==0)
      throw new Exception("Erreur de parent() sur le Peano Monde");
    else
      return new Peano(substr($this->str, 0, strlen($this->str)-1));
  }
  
  function child(string $c) {
    if (!in_array($c, ['1','2','3','4']))
      throw new Exception("Erreur de child sur $c");
    return new Peano($this->str.$c);
  }
  
  // le carré de même niveau au Sud
  function s(): Peano {
    switch($this->lastDigit()) {
      case '1': return $this->parent()->s()->child('3');
      case '2': return $this->parent()->s()->child('4');
      case '3': return $this->parent()->child('1');
      case '4': return $this->parent()->child('2');
    }
  }
  
  // le carré de même niveau à l'West
  function w(): Peano {
    switch($this->lastDigit()) {
      case '1': return $this->parent()->w()->child('2');
      case '2': return $this->parent()->child('1');
      case '3': return $this->parent()->w()->child('4');
      case '4': return $this->parent()->child('3');
    }
  }
  /*
  // le carré de même niveau au Nord
  function n(): Peano {
    switch($this->lastDigit()) {
      case '1': return $this->parent()->child('3');
      case '2': return $this->parent()->child('4');
      case '3': return $this->parent()->n()->child('1');
      case '4': return $this->parent()->n()->child('2');
    }
  }
  
  // le carré de même niveau à l'Est
  function e(): Peano {
    switch($this->lastDigit()) {
      case '1': return $this->parent()->child('2');
      case '2': return $this->parent()->e()->child('1');
      case '3': return $this->parent()->child('4');
      case '4': return $this->parent()->e()->child('3');
    }
  }
  */
  static function test_wsenpc() { // tests unitaires 
    $w14 = new Peano('41');
    echo "w14=",json_encode($w14->mbr()),"\n";
    echo "w14->parent()=",json_encode($w14->parent()->mbr()),"\n";
    //echo "w14->n()=",json_encode($w14->n()->mbr()),"\n";
    echo "w14->s()=",json_encode($w14->s()->mbr()),"\n";
    echo "w14->w()=",json_encode($w14->w()->mbr()),"\n";
    //echo "w14->e()=",json_encode($w14->e()->mbr()),"\n";
    echo "w14->child(1)=",json_encode($w14->child('1')->mbr()),"\n";
  }
  
  // génère les 4 clés de Péano correspondant à un bbox
  // bbox sous la forme [west, south, east, north] ou ['W'=> west, 'S'=> south, 'E'=> east, 'N'=> north]
  static function g4(array $bbox): array {
    //echo "find4Peano(bbox=",json_encode($bbox),")\n";
    if (array_keys($bbox) == [0, 1, 2, 3])
      $bbox = ['W'=> $bbox[0], 'S'=> $bbox[1], 'E'=> $bbox[2], 'N'=> $bbox[3]];
    // je cherche le niveau des clés
    $n = floor(log(min(self::LonMax / ($bbox['E']-$bbox['W']), self::LatMax / ($bbox['N']-$bbox['S'])), 2));
    //echo "n=$n\n";
    //$p = new self('111111'); echo "p=",$p,", bbox=",json_encode($p->bbox()),"\n";
    // je cherche le point dans le rectangle à ce niveau
    $size = [ self::LonMax / 2 ** ($n-1), self::LatMax / 2 ** ($n-1)]; // taille du la cellule de la grille
    $pt = [ ceil($bbox['W'] / $size[0]) * $size[0], ceil($bbox['S'] / $size[1]) * $size[1] ];
    // point du niveau adhoc dans le bbox
    //echo "pt=",json_encode($pt),"\n";
    $peano = new self($pt, $n);
    //echo "peano(pt)=$peano\n";
    // si les 4 peano ont le même père, il vaut mieux prendre le pêre
    if ($peano->s()->w()->parent() == $peano->parent())
      return [ $peano->parent() ];
    else {
      $r = [];
      foreach ([ $peano->s()->w(), $peano->s(), $peano->w(), $peano ] as $p) {
        if (bboxIntersects($bbox, $p->mbr()))
          $r[] = $p;
      }
      return $r;
    }
  }
  static function test_g4() { // test unitaire de g4()
    foreach ([
        ['W'=> 5, 'S'=> 47, 'E'=> 6, 'N'=> 48],
        ['W'=> 1, 'S'=> 47, 'E'=> 1.1, 'N'=> 47.1],
        [-0.1, 47, 0.1, 47.1],
      ] as $bbox) {
      echo "<br>g4(bbox=",json_encode($bbox),")\n";
      foreach (Peano::g4($bbox) as $peano)
        echo "peano=$peano, bbox=",json_encode($peano->mbr()),"\n";
    }
  }
};

if (basename(__FILE__)<>basename($_SERVER['PHP_SELF'])) return;
echo "<html><head><meta charset='UTF-8'><title>peano</title></head><body><pre>";

if (!isset($_GET['test'])) {
  echo <<<EOT
</pre><h2>Test de la classe Peano</h2><ul>
  <li><a href='?test=test_mbr'>création et mbr()</a>
  <li><a href='?test=test_wsenpc'>4 cardinaux, parent() et child()</a>
  <li><a href='?test=test_g4'>g4</a>
</ul>\n
EOT;
  die();
}
else {
  $test = $_GET['test'];
  Peano::$test();
}
