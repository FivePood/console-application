CREATE TABLE `optionsMeaning`
(
    `optionsMeaningId` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `meaning` text,
    `optionsId` int(11) unsigned NOT NULL,
    PRIMARY KEY (`optionsMeaningId`),
    CONSTRAINT fk_optionsMeaning_options
    FOREIGN KEY (`optionsId`)  REFERENCES `options`(`optionsId`)
)
ENGINE = innodb
AUTO_INCREMENT = 1
CHARACTER SET utf8
COLLATE utf8_general_ci;