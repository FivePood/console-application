CREATE TABLE `argument`
(
    `argumentId` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `argumentName` text,
    `commandId` int(11) unsigned NOT NULL,
    PRIMARY KEY (`argumentId`),
    CONSTRAINT `fk_argument_command`
    FOREIGN KEY (`commandId`)  REFERENCES `command`(`commandId`)
)
ENGINE = innodb
AUTO_INCREMENT = 1
CHARACTER SET utf8
COLLATE utf8_general_ci;