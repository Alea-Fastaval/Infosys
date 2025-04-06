CREATE TABLE boardgame_rankings (
  id INT NOT NULL AUTO_INCREMENT,
  participant_id INT NOT NULL,
  boardgame_id INT NOT NULL,
  ranking INT NOT NULL,
  PRIMARY KEY(id),
  UNIQUE (participant_id, boardgame_id),
  CONSTRAINT `boardgame_rankings_pifk` FOREIGN KEY (`participant_id`) REFERENCES `deltagere` (`id`),
  CONSTRAINT `boardgame_rankings_bifk` FOREIGN KEY (`boardgame_id`) REFERENCES `aktiviteter` (`id`)
) engine=InnoDB DEFAULT CHARSET utf8mb4;