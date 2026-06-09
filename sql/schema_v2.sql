-- ============================================================
-- Real Life German Scenarios — Database Schema v2
-- Run this in phpMyAdmin on database: deutschlernen
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

-- ============================================================
-- 1. LEVELS
-- ============================================================
CREATE TABLE IF NOT EXISTS `levels` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`        VARCHAR(10)  NOT NULL UNIQUE,
  `label`       VARCHAR(50)  NOT NULL,
  `description` TEXT,
  `color_hex`   VARCHAR(7)   DEFAULT '#6366F1',
  `icon`        VARCHAR(10)  DEFAULT '📚',
  `sort_order`  TINYINT UNSIGNED DEFAULT 0,
  `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `levels` (`name`,`label`,`description`,`color_hex`,`icon`,`sort_order`) VALUES
('A1','Anfänger',      'Basiswissen: Begrüßungen, Zahlen, einfache Sätze und Alltagssituationen.',            '#22D3A5','🌱',1),
('A2','Grundlagen',    'Aufbau: Sich vorstellen, Einkaufen, öffentliche Verkehrsmittel und Freizeit.',         '#38BDF8','📘',2),
('B1','Mittelstufe',   'Konversation: Meinungen äußern, Geschichten erzählen, Medien verstehen.',              '#FBBF24','⚡',3),
('B2','Fortgeschritten','Komplexe Themen: Berichte schreiben, schnelle Muttersprachler verstehen.',            '#F87171','🔥',4),
('C1','Hochfortgeschritten','Abstrakte Ideen diskutieren, akademische Texte lesen, flüssig sprechen.',         '#A78BFA','💎',5),
('C2','Meisterniveau', 'Nahezu muttersprachliche Kompetenz: Nuancen, Humor, Kultur verstehen.',               '#EC4899','🏆',6);

-- ============================================================
-- 2. SCENARIOS
-- ============================================================
DROP TABLE IF EXISTS `scenario_dialogues`;
DROP TABLE IF EXISTS `scenario_tips`;
ALTER TABLE `scenarios` RENAME TO `scenarios_legacy`;

CREATE TABLE IF NOT EXISTS `scenarios` (
  `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `level_id`      INT UNSIGNED NOT NULL,
  `level`         VARCHAR(10) NOT NULL,
  `title`         VARCHAR(200) NOT NULL,
  `description`   TEXT NOT NULL,
  `image`         VARCHAR(500) NOT NULL,
  `category`      VARCHAR(80)  NOT NULL,
  `difficulty`    TINYINT UNSIGNED DEFAULT 1 COMMENT '1=Easy 2=Medium 3=Hard',
  `content_hash`  CHAR(32)     NOT NULL UNIQUE COMMENT 'MD5 of title+description',
  `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`level_id`) REFERENCES `levels`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 3. SCENARIO DIALOGUES
-- ============================================================
CREATE TABLE IF NOT EXISTS `scenario_dialogues` (
  `id`                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `scenario_id`       INT UNSIGNED NOT NULL,
  `step_order`        TINYINT UNSIGNED NOT NULL,
  `speaker`           ENUM('npc','user_expected') DEFAULT 'npc',
  `german_text`       TEXT NOT NULL,
  `arabic_translation` TEXT,
  `pronunciation`     TEXT COMMENT 'Phonetic guide in Latin chars',
  `expected_keywords` JSON  COMMENT 'Array of accepted answer keywords',
  `vocabulary`        JSON  COMMENT '[{"de":"...","ar":"..."}]',
  `hint`              TEXT  COMMENT 'Displayed when user answers incorrectly',
  `grammar_tip`       TEXT,
  `cultural_note`     TEXT,
  `common_mistake`    TEXT,
  `content_hash`      CHAR(32) NOT NULL COMMENT 'MD5 of german_text',
  FOREIGN KEY (`scenario_id`) REFERENCES `scenarios`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_step` (`scenario_id`,`step_order`),
  INDEX `idx_scenario` (`scenario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 4. SCENARIO TIPS (one record per scenario)
-- ============================================================
CREATE TABLE IF NOT EXISTS `scenario_tips` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `scenario_id`     INT UNSIGNED NOT NULL UNIQUE,
  `grammar_tip`     TEXT,
  `vocabulary_list` JSON COMMENT '[{"de":"...","ar":"..."}]',
  `cultural_note`   TEXT,
  `common_mistakes` JSON COMMENT '["mistake1","mistake2"]',
  FOREIGN KEY (`scenario_id`) REFERENCES `scenarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 5. USER PROGRESS (new schema)
-- ============================================================
-- Rename old table if it exists
ALTER TABLE `user_progress` RENAME TO `user_progress_legacy`;

CREATE TABLE IF NOT EXISTS `user_progress` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`      INT UNSIGNED NOT NULL,
  `scenario_id`  INT UNSIGNED NOT NULL,
  `completed`    TINYINT(1) DEFAULT 0,
  `score`        TINYINT UNSIGNED DEFAULT 0 COMMENT '0-100',
  `xp_earned`    SMALLINT UNSIGNED DEFAULT 0,
  `completed_at` TIMESTAMP NULL,
  UNIQUE KEY `user_scenario` (`user_id`,`scenario_id`),
  INDEX `idx_user` (`user_id`),
  FOREIGN KEY (`scenario_id`) REFERENCES `scenarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 6. USER XP & LEVEL UNLOCK STATE
-- ============================================================
CREATE TABLE IF NOT EXISTS `user_xp` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`         INT UNSIGNED NOT NULL UNIQUE,
  `total_xp`        INT UNSIGNED DEFAULT 0,
  `level_unlocked`  JSON DEFAULT ('{"A1":true,"A2":false,"B1":false,"B2":false,"C1":false,"C2":false}'),
  `updated_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- SEED DATA — A1 Level (10 Scenarios)
-- ============================================================

-- Helper: insert scenarios referencing level A1
SET @a1 = (SELECT id FROM levels WHERE name='A1');

INSERT IGNORE INTO `scenarios`
  (`level_id`,`level`,`title`,`description`,`image`,`category`,`difficulty`,`content_hash`)
VALUES
(@a1,'A1',
 'Am Flughafen ankommen',
 'Du landest zum ersten Mal in Deutschland. Lerne, wie du dich am Flughafen orientierst, nach dem Gepäck fragst und den Ausgang findest.',
 'https://images.unsplash.com/photo-1436491865332-7a61a109cc05?auto=format&fit=crop&w=800&q=80',
 'Reise', 1,
 MD5(CONCAT('Am Flughafen ankommen','Du landest zum ersten Mal in Deutschland. Lerne, wie du dich am Flughafen orientierst, nach dem Gepäck fragst und den Ausgang findest.'))),

(@a1,'A1',
 'Im Hotel einchecken',
 'Du kommst in deinem Hotel an und möchtest einchecken. Übe typische Fragen zur Reservierung, zum Zimmer und zu den Einrichtungen.',
 'https://images.unsplash.com/photo-1551882547-ff40c242fb36?auto=format&fit=crop&w=800&q=80',
 'Reise', 1,
 MD5(CONCAT('Im Hotel einchecken','Du kommst in deinem Hotel an und möchtest einchecken. Übe typische Fragen zur Reservierung, zum Zimmer und zu den Einrichtungen.'))),

(@a1,'A1',
 'Im Supermarkt einkaufen',
 'Du brauchst Lebensmittel für die Woche. Lerne, wie du nach Produkten fragst, Preise verstehst und an der Kasse bezahlst.',
 'https://images.unsplash.com/photo-1578916171728-46686eac8d58?auto=format&fit=crop&w=800&q=80',
 'Einkaufen', 1,
 MD5(CONCAT('Im Supermarkt einkaufen','Du brauchst Lebensmittel für die Woche. Lerne, wie du nach Produkten fragst, Preise verstehst und an der Kasse bezahlst.'))),

(@a1,'A1',
 'Beim Arzt',
 'Du bist krank und gehst zum Arzt. Übe, deine Symptome auf Deutsch zu beschreiben und die Anweisungen des Arztes zu verstehen.',
 'https://images.unsplash.com/photo-1584515933487-779824d29309?auto=format&fit=crop&w=800&q=80',
 'Gesundheit', 2,
 MD5(CONCAT('Beim Arzt','Du bist krank und gehst zum Arzt. Übe, deine Symptome auf Deutsch zu beschreiben und die Anweisungen des Arztes zu verstehen.'))),

(@a1,'A1',
 'Auf dem Bahnhof',
 'Du möchtest mit dem Zug von Berlin nach München fahren. Lerne, Fahrkarten zu kaufen, nach Abfahrtszeiten zu fragen und den richtigen Bahnsteig zu finden.',
 'https://images.unsplash.com/photo-1474487548417-781cb6d646df?auto=format&fit=crop&w=800&q=80',
 'Reise', 1,
 MD5(CONCAT('Auf dem Bahnhof','Du möchtest mit dem Zug von Berlin nach München fahren. Lerne, Fahrkarten zu kaufen, nach Abfahrtszeiten zu fragen und den richtigen Bahnsteig zu finden.'))),

(@a1,'A1',
 'Im Restaurant bestellen',
 'Du sitzt in einem deutschen Restaurant. Übe, die Speisekarte zu lesen, das Essen zu bestellen und die Rechnung zu verlangen.',
 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?auto=format&fit=crop&w=800&q=80',
 'Essen', 1,
 MD5(CONCAT('Im Restaurant bestellen','Du sitzt in einem deutschen Restaurant. Übe, die Speisekarte zu lesen, das Essen zu bestellen und die Rechnung zu verlangen.'))),

(@a1,'A1',
 'Sich vorstellen',
 'Du triffst neue Leute auf einer Sprachschule. Lerne, dich vorzustellen, deinen Namen, deine Herkunft und deinen Beruf zu nennen.',
 'https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&w=800&q=80',
 'Soziales', 1,
 MD5(CONCAT('Sich vorstellen','Du triffst neue Leute auf einer Sprachschule. Lerne, dich vorzustellen, deinen Namen, deine Herkunft und deinen Beruf zu nennen.'))),

(@a1,'A1',
 'Eine Wohnung mieten',
 'Du suchst eine Wohnung in Deutschland. Übe, mit einem Vermieter zu sprechen, nach Miete und Nebenkosten zu fragen und einen Besichtigungstermin zu vereinbaren.',
 'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?auto=format&fit=crop&w=800&q=80',
 'Wohnen', 2,
 MD5(CONCAT('Eine Wohnung mieten','Du suchst eine Wohnung in Deutschland. Übe, mit einem Vermieter zu sprechen, nach Miete und Nebenkosten zu fragen und einen Besichtigungstermin zu vereinbaren.'))),

(@a1,'A1',
 'Beim Bäcker',
 'Du gehst morgens zum Bäcker um die Ecke. Lerne typische Bestellungen, Brötchenarten und höfliche Ausdrücke beim Einkauf.',
 'https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=800&q=80',
 'Essen', 1,
 MD5(CONCAT('Beim Bäcker','Du gehst morgens zum Bäcker um die Ecke. Lerne typische Bestellungen, Brötchenarten und höfliche Ausdrücke beim Einkauf.'))),

(@a1,'A1',
 'Im Café',
 'Du triffst einen deutschen Bekannten in einem Café. Übe Small-Talk, Getränke bestellen und einfache Unterhaltungen auf Deutsch führen.',
 'https://images.unsplash.com/photo-1501339847302-ac426a4a7cbb?auto=format&fit=crop&w=800&q=80',
 'Soziales', 1,
 MD5(CONCAT('Im Café','Du triffst einen deutschen Bekannten in einem Café. Übe Small-Talk, Getränke bestellen und einfache Unterhaltungen auf Deutsch führen.')));

-- ============================================================
-- DIALOGUES — Scenario 1: Am Flughafen ankommen
-- ============================================================
SET @s1 = (SELECT id FROM scenarios WHERE content_hash = MD5(CONCAT('Am Flughafen ankommen','Du landest zum ersten Mal in Deutschland. Lerne, wie du dich am Flughafen orientierst, nach dem Gepäck fragst und den Ausgang findest.')));

INSERT IGNORE INTO `scenario_dialogues`
  (`scenario_id`,`step_order`,`speaker`,`german_text`,`arabic_translation`,`pronunciation`,`expected_keywords`,`vocabulary`,`hint`,`grammar_tip`,`cultural_note`,`common_mistake`,`content_hash`)
VALUES
(@s1,1,'npc',
 'Willkommen in Deutschland! Kann ich Ihnen helfen?',
 'مرحبًا بك في ألمانيا! هل يمكنني مساعدتك؟',
 'Vil-KOM-men in Doytsch-land! Kan ich EE-nen HEL-fen?',
 '["ja bitte","danke","hilfe"]',
 '[{"de":"Willkommen","ar":"مرحبًا"},{"de":"helfen","ar":"يساعد"},{"de":"bitte","ar":"من فضلك"}]',
 'Antworte mit "Ja, bitte!" oder "Danke, ich suche..."',
 'Benutze "Können Sie" (formell) statt "Kannst du" mit Fremden.',
 'In Deutschland ist die Anrede mit "Sie" (formell) sehr wichtig bei Fremden.',
 'Vergiss nicht das "bitte" – es zeigt Höflichkeit!',
 MD5('Willkommen in Deutschland! Kann ich Ihnen helfen?')),

(@s1,2,'user_expected',
 'Ja, bitte. Wo ist die Gepäckausgabe?',
 'نعم، من فضلك. أين استلام الأمتعة؟',
 'Ya, BIT-teh. Vo ist dee geh-PECK-ows-GAH-beh?',
 '["gepäckausgabe","wo","gepäck","suche"]',
 '[{"de":"Gepäckausgabe","ar":"استلام الأمتعة"},{"de":"wo","ar":"أين"},{"de":"suchen","ar":"يبحث عن"}]',
 'Frage: "Wo ist die Gepäckausgabe?" oder "Ich suche mein Gepäck."',
 'W-Fragen: Wo (wo), Was (ماذا), Wer (من), Wann (متى).',
 'Die Gepäckausgabe heißt auf Englisch "baggage claim".',
 '"Wo ist" – immer Nominativ nach "ist"!',
 MD5('Ja, bitte. Wo ist die Gepäckausgabe?')),

(@s1,3,'npc',
 'Die Gepäckausgabe ist in Halle B, geradeaus und dann links.',
 'استلام الأمتعة في القاعة B، مباشرةً ثم إلى اليسار.',
 'Dee geh-PECK-ows-GAH-beh ist in HA-leh B, geh-RAH-de-ows unt dan links.',
 '["verstehe","danke","okay","gut"]',
 '[{"de":"geradeaus","ar":"مباشرةً"},{"de":"links","ar":"يسار"},{"de":"rechts","ar":"يمين"},{"de":"Halle","ar":"القاعة"}]',
 'Antworte mit "Danke sehr!" oder "Ich verstehe."',
 'Richtungsangaben: geradeaus (مباشرة), links (يسار), rechts (يمين).',
 'Hallen in Flughäfen werden mit Buchstaben bezeichnet: A, B, C.',
 'Sag "Danke sehr" statt nur "Danke" – das klingt höflicher.',
 MD5('Die Gepäckausgabe ist in Halle B, geradeaus und dann links.')),

(@s1,4,'user_expected',
 'Danke sehr! Und wo ist der Ausgang?',
 'شكرًا جزيلًا! وأين المخرج؟',
 'DAN-keh zayr! Unt vo ist dehr OWS-gang?',
 '["ausgang","wo","exit","danke"]',
 '[{"de":"Ausgang","ar":"المخرج"},{"de":"Eingang","ar":"المدخل"},{"de":"Ausgang","ar":"خروج"}]',
 'Frage nach dem Ausgang: "Wo ist der Ausgang?"',
 '"Der Ausgang" ist maskulin → "wo ist DER Ausgang?"',
 'In Deutschland sind Ausgänge immer grün markiert mit "Ausgang" oder "Exit".',
 '"Der/Die/Das" – lerne die Artikel immer mit dem Wort!',
 MD5('Danke sehr! Und wo ist der Ausgang?')),

(@s1,5,'npc',
 'Der Ausgang ist dort drüben, neben dem Informationsschalter.',
 'المخرج هناك، بجانب مكتب المعلومات.',
 'Dehr OWS-gang ist dort DROO-ben, NEH-ben dem In-for-ma-tsee-OHns-SHAL-ter.',
 '["danke","verstanden","gut","okay","perfekt"]',
 '[{"de":"drüben","ar":"هناك"},{"de":"neben","ar":"بجانب"},{"de":"Informationsschalter","ar":"مكتب المعلومات"}]',
 'Antworte mit "Perfekt, danke!" oder "Sehr gut, ich gehe jetzt."',
 '"Neben" + Dativ: neben DEM Schalter.',
 'Deutsche Flughäfen haben immer einen Informationsschalter für Reisende.',
 '"Neben dem" nicht "neben der" – Schalter ist maskulin!',
 MD5('Der Ausgang ist dort drüben, neben dem Informationsschalter.')),

(@s1,6,'user_expected',
 'Perfekt, vielen Dank für Ihre Hilfe!',
 'ممتاز، شكرًا جزيلًا على مساعدتك!',
 'Per-FEKT, FEE-len Dank foor EE-reh HIL-feh!',
 '["danke","vielen dank","hilfe","perfekt","super"]',
 '[{"de":"vielen Dank","ar":"شكرًا جزيلًا"},{"de":"Hilfe","ar":"المساعدة"},{"de":"Ihre","ar":"مساعدتك (رسمي)"}]',
 'Verabschiede dich höflich: "Vielen Dank für Ihre Hilfe!"',
 '"Vielen Dank" ist formeller als "Danke". Benutze "Ihre" (formell) mit Fremden.',
 'Es ist üblich in Deutschland, sich bei jeder Hilfe höflich zu bedanken.',
 '"Vielen Dank" nicht "Vielen Danke" – kein -e am Ende!',
 MD5('Perfekt, vielen Dank für Ihre Hilfe!'));

-- ============================================================
-- DIALOGUES — Scenario 2: Im Hotel einchecken
-- ============================================================
SET @s2 = (SELECT id FROM scenarios WHERE content_hash = MD5(CONCAT('Im Hotel einchecken','Du kommst in deinem Hotel an und möchtest einchecken. Übe typische Fragen zur Reservierung, zum Zimmer und zu den Einrichtungen.')));

INSERT IGNORE INTO `scenario_dialogues`
  (`scenario_id`,`step_order`,`speaker`,`german_text`,`arabic_translation`,`pronunciation`,`expected_keywords`,`vocabulary`,`hint`,`grammar_tip`,`cultural_note`,`common_mistake`,`content_hash`)
VALUES
(@s2,1,'npc',
 'Guten Tag! Herzlich willkommen. Haben Sie eine Reservierung?',
 'مرحبًا! أهلاً وسهلاً. هل لديك حجز؟',
 'GOO-ten Tahg! HER-tslich vil-KOM-men. HAH-ben zee eye-neh reh-zer-FEER-ung?',
 '["ja","reservierung","name","buchung"]',
 '[{"de":"Reservierung","ar":"الحجز"},{"de":"haben","ar":"يملك / لديه"},{"de":"Guten Tag","ar":"مرحبًا (نهارًا)"}]',
 'Antworte: "Ja, ich habe eine Reservierung auf den Namen..."',
 '"Haben Sie" ist die formelle Frage (Do you have?). Immer "Sie" mit Fremden.',
 'In Deutschland checkt man meist ab 14:00 Uhr ein.',
 'Sag "auf den Namen" nicht "mit dem Namen" bei Reservierungen!',
 MD5('Guten Tag! Herzlich willkommen. Haben Sie eine Reservierung?')),

(@s2,2,'user_expected',
 'Ja, ich habe eine Reservierung. Mein Name ist...',
 'نعم، لدي حجز. اسمي هو...',
 'Ya, ich HAH-beh eye-neh reh-zer-FEER-ung. Mine NAH-meh ist...',
 '["reservierung","name","habe","ja"]',
 '[{"de":"Reservierung","ar":"الحجز"},{"de":"mein Name","ar":"اسمي"},{"de":"auf den Namen","ar":"باسم"}]',
 'Sage: "Ja, ich habe eine Reservierung auf den Namen [dein Name]."',
 '"Ich habe" = I have. Trennbares Verb "vorhaben" wird zu "habe ... vor".',
 'Beim Einchecken braucht man immer den Reisepass (Reisepass) oder Personalausweis.',
 'Sag "mein Name IST" nicht "mein Name BIN"!',
 MD5('Ja, ich habe eine Reservierung. Mein Name ist...')),

(@s2,3,'npc',
 'Einen Moment bitte. Ja, ich habe Ihre Buchung gefunden. Ein Einzelzimmer für drei Nächte?',
 'لحظة من فضلك. نعم، وجدت حجزك. غرفة مفردة لثلاث ليالٍ؟',
 'EYE-nen mo-MENT BIT-teh. Ya, ich HAH-beh EE-reh BOO-khung geh-FUN-den.',
 '["ja","richtig","korrekt","stimmt","genau"]',
 '[{"de":"Einzelzimmer","ar":"غرفة مفردة"},{"de":"Doppelzimmer","ar":"غرفة مزدوجة"},{"de":"Nacht/Nächte","ar":"ليلة/ليالٍ"}]',
 'Bestätige: "Ja, das ist richtig." oder korrigiere falls nötig.',
 '"Gefunden" = Partizip II von "finden" (Perfekt-Tense).',
 'In Deutschland muss man beim Einchecken oft eine Meldepflicht-Karte ausfüllen.',
 'Einzelzimmer (single), Doppelzimmer (double) – merke diese Wörter!',
 MD5('Einen Moment bitte. Ja, ich habe Ihre Buchung gefunden. Ein Einzelzimmer für drei Nächte?')),

(@s2,4,'user_expected',
 'Ja, genau. Ist das Frühstück inbegriffen?',
 'نعم، بالضبط. هل الإفطار مشمول؟',
 'Ya, geh-NOW. Ist das FROO-shtook IN-beh-grif-fen?',
 '["frühstück","inbegriffen","inklusive","ja","nein"]',
 '[{"de":"Frühstück","ar":"الإفطار"},{"de":"inbegriffen","ar":"مشمول"},{"de":"inklusive","ar":"شامل"}]',
 'Frage: "Ist das Frühstück inbegriffen?" oder "Ist das Frühstück inklusive?"',
 '"Inbegriffen" = included. Kommt immer nach dem Nomen.',
 'Viele deutsche Hotels bieten Frühstücksbuffet an – sehr typisch!',
 'Sag "inbegriffen" nicht "ingegriffen" – häufiger Fehler!',
 MD5('Ja, genau. Ist das Frühstück inbegriffen?')),

(@s2,5,'npc',
 'Ja, das Frühstück ist inklusive. Es wird von 7 bis 10 Uhr serviert. Hier ist Ihr Schlüssel, Zimmer 305.',
 'نعم، الإفطار مشمول. يُقدَّم من الساعة 7 حتى 10. هذا هو مفتاحك، الغرفة 305.',
 'Ya, das FROO-shtook ist in-kloo-ZEE-feh. Es virt fon ZEE-ben bis TSAYN oor ser-FEERT.',
 '["danke","gut","verstanden","perfekt","okay"]',
 '[{"de":"Schlüssel","ar":"المفتاح"},{"de":"Zimmer","ar":"الغرفة"},{"de":"serviert","ar":"يُقدَّم"}]',
 'Antworte mit "Danke" und frage nach dem WLAN: "Gibt es WLAN?"',
 'Uhrzeiten: "von 7 bis 10 Uhr" = from 7 to 10 o\'clock.',
 'Das deutsche Frühstück ist oft sehr reichhaltig: Brot, Käse, Wurst, Eier.',
 '"Zimmer 305" = dreihundertfünf (Zahlen lernen ist wichtig!)',
 MD5('Ja, das Frühstück ist inklusive. Es wird von 7 bis 10 Uhr serviert. Hier ist Ihr Schlüssel, Zimmer 305.')),

(@s2,6,'user_expected',
 'Vielen Dank! Gibt es WLAN im Zimmer?',
 'شكرًا جزيلًا! هل يوجد واي فاي في الغرفة؟',
 'FEE-len Dank! Gipt es VAY-lan im TSIM-mer?',
 '["wlan","internet","passwort","wifi"]',
 '[{"de":"WLAN","ar":"الواي فاي"},{"de":"Passwort","ar":"كلمة المرور"},{"de":"Zimmer","ar":"الغرفة"}]',
 'Frage: "Gibt es WLAN?" und frage nach dem Passwort: "Was ist das Passwort?"',
 '"Gibt es" = Is there? Benutze es für Fragen über Verfügbarkeit.',
 'WLAN (gesprochen: "Vay-Lan") ist in Deutschland der Begriff für WiFi.',
 '"Gibt es WLAN" nicht "Ist WLAN" – "gibt es" ist die korrekte Struktur!',
 MD5('Vielen Dank! Gibt es WLAN im Zimmer?'));

-- ============================================================
-- DIALOGUES — Scenario 3: Im Supermarkt einkaufen
-- ============================================================
SET @s3 = (SELECT id FROM scenarios WHERE content_hash = MD5(CONCAT('Im Supermarkt einkaufen','Du brauchst Lebensmittel für die Woche. Lerne, wie du nach Produkten fragst, Preise verstehst und an der Kasse bezahlst.')));

INSERT IGNORE INTO `scenario_dialogues`
  (`scenario_id`,`step_order`,`speaker`,`german_text`,`arabic_translation`,`pronunciation`,`expected_keywords`,`vocabulary`,`hint`,`grammar_tip`,`cultural_note`,`common_mistake`,`content_hash`)
VALUES
(@s3,1,'npc',
 'Guten Tag! Kann ich Ihnen helfen?',
 'مرحبًا! هل يمكنني مساعدتك؟',
 'GOO-ten Tahg! Kan ich EE-nen HEL-fen?',
 '["ja","suche","brauche","bitte"]',
 '[{"de":"suchen","ar":"يبحث عن"},{"de":"brauchen","ar":"يحتاج"},{"de":"Abteilung","ar":"القسم"}]',
 'Antworte: "Ja, ich suche..." oder "Wo finde ich..."',
 '"Ich suche" + Akkusativ: "Ich suche den Käse" / "Ich suche die Milch".',
 'In Deutschland sind Supermärkte oft bis 22 Uhr geöffnet.',
 'Sag "Ich suche" nicht "Ich schaue für" – direkter Ausdruck!',
 MD5('Guten Tag! Kann ich Ihnen helfen?')),

(@s3,2,'user_expected',
 'Ja, bitte. Wo finde ich die Milch und das Brot?',
 'نعم، من فضلك. أين أجد الحليب والخبز؟',
 'Ya, BIT-teh. Vo FIN-deh ich dee Milch unt das Broht?',
 '["milch","brot","wo","finde"]',
 '[{"de":"Milch","ar":"الحليب"},{"de":"Brot","ar":"الخبز"},{"de":"finden","ar":"يجد"}]',
 'Frage: "Wo finde ich die Milch?" und "Wo ist das Brot?"',
 '"Die Milch" (feminin), "das Brot" (neutral) – Artikel sind wichtig!',
 'Deutsche kaufen sehr viel Brot – über 300 Brotsorten gibt es in Deutschland!',
 '"Wo finde ich" korrekt, nicht "Wo kann ich finden"!',
 MD5('Ja, bitte. Wo finde ich die Milch und das Brot?')),

(@s3,3,'npc',
 'Die Milch finden Sie in Gang 3, und das Brot ist in der Bäckereiabteilung hinten rechts.',
 'ستجد الحليب في الممر 3، والخبز في قسم المخبز في الخلف على اليمين.',
 'Dee Milch FIN-den zee in Gang dry, unt das Broht ist in der BEK-er-eye-op-TY-lung HIN-ten rechts.',
 '["danke","verstehe","gut","okay"]',
 '[{"de":"Gang","ar":"الممر"},{"de":"hinten","ar":"في الخلف"},{"de":"Bäckereiabteilung","ar":"قسم المخبز"}]',
 'Antworte: "Danke sehr!" und gehe einkaufen.',
 '"In Gang 3" – Zahlen als Adjektiv ohne Endung.',
 'Viele Supermärkte in Deutschland haben eine eigene Bäckereiabteilung.',
 'Gang = aisle, nicht "Flur" im Supermarkt-Kontext.',
 MD5('Die Milch finden Sie in Gang 3, und das Brot ist in der Bäckereiabteilung hinten rechts.')),

(@s3,4,'user_expected',
 'Wie viel kostet dieses Brot?',
 'كم يكلف هذا الخبز؟',
 'Vee feel KOS-tet DEE-zes Broht?',
 '["kostet","preis","euro","wie viel"]',
 '[{"de":"kosten","ar":"يكلف"},{"de":"Preis","ar":"السعر"},{"de":"Euro","ar":"يورو"}]',
 'Frage: "Wie viel kostet das?" oder "Was kostet das Brot?"',
 '"Wie viel kostet" = How much does it cost? "Wie viel" immer zusammen.',
 'Preise in Deutschland mit Komma: 1,99 € = ein Euro neunundneunzig.',
 '"Wieviel" oder "Wie viel" – beide Schreibweisen sind akzeptabel.',
 MD5('Wie viel kostet dieses Brot?')),

(@s3,5,'npc',
 'Das kostet 2,49 Euro. Möchten Sie noch etwas?',
 'يكلف 2.49 يورو. هل تريد شيئًا آخر؟',
 'Das KOS-tet TSVYE EURO NOYN-unt-FEER-tsig. MÖKH-ten zee nokh ET-vas?',
 '["nein danke","ja","noch","tüte","beutel"]',
 '[{"de":"noch etwas","ar":"شيء آخر"},{"de":"Tüte","ar":"كيس"},{"de":"Pfand","ar":"وديعة العبوة"}]',
 'Antworte: "Nein danke" oder "Ja, ich brauche noch..." oder "Brauche ich eine Tüte?"',
 '"Möchten" = would like (höflicher als "wollen").',
 'In Deutschland kostet eine Plastiktüte extra. Man sagt: "Eine Tüte bitte."',
 '"Möchten Sie" (formal) vs "Möchtest du" (informal).',
 MD5('Das kostet 2,49 Euro. Möchten Sie noch etwas?')),

(@s3,6,'user_expected',
 'Nein danke. Kann ich mit Karte bezahlen?',
 'لا شكرًا. هل يمكنني الدفع بالبطاقة؟',
 'Nine DAN-keh. Kan ich mit KAR-teh beh-TSAH-len?',
 '["karte","bezahlen","ec","girocard","zahlen"]',
 '[{"de":"Karte","ar":"البطاقة"},{"de":"bezahlen","ar":"يدفع"},{"de":"bar","ar":"نقدًا"},{"de":"Girocard","ar":"بطاقة البنك الألماني"}]',
 'Frage: "Kann ich mit Karte bezahlen?" oder "Nehmen Sie Karte?"',
 '"Mit Karte bezahlen" = pay by card. "Mit" + Dativ.',
 'In Deutschland bevorzugen viele Geschäfte immer noch Bargeld (bar)!',
 '"Kann ich mit Karte" nicht "Kann ich per Karte" – beide OK aber erste häufiger.',
 MD5('Nein danke. Kann ich mit Karte bezahlen?'));

-- ============================================================
-- DIALOGUES — Scenario 4: Beim Arzt
-- ============================================================
SET @s4 = (SELECT id FROM scenarios WHERE content_hash = MD5(CONCAT('Beim Arzt','Du bist krank und gehst zum Arzt. Übe, deine Symptome auf Deutsch zu beschreiben und die Anweisungen des Arztes zu verstehen.')));

INSERT IGNORE INTO `scenario_dialogues`
  (`scenario_id`,`step_order`,`speaker`,`german_text`,`arabic_translation`,`pronunciation`,`expected_keywords`,`vocabulary`,`hint`,`grammar_tip`,`cultural_note`,`common_mistake`,`content_hash`)
VALUES
(@s4,1,'npc',
 'Guten Morgen! Was fehlt Ihnen heute?',
 'صباح الخير! ما الذي يزعجك اليوم؟',
 'GOO-ten MOR-gen! Vas failt EE-nen HOY-teh?',
 '["krank","schmerzen","fieber","husten","kopf","bauch"]',
 '[{"de":"Was fehlt Ihnen","ar":"ما الذي يزعجك"},{"de":"krank","ar":"مريض"},{"de":"Schmerzen","ar":"ألم"}]',
 'Beschreibe deine Symptome: "Ich habe Kopfschmerzen." oder "Mir ist schlecht."',
 '"Was fehlt Ihnen?" = What is wrong with you? (formal doctor expression)',
 'In Deutschland braucht man eine Krankenversicherungskarte (Gesundheitskarte) beim Arztbesuch.',
 '"Was fehlt Ihnen" nicht "Was ist Ihr Problem" – zu direkt!',
 MD5('Guten Morgen! Was fehlt Ihnen heute?')),

(@s4,2,'user_expected',
 'Ich habe starke Kopfschmerzen und Fieber seit zwei Tagen.',
 'لدي صداع شديد وحمى منذ يومين.',
 'Ich HAH-beh SHTAR-keh KOPF-shmer-tsen unt FEE-ber ZITE tsvye TAH-gen.',
 '["kopfschmerzen","fieber","schmerzen","krank","seit","tagen"]',
 '[{"de":"Kopfschmerzen","ar":"صداع"},{"de":"Fieber","ar":"حمى"},{"de":"seit","ar":"منذ"},{"de":"stark","ar":"شديد"}]',
 'Beschreibe: "Ich habe [Symptom] seit [Zeit]." z.B. "Ich habe Fieber seit gestern."',
 '"Seit" + Dativ für Zeitangaben: "seit zwei TAGEN" (Dativ Plural).',
 'Normaltemperatur in Deutschland: 36,5°C. Fieber ab 38°C.',
 '"Kopf-schmerzen" (Plural) nicht "Kopfschmerz" (Singular) – Deutsch benutzt Plural!',
 MD5('Ich habe starke Kopfschmerzen und Fieber seit zwei Tagen.')),

(@s4,3,'npc',
 'Haben Sie auch Halsschmerzen oder Husten?',
 'هل لديك أيضًا ألم في الحلق أو سعال؟',
 'HAH-ben zee owkh HALS-shmer-tsen OH-der HOOS-ten?',
 '["ja","nein","halsschmerzen","husten","auch"]',
 '[{"de":"Halsschmerzen","ar":"ألم الحلق"},{"de":"Husten","ar":"سعال"},{"de":"auch","ar":"أيضًا"}]',
 'Antworte: "Ja, ich habe auch..." oder "Nein, keinen Husten."',
 '"Keinen" = Negation im Akkusativ maskulin: keinen Husten.',
 'Erkältung (cold) vs Grippe (flu) – wichtiger Unterschied beim Arzt.',
 '"Kein Husten" (Nominativ) vs "keinen Husten" (Akkusativ) nach "haben".',
 MD5('Haben Sie auch Halsschmerzen oder Husten?')),

(@s4,4,'user_expected',
 'Ja, ich habe leichte Halsschmerzen aber keinen Husten.',
 'نعم، لدي ألم خفيف في الحلق لكن لا سعال.',
 'Ya, ich HAH-beh LYKH-teh HALS-shmer-tsen AH-ber KY-nen HOOS-ten.',
 '["halsschmerzen","keinen","husten","leichte","aber"]',
 '[{"de":"leicht","ar":"خفيف"},{"de":"aber","ar":"لكن"},{"de":"kein/keinen","ar":"لا/لا يوجد"}]',
 'Benutze "aber" (but) und "kein/keine/keinen" für Verneinung.',
 '"Keinen" = kein + en (maskulin Akkusativ). Adjektivdeklination!',
 'Beim Arzt ist präzise Beschreibung wichtig für die richtige Diagnose.',
 '"Leichte" stimmt (feminin Adj.) – "leichten Schmerzen" wäre Plural.',
 MD5('Ja, ich habe leichte Halsschmerzen aber keinen Husten.')),

(@s4,5,'npc',
 'Ich werde Ihnen ein Antibiotikum verschreiben. Nehmen Sie es dreimal täglich ein.',
 'سأصف لك مضادًا حيويًا. تناوله ثلاث مرات يوميًا.',
 'Ich VER-deh EE-nen ein An-tee-bee-OH-tee-kum fer-SHRY-ben.',
 '["danke","verstanden","wie oft","wann","wie lange"]',
 '[{"de":"Antibiotikum","ar":"مضاد حيوي"},{"de":"verschreiben","ar":"يصف (دواء)"},{"de":"dreimal täglich","ar":"ثلاث مرات يوميًا"}]',
 'Frage: "Wie lange soll ich es nehmen?" oder bestätige: "Verstanden, danke."',
 '"Dreimal täglich" = three times daily. Zahlen + mal: einmal, zweimal, dreimal.',
 'In Deutschland braucht man für Antibiotika immer ein Rezept (prescription).',
 '"Nehmen Sie... ein" = trennbares Verb "einnehmen" (to take medication).',
 MD5('Ich werde Ihnen ein Antibiotikum verschreiben. Nehmen Sie es dreimal täglich ein.')),

(@s4,6,'user_expected',
 'Danke, Herr Doktor. Wie lange soll ich das Medikament nehmen?',
 'شكرًا دكتور. كم من الوقت يجب أن آخذ هذا الدواء؟',
 'DAN-keh, Her DOK-tor. Vee LAN-geh zol ich das meh-dee-kah-MENT NAY-men?',
 '["wie lange","medikament","nehmen","tage","woche"]',
 '[{"de":"Medikament","ar":"الدواء"},{"de":"Rezept","ar":"الوصفة الطبية"},{"de":"wie lange","ar":"كم من الوقت"}]',
 'Frage: "Wie lange soll ich das Medikament nehmen?"',
 '"Soll ich" = should I. Modal verb "sollen" + Infinitiv am Ende.',
 'Medikamente in Deutschland kauft man in der Apotheke (pharmacy).',
 'Sag "Herr Doktor" nicht "Herr Arzt" – der Titel ist Doktor!',
 MD5('Danke, Herr Doktor. Wie lange soll ich das Medikament nehmen?'));

-- ============================================================
-- DIALOGUES — Scenario 5: Auf dem Bahnhof
-- ============================================================
SET @s5 = (SELECT id FROM scenarios WHERE content_hash = MD5(CONCAT('Auf dem Bahnhof','Du möchtest mit dem Zug von Berlin nach München fahren. Lerne, Fahrkarten zu kaufen, nach Abfahrtszeiten zu fragen und den richtigen Bahnsteig zu finden.')));

INSERT IGNORE INTO `scenario_dialogues`
  (`scenario_id`,`step_order`,`speaker`,`german_text`,`arabic_translation`,`pronunciation`,`expected_keywords`,`vocabulary`,`hint`,`grammar_tip`,`cultural_note`,`common_mistake`,`content_hash`)
VALUES
(@s5,1,'npc',
 'Guten Tag! Was kann ich für Sie tun?',
 'مرحبًا! كيف يمكنني مساعدتك؟',
 'GOO-ten Tahg! Vas kan ich foor zee toon?',
 '["fahrkarte","ticket","münchen","berlin","zug"]',
 '[{"de":"Fahrkarte","ar":"تذكرة القطار"},{"de":"Zug","ar":"القطار"},{"de":"Bahnhof","ar":"محطة القطار"}]',
 'Bestelle: "Eine Fahrkarte nach München, bitte." oder "Ich möchte nach München fahren."',
 '"Ich möchte" = I would like. Höfliche Bestellung.',
 'Die Deutsche Bahn (DB) ist das nationale Eisenbahnnetz in Deutschland.',
 '"Eine Fahrkarte" (Singular) nicht "Ein Ticket" – beides OK aber Fahrkarte ist formeller.',
 MD5('Guten Tag! Was kann ich für Sie tun?')),

(@s5,2,'user_expected',
 'Eine Fahrkarte nach München, bitte. Wann fährt der nächste Zug?',
 'تذكرة إلى ميونيخ، من فضلك. متى يغادر القطار التالي؟',
 'EYE-neh FAR-kar-teh nakh MYON-chen, BIT-teh. Van fert dehr NEKH-steh Tsoog?',
 '["münchen","fahrkarte","wann","zug","nächste"]',
 '[{"de":"nächste","ar":"التالي"},{"de":"fahren","ar":"يسافر / يذهب"},{"de":"wann","ar":"متى"}]',
 'Frage: "Wann fährt der nächste Zug nach München?"',
 '"Wann fährt" – Verb an 2. Position nach W-Fragen.',
 'ICE (Intercity-Express) ist der schnellste Zug in Deutschland.',
 '"Fährt" von "fahren" – starkes Verb: ich fahre, du fährst, er fährt.',
 MD5('Eine Fahrkarte nach München, bitte. Wann fährt der nächste Zug?')),

(@s5,3,'npc',
 'Der nächste ICE fährt um 14:35 Uhr von Gleis 7. Die Fahrt dauert etwa 4 Stunden.',
 'أسرع قطار يغادر الساعة 14:35 من الرصيف 7. تستغرق الرحلة نحو 4 ساعات.',
 'Dehr NEKH-steh ee-tseh-eh fert oom FEER-tsayn-oor DRAY-sig fon GLS ZIB-en.',
 '["danke","gut","gleis","verstanden","stunden"]',
 '[{"de":"Gleis","ar":"الرصيف"},{"de":"Stunde","ar":"ساعة"},{"de":"dauern","ar":"يستغرق"}]',
 'Antworte: "Danke, und von welchem Gleis?" oder bestätige die Information.',
 '"Dauert" von "dauern" = lasts/takes. "Die Fahrt dauert 4 Stunden."',
 'Gleise (tracks) werden nummeriert. "Gleis 7" – kein Artikel nötig mit Nummern!',
 '"Um 14:35 Uhr" = at 14:35. In Deutschland benutzt man die 24-Stunden-Uhr!',
 MD5('Der nächste ICE fährt um 14:35 Uhr von Gleis 7. Die Fahrt dauert etwa 4 Stunden.')),

(@s5,4,'user_expected',
 'Was kostet die Fahrkarte?',
 'كم تكلف التذكرة؟',
 'Vas KOS-tet dee FAR-kar-teh?',
 '["kostet","preis","euro","fahrkarte"]',
 '[{"de":"Preis","ar":"السعر"},{"de":"Sparpreis","ar":"السعر الخاص"},{"de":"BahnCard","ar":"بطاقة الخصم"}]',
 'Frage: "Was kostet die Fahrkarte?" oder "Wie viel kostet das?"',
 '"Was kostet" vs "Wie viel kostet" – beide korrekt und austauschbar.',
 'Die DB hat verschiedene Preisklassen: Sparpreis (günstig), Flexpreis (flexibel).',
 '"Kostet" Singular, auch wenn der Preis hoch ist!',
 MD5('Was kostet die Fahrkarte?')),

(@s5,5,'npc',
 'Der Sparpreis kostet 39 Euro, der Flexpreis 89 Euro. Welchen möchten Sie?',
 'السعر الخاص 39 يورو، والسعر المرن 89 يورو. أيهما تريد؟',
 'Dehr SHPAR-pryce KOS-tet noyn-unt-DRYS-sig OY-ro, dehr FLEX-pryce noyn-unt-AKHT-tsig.',
 '["sparpreis","flexpreis","günstigen","nehme","möchte"]',
 '[{"de":"Sparpreis","ar":"السعر الخاص / الأوفر"},{"de":"Flexpreis","ar":"السعر المرن"},{"de":"welchen","ar":"أيهما"}]',
 'Wähle: "Den Sparpreis bitte." oder "Ich nehme den Flexpreis."',
 '"Welchen" = which one (maskulin Akkusativ). Kasus-Übereinstimmung!',
 'Sparpreis ist nicht erstattungsfähig (non-refundable). Flexpreis kann umgebucht werden.',
 'Sag "Den Sparpreis" (Akkusativ) nicht "Der Sparpreis" (Nominativ).',
 MD5('Der Sparpreis kostet 39 Euro, der Flexpreis 89 Euro. Welchen möchten Sie?')),

(@s5,6,'user_expected',
 'Den Sparpreis, bitte. Ich zahle mit Kreditkarte.',
 'السعر الخاص، من فضلك. سأدفع ببطاقة الائتمان.',
 'Den SHPAR-pryce, BIT-teh. Ich TSAH-leh mit KRAY-dit-kar-teh.',
 '["sparpreis","kreditkarte","zahle","bitte"]',
 '[{"de":"Kreditkarte","ar":"بطاقة ائتمان"},{"de":"zahlen","ar":"يدفع"},{"de":"bar","ar":"نقدًا"}]',
 'Bestelle und zahle: "Den Sparpreis bitte. Ich zahle mit Kreditkarte."',
 '"Ich zahle mit" + Dativ: "mit Kreditkarte / mit Bargeld".',
 'An deutschen Bahnhöfen gibt es Geldautomaten (ATMs) für Bargeld.',
 'Kreditkarte (credit card) vs EC-Karte (debit card) – in Deutschland beides akzeptiert.',
 MD5('Den Sparpreis, bitte. Ich zahle mit Kreditkarte.'));

-- ============================================================
-- DIALOGUES — Scenario 6: Im Restaurant bestellen
-- ============================================================
SET @s6 = (SELECT id FROM scenarios WHERE content_hash = MD5(CONCAT('Im Restaurant bestellen','Du sitzt in einem deutschen Restaurant. Übe, die Speisekarte zu lesen, das Essen zu bestellen und die Rechnung zu verlangen.')));

INSERT IGNORE INTO `scenario_dialogues`
  (`scenario_id`,`step_order`,`speaker`,`german_text`,`arabic_translation`,`pronunciation`,`expected_keywords`,`vocabulary`,`hint`,`grammar_tip`,`cultural_note`,`common_mistake`,`content_hash`)
VALUES
(@s6,1,'npc',
 'Guten Abend! Haben Sie schon gewählt oder brauchen Sie noch einen Moment?',
 'مساء الخير! هل اخترت أم تحتاج لحظة أخرى؟',
 'GOO-ten AH-bent! HAH-ben zee shon geh-VELT oh-der BROW-chen zee nokh EYE-nen mo-MENT?',
 '["bestellen","gewählt","moment","speisekarte","ja"]',
 '[{"de":"Speisekarte","ar":"قائمة الطعام"},{"de":"wählen","ar":"يختار"},{"de":"Moment","ar":"لحظة"}]',
 'Antworte: "Ja, ich möchte bestellen." oder "Einen Moment bitte, ich schaue noch."',
 '"Haben Sie gewählt?" = Perfekt-Tense. "Gewählt" = Partizip II von "wählen".',
 'In Deutschland bestellt man beim Kellner, nicht per App.',
 '"Ich schaue noch" (I\'m still looking) – höflicher als "ich weiß nicht".',
 MD5('Guten Abend! Haben Sie schon gewählt oder brauchen Sie noch einen Moment?')),

(@s6,2,'user_expected',
 'Ja, ich möchte die Schnitzel mit Kartoffeln, bitte.',
 'نعم، أريد شنيتسل مع البطاطس، من فضلك.',
 'Ya, ich MÖKH-teh dee SHNI-tsel mit kar-TOF-feln, BIT-teh.',
 '["schnitzel","kartoffeln","möchte","bestellen","bitte"]',
 '[{"de":"Schnitzel","ar":"شنيتسل"},{"de":"Kartoffeln","ar":"البطاطس"},{"de":"Pommes","ar":"البطاطس المقلية"}]',
 'Bestelle: "Ich möchte [Gericht] mit [Beilage], bitte."',
 '"Ich möchte" + Akkusativ: "das Schnitzel" aber "die Schnitzel" (manchmal Plural).',
 'Schnitzel ist eines der bekanntesten deutschen Gerichte – originär aus Österreich.',
 '"Ich möchte" (polite) vs "Ich will" (too direct for restaurant ordering).',
 MD5('Ja, ich möchte die Schnitzel mit Kartoffeln, bitte.')),

(@s6,3,'npc',
 'Sehr gerne! Und was möchten Sie trinken?',
 'بكل سرور! وماذا تريد أن تشرب؟',
 'Zayr GERN-eh! Unt vas MÖKH-ten zee TRIN-ken?',
 '["wasser","bier","wein","cola","saft","trinken"]',
 '[{"de":"Wasser","ar":"الماء"},{"de":"Bier","ar":"البيرة"},{"de":"Saft","ar":"العصير"},{"de":"stilles Wasser","ar":"ماء بدون غاز"}]',
 'Bestelle ein Getränk: "Ein Wasser bitte." oder "Ich möchte ein Bier."',
 '"Ein Wasser" (neutral), "eine Cola" (feminin), "ein Bier" (neutral).',
 'In Deutschland muss man nach "stilles Wasser" fragen – sonst kommt Sprudelwasser!',
 '"Ein stilles Wasser" nicht "ein normales Wasser" – stilles ist der richtige Begriff.',
 MD5('Sehr gerne! Und was möchten Sie trinken?')),

(@s6,4,'user_expected',
 'Ein stilles Wasser, bitte.',
 'ماء بدون فوار، من فضلك.',
 'Yn SHTI-les VA-ser, BIT-teh.',
 '["wasser","stilles","bitte","ein"]',
 '[{"de":"still","ar":"بدون فوار"},{"de":"Sprudelwasser","ar":"ماء فوار"},{"de":"Mineralwasser","ar":"مياه معدنية"}]',
 'Bestelle: "Ein stilles Wasser, bitte." oder "Ein Mineralwasser ohne Kohlensäure."',
 '"Ein stilles Wasser" – "stilles" ist Adjektiv mit Endung (Neutrum, unbestimmter Artikel).',
 'Leitungswasser (tap water) ist in Deutschland oft gratis nicht üblich – bestellt man nicht.',
 'Sag "ohne Kohlensäure" oder "still" – beides bedeutet non-sparkling.',
 MD5('Ein stilles Wasser, bitte.')),

(@s6,5,'npc',
 'Kommt sofort! ... Bitte sehr, guten Appetit!',
 'يأتي فورًا! ... تفضل، شهية طيبة!',
 'Komt zo-FORT! ... BIT-teh zayr, GOO-ten a-peh-TEET!',
 '["danke","guten appetit","danke schön"]',
 '[{"de":"Guten Appetit","ar":"شهية طيبة"},{"de":"danke","ar":"شكرًا"},{"de":"sofort","ar":"فورًا"}]',
 'Antworte: "Danke schön!" und genieße dein Essen.',
 '"Sofort" = immediately. "Kommt sofort" = Coming right away.',
 '"Guten Appetit" sagen alle am Tisch gleichzeitig – eine deutsche Tradition!',
 'Immer "Danke schön" antworten wenn der Kellner "Guten Appetit" sagt.',
 MD5('Kommt sofort! ... Bitte sehr, guten Appetit!')),

(@s6,6,'user_expected',
 'Entschuldigung, ich hätte gerne die Rechnung.',
 'عذرًا، أريد الحساب من فضلك.',
 'Ent-SHOOL-dee-gung, ich HET-teh GERN-eh dee REKH-nung.',
 '["rechnung","zahlen","entschuldigung","bitte","bezahlen"]',
 '[{"de":"Rechnung","ar":"الحساب / الفاتورة"},{"de":"getrennt","ar":"منفصل"},{"de":"zusammen","ar":"مجتمع"}]',
 'Verlange die Rechnung: "Die Rechnung bitte!" oder "Ich hätte gerne die Rechnung."',
 '"Ich hätte gerne" = I would like (very polite, Konjunktiv II).',
 'In Deutschland zahlt oft jeder seinen eigenen Teil (getrennt). "Zusammen" = together.',
 '"Entschuldigung" zum Kellner rufen – nicht "Hey!" – das ist unhöflich!',
 MD5('Entschuldigung, ich hätte gerne die Rechnung.'));

-- ============================================================
-- DIALOGUES — Scenario 7: Sich vorstellen
-- ============================================================
SET @s7 = (SELECT id FROM scenarios WHERE content_hash = MD5(CONCAT('Sich vorstellen','Du triffst neue Leute auf einer Sprachschule. Lerne, dich vorzustellen, deinen Namen, deine Herkunft und deinen Beruf zu nennen.')));

INSERT IGNORE INTO `scenario_dialogues`
  (`scenario_id`,`step_order`,`speaker`,`german_text`,`arabic_translation`,`pronunciation`,`expected_keywords`,`vocabulary`,`hint`,`grammar_tip`,`cultural_note`,`common_mistake`,`content_hash`)
VALUES
(@s7,1,'npc',
 'Hallo! Ich bin Maria. Wie heißt du?',
 'مرحبًا! أنا ماريا. ما اسمك؟',
 'HA-lo! Ich bin ma-REE-a. Vee HYST doo?',
 '["heiße","name","ich bin","hallo"]',
 '[{"de":"heißen","ar":"يسمى / اسمه"},{"de":"Ich bin","ar":"أنا"},{"de":"Wie heißt du?","ar":"ما اسمك؟"}]',
 'Antworte: "Ich heiße [Name]." oder "Mein Name ist [Name]."',
 '"Wie heißt du?" (informal) vs "Wie heißen Sie?" (formal).',
 'In Deutschland begrüßt man neue Leute oft mit Handschlag.',
 '"Ich heiße" nicht "Mein Name bin" – bin passt nicht mit Name!',
 MD5('Hallo! Ich bin Maria. Wie heißt du?')),

(@s7,2,'user_expected',
 'Ich heiße [dein Name]. Woher kommst du?',
 'اسمي [اسمك]. من أين أنت؟',
 'Ich HY-seh [NAME]. Vo-HER KOMST doo?',
 '["heiße","woher","kommst","bin","name"]',
 '[{"de":"heißen","ar":"يسمى"},{"de":"Woher kommst du?","ar":"من أين أنت؟"},{"de":"aus","ar":"من"}]',
 'Stelle dich vor: "Ich heiße [Name]. Woher kommst du?" oder "Ich komme aus..."',
 '"Woher" = from where. "Woher kommst du?" Verb an 2. Position.',
 'Es ist höflich, nach dem eigenen Namen auch nach dem Namen der anderen Person zu fragen.',
 '"Woher" (aus welchem Ort) vs "Wo" (an welchem Ort).',
 MD5('Ich heiße [dein Name]. Woher kommst du?')),

(@s7,3,'npc',
 'Ich komme aus Berlin. Und du? Woher kommst du?',
 'أنا من برلين. وأنت؟ من أين أنت؟',
 'Ich KOM-meh ows ber-LEEN. Unt doo? Vo-HER KOMST doo?',
 '["komme","aus","herkunft","land","stadt"]',
 '[{"de":"aus","ar":"من"},{"de":"Land","ar":"البلد"},{"de":"Stadt","ar":"المدينة"}]',
 'Antworte: "Ich komme aus [Land/Stadt]." z.B. "Ich komme aus Marokko."',
 '"Ich komme aus" + Ortsname (kein Artikel für die meisten Länder).',
 '"Ich komme aus der Türkei / aus dem Iran" – mit Artikel für manche Länder.',
 'Die meisten Ländernamen haben keinen Artikel: "aus Deutschland", "aus Marokko".',
 MD5('Ich komme aus Berlin. Und du? Woher kommst du?')),

(@s7,4,'user_expected',
 'Ich komme aus Marokko. Was machst du beruflich?',
 'أنا من المغرب. ماذا تعمل؟',
 'Ich KOM-meh ows ma-ROK-ko. Vas MAKST doo beh-ROOF-likh?',
 '["marokko","beruf","arbeite","studiere","komme"]',
 '[{"de":"beruflich","ar":"مهنيًا"},{"de":"Beruf","ar":"المهنة"},{"de":"Was machst du?","ar":"ماذا تعمل؟"}]',
 'Frage nach dem Beruf: "Was machst du beruflich?" oder "Was bist du von Beruf?"',
 '"Was machst du beruflich?" = What do you do for work?',
 'Beruf (profession) ist ein wichtiges Gesprächsthema in Deutschland.',
 '"Beruflich" (professionally) macht die Frage klarer als nur "Was machst du?"',
 MD5('Ich komme aus Marokko. Was machst du beruflich?')),

(@s7,5,'npc',
 'Ich bin Lehrerin. Und du, was machst du?',
 'أنا معلمة. وأنت، ماذا تعمل؟',
 'Ich bin LEH-rer-in. Unt doo, vas MAKST doo?',
 '["arbeite","studiere","bin","beruf","schüler","student"]',
 '[{"de":"Lehrer/in","ar":"معلم/معلمة"},{"de":"Student/in","ar":"طالب/طالبة"},{"de":"Arzt/Ärztin","ar":"طبيب/طبيبة"}]',
 'Antworte: "Ich bin [Beruf]." oder "Ich studiere [Fach]." oder "Ich arbeite als [Beruf]."',
 '"Ich bin Lehrer" (kein Artikel!) vs "Ich bin EIN guter Lehrer" (mit Artikel + Adjektiv).',
 'Im Deutschen gibt es männliche und weibliche Berufsformen: Lehrer / Lehrerin.',
 'Sag "Ich bin Student" ohne Artikel – Berufsbezeichnungen haben keinen Artikel!',
 MD5('Ich bin Lehrerin. Und du, was machst du?')),

(@s7,6,'user_expected',
 'Ich studiere Deutsch an der Universität. Schön, dich kennenzulernen!',
 'أدرس الألمانية في الجامعة. سعيد بمعرفتك!',
 'Ich shtoo-DEER-eh Doytsh an dehr oo-nee-ver-zee-TET. Shoen, dikh KEN-en-tsoo-LER-nen!',
 '["studiere","deutsch","universität","kennenzulernen","schön"]',
 '[{"de":"studieren","ar":"يدرس"},{"de":"Universität","ar":"الجامعة"},{"de":"kennenlernen","ar":"التعرف على"}]',
 'Stelle dich beruflich vor und verabschiede dich: "Schön, dich kennenzulernen!"',
 '"Schön, dich kennenzulernen" = Nice to meet you (informal).',
 'In Deutschland sagt man "Schön, Sie kennenzulernen" (formell) bei Erwachsenen.',
 '"Ich studiere" (Present Tense) vs "Ich habe studiert" (Past – abgeschlossen).',
 MD5('Ich studiere Deutsch an der Universität. Schön, dich kennenzulernen!'));

-- ============================================================
-- DIALOGUES — Scenario 8: Eine Wohnung mieten
-- ============================================================
SET @s8 = (SELECT id FROM scenarios WHERE content_hash = MD5(CONCAT('Eine Wohnung mieten','Du suchst eine Wohnung in Deutschland. Übe, mit einem Vermieter zu sprechen, nach Miete und Nebenkosten zu fragen und einen Besichtigungstermin zu vereinbaren.')));

INSERT IGNORE INTO `scenario_dialogues`
  (`scenario_id`,`step_order`,`speaker`,`german_text`,`arabic_translation`,`pronunciation`,`expected_keywords`,`vocabulary`,`hint`,`grammar_tip`,`cultural_note`,`common_mistake`,`content_hash`)
VALUES
(@s8,1,'npc',
 'Guten Tag! Sie interessieren sich für die Wohnung?',
 'مرحبًا! أنت مهتم بالشقة؟',
 'GOO-ten Tahg! Zee in-ter-ES-see-ren zikh foor dee VOH-nung?',
 '["ja","wohnung","interessiere","besichtigen","miete"]',
 '[{"de":"Wohnung","ar":"الشقة"},{"de":"sich interessieren","ar":"يهتم بـ"},{"de":"Miete","ar":"الإيجار"}]',
 'Antworte: "Ja, genau. Ich interessiere mich für die Wohnung."',
 '"Sich interessieren für" = to be interested in. Reflexivverb!',
 'Der Wohnungsmarkt in deutschen Großstädten ist sehr umkämpft.',
 '"Ich interessiere mich" nicht "Ich interessiere" – Reflexivpronomen nicht vergessen!',
 MD5('Guten Tag! Sie interessieren sich für die Wohnung?')),

(@s8,2,'user_expected',
 'Ja, genau. Wie hoch ist die monatliche Miete?',
 'نعم، بالضبط. كم هو الإيجار الشهري؟',
 'Ya, geh-NOW. Vee hohkh ist dee mo-NAT-li-kheh MEE-teh?',
 '["miete","monatlich","wie hoch","kosten","warm","kalt"]',
 '[{"de":"Miete","ar":"الإيجار"},{"de":"Warmmiete","ar":"الإيجار شامل التدفئة"},{"de":"Kaltmiete","ar":"الإيجار بدون تدفئة"}]',
 'Frage: "Wie hoch ist die Miete?" und "Sind die Nebenkosten inbegriffen?"',
 '"Wie hoch" = how high/much. Mit Preisen und Mengen.',
 'In Deutschland unterscheidet man zwischen Kaltmiete (ohne Nebenkosten) und Warmmiete.',
 '"Wie hoch" für Miete/Preise, "wie groß" für Fläche.',
 MD5('Ja, genau. Wie hoch ist die monatliche Miete?')),

(@s8,3,'npc',
 'Die Kaltmiete beträgt 750 Euro. Mit Nebenkosten sind es etwa 950 Euro warm.',
 'الإيجار الأساسي 750 يورو. مع الرسوم الإضافية حوالي 950 يورو شامل.',
 'Dee KALT-mee-teh beh-TREKHT ZIB-en-HON-dert FOONF-tsig OY-ro.',
 '["verstanden","nebenkosten","kaution","warm","kalt"]',
 '[{"de":"Nebenkosten","ar":"الرسوم الإضافية"},{"de":"Kaution","ar":"التأمين"},{"de":"betragen","ar":"يبلغ"}]',
 'Frage nach der Kaution: "Wie hoch ist die Kaution?" oder bestätige die Info.',
 '"Betragen" = to amount to. "Die Miete beträgt 750 Euro."',
 'Kaution in Deutschland ist meist 3 Monatsmieten – gesetzlich geregelt.',
 '"Beträgt" von "betragen" – starkes Verb, merke die Form!',
 MD5('Die Kaltmiete beträgt 750 Euro. Mit Nebenkosten sind es etwa 950 Euro warm.')),

(@s8,4,'user_expected',
 'Und wie hoch ist die Kaution?',
 'وكم مبلغ التأمين؟',
 'Unt vee hohkh ist dee kow-tsee-OHN?',
 '["kaution","hoch","monatlich","drei"]',
 '[{"de":"Kaution","ar":"التأمين"},{"de":"Monatsmiete","ar":"إيجار شهري"},{"de":"drei","ar":"ثلاثة"}]',
 'Frage: "Wie hoch ist die Kaution?" Typisch: 2-3 Monatsmieten.',
 '"Kaution" = deposit/security. Immer feminine: die Kaution.',
 'Die Kaution wird nach dem Auszug zurückgezahlt, wenn die Wohnung in Ordnung ist.',
 'Sag "die Kaution" mit dem Artikel – Nomen immer mit Artikel lernen!',
 MD5('Und wie hoch ist die Kaution?')),

(@s8,5,'npc',
 'Drei Monatsmieten, also 2.250 Euro. Möchten Sie die Wohnung besichtigen?',
 'ثلاثة أشهر إيجار، أي 2.250 يورو. هل تريد معاينة الشقة؟',
 'Dry mo-NATS-mee-ten, AL-zo TSVYE-TOW-zent TSVYE-HUNT-dert FOONF-tsig OY-ro.',
 '["besichtigen","ja","termin","wann","gerne"]',
 '[{"de":"besichtigen","ar":"يعاين / يزور"},{"de":"Termin","ar":"الموعد"},{"de":"vereinbaren","ar":"يتفق على"}]',
 'Antworte: "Ja, gerne! Wann kann ich die Wohnung besichtigen?"',
 '"Möchten Sie" + Infinitiv am Ende: "besichtigen".',
 'Wohnungsbesichtigungen sind in Deutschland oft für mehrere Bewerber gleichzeitig.',
 '"Besichtigen" (to view a property) – spezifisches Wort für Immobilien.',
 MD5('Drei Monatsmieten, also 2.250 Euro. Möchten Sie die Wohnung besichtigen?')),

(@s8,6,'user_expected',
 'Ja, sehr gerne! Wann wäre ein Termin möglich?',
 'نعم، بكل سرور! متى يمكن تحديد موعد؟',
 'Ya, zayr GERN-eh! Van VEH-reh yn ter-MEEN MÖKH-likh?',
 '["termin","wäre","möglich","wann","besichtigung"]',
 '[{"de":"Termin","ar":"الموعد"},{"de":"möglich","ar":"ممكن"},{"de":"wäre","ar":"سيكون (مهذب)"}]',
 'Frage nach einem Termin: "Wann wäre ein Termin möglich?" oder "Haben Sie Zeit am...?"',
 '"Wäre" = Konjunktiv II von "sein" – sehr höflich für Anfragen.',
 'In Deutschland vereinbart man Termine immer im Voraus – einfach auftauchen ist unhöflich.',
 '"Wann wäre möglich" – "wäre" macht es höflicher als "wann ist möglich".',
 MD5('Ja, sehr gerne! Wann wäre ein Termin möglich?'));

-- ============================================================
-- DIALOGUES — Scenario 9: Beim Bäcker
-- ============================================================
SET @s9 = (SELECT id FROM scenarios WHERE content_hash = MD5(CONCAT('Beim Bäcker','Du gehst morgens zum Bäcker um die Ecke. Lerne typische Bestellungen, Brötchenarten und höfliche Ausdrücke beim Einkauf.')));

INSERT IGNORE INTO `scenario_dialogues`
  (`scenario_id`,`step_order`,`speaker`,`german_text`,`arabic_translation`,`pronunciation`,`expected_keywords`,`vocabulary`,`hint`,`grammar_tip`,`cultural_note`,`common_mistake`,`content_hash`)
VALUES
(@s9,1,'npc',
 'Guten Morgen! Was darf es sein?',
 'صباح الخير! ماذا تريد؟',
 'GOO-ten MOR-gen! Vas darf es zyn?',
 '["brötchen","brot","bestelle","hätte","möchte"]',
 '[{"de":"Brötchen","ar":"الخبيز الصغير"},{"de":"Brot","ar":"الخبز"},{"de":"Was darf es sein?","ar":"ماذا تريد؟"}]',
 'Bestelle: "Ich hätte gerne vier Brötchen." oder "Geben Sie mir bitte..."',
 '"Was darf es sein?" = What would you like? Typische Bäckerphrase.',
 'Deutsche kaufen täglich frische Brötchen – das ist ein wichtiges Ritual.',
 '"Ich hätte gerne" (polite) – perfekt für Läden und Restaurants.',
 MD5('Guten Morgen! Was darf es sein?')),

(@s9,2,'user_expected',
 'Ich hätte gerne vier Brötchen und ein Vollkornbrot, bitte.',
 'أريد أربعة خبيزات وخبز حبوب كاملة، من فضلك.',
 'Ich HET-teh GERN-eh feer BROET-khen unt yn FOL-korn-broht, BIT-teh.',
 '["brötchen","vollkornbrot","hätte","gerne","vier"]',
 '[{"de":"Vollkornbrot","ar":"خبز الحبوب الكاملة"},{"de":"Weißbrot","ar":"الخبز الأبيض"},{"de":"Roggenbrötchen","ar":"خبيز الجاودار"}]',
 'Bestelle: "Ich hätte gerne [Anzahl] [Backware]." z.B. "vier Brötchen und ein Croissant".',
 '"Vier Brötchen" – Zahlen ohne Artikel direkt vor dem Nomen.',
 'Es gibt über 300 Brotsorten in Deutschland – Brot ist Kulturgut!',
 '"Brötchen" = Plural = Singular (kein -en nötig)! Brötchen / Brötchen.',
 MD5('Ich hätte gerne vier Brötchen und ein Vollkornbrot, bitte.')),

(@s9,3,'npc',
 'Sehr gerne. Möchten Sie noch etwas dazu? Wir haben frische Croissants und Kuchen.',
 'بكل سرور. هل تريد شيئًا آخر؟ لدينا كرواسان وكيك طازج.',
 'Zayr GERN-eh. MÖKH-ten zee nokh ET-vas da-TSOO? Vir HAH-ben FRESH-eh cro-SANTS unt KOO-khen.',
 '["nein","ja","croissant","kuchen","danke","noch"]',
 '[{"de":"Kuchen","ar":"الكيك"},{"de":"Croissant","ar":"كرواسان"},{"de":"frisch","ar":"طازج"}]',
 'Antworte: "Nein danke, das ist alles." oder "Ja, ich hätte gerne ein Croissant."',
 '"Das ist alles" = That is everything/all. Schlussformel beim Einkaufen.',
 'Kuchen am Wochenende ist eine deutsche Tradition – "Kaffeekuchen" am Nachmittag.',
 '"Noch etwas" = anything else. Lerne diese Formel auswendig!',
 MD5('Sehr gerne. Möchten Sie noch etwas dazu? Wir haben frische Croissants und Kuchen.')),

(@s9,4,'user_expected',
 'Nein danke, das ist alles. Was macht das zusammen?',
 'لا شكرًا، هذا كل شيء. كم المجموع؟',
 'Nine DAN-keh, das ist A-les. Vas MAKHT das tsoo-ZA-men?',
 '["alles","zusammen","was macht","preis","euro"]',
 '[{"de":"zusammen","ar":"المجموع"},{"de":"Was macht das?","ar":"كم المجموع؟"},{"de":"insgesamt","ar":"إجمالًا"}]',
 'Frage nach dem Gesamtpreis: "Was macht das zusammen?" oder "Was kostet das alles?"',
 '"Was macht das zusammen?" = What does that come to? Typische Kassenphrase.',
 'In der Bäckerei zahlt man oft bar – viele kleine Läden nehmen keine Karte.',
 '"Zusammen" (together/total) am Ende der Frage – typische Satzstruktur.',
 MD5('Nein danke, das ist alles. Was macht das zusammen?')),

(@s9,5,'npc',
 'Das macht 4 Euro 80 zusammen. Zahlen Sie bar oder mit Karte?',
 'المجموع 4 يورو و80 سنتًا. هل تدفع نقدًا أم بالبطاقة؟',
 'Das makht FEER OY-ro AKHT-tsig tsoo-ZA-men. TSAH-len zee bar OH-der mit KAR-teh?',
 '["bar","karte","zahle","euro","cent"]',
 '[{"de":"bar","ar":"نقدًا"},{"de":"Karte","ar":"البطاقة"},{"de":"Wechselgeld","ar":"الباقي / الفكة"}]',
 'Antworte: "Ich zahle bar." oder "Mit Karte bitte." Gib das Geld oder die Karte.',
 '"4 Euro 80" = vier Euro achtzig. Cents nach dem Komma.',
 'Viele Bäcker in Deutschland bevorzugen noch Bargeld!',
 '"Ich zahle bar" (I pay cash) – "zahlen" nicht "bezahlen" – beides OK.',
 MD5('Das macht 4 Euro 80 zusammen. Zahlen Sie bar oder mit Karte?')),

(@s9,6,'user_expected',
 'Ich zahle bar. Hier sind 5 Euro.',
 'سأدفع نقدًا. ها هو خمسة يورو.',
 'Ich TSAH-leh bar. Heer zint FOONF OY-ro.',
 '["bar","zahle","euro","fünf","hier"]',
 '[{"de":"Hier sind","ar":"ها هي/هو"},{"de":"Wechselgeld","ar":"الباقي"},{"de":"Rückgeld","ar":"الفكة"}]',
 'Bezahle: "Ich zahle bar. Hier sind 5 Euro." Warte auf das Wechselgeld.',
 '"Hier sind" = here are. Mit Plural "Hier sind 5 Euro".',
 'Sag "Stimmt so" (keep the change) wenn du Trinkgeld geben möchtest.',
 '"Hier ist" (Singular) vs "Hier sind" (Plural) – "5 Euro" ist Plural!',
 MD5('Ich zahle bar. Hier sind 5 Euro.'));

-- ============================================================
-- DIALOGUES — Scenario 10: Im Café
-- ============================================================
SET @s10 = (SELECT id FROM scenarios WHERE content_hash = MD5(CONCAT('Im Café','Du triffst einen deutschen Bekannten in einem Café. Übe Small-Talk, Getränke bestellen und einfache Unterhaltungen auf Deutsch führen.')));

INSERT IGNORE INTO `scenario_dialogues`
  (`scenario_id`,`step_order`,`speaker`,`german_text`,`arabic_translation`,`pronunciation`,`expected_keywords`,`vocabulary`,`hint`,`grammar_tip`,`cultural_note`,`common_mistake`,`content_hash`)
VALUES
(@s10,1,'npc',
 'Hey! Schön dich zu sehen! Wie geht es dir?',
 'مرحبًا! سعيد برؤيتك! كيف حالك؟',
 'Hey! Shoen dikh tsoo ZAY-en! Vee gayt es deer?',
 '["gut","super","danke","und dir","geht es"]',
 '[{"de":"Wie geht es dir?","ar":"كيف حالك؟"},{"de":"gut","ar":"جيد"},{"de":"super","ar":"ممتاز"}]',
 'Antworte: "Gut, danke! Und dir?" oder "Mir geht es super!"',
 '"Wie geht es dir?" (informal) vs "Wie geht es Ihnen?" (formal).',
 'Small-Talk ist in Deutschland wichtig als Einstieg in jedes Gespräch.',
 '"Mir geht es gut" (Dativ: mir) nicht "Ich gehe gut"!',
 MD5('Hey! Schön dich zu sehen! Wie geht es dir?')),

(@s10,2,'user_expected',
 'Mir geht es gut, danke! Und dir?',
 'أنا بخير شكرًا! وأنت؟',
 'Meer gayt es goot, DAN-keh! Unt deer?',
 '["gut","danke","dir","und","geht"]',
 '[{"de":"Mir","ar":"لي (بصيغة ممتازة)"},{"de":"es geht","ar":"الحال"},{"de":"super","ar":"رائع"}]',
 'Antworte: "Mir geht es gut, danke! Und dir?" – Die Gegenfrage zeigt Interesse.',
 '"Mir geht es gut" – "mir" ist Dativ von "ich". Reflexiver Ausdruck.',
 'In Deutschland ist es höflich, immer zurückzufragen "Und dir/Ihnen?".',
 '"Mir" nicht "Mich" – Dativ nicht Akkusativ!',
 MD5('Mir geht es gut, danke! Und dir?')),

(@s10,3,'npc',
 'Auch gut! Was möchtest du bestellen? Ich nehme einen Cappuccino.',
 'أنا أيضًا بخير! ماذا تريد أن تطلب؟ سآخذ كابوتشينو.',
 'Owkh goot! Vas MÖKH-test doo beh-SHTE-len? Ich NAY-meh EY-nen Ka-pu-CHEE-no.',
 '["kaffee","tee","cappuccino","latte","nehme","möchte"]',
 '[{"de":"Cappuccino","ar":"كابوتشينو"},{"de":"Latte Macchiato","ar":"لاتيه ماكياتو"},{"de":"Espresso","ar":"إسبريسو"}]',
 'Bestelle: "Ich nehme auch einen Cappuccino." oder "Ich möchte einen Tee bitte."',
 '"Einen Cappuccino" – maskulin + Akkusativ = einen (unbestimmter Artikel).',
 'Deutschland hat eine starke Kaffeekultur – viele Cafés mit Kuchen!',
 '"Ich nehme" (I\'ll take) ist kolloqiualer als "Ich möchte" am Tisch mit Freunden.',
 MD5('Auch gut! Was möchtest du bestellen? Ich nehme einen Cappuccino.')),

(@s10,4,'user_expected',
 'Ich nehme einen Latte Macchiato. Wie war dein Wochenende?',
 'سآخذ لاتيه ماكياتو. كيف كانت عطلة نهاية الأسبوع؟',
 'Ich NAY-meh EY-nen LA-teh ma-KEE-a-to. Vee var dyn VO-khen-en-deh?',
 '["latte","wochenende","war","wie","weekend"]',
 '[{"de":"Wochenende","ar":"عطلة نهاية الأسبوع"},{"de":"war","ar":"كان"},{"de":"wie war","ar":"كيف كان"}]',
 'Bestelle und frage nach dem Wochenende: "Wie war dein Wochenende?"',
 '"Wie war" = Vergangenheit (Präteritum) von "sein". "War" = was.',
 'Wochenende-Pläne sind beliebtes Small-Talk-Thema in Deutschland.',
 '"Wie war dein Wochenende?" – "war" (past) nicht "ist" (present)!',
 MD5('Ich nehme einen Latte Macchiato. Wie war dein Wochenende?')),

(@s10,5,'npc',
 'Es war super! Ich war mit Freunden in einem Konzert. Und deins?',
 'كان رائعًا! كنت مع أصدقاء في حفلة موسيقية. وعطلتك؟',
 'Es var ZOO-per! Ich var mit FROYN-den in EY-nem kon-TSERT. Unt dyns?',
 '["war","gemacht","gegangen","zuhause","schön","auch"]',
 '[{"de":"Konzert","ar":"الحفل الموسيقي"},{"de":"Freunde","ar":"الأصدقاء"},{"de":"mit","ar":"مع"}]',
 'Antworte über dein Wochenende: "Ich war zu Hause." oder "Ich habe [Aktivität] gemacht."',
 '"Ich war" = I was. "Ich habe gemacht" = I did/made (Perfekt).',
 'Deutsche gehen sehr gerne zu Konzerten und kulturellen Veranstaltungen.',
 '"Mit Freunden" + Dativ Plural: Freunden (nicht Freunde nach "mit").',
 MD5('Es war super! Ich war mit Freunden in einem Konzert. Und deins?')),

(@s10,6,'user_expected',
 'Ich habe zu Hause entspannt und Deutsch gelernt. Es macht mir Spaß!',
 'استرحت في المنزل وتعلمت الألمانية. إنه ممتع بالنسبة لي!',
 'Ich HAH-beh tsoo HOW-zeh ent-SHPANT unt Doytsh geh-LERNT. Es MAKHT meer Shpass!',
 '["entspannt","gelernt","zuhause","spaß","deutsch"]',
 '[{"de":"entspannen","ar":"يسترخي"},{"de":"lernen","ar":"يتعلم"},{"de":"Spaß machen","ar":"يكون ممتعًا"}]',
 'Erzähle von deinem Wochenende: "Ich habe Deutsch gelernt. Es macht mir Spaß!"',
 '"Es macht mir Spaß" = It is fun for me. "Mir" (Dativ)!',
 'Deutsche schätzen es sehr, wenn man ihre Sprache lernt und Interesse zeigt.',
 '"Spaß machen" (to be fun) – "mir" nicht "mich" – Dativ!',
 MD5('Ich habe zu Hause entspannt und Deutsch gelernt. Es macht mir Spaß!'));

-- ============================================================
-- SCENARIO TIPS (one per scenario)
-- ============================================================
INSERT IGNORE INTO `scenario_tips` (`scenario_id`,`grammar_tip`,`vocabulary_list`,`cultural_note`,`common_mistakes`) VALUES

((SELECT id FROM scenarios WHERE content_hash = MD5(CONCAT('Am Flughafen ankommen','Du landest zum ersten Mal in Deutschland. Lerne, wie du dich am Flughafen orientierst, nach dem Gepäck fragst und den Ausgang findest.'))),
'Benutze "Können Sie mir helfen?" (formell) mit Fremden. W-Fragen: Wo, Was, Wann, Wer.',
'[{"de":"Gepäck","ar":"الأمتعة"},{"de":"Ausgang","ar":"المخرج"},{"de":"Ankunft","ar":"الوصول"},{"de":"Abflug","ar":"المغادرة"},{"de":"Zoll","ar":"الجمارك"}]',
'In deutschen Flughäfen wird immer Formell gesprochen. Benutze Sie (formell) nicht du.',
'["Vergiss nicht das bitte nach jeder Bitte","Benutze Sie nicht du mit Fremden","Wo ist vs Wo befindet sich – beide korrekt"]'),

((SELECT id FROM scenarios WHERE content_hash = MD5(CONCAT('Im Hotel einchecken','Du kommst in deinem Hotel an und möchtest einchecken. Übe typische Fragen zur Reservierung, zum Zimmer und zu den Einrichtungen.'))),
'Perfekt: haben + Partizip II: Ich habe gebucht. Uhrzeiten auf Deutsch: 14:00 = vierzehn Uhr.',
'[{"de":"Rezeption","ar":"الاستقبال"},{"de":"Einzelzimmer","ar":"غرفة مفردة"},{"de":"Doppelzimmer","ar":"غرفة مزدوجة"},{"de":"Frühstück","ar":"الإفطار"},{"de":"WLAN","ar":"الواي فاي"}]',
'Deutsche Hotels verlangen oft einen Personalausweis oder Reisepass beim Einchecken.',
'["Sag auf den Namen nicht mit dem Namen","Frühstück inklusive fragen ist wichtig","WLAN-Passwort immer fragen"]'),

((SELECT id FROM scenarios WHERE content_hash = MD5(CONCAT('Im Supermarkt einkaufen','Du brauchst Lebensmittel für die Woche. Lerne, wie du nach Produkten fragst, Preise verstehst und an der Kasse bezahlst.'))),
'Artikel lernen: der/die/das. Akkusativ nach möchten und kaufen. Wie viel kostet...?',
'[{"de":"Kasse","ar":"الصندوق"},{"de":"Tüte","ar":"الكيس"},{"de":"Pfand","ar":"وديعة العبوة"},{"de":"Angebot","ar":"العرض"},{"de":"Rabatt","ar":"الخصم"}]',
'Pfandsystem: In Deutschland gibt man leere Flaschen zurück und bekommt Geld zurück.',
'["Tüte extra bezahlen nicht vergessen","Bar oft bevorzugt in kleinen Läden","Preise mit Komma: 2,99 nicht 2.99"]'),

((SELECT id FROM scenarios WHERE content_hash = MD5(CONCAT('Beim Arzt','Du bist krank und gehst zum Arzt. Übe, deine Symptome auf Deutsch zu beschreiben und die Anweisungen des Arztes zu verstehen.'))),
'Zeitangaben mit seit + Dativ: seit zwei Tagen. Körperteile mit Schmerzen: Kopfschmerzen, Bauchschmerzen.',
'[{"de":"Apotheke","ar":"الصيدلية"},{"de":"Rezept","ar":"الوصفة"},{"de":"Krankenversicherung","ar":"التأمين الصحي"},{"de":"Symptome","ar":"الأعراض"},{"de":"Fieber","ar":"الحمى"}]',
'In Deutschland braucht man immer die Gesundheitskarte (Krankenversicherungskarte) beim Arzt.',
'["Seit + Dativ: seit zwei Tagen nicht seit zwei Tage","Kopfschmerzen ist Plural – immer!","Doktor nicht Arzt als Anrede"]'),

((SELECT id FROM scenarios WHERE content_hash = MD5(CONCAT('Auf dem Bahnhof','Du möchtest mit dem Zug von Berlin nach München fahren. Lerne, Fahrkarten zu kaufen, nach Abfahrtszeiten zu fragen und den richtigen Bahnsteig zu finden.'))),
'24-Stunden-Uhr: 14:35 = vierzehn Uhr fünfunddreißig. Akkusativ für Fahrkarte: eine Fahrkarte kaufen.',
'[{"de":"Gleis","ar":"الرصيف"},{"de":"Abfahrt","ar":"المغادرة"},{"de":"Ankunft","ar":"الوصول"},{"de":"Verspätung","ar":"التأخير"},{"de":"ICE","ar":"القطار السريع"}]',
'Die Deutsche Bahn (DB) hat oft Verspätungen – lerne: Der Zug hat X Minuten Verspätung.',
'["Gleis ohne Artikel mit Nummer: Gleis 7","Fahrkarte kaufen nicht ticket kaufen","Den Sparpreis (Akkusativ) nicht der Sparpreis"]'),

((SELECT id FROM scenarios WHERE content_hash = MD5(CONCAT('Im Restaurant bestellen','Du sitzt in einem deutschen Restaurant. Übe, die Speisekarte zu lesen, das Essen zu bestellen und die Rechnung zu verlangen.'))),
'Konjunktiv II für Höflichkeit: Ich hätte gerne... Artikel nach Ich möchte: Akkusativ!',
'[{"de":"Speisekarte","ar":"قائمة الطعام"},{"de":"Rechnung","ar":"الحساب"},{"de":"Trinkgeld","ar":"الإكراميه"},{"de":"Kellner","ar":"النادل"},{"de":"Tageskarte","ar":"قائمة اليوم"}]',
'Trinkgeld (tip) ist in Deutschland nicht Pflicht – 5-10% ist üblich wenn zufrieden.',
'["Ich hätte gerne (nicht Ich will) – höflicher","Getrennt oder zusammen zahlen fragen","Stilles Wasser bestellen wenn kein Sprudel gewünscht"]'),

((SELECT id FROM scenarios WHERE content_hash = MD5(CONCAT('Sich vorstellen','Du triffst neue Leute auf einer Sprachschule. Lerne, dich vorzustellen, deinen Namen, deine Herkunft und deinen Beruf zu nennen.'))),
'Berufe ohne Artikel: Ich bin Lehrer/Studentin. Aus + Landesnamen meist ohne Artikel.',
'[{"de":"Beruf","ar":"المهنة"},{"de":"Herkunft","ar":"المنشأ"},{"de":"Sprachen","ar":"اللغات"},{"de":"Hobby","ar":"الهواية"},{"de":"Alter","ar":"العمر"}]',
'Beim ersten Kennenlernen fragt man oft: Name, Herkunft, Beruf, Sprachen.',
'["Ich bin Student ohne Artikel – kein ein vor Berufen","Ich heiße nicht Ich bin mein Name","Schön dich kennenzulernen (informal) vs Sie (formal)"]'),

((SELECT id FROM scenarios WHERE content_hash = MD5(CONCAT('Eine Wohnung mieten','Du suchst eine Wohnung in Deutschland. Übe, mit einem Vermieter zu sprechen, nach Miete und Nebenkosten zu fragen und einen Besichtigungstermin zu vereinbaren.'))),
'Reflexivverben: Ich interessiere mich für. Konjunktiv II für Höflichkeit: Wann wäre möglich?',
'[{"de":"Kaltmiete","ar":"الإيجار بدون تدفئة"},{"de":"Warmmiete","ar":"الإيجار شامل التدفئة"},{"de":"Kaution","ar":"التأمين"},{"de":"Vermieter","ar":"المالك"},{"de":"Mietvertrag","ar":"عقد الإيجار"}]',
'In Deutschland gibt es oft viele Bewerber für eine Wohnung – Selbstauskunft und Schufa nötig.',
'["Sich interessieren für – Reflexivpronomen nicht vergessen","Warmmiete vs Kaltmiete Unterschied kennen","Kaution: maximal 3 Monatsmieten laut Gesetz"]'),

((SELECT id FROM scenarios WHERE content_hash = MD5(CONCAT('Beim Bäcker','Du gehst morgens zum Bäcker um die Ecke. Lerne typische Bestellungen, Brötchenarten und höfliche Ausdrücke beim Einkauf.'))),
'Ich hätte gerne = I would like (sehr höflich). Zahlen vor Nomen: vier Brötchen.',
'[{"de":"Brötchen","ar":"الخبيز"},{"de":"Vollkornbrot","ar":"خبز الحبوب الكاملة"},{"de":"Croissant","ar":"كرواسان"},{"de":"Kuchen","ar":"الكيك"},{"de":"frisch","ar":"طازج"}]',
'Bäcker in Deutschland öffnen sehr früh (ab 5-6 Uhr) und viele Deutsche kaufen täglich Brot.',
'["Brötchen Plural = Brötchen (kein -s oder -en)","Stimmt so = keep the change als Trinkgeld","Bar oft bevorzugt in Bäckereien"]'),

((SELECT id FROM scenarios WHERE content_hash = MD5(CONCAT('Im Café','Du triffst einen deutschen Bekannten in einem Café. Übe Small-Talk, Getränke bestellen und einfache Unterhaltungen auf Deutsch führen.'))),
'Mir geht es gut = Dativ (nicht mich). Perfekt für vergangene Ereignisse: Ich habe gemacht.',
'[{"de":"Cappuccino","ar":"كابوتشينو"},{"de":"Small-Talk","ar":"محادثة عامة"},{"de":"Wochenende","ar":"عطلة نهاية الأسبوع"},{"de":"Konzert","ar":"حفلة موسيقية"},{"de":"entspannen","ar":"يسترخي"}]',
'Kaffeehauskultur ist sehr wichtig in Deutschland und Österreich – man sitzt lange.',
'["Mir geht es gut nicht Ich gehe gut","Und dir? als Gegenfrage zeigt Höflichkeit","Spaß machen: mir (Dativ) Spaß nicht mich Spaß"]');
