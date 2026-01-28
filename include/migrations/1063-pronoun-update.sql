ALTER TABLE deltagere DROP COLUMN `pronoun`;
ALTER TABLE deltagere ADD COLUMN `pronouns` varchar(4) DEFAULT "none" NOT NULL;