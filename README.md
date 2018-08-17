# Package Php de gestion de la géométrie

Ce package Php implémente les primitives géométriques [GeoJSON](https://tools.ietf.org/html/rfc7946)
et [OGC WKT](https://en.wikipedia.org/wiki/Well-known_text) sous la forme de classes Php.  
Il comprend une classe abstraite Geometry ainsi que les 7 sous-classes suivantes correspondant
aux primitives géométriques :
  
  - 3 types géométriques élémentaires: Point, LineString et Polygon
  - 3 types de collections homogènes de géométries élémentaires: MultiPoint, MultiLineString et MultiPolygon
  - 1 type de collection hétérogène de géométries élémentaires: GeometryCollection

Il comprend en outre :

  - la classe CoordSys qui implémente des changements de coordonnées simples,
  - la classe BBox qui implémente les boites englobantes,
  - la classe statique Wkt2GeoJson qui implémente la transformation d'une primitive WKT en GeoJSON
    de manière optimisée.

### La classe abstraite Geometry
La classe abstraite Geometry permet de gérer a minima une géométrie sans avoir à connaître son type.  

#### Méthodes
  
  - 2 méthodes statiques de construction d'objet à partir respectivement d'un WKT ou d'un GeoJSON :
      - `static fromWkt(string $wkt, int $nbdigits=null): Geometry` - crée une géométrie à partir d'un WKT
      - `static fromGeoJSON(array $geometry, int $nbdigits=null): Geometry` - crée une géométrie
        à partir d'une géométrie GeoJSON
  - la méthode générique `geojson(): array` qui génère comme Array Php qui, encodé en JSON,
    correspondra à la geometry GeoJSON
  - la méthode `wkt(): string` qui fabrique une représentations WKT
  - la méthode `bbox(): BBox` qui fabrique le BBox de l'objet
  
### La classe CoordSys
La classe statique CoordSys implémente les changements simples entre systèmes de coordonnées
définis sur l'elliposide IAG_GRS_1980.
Les systèmes de coordonnées suivants sont gérés :

  - 'geo' pour coordonnées géographiques WGS 84 en degrés décimaux dans l'ordre longitude, lattitude
  - 'L93' pour Lambert 93
  - 'WM' pour web Mercator
  - UTM-ddX où dd est le numéro de zone et X est soit 'N', soit 'S'

#### Méthodes

  - `static detect($opengiswkt)` - detecte le système de coord exprimé en Well Known Text d'OpenGIS
  - `static chg(string $src, string $dest, number $x, number $y): array` - chg de syst. de coord. de $src vers $dest
    renvoie un tableau de 2 coordonnées. Les couples acceptés sont 'geo',proj et proj,'geo'
  
### La classe Point
La classe Point implémente la primtive Point en 2D ou 3D et dans certains cas à un vecteur.

#### Méthodes

  - `__construct($param)` - construction à partir d'un WKT ou d'un [number, number {, number}]
  - `x(): number` - accès à la première coordonnée
  - `y(): number` - accès à la seconde coordonnée
  - `isValid(): bool` - renvoie vrai ssi l'objet est valide
  - `round(int $nbdigits): Point` - arrondit un point avec le nb de chiffres indiqués
  - `filter(int $nbdigits): Point` - synonyme de round()
  - `proj2D(): Point` - projection 2D, supprime l'éventuelle 3ème coordonnée
  -  `__toString(): string` - affichage des coordonnées séparées par un blanc
  - `wkt($nbdigits=null): string` - retourne la chaine WKT
  -  `bbox(): BBox` - calcule la bbox
  - `drawCircle(Drawing $drawing, $r, $fill): void` - dessine un cercle centré sur le point de rayon r
    dans la couleur indiquée
  - `distance(): float` - retourne la distance euclidienne entre 2 points
  - `chgCoordSys($src, $dest): Point` - crée un nouveau Point en changeant le syst. de coord. de $src en $dest
  - `coordinates(): array` - renvoie les coordonnées sous la forme [ number, number {, number} ]
  - `vectLength(): float` - renvoie la norme du vecteur
  - `static substract(Point $p0, Point $p): Point` - différence $p - $p0
  - `static add(Point $a, Point $b): Point` - somme $a + $b
  - `static scalMult($u, Point $v): Point` - multiplication du vecteur $v par le scalaire $u
  - `static pvect(Point $va, Point $vb): float` - produit vectoriel
  - `static pscal(Point $va, Point $vb): float` - produit scalaire
  - `distancePointLine(Point $a, Point $b): float` - distance signée du point courant à la droite définie
    par les 2 points  
    La distance est positive si le point est à gauche de la droite AB et négative s'il est à droite
  -  `projPointOnLine(Point $a, Point $b): float` - projection du point sur la droite A,B,  
    renvoie u / P' = A + u * (B-A). u == 0 <=> P'== A, u == 1 <=> P' == B
    Le point projeté est sur le segment ssi u est dans [0 .. 1].
  - `static interSegSeg(array $a, array $b): ?array` - intersection entre 2 segments a et b  
    Chaque segment en paramètre est défini comme un tableau de 2 points  
    Si les segments ne s'intersectent pas alors retourne null  
    S'il s'intersectent, retourne le pt ainsi que les abscisses u et v  
    Si les 2 segments sont parallèles, alors retourne null même s'ils sont partiellement confondus
    
### La classe BBox
La classe BBox gère les boites englobantes.  
Une boite peut ne contenir aucun point ; dans ce cas min et max contiennent la valeur null.
On dit qu'elle est indéterminée.
Si une boite n'est pas indéterminée alors min et max contiennent chacun un point.

#### Méthodes

  - `__construct($param=null)` - initialise une boite en fonction du paramètre
  - `__toString(): string` - affiche les 2 points entourées de []
  - `min(): ?Point` - renvoie le point min
  - `max(): ?Point` - renvoie le point max
  - `bound(Point $pt): BBox` - agrandit la boite pour contenir le point et la renvoie
  - `union(BBox $bbox): BBox` - Agrandit la boite courante pour contenir la boite en paramètre et la renvoie
  - `size(): float` - longueur de la diagonale
  - `area(): float` - surface de la boite
  - `mindist(BBox $r1): float` - calcul du minimum les distances entre les points de 2 boites
  - `inters(BBox $bbox1): float` - calcul du rapport de l'intersection des 2 boites sur le maximum des surfaces des 2 boites
  - `isIncludedIn(BBox $bbox1):bool` - teste si this est inclus dans bbox1
  - `asPolygon(): Polygon` - renvoie la bbox comme Polygon
  - `asArray(): array` - renvoie [xmin, ymin, xmax, ymax] ou null
  - `chgCoordSys(string $src, string $dest): BBox` - crée un nouveau BBox en changeant le syst. de coord. de $src en $dest
  - `pointInBBox(Point $pt): bool` - teste si un point est dans un BBox
  - `edges(): array` - retourne les 4 côtés sous la forme de couple de points
  - `corner(int $no)` - retourne un des 4 coins : 0=>SW, 1=>SE, ... 

### La classe LineString
La classe LineString implémente la primtive LineString en 2D ou 3D correspondant à une liste brisée.

#### Méthodes

  - `__construct($param)` - construction à partir d'un WKT ou d'un [Point] ou d'un [[num, num {,num}]]
  - `__toString(): string` - affiche la liste des points entourées par des ()
  - `points(): array` - retourne la liste des points composant la ligne
  - `point(int $i): Point` - retourne un point particulier numéroté à partir de 0
  - `isValid(): bool` - renvoie vrai ssi l'objet est valide
  - `proj2D(): LineString` - projection 2D, supprime l'éventuelle 3ème coordonnée
  - `filter(int $nbdigits): LineString` - renvoie un nouveau LineString filtré supprimant les points successifs identiques
  - `chgCoordSys($src, $dest): LineString` - créée un nouveau LineString en changeant le syst. de coord. de $src en $dest
  - `coordinates(): array` - renvoie un tableau de coordonnées sous la forme [ [ num ] ]
  - `draw($drawing, $stroke='black', $fill='transparent', $stroke_with=2)` - dessine la liste brisée
  - `isClosed(): bool` - teste la fermeture de la liste brisée
  - `length(): float` - renvoie la longueur de la liste brisée
  - `area(): float` - renvoie la surface dans le système de coordonnées courant
  - `distancePointPointList(Point $pt): array` - distance minimum d'une liste de points au point pt  
    retourne la distance et le no du point qui correspond à la distance minimum sous la forme ['dist'=>$dist, 'n'=>$n]
  - `distancePointLineString(Point $pt): array` - distance minimum de la ligne brisée au point pt  
    retourne la distance et le point qui correspond à la distance minimum sous la forme ['dmin'=>$dmin, 'pt'=>$pt]
  - `simplify(float $distTreshold): ?LineString` - simplifie la géométrie de la ligne brisée  
    Algorithme de Douglas & Peucker
    Ne modifie pas l'objet courant. Retourne un nouvel objet LineString simplifié ou null si la ligne est fermée
    et que la distance max est inférieure au seuil.
  - `pointInPolygon(Point $pt): bool` - teste si un point pt est dans le polygone 

### La classe Polygon
La classe Polygon implémente la primtive Polygon correspondant à un extérieur défini par une ligne brisée fermée
et d'éventuels trous chacun défini comme une ligne brisée fermée.

#### Méthodes

  - `lineStrings(): array` - retourne la liste des LineStrings composant le polygone
  - `__construct($param)` - construction à partir d'un WKT ou d'un [LineString] ou d'un [[[num,num]]]
  - `addHole(LineString $hole): void` - ajoute un trou au polygone
  - `chgCoordSys($src, $dest): Polygon` - créée un nouveau Polygon en changeant le syst. de coord. de $src en $dest
  - `__toString(): string` - affiche la liste des LineString entourée par des ()
  - `toString($nbdigits=null)` - affiche la liste des LineString entourée par des () en précisant évent. le nbre de chiffres significatifs
  - `bbox(): BBox` - calcul du rectangle englobant
  - `isValid(): bool` - renvoie vrai ssi l'objet est valide
  - `proj2D(): Polygon` - projection 2D, supprime l'éventuelle 3ème coordonnée, renvoie un nouveau Polygon
  - `filter($nbdigits): Polygon` - filtre la géométrie en supprimant les points intermédiaires successifs identiques
  - `coordinates(): array` - renvoie les coordonnées comme [ [ [ num ] ] ]
  - `draw($drawing, $stroke='black', $fill='transparent', $stroke_with=2)` - dessine le polygone
  - `area($options=[])` - renvoie la surface dans le système de coordonnées courant  
    Par défaut, l'extérieur et les intérieurs tournent dans des sens différents.
    La surface est positive si l'extérieur tourne dans le sens trigonométrique, < 0 sinon.
    Si l'option 'noDirection' vaut true alors les sens ne sont pas pris en compte.
  - `pointInPolygon(Point $pt)` - teste si un point pt est dans le polygone 

### La classe abstraite MultiGeom
La classe abstraite MultiGeom factorise des méthodes communes sur les listes de géométries élémentaires homogènes.

#### Méthodes

  - `__toString(): string` - génère une chaine de caractère correspondant au WKT sans l'entete
  - `coordinates(): array` - renvoie un tableau de coordonnées
  - `isValid(): bool` - renvoie vrai ssi l'objet est valide
  - `proj2D(): MultiGeom` - projection 2D, supprime l'éventuelle 3ème coordonnée, renvoie un nouvel objet
  - `filter(int $nbdigits): MultiGeom` - renvoie une nouvelle collection dont chaque élément est filtré
    supprimant les points successifs identiques
  
### La classe MultiPoint
La classe MultiPoint implémente la primitive MultiPoint correspondant à une liste de Points.

### Méthodes
  - `__construct($param)` - initialise un MultiPoint à partir d'un WKT ou de [Point] ou de [[num,num]]
  - `wkt(): string` - génère la chaine de caractère correspondant au WKT avec l'entete

### La classe MultiLineString
La classe MultiLineString implémente la primitive MultiLineString correspondant à une liste de lignes brisées.

### Méthodes
  - `__construct($param)` - initialise un MultiPoint à partir d'un WKT ou de [Point] ou de [[[num,num]]]
  - `wkt(): string` - génère la chaine de caractère correspondant au WKT avec l'entete

### La classe MultiPolygon
La classe MultiPolygon implémente la primitive MultiPolygon correspondant à une liste de polygones.

### Méthodes
  - `__construct($param)` - initialise un MultiPolygon à partir d'un WKT ou de [Polygon] ou de [[[[num,num]]]]
  - `wkt(): string` - génère la chaine de caractère correspondant au WKT avec l'entete

### La classe GeometryCollection
La classe GeometryCollection implémente la primitive GeometryCollection correspondant à une liste
d'objets élémentaires.

### Méthodes
  - `__construct($param)` - initialise un GeomCollection à partir d'un WKT ou d'une liste de Geometry
  - `__toString(): string` - génère une chaine de caractère correspondant au WKT sans l'entete
  - `filter(int $nbdigits)` - filtre la géométrie en supprimant les points intermédiaires successifs identiques
  - `wkt(): string` - génère la chaine de caractère correspondant au WKT avec l'entete
  - `chgCoordSys($src, $dest)` - créée un nouveau GeomCollection en changeant le syst. de coord. de $src en $dest
  - `wkt(): string` - génère une chaine de caractère correspondant au WKT avec l'entete
  - `geojson(): array` - retourne un tableau Php qui encodé en JSON correspondra à la geometry GeoJSON
  - `function draw($drawing, $stroke='black', $fill='transparent', $stroke_with=2): void` - itère l'appel de draw sur chaque élément 

### La classe statique Wkt2GeoJson
La classe statique Wkt2GeoJson implémente la transformation WKT en GeoJSON de manière optimisée,
ce qui est nécessaire pour les objets volumineux.
La transformation ne crée aucune copie du $wkt afin d'optimiser la gestion mémoire.
De plus, elle utilise ni preg_match() ni preg_replace().

### Méthodes
  - `static convert(string $wkt, float $shift=0.0): array` - prend un WKT et renvoie un array correspondant à un GeoJSON, $shift est un décalage à appliquer en latitude, il vaut normalement 0, -360.0 ou +360.0
