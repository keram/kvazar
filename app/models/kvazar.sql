SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL';

CREATE SCHEMA IF NOT EXISTS `kvazar` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
SHOW WARNINGS;
USE `kvazar`;

-- -----------------------------------------------------
-- Table `kvazar`.`quiz`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `kvazar`.`quiz` ;

SHOW WARNINGS;
CREATE  TABLE IF NOT EXISTS `kvazar`.`quiz` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `title` VARCHAR(45) NOT NULL ,
  `scope` VARCHAR(45) NOT NULL DEFAULT 'all' ,
  `privacy` TINYINT(1) NOT NULL DEFAULT 0 ,
  `key` VARCHAR(16) NOT NULL ,
  `datetime_create` DATETIME NOT NULL ,
  `datetime_start` DATETIME NULL ,
  `datetime_end` DATETIME NULL ,
  PRIMARY KEY (`id`, `key`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;

SHOW WARNINGS;

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
  `datetime_lastlogin` DATETIME NOT NULL ,
  PRIMARY KEY (`id`) )
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
  `scope` VARCHAR(45) NOT NULL DEFAULT 'elementary' ,
  `type` ENUM('simple', 'multi') NOT NULL DEFAULT 'simple' ,
  `title_en` TEXT NULL ,
  `title_sk` TEXT NULL ,
  `datetime_create` DATETIME NOT NULL ,
  `datetime_approved` DATETIME NULL ,
  `state` ENUM('created', 'approved') NULL DEFAULT 'approved' ,
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
  `value_sk` VARCHAR(128) NULL ,
  `value_en` VARCHAR(128) NULL ,
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
  `open` TINYINT(1) NOT NULL DEFAULT 1 ,
  `datetime_start` DATETIME NOT NULL ,
  `time` SMALLINT UNSIGNED NOT NULL DEFAULT 30 ,
  PRIMARY KEY (`quiz_id`, `question_id`) ,
  INDEX `fk_quiz_has_question_quiz` (`quiz_id` ASC) ,
  INDEX `fk_quiz_has_question_question` (`question_id` ASC) ,
  CONSTRAINT `fk_quiz_has_question_quiz`
    FOREIGN KEY (`quiz_id` )
    REFERENCES `kvazar`.`quiz` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_quiz_has_question_question`
    FOREIGN KEY (`question_id` )
    REFERENCES `kvazar`.`question` (`id` )
    ON DELETE NO ACTION
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
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;

SHOW WARNINGS;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
