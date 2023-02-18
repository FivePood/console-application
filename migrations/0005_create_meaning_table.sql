CREATE TABLE `meaning`
(
    `meaningId` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `meaning` text,
    `optionId` int(11) unsigned NOT NULL,
    PRIMARY KEY (`meaningId`),
    CONSTRAINT fk_meaning_option
    FOREIGN KEY (`optionId`)  REFERENCES `option`(`optionId`)
)
ENGINE = innodb
AUTO_INCREMENT = 1
CHARACTER SET utf8
COLLATE utf8_general_ci;