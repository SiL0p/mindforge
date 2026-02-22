# mathv7

## Objectif
Pack orienté exercices sur les **espaces vectoriels** (niveau licence): tester si un ensemble est un sous-espace, manipuler des familles génératrices/libres, base et dimension.

## Rappels essentiels
- Un sous-ensemble $F$ est un sous-espace de $E$ si: (i) $0 \in F$, (ii) $u,v \in F \Rightarrow u+v \in F$, (iii) $\lambda \in \mathbb{R}, u \in F \Rightarrow \lambda u \in F$.
- Une famille est **libre** si la combinaison linéaire nulle est triviale.
- Une base est une famille libre et génératrice.
- Dimension: nombre de vecteurs d'une base.

## Exercice 1 — Sous-espace ?
Dans $\mathbb{R}^3$, étudier $F=\{(x,y,z)\mid x-2y+z=0\}$.
**Correction courte :** Oui, c'est le noyau d'une forme linéaire $\varphi(x,y,z)=x-2y+z$, donc sous-espace.

## Exercice 2 — Contre-exemple
Dans $\mathbb{R}^2$, étudier $G=\{(x,y)\mid x+y=1\}$.
**Correction courte :** Non, $0\notin G$ car $0+0\neq 1$.

## Exercice 3 — Liberté linéaire
Étudier la liberté de $u_1=(1,0,1)$, $u_2=(2,1,3)$, $u_3=(0,1,1)$ dans $\mathbb{R}^3$.
**Correction courte :** Poser $a u_1+b u_2+c u_3=0$. Système:
- $a+2b=0$
- $b+c=0$
- $a+3b+c=0$
On obtient $a=b=c=0$ donc famille libre.

## Exercice 4 — Base et dimension
Soit $H=\{(x,y,z,t)\in\mathbb{R}^4\mid x+y+z+t=0\}$. Trouver une base et la dimension.
**Correction courte :** Paramétrer $x=-y-z-t$:
- $(x,y,z,t)=y(-1,1,0,0)+z(-1,0,1,0)+t(-1,0,0,1)$
Base possible $\{(-1,1,0,0),(-1,0,1,0),(-1,0,0,1)\}$, donc $\dim(H)=3$.

## Exercice 5 — Génératrice minimale
Dans $\mathbb{R}^2$, simplifier la famille $S=\{(1,1),(2,2),(1,0),(0,1)\}$.
**Correction courte :** $(2,2)=2(1,1)$ est redondant, $(1,1)=(1,0)+(0,1)$. Base finale: $\{(1,0),(0,1)\}$.

## Exercice 6 — Vrai/Faux
1) Toute famille de 4 vecteurs de $\mathbb{R}^3$ est liée.
2) Toute famille libre de $\mathbb{R}^3$ de 3 vecteurs est une base.
3) Un sous-espace de dimension 2 de $\mathbb{R}^3$ est un plan passant par l'origine.
**Réponses :** 1) Vrai, 2) Vrai, 3) Vrai.

## Plan de travail (45 min)
- 10 min: rappels + définitions.
- 25 min: exercices 1 à 4.
- 10 min: correction + fiche erreurs fréquentes.

## Erreurs fréquentes
- Oublier de vérifier la présence du vecteur nul.
- Confondre famille génératrice et famille libre.
- Conclure trop vite sans résoudre le système linéaire.