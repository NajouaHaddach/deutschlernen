-- ============================================================
--  AJOUT DES QUESTIONS B1 & B2 (20 PAR NIVEAU)
-- ============================================================

USE `deutschlernen`;

-- -------------------------------------------------------
-- QUESTIONS B1 (20 Questions)
-- -------------------------------------------------------
SET @b1 = (SELECT `id` FROM `niveau_tests` WHERE `niveau` = 'B1');

INSERT INTO `questions_test` (`test_id`,`question`,`option_a`,`option_b`,`option_c`,`option_d`,`bonne_reponse`,`ordre`) VALUES
(@b1, 'Quelle conjonction exprime une condition ?', 'obwohl', 'wenn', 'dass', 'weil', 'b', 1),
(@b1, 'Traduisez : "Si j\'avais le temps, je viendrais".', 'Wenn ich Zeit habe, komme ich', 'Wenn ich Zeit hätte, käme ich', 'Ich habe Zeit und komme', 'Hatte ich Zeit, komme ich', 'b', 2),
(@b1, 'Quel verbe signifie "s\'habituer à" ?', 'sich erinnern an', 'sich freuen sur', 'sich gewöhnen an', 'sich interessieren für', 'c', 3),
(@b1, 'Quel est le génitif de "der Vater" ?', 'dem Vater', 'den Vater', 'des Vaters', 'der Vaters', 'c', 4),
(@b1, 'Traduisez : "Malgré la pluie".', 'Wegen dem Regen', 'Trotz des Regens', 'Dank dem Regen', 'Während dem Regen', 'b', 5),
(@b1, 'Quelle préposition utilise-t-on avec "warten" (attendre) ?', 'auf', 'für', 'an', 'zu', 'a', 6),
(@b1, 'Comment dit-on "pendant ce temps" ?', 'danach', 'währenddessen', 'vorher', 'später', 'b', 7),
(@b1, 'Quel est le participe passé de "verstehen" ?', 'versteht', 'verstanden', 'gevestehen', 'geverstanden', 'b', 8),
(@b1, 'Traduisez : "Je me réjouis de tes vacances (à venir)".', 'Ich freue mich über deinen Urlaub', 'Ich freue mich auf deinen Urlaub', 'Ich freue mich für dich', 'Ich habe Freude am Urlaub', 'b', 9),
(@b1, 'Quel pronom relatif pour "Das Kind, ___ ich helfe" ?', 'das', 'dem', 'den', 'dessen', 'b', 10),
(@b1, 'Comment dit-on "plusieurs" ?', 'viel', 'mehr', 'mehrere', 'manche', 'c', 11),
(@b1, 'Traduisez : "C\'est dommage".', 'Das ist schön', 'Das ist schade', 'Das ist egal', 'Das ist wahr', 'b', 12),
(@b1, 'Quel verbe signifie "réussir un examen" ?', 'eine Prüfung machen', 'une Prüfung bestehen', 'eine Prüfung schreiben', 'eine Prüfung lernen', 'b', 13),
(@b1, 'Traduisez : "Je n\'en ai aucune idée".', 'Ich habe keine Ahnung', 'Ich weiß nicht', 'Ich denke nichts', 'Das ist mir egal', 'a', 14),
(@b1, 'Quelle est la forme passive de "Man baut ein Haus" ?', 'Ein Haus wird gebaut', 'Ein Haus ist gebaut', 'Ein Haus wurde bauen', 'Man wird ein Haus bauen', 'a', 15),
(@b1, 'Que signifie "Vielleicht" ?', 'Certainement', 'Peut-être', 'Souvent', 'Jamais', 'b', 16),
(@b1, 'Traduisez : "Il est tombé amoureux".', 'Er ist verliebt', 'Er hat sich verliebt', 'Er ist Liebe', 'Er macht Liebe', 'b', 17),
(@b1, 'Quel mot pour "Environ" ?', 'Genau', 'Ungefähr', 'Sicher', 'Einfach', 'b', 18),
(@b1, 'Comment dit-on "La plupart des gens" ?', 'Die meisten Leute', 'Viel Leute', 'Alle Leute', 'Manche Leute', 'a', 19),
(@b1, 'Traduisez : "D\'un côté... de l\'autre côté".', 'Einerseits... andererseits', 'Entweder... oder', 'Sowohl... als auch', 'Weder... noch', 'a', 20);

-- -------------------------------------------------------
-- QUESTIONS B2 (20 Questions)
-- -------------------------------------------------------
SET @b2 = (SELECT `id` FROM `niveau_tests` WHERE `niveau` = 'B2');

INSERT INTO `questions_test` (`test_id`,`question`,`option_a`,`option_b`,`option_c`,`option_d`,`bonne_reponse`,`ordre`) VALUES
(@b2, 'Quelle est la structure de "Sowohl... als auch" ?', 'soit... soit', 'ni... ni', 'aussi bien... que', 'non seulement... mais aussi', 'c', 1),
(@b2, 'Traduisez : "Je me rends compte que..."', 'Ich denke, dass...', 'Mir ist bewusst, dass...', 'Ich weiß, dass...', 'Ich sehe, dass...', 'b', 2),
(@b2, 'Quel verbe signifie "interrompre" ?', 'aufhören', 'unterbrechen', 'beenden', 'pausieren', 'b', 3),
(@b2, 'Traduisez : "Aussitôt que possible".', 'So bald wie möglich', 'So schnell wie möglich', 'So oft wie möglich', 'So gut wie möglich', 'a', 4),
(@b2, 'Quel est le subjonctif II de "sein" (il serait) ?', 'er wäre', 'er sei', 'er ist', 'er war', 'a', 5),
(@b2, 'Que signifie "Allerdings" ?', 'D\'ailleurs', 'Toutefois', 'Certes', 'Sinon', 'b', 6),
(@b2, 'Traduisez : "Il s\'agit de..."', 'Es handelt sich um...', 'Es geht auf...', 'Es macht...', 'Es ist...', 'a', 7),
(@b2, 'Quel mot pour "La conséquence" ?', 'Der Grund', 'Die Folge', 'Die Sache', 'Der Fall', 'b', 8),
(@b2, 'Traduisez : "Par rapport à..."', 'Im Vergleich zu...', 'In Bezug auf...', 'Wegen...', 'Trotz...', 'a', 9),
(@b2, 'Quel verbe signifie "proposer" ?', 'geben', 'vorschlagen', 'zeigen', 'sagen', 'b', 10),
(@b2, 'Traduisez : "C\'est incroyable".', 'Das est wunderbar', 'Das ist unglaublich', 'Das ist unmöglich', 'Das ist wichtig', 'b', 11),
(@b2, 'Que signifie "Tatsächlich" ?', 'Probablement', 'En fait / Effectivement', 'Rarement', 'Clairement', 'b', 12),
(@b2, 'Traduisez : "Je n\'y peux rien".', 'Ich kann nichts machen', 'Ich kann nichts dafür', 'Ich weiß nichts', 'Das ist nicht mein Problem', 'b', 13),
(@b2, 'Quel est le contraire de "Vorteil" (avantage) ?', 'Nachteil', 'Fehler', 'Problem', 'Schaden', 'a', 14),
(@b2, 'Traduisez : "À condition que..."', 'Wenn...', 'Unter der Bedingung, dass...', 'Falls...', 'Damit...', 'b', 15),
(@b2, 'Que signifie "Anscheinend" ?', 'Clairement', 'Apparemment', 'Sûrement', 'Vraiment', 'b', 16),
(@b2, 'Traduisez : "De plus..."', 'Zudem...', 'Aber...', 'Denn...', 'Obwohl...', 'a', 17),
(@b2, 'Quel verbe pour "Confirmer" ?', 'sagen', 'bestätigen', 'antworten', 'fragen', 'b', 18),
(@b2, 'Traduisez : "D\'après mon opinion".', 'Meiner Meinung nach', 'Ich denke', 'Für mich', 'In meinem Kopf', 'a', 19),
(@b2, 'Quel mot pour "Le succès" ?', 'Der Versuch', 'Der Erfolg', 'Das Ergebnis', 'Die Arbeit', 'b', 20);
