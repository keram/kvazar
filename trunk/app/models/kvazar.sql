SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL';

CREATE SCHEMA IF NOT EXISTS `kvazar` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
SHOW WARNINGS;
USE `kvazar`;

-- -----------------------------------------------------
-- Table `kvazar`.`user`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `kvazar`.`user` ;

SHOW WARNINGS;
CREATE  TABLE IF NOT EXISTS `kvazar`.`user` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `nick` VARCHAR(45) NULL ,
  `email` VARCHAR(64) NOT NULL ,
  `password` VARCHAR(64) NOT NULL ,
  `datetime_register` DATETIME NOT NULL ,
  `datetime_lastlogin` DATETIME NULL ,
  PRIMARY KEY (`id`, `email`) ,
  UNIQUE INDEX `index2` (`email` ASC) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;

SHOW WARNINGS;

-- -----------------------------------------------------
-- Table `kvazar`.`quiz`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `kvazar`.`quiz` ;

SHOW WARNINGS;
CREATE  TABLE IF NOT EXISTS `kvazar`.`quiz` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `key` CHAR(16) NOT NULL ,
  `datetime_create` DATETIME NOT NULL ,
  `datetime_start` DATETIME NULL ,
  `datetime_end` DATETIME NULL ,
  `admin` INT UNSIGNED NOT NULL ,
  `questions` SMALLINT UNSIGNED NOT NULL DEFAULT 20 ,
  PRIMARY KEY (`id`, `key`, `admin`) ,
  INDEX `fk_quiz_user` (`admin` ASC) ,
  CONSTRAINT `fk_quiz_user`
    FOREIGN KEY (`admin` )
    REFERENCES `kvazar`.`user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;

SHOW WARNINGS;

-- -----------------------------------------------------
-- Table `kvazar`.`question`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `kvazar`.`question` ;

SHOW WARNINGS;
CREATE  TABLE IF NOT EXISTS `kvazar`.`question` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `title_en` TEXT NULL ,
  `title_sk` TEXT NULL ,
  `datetime_create` DATETIME NOT NULL ,
  `datetime_approved` DATETIME NULL ,
  `state` ENUM('unapproved', 'approved', 'blocked') NULL DEFAULT 'unapproved' ,
  `response_time` SMALLINT NOT NULL DEFAULT 60 COMMENT 'in second' ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB;

SHOW WARNINGS;

-- -----------------------------------------------------
-- Table `kvazar`.`answer`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `kvazar`.`answer` ;

SHOW WARNINGS;
CREATE  TABLE IF NOT EXISTS `kvazar`.`answer` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `correct` TINYINT(1) NOT NULL DEFAULT 1 ,
  `value` VARCHAR(128) NOT NULL ,
  `question_id` INT UNSIGNED NOT NULL ,
  PRIMARY KEY (`id`, `question_id`) ,
  INDEX `fk_answer_question` (`question_id` ASC) ,
  CONSTRAINT `fk_answer_question`
    FOREIGN KEY (`question_id` )
    REFERENCES `kvazar`.`question` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;

SHOW WARNINGS;

-- -----------------------------------------------------
-- Table `kvazar`.`quiz_has_question`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `kvazar`.`quiz_has_question` ;

SHOW WARNINGS;
CREATE  TABLE IF NOT EXISTS `kvazar`.`quiz_has_question` (
  `quiz_id` INT UNSIGNED NOT NULL ,
  `question_id` INT UNSIGNED NOT NULL ,
  `datetime_start` DATETIME NULL ,
  PRIMARY KEY (`quiz_id`, `question_id`) ,
  INDEX `fk_quiz_has_question_quiz` (`quiz_id` ASC) ,
  INDEX `fk_quiz_has_question_question` (`question_id` ASC) ,
  CONSTRAINT `fk_quiz_has_question_quiz`
    FOREIGN KEY (`quiz_id` )
    REFERENCES `kvazar`.`quiz` (`id` )
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_quiz_has_question_question`
    FOREIGN KEY (`question_id` )
    REFERENCES `kvazar`.`question` (`id` )
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;

SHOW WARNINGS;

-- -----------------------------------------------------
-- Table `kvazar`.`user_answer`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `kvazar`.`user_answer` ;

SHOW WARNINGS;
CREATE  TABLE IF NOT EXISTS `kvazar`.`user_answer` (
  `user_id` INT UNSIGNED NOT NULL ,
  `quiz_id` INT UNSIGNED NOT NULL ,
  `question_id` INT UNSIGNED NOT NULL ,
  `value` VARCHAR(128) NOT NULL ,
  `time` DATETIME NOT NULL ,
  `comment` TEXT NULL ,
  PRIMARY KEY (`user_id`, `quiz_id`, `question_id`) ,
  INDEX `fk_user_has_quiz_has_question_user` (`user_id` ASC) ,
  INDEX `fk_user_has_quiz_has_question_quiz_has_question` (`quiz_id` ASC, `question_id` ASC) ,
  CONSTRAINT `fk_user_has_quiz_has_question_user`
    FOREIGN KEY (`user_id` )
    REFERENCES `kvazar`.`user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_user_has_quiz_has_question_quiz_has_question`
    FOREIGN KEY (`quiz_id` , `question_id` )
    REFERENCES `kvazar`.`quiz_has_question` (`quiz_id` , `question_id` )
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;

SHOW WARNINGS;

-- -----------------------------------------------------
-- Table `kvazar`.`logged`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `kvazar`.`logged` ;

SHOW WARNINGS;
CREATE  TABLE IF NOT EXISTS `kvazar`.`logged` (
  `user_id` INT UNSIGNED NOT NULL ,
  `datetime_logged` DATETIME NOT NULL ,
  `datetime_last_action` DATETIME NOT NULL ,
  PRIMARY KEY (`user_id`) ,
  INDEX `fk_logged_user` (`user_id` ASC) ,
  CONSTRAINT `fk_logged_user`
    FOREIGN KEY (`user_id` )
    REFERENCES `kvazar`.`user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

SHOW WARNINGS;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
