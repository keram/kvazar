SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL';

ALTER TABLE `kvazar`.`user` 
DROP INDEX `index2` 
, ADD UNIQUE INDEX `index2` (`email` ASC) ;

ALTER TABLE `kvazar`.`quiz_has_question` DROP COLUMN `open` , DROP COLUMN `time` , DROP FOREIGN KEY `fk_quiz_has_question_quiz` , DROP FOREIGN KEY `fk_quiz_has_question_question` ;

ALTER TABLE `kvazar`.`quiz_has_question` 
  ADD CONSTRAINT `fk_quiz_has_question_quiz`
  FOREIGN KEY (`quiz_id` )
  REFERENCES `kvazar`.`quiz` (`id` )
  ON DELETE CASCADE
  ON UPDATE NO ACTION, 
  ADD CONSTRAINT `fk_quiz_has_question_question`
  FOREIGN KEY (`question_id` )
  REFERENCES `kvazar`.`question` (`id` )
  ON DELETE CASCADE
  ON UPDATE NO ACTION;

ALTER TABLE `kvazar`.`user_answer` DROP FOREIGN KEY `fk_user_has_quiz_has_question_quiz_has_question` ;

ALTER TABLE `kvazar`.`user_answer` 
  ADD CONSTRAINT `fk_user_has_quiz_has_question_quiz_has_question`
  FOREIGN KEY (`quiz_id` , `question_id` )
  REFERENCES `kvazar`.`quiz_has_question` (`quiz_id` , `question_id` )
  ON DELETE CASCADE
  ON UPDATE NO ACTION;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
