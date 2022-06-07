CREATE TABLE `arguments`
(
    `argumentsId` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `argumentsName` text,
    `commandId` int(11) unsigned NOT NULL,
    PRIMARY KEY (`argumentsId`),
    CONSTRAINT `fk_arguments_command`
    FOREIGN KEY (`commandId`)  REFERENCES `command`(`commandId`)
)
ENGINE = innodb
AUTO_INCREMENT = 1
CHARACTER SET utf8
COLLATE utf8_general_ci;