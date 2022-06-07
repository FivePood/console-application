CREATE TABLE if NOT EXISTS `versions`
(
    `id` int(10) unsigned not NULL AUTO_INCREMENT,
    `name` varchar(255) not NULL,
    `created` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
)
ENGINE = innodb
AUTO_INCREMENT = 1
CHARACTER SET utf8
COLLATE utf8_general_ci;