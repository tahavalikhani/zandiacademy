<?php
/**
 * متن پادکست — the transcripts, VERBATIM.
 *
 * Every character here is what the owner wrote, including the spacing before
 * commas and full stops, the accents, and the occasional typo («histoir»,
 * «célèbles», «siécle», «devantla»). Do not tidy any of it. The same rule the
 * reviews follow applies here for a stronger reason: these are teaching notes
 * that a student reads while listening, so a «corrected» line no longer matches
 * what they are hearing.
 *
 * THE MARKERS ARE THE FORMAT, and zandi_podcast_transcript_blocks() is what
 * reads them:
 *
 *   ■   a part title            → a heading
 *   ●●  a group lead            → a bolder line introducing the bullets under it
 *   ●   an expression or example → a bullet
 *   ○   a sub-point             → an indented bullet
 *   (blank line)                → the end of a group
 *   anything else               → a line of the story
 *
 * Adding a transcript is adding a key here; the slug matches the attachment
 * slug the audio is uploaded under, so `podcast-free-3` starts working the day
 * its text is pasted in.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

return array(
	'podcast-free-1' => <<<'EOT'
■ Le musée de la mode à Paris
Les enfants regardaient les vêtements et leurs décorations avec beaucoup d'attention et d'enthousiasme.
En même temps ,ils écoutaient aussi la maîtresse .
● En même temps
● Il mangeait et regardait la télé en même temps.
● Elle parlait au téléphone tout en conduisant ,en même temps.

Comme vous pouvez le voir , cette robe de femme date de 1946 .
● Dater de
● Cette robe date de 1946 .
● Le bâtiment date du 18e siécle .

Faites attention à la couleur et à la texture du tissu .
● Tissu (n.m)
La laine
Le coton
La soie
Le cuir
Le velours
Le lin
● Ce tissu en lin est très léger.
○ Texture (n.f)
○ Doux /douce
○ Fin   / fine
○ Épais / épaisse
○ Élastique (Élastique à cheveux)
○ Grainé(e)
○ Ce tissu a une texture douce.

À cette époque ,on portait de longues jupes et froncées avec de petit gilets.
● Froncer
Et ces chaussures en cuir qui étaient légères et très durables .
Le bel uniforme qui  a attiré l'attention des enfants appartenait à un général de la Seconde Guerre mondiale .
● Appartenir
(Beau ,Belle ,Bel )
● Une belle femme (f)
● Un beau garçon   (m)
● Un bel homme  (m)
● Un bel uniforme  (m)
Voyelles (A ,E ,U ,I , O )+ H(consonne)

Il était très impressionnant avec ses boutons dorés et ses décorations militaires .
● Impressionner

Au musée il y avait beaucoup de vêtements de personnes célèbles ,comme Napoléon ,Marie - Antoinette , la reine Élisabeth .
Chaque vêtement montrait une époque et un style différent .
● Montrer

Les enfants ont beaucoup appris sur la mode ,l'histoire et la vie des ancêtres .
● Elle a beaucoup de livres .
Ils ont découvert de près les vêtements , les bijoux et les tenues des gens dans siècles passés .
● Passer

À mon avis la maîtresse a beaucoup aidé les enfants à apprendre en les emmenant au musée de la mode.
● Emmener
● J'emmène les enfants au musée .

Elle a enseigné des choses importantes de l'histoire aux enfants avec des images simples.
● Enseigner
● Le professeur enseigne le français .
● Elle enseigne le français aux enfants .
EOT,

	'podcast-free-2' => <<<'EOT'
Mais au fond de son cœur il était triste parce qu'il avait peur de se séparer de sa maman .
La maîtresse a dit en souriant :
Aujourd'hui le petit Nick reste à l'école seulement une heure et sa maman promet de venir le chercher dans une heure.
● Rester
● Promettre
● Chercher
● Je viens dans une heure.
● Il part dans cinq minutes.
● Je part dans deux mois.

À partir de demain , il reste à l'école jusqu'à midi avec son sac à dos et ses livres.
●● À partir de
● À partir de demain ,je travaille ici.
● La construction du pont commencera à partir de l'année prochaine.

●● Depuis
● je travaille ici depuis deux ans .
● Il pleut depuis ce matin .

La maîtresse a caressé Nick gentiment et lui a montré où s'asseoir.
● Caresser
● montrer
Nick en souriant s'est assis à côté d'un des élèves de la classe.
● s'asseoir

■ Tortue adorable

Nick est debout devant la porte de la maison et il regarde passer les voitures.(Nick se tient devantla porte)
● Se tenir
Il essaie de s'amuser.
Par exemple ,il fait une histoir pour un chat qui mange près de la poubelle.
● Faire une histoir
● La poubelle est pleine.
● J'ai mis les déchets dans la poubelle.
● Mettre
Cette scène peut occuper son esprit pendant un moment .
Mais avec l'arrivée de George ,le garçons du voisin ,tout a changé .
Il a acheté une petite tortue récemment .
George s'est approché de Nick avec une boîte dans la main .
● s'approcher
Et il a dit avec enthousiasme : tu veux voir ma tortue !?
Nick a dit joyeusement : Bien sûr !!
● Avec joie
Quelque minutes plus tard ,ils étaient tous les deux sur la terrasse en train de regarder la tortue.
Ils ont apporté quelques morceaux de carotte et quelque feuilles de laitue pour la tortue ,ainsi qu'un petit bol d'eau .
● ainsi que
● J'aime les pommes ainsi que les oranges .
● Nous avons visité Paris ainsi que Lyon.

Nick a touché la tortue ,elle était rugueuse et dure.
● Toucher
● Rugueux(euse)
C'était très intéressant pour lui ,car il n'avait jamais vu une tortue  de près .
● voir
Elle bougeait très lentement et elle pouvait rentrer ses pattes et sa tête dans sa carapace .
● Rentrer
● La tortue peut rentrer sa tête dans sa carapace.
Nick s'est souvenu de l'histoir du lièvre et de la tortue .
● Se souvenir de
● Elle se souvient de son grand-père .
● Elle se souvient de lui.

Sa mère lui avait raconté cette histoire plusieurs fois et elle lui avait aussi acheté le livre.
EOT,
);
