CREATE TABLE boardgame_alerts (
  participant_id INT NOT NULL,
  boardgame_id INT UNSIGNED NOT NULL,
  PRIMARY KEY(participant_id, boardgame_id),
  CONSTRAINT `boardgame_alerts_pifk` FOREIGN KEY (`participant_id`) REFERENCES `deltagere` (`id`),
  CONSTRAINT `boardgame_alerts_bifk` FOREIGN KEY (`boardgame_id`) REFERENCES `boardgames` (`id`)
) engine=InnoDB DEFAULT CHARSET utf8mb4;