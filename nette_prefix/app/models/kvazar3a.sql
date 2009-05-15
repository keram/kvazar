SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL';

CREATE  TABLE IF NOT EXISTS `kvazar`.`message` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `user_id` INT(11) UNSIGNED NOT NULL ,
  `text` VARCHAR(256) NOT NULL ,
  `datetime` DATETIME NOT NULL ,
  PRIMARY KEY (`id`, `user_id`) ,
  INDEX `fk_message_user` (`user_id` ASC) ,
  CONSTRAINT `fk_message_user`
    FOREIGN KEY (`user_id` )
    REFERENCES `kvazar`.`user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;

CREATE  TABLE IF NOT EXISTS `kvazar`.`question_attachment` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `question_id` INT(11) UNSIGNED NOT NULL ,
  `name` VARCHAR(45) NOT NULL ,
  `value` TEXT NOT NULL ,
  `title` VARCHAR(45) NULL DEFAULT NULL ,
  `type` ENUM('img', 'link', 'mp3', 'youtube') NOT NULL DEFAULT 'img' ,
  `params` VARCHAR(128) NULL DEFAULT NULL ,
  PRIMARY KEY (`id`, `question_id`) ,
  INDEX `fk_question_attachment_question` (`question_id` ASC) ,
  CONSTRAINT `fk_question_attachment_question`
    FOREIGN KEY (`question_id` )
    REFERENCES `kvazar`.`question` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;

ALTER TABLE `kvazar`.`user` ADD COLUMN `role` ENUM('admin', 'moderator', 'corrector', 'editor', 'user') NOT NULL DEFAULT 'user'  AFTER `datetime_lastlogin` 
, DROP INDEX `index2` 
, ADD UNIQUE INDEX `index2` (`email` ASC) 
, DROP INDEX `nick` 
, ADD UNIQUE INDEX `nick` (`nick` ASC) ;

ALTER TABLE `kvazar`.`quiz` ADD COLUMN `proceeding` ENUM('moderated', 'automated', 'combined') NOT NULL DEFAULT 'combined'  AFTER `questions` , ADD COLUMN `scope` SET('general', 'art', 'sport', 'science', 'history', 'geography', 'society', 'logic', 'health') NOT NULL DEFAULT 'general'  AFTER `questions` , CHANGE COLUMN `datetime_start` `datetime_start` DATETIME NULL DEFAULT '0000-00-00 00:00:00'  , CHANGE COLUMN `datetime_end` `datetime_end` DATETIME NULL DEFAULT '0000-00-00 00:00:00'  ;

ALTER TABLE `kvazar`.`question` ADD COLUMN `additional_info` TEXT NULL DEFAULT NULL  AFTER `response_time` , ADD COLUMN `scope` SET('general', 'art', 'sport', 'science', 'history', 'geography', 'society', 'logic', 'health') NOT NULL DEFAULT 'general'  AFTER `response_time` , ADD COLUMN `type` ENUM('simple', 'multi') NOT NULL DEFAULT 'simple'  AFTER `response_time` ;

ALTER TABLE `kvazar`.`answer` DROP COLUMN `value` , ADD COLUMN `value_en` VARCHAR(128) NULL DEFAULT NULL  AFTER `question_id` , ADD COLUMN `value_sk` VARCHAR(128) NULL DEFAULT NULL  AFTER `value_en` , CHANGE COLUMN `correct` `correct` TINYINT(1) NOT NULL DEFAULT 1  AFTER `value_sk` ;

ALTER TABLE `kvazar`.`quiz_has_question` ADD COLUMN `order` TINYINT(4) UNSIGNED NOT NULL  AFTER `datetime_start` 
, ADD UNIQUE INDEX `order` (`quiz_id` ASC, `question_id` ASC, `order` ASC) ;

ALTER TABLE `kvazar`.`user_answer` ADD COLUMN `answer_id` INT(11) UNSIGNED NOT NULL  AFTER `points` , 
  ADD CONSTRAINT `fk_user_answer_answer`
  FOREIGN KEY (`answer_id` )
  REFERENCES `kvazar`.`answer` (`id` )
  ON DELETE NO ACTION
  ON UPDATE NO ACTION
, ADD INDEX `fk_user_answer_answer` (`answer_id` ASC) 
, DROP PRIMARY KEY 
, ADD PRIMARY KEY (`user_id`, `quiz_id`, `question_id`, `value`, `answer_id`) ;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
