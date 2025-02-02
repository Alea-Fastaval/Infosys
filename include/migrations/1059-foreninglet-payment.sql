DROP TABLE IF EXISTS `payment_registrations`;
DROP TABLE IF EXISTS `payment_users`;
CREATE TABLE payment_users (
  participant_id INT NOT NULL,
  payment_user_id INT NOT NULL,
  payment_display_id INT NOT NULL,
  PRIMARY KEY(participant_id),
  UNIQUE (payment_user_id),
  CONSTRAINT `payment_users_pifk` FOREIGN KEY (`participant_id`) REFERENCES `deltagere` (`id`)
) engine=InnoDB DEFAULT CHARSET utf8mb4;

CREATE TABLE payment_registrations (
  id INT NOT NULL AUTO_INCREMENT,
  payment_user_id INT NOT NULL,
  amount INT NOT NULL,
  created INT,
  token TEXT DEFAULT NULL,
  fl_id INT DEFAULT NULL,
  completed INT DEFAULT NULL,
  status char(10) DEFAULT 'created',
  PRIMARY KEY(id),
  CONSTRAINT `payment_registrations_pufk` FOREIGN KEY (`payment_user_id`) REFERENCES `payment_users` (`payment_user_id`)
) engine=InnoDB DEFAULT CHARSET utf8mb4;
