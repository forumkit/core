# core

USE kit;
ALTER TABLE fk_posts DROP INDEX content;
CREATE FULLTEXT INDEX content ON `fk_posts` (`content`) WITH PARSER ngram;


USE kit;
ALTER TABLE fk_discussions DROP INDEX title;
CREATE FULLTEXT INDEX title ON `fk_discussions` (`title`) WITH PARSER ngram;