CREATE TABLE `options`
(
    `optionsId` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `optionsName` text,
    `commandId` int(11) unsigned NOT NULL,
    PRIMARY KEY (`optionsId`),
    CONSTRAINT fk_options_command
    FOREIGN KEY (`commandId`)  REFERENCES `command`(`commandId`)
)
ENGINE = innodb
AUTO_INCREMENT = 1
CHARACTER SET utf8
COLLATE utf8_general_ci;