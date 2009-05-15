-- MySQL dump 10.11
--
-- Host: localhost    Database: kvazar
-- ------------------------------------------------------
-- Server version	5.0.45-community-nt

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `answer`
--

DROP TABLE IF EXISTS `answer`;
CREATE TABLE `answer` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `question_id` int(10) unsigned NOT NULL,
  `value_en` varchar(128) default NULL,
  `value_sk` varchar(128) default NULL,
  `correct` tinyint(1) NOT NULL default '1',
  PRIMARY KEY  (`id`,`question_id`),
  KEY `fk_answer_question` (`question_id`),
  CONSTRAINT `fk_answer_question` FOREIGN KEY (`question_id`) REFERENCES `question` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `answer`
--

LOCK TABLES `answer` WRITE;
/*!40000 ALTER TABLE `answer` DISABLE KEYS */;
INSERT INTO `answer` VALUES (1,1,NULL,NULL,1),(2,2,NULL,NULL,0),(3,2,NULL,NULL,0),(4,2,NULL,NULL,1),(5,2,NULL,NULL,0),(6,3,NULL,NULL,1),(7,4,NULL,NULL,1),(8,4,NULL,NULL,0),(9,4,NULL,NULL,1),(10,4,NULL,NULL,0),(11,5,NULL,NULL,1),(12,6,NULL,NULL,1),(13,7,NULL,NULL,0),(14,7,NULL,NULL,0),(15,7,NULL,NULL,0),(16,7,NULL,NULL,1),(17,8,NULL,NULL,1),(18,9,NULL,NULL,1),(19,10,NULL,NULL,1);
/*!40000 ALTER TABLE `answer` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `logged`
--

DROP TABLE IF EXISTS `logged`;
CREATE TABLE `logged` (
  `user_id` int(10) unsigned NOT NULL,
  `datetime_logged` datetime NOT NULL,
  `datetime_last_action` datetime NOT NULL,
  PRIMARY KEY  (`user_id`),
  KEY `fk_logged_user` (`user_id`),
  CONSTRAINT `fk_logged_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `logged`
--

LOCK TABLES `logged` WRITE;
/*!40000 ALTER TABLE `logged` DISABLE KEYS */;
INSERT INTO `logged` VALUES (1,'2009-05-10 06:56:34','2009-05-10 06:57:14'),(2,'2009-05-10 06:57:14','2009-05-10 06:57:47'),(3,'2009-05-10 14:51:33','2009-05-10 17:39:25'),(4,'2009-05-10 06:59:39','2009-05-10 07:00:27'),(5,'2009-05-14 15:23:04','2009-05-14 19:08:43');
/*!40000 ALTER TABLE `logged` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `message`
--

DROP TABLE IF EXISTS `message`;
CREATE TABLE `message` (
  `id` int(11) NOT NULL auto_increment,
  `user_id` int(11) unsigned NOT NULL,
  `text` varchar(256) NOT NULL,
  `datetime` datetime NOT NULL,
  PRIMARY KEY  (`id`,`user_id`),
  KEY `fk_message_user` (`user_id`),
  CONSTRAINT `fk_message_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `message`
--

LOCK TABLES `message` WRITE;
/*!40000 ALTER TABLE `message` DISABLE KEYS */;
/*!40000 ALTER TABLE `message` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `question`
--

DROP TABLE IF EXISTS `question`;
CREATE TABLE `question` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `title_en` text,
  `title_sk` text,
  `datetime_create` datetime NOT NULL,
  `datetime_approved` datetime default NULL,
  `state` enum('unapproved','approved','blocked') default 'unapproved',
  `response_time` smallint(6) NOT NULL default '60' COMMENT 'in second',
  `type` enum('simple','multi') NOT NULL default 'simple',
  `scope` set('general','art','sport','science','history','geography','society','logic','health') NOT NULL default 'general',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `question`
--

LOCK TABLES `question` WRITE;
/*!40000 ALTER TABLE `question` DISABLE KEYS */;
INSERT INTO `question` VALUES (1,'','Koho postavou sa stal zn','2009-05-10 07:03:37','2009-05-10 07:03:37','approved',30,'simple','general'),(2,'Max length of Email address is:','','2009-05-10 07:14:25','2009-05-10 07:14:25','approved',30,'simple','general'),(3,'Capital City of Washington is:','Hlavn','2009-05-10 07:24:06','2009-05-10 07:24:06','approved',30,'simple','general'),(4,'','Druh','2009-05-10 07:33:23','2009-05-10 07:33:23','approved',30,'simple','general'),(5,'','Aky je sucasny nazov mesta \\\"Stalingrad\\\"','2009-05-10 07:57:20','2009-05-10 07:57:20','approved',30,'simple','general'),(6,'','(Pr','2009-05-10 08:00:33','2009-05-10 08:00:33','approved',30,'simple','general'),(7,'','Anemometer je','2009-05-10 08:10:01','2009-05-10 08:10:01','approved',30,'simple','general'),(8,'','Sv','2009-05-10 08:18:48','2009-05-10 08:18:48','approved',30,'simple','general'),(9,'','Opakom polygamie je:','2009-05-10 08:22:39','2009-05-10 08:22:39','approved',30,'simple','general'),(10,'','Kolko existuje druhov rastiliny narcis?','2009-05-10 08:32:54','2009-05-10 08:32:54','approved',30,'simple','general');
/*!40000 ALTER TABLE `question` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `question_attachment`
--

DROP TABLE IF EXISTS `question_attachment`;
CREATE TABLE `question_attachment` (
  `id` int(11) NOT NULL auto_increment,
  `question_id` int(11) unsigned NOT NULL,
  `name` varchar(45) NOT NULL,
  `value` text NOT NULL,
  `title` varchar(45) default NULL,
  `type` enum('img','link','mp3','youtube') NOT NULL default 'img',
  `params` varchar(128) default NULL,
  PRIMARY KEY  (`id`,`question_id`),
  KEY `fk_question_attachment_question` (`question_id`),
  CONSTRAINT `fk_question_attachment_question` FOREIGN KEY (`question_id`) REFERENCES `question` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `question_attachment`
--

LOCK TABLES `question_attachment` WRITE;
/*!40000 ALTER TABLE `question_attachment` DISABLE KEYS */;
/*!40000 ALTER TABLE `question_attachment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quiz`
--

DROP TABLE IF EXISTS `quiz`;
CREATE TABLE `quiz` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `key` char(16) NOT NULL,
  `datetime_create` datetime NOT NULL,
  `datetime_start` datetime default NULL,
  `datetime_end` datetime default NULL,
  `admin` int(10) unsigned NOT NULL,
  `questions` smallint(5) unsigned NOT NULL default '20',
  PRIMARY KEY  (`id`,`key`,`admin`),
  KEY `fk_quiz_user` (`admin`),
  CONSTRAINT `fk_quiz_user` FOREIGN KEY (`admin`) REFERENCES `user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `quiz`
--

LOCK TABLES `quiz` WRITE;
/*!40000 ALTER TABLE `quiz` DISABLE KEYS */;
INSERT INTO `quiz` VALUES (1,'75ee5cb710fba662','2009-05-12 07:07:06','2009-05-12 07:07:14','2009-05-14 05:44:43',5,10),(2,'0d36786a4d180668','2009-05-14 05:58:08','2009-05-14 05:58:17','2009-05-14 06:01:12',5,5),(3,'22fad8d97433993e','2009-05-14 16:48:13','2009-05-14 16:48:29','2009-05-14 16:48:31',5,5),(4,'06d64c3225d722c4','2009-05-14 16:54:06','2009-05-14 16:54:16',NULL,5,5);
/*!40000 ALTER TABLE `quiz` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quiz_has_question`
--

DROP TABLE IF EXISTS `quiz_has_question`;
CREATE TABLE `quiz_has_question` (
  `quiz_id` int(10) unsigned NOT NULL,
  `question_id` int(10) unsigned NOT NULL,
  `datetime_start` datetime default NULL,
  `order` tinyint(4) unsigned NOT NULL,
  PRIMARY KEY  (`quiz_id`,`question_id`),
  UNIQUE KEY `order` (`quiz_id`,`question_id`,`order`),
  KEY `fk_quiz_has_question_quiz` (`quiz_id`),
  KEY `fk_quiz_has_question_question` (`question_id`),
  CONSTRAINT `fk_quiz_has_question_question` FOREIGN KEY (`question_id`) REFERENCES `question` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `fk_quiz_has_question_quiz` FOREIGN KEY (`quiz_id`) REFERENCES `quiz` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `quiz_has_question`
--

LOCK TABLES `quiz_has_question` WRITE;
/*!40000 ALTER TABLE `quiz_has_question` DISABLE KEYS */;
INSERT INTO `quiz_has_question` VALUES (1,1,'2009-05-14 02:18:26',1),(1,2,'2009-05-14 02:19:01',2),(1,3,'2009-05-14 02:21:20',6),(1,4,'2009-05-14 02:19:36',3),(1,5,'2009-05-14 02:20:11',4),(1,6,'2009-05-14 02:23:40',10),(1,7,'2009-05-14 02:21:55',7),(1,8,'2009-05-14 02:22:30',8),(1,9,'2009-05-14 02:20:45',5),(1,10,'2009-05-14 02:23:04',9),(2,1,'2009-05-14 06:00:40',5),(2,2,'2009-05-14 05:59:32',3),(2,3,'2009-05-14 15:09:45',7),(2,5,'2009-05-14 05:58:57',2),(2,6,'2009-05-14 15:10:20',8),(2,7,'2009-05-14 06:00:06',4),(2,8,'2009-05-14 15:10:55',9),(2,9,'2009-05-14 15:09:10',5),(2,10,'2009-05-14 05:58:22',1),(3,4,'2009-05-14 16:48:34',1),(4,1,'2009-05-14 17:05:25',10),(4,2,'2009-05-14 17:00:04',2),(4,3,'2009-05-14 17:00:39',3),(4,4,'2009-05-14 16:59:29',1),(4,5,'2009-05-14 17:03:41',7),(4,6,'2009-05-14 17:01:49',5),(4,7,'2009-05-14 17:03:06',5),(4,8,'2009-05-14 17:04:16',8),(4,9,'2009-05-14 17:04:50',9),(4,10,'2009-05-14 17:01:14',4);
/*!40000 ALTER TABLE `quiz_has_question` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `nick` varchar(45) NOT NULL,
  `email` varchar(64) NOT NULL,
  `password` varchar(64) NOT NULL,
  `datetime_register` datetime NOT NULL,
  `datetime_lastlogin` datetime default NULL,
  PRIMARY KEY  (`id`,`email`),
  UNIQUE KEY `index2` (`email`),
  UNIQUE KEY `nick` (`nick`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `user`
--

LOCK TABLES `user` WRITE;
/*!40000 ALTER TABLE `user` DISABLE KEYS */;
INSERT INTO `user` VALUES (1,'keram','keraml@gmail.com','8381b19c27600ec54f7b6dffbbe0e9aeb85320d8','2009-05-10 06:56:07',NULL),(2,'test','kvazar@keram.name','a94a8fe5ccb19ba61c4c0873d391e987982fbbd3','2009-05-10 06:57:14',NULL),(3,'marek','marek','e54ec4e8b56ff7382fb135e028860ad99be4caf9','2009-05-10 06:57:47',NULL),(4,'anonym','anonym@anonym.com','22a3e66b7a7f81f1889bdf5b993d2a84f91c19b6','2009-05-10 06:59:39',NULL),(5,'k','marek@keram.name','8381b19c27600ec54f7b6dffbbe0e9aeb85320d8','2009-05-10 07:00:28',NULL);
/*!40000 ALTER TABLE `user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_answer`
--

DROP TABLE IF EXISTS `user_answer`;
CREATE TABLE `user_answer` (
  `user_id` int(10) unsigned NOT NULL,
  `quiz_id` int(10) unsigned NOT NULL,
  `question_id` int(10) unsigned NOT NULL,
  `value` varchar(128) NOT NULL,
  `time` datetime NOT NULL,
  `comment` text,
  `points` tinyint(3) unsigned default '0',
  PRIMARY KEY  (`user_id`,`quiz_id`,`question_id`,`value`),
  KEY `fk_user_has_quiz_has_question_user` (`user_id`),
  KEY `fk_user_has_quiz_has_question_quiz_has_question` (`quiz_id`,`question_id`),
  CONSTRAINT `fk_user_has_quiz_has_question_quiz_has_question` FOREIGN KEY (`quiz_id`, `question_id`) REFERENCES `quiz_has_question` (`quiz_id`, `question_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `fk_user_has_quiz_has_question_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `user_answer`
--

LOCK TABLES `user_answer` WRITE;
/*!40000 ALTER TABLE `user_answer` DISABLE KEYS */;
INSERT INTO `user_answer` VALUES (1,1,1,'aa','2009-05-14 04:10:48',NULL,1),(1,1,3,'aa','2009-05-14 04:12:45',NULL,1),(1,1,6,'aa','2009-05-14 04:12:47',NULL,1),(1,1,9,'aa','2009-05-14 04:12:45',NULL,1),(2,1,5,'aa','2009-05-14 04:12:45',NULL,1),(2,1,10,'aa','2009-05-14 04:12:45',NULL,1),(3,1,3,'aa','2009-05-14 04:12:45',NULL,1),(3,1,6,'aa','2009-05-14 04:12:45',NULL,1),(4,1,5,'aa','2009-05-14 04:12:46',NULL,1),(4,1,8,'aa','2009-05-14 04:12:45',NULL,1),(5,1,1,'+klinger','2009-05-14 02:18:55',NULL,1),(5,1,1,'-klinger','2009-05-14 02:18:36',NULL,1),(5,1,1,'klinger','2009-05-14 02:18:31',NULL,1),(5,1,1,'ˇklinger','2009-05-14 02:18:42',NULL,0),(5,1,2,'4','2009-05-14 02:19:07',NULL,1),(5,1,4,'9;7','2009-05-14 02:19:42',NULL,2),(5,1,9,'polyand ria','2009-05-14 02:20:51',NULL,0),(5,1,9,'polyandria','2009-05-14 02:20:56',NULL,1),(5,4,4,'7','2009-05-14 16:59:39',NULL,1);
/*!40000 ALTER TABLE `user_answer` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2009-05-14 23:07:10
