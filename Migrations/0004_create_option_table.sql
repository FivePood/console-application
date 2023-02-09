CREATE TABLE `option`
(
    `optionId` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `optionName` text,
    `commandId` int(11) unsigned NOT NULL,
    PRIMARY KEY (`optionId`),
    CONSTRAINT fk_option_command
    FOREIGN KEY (`commandId`)  REFERENCES `command`(`commandId`)
)
ENGINE = innodb
AUTO_INCREMENT = 1
CHARACTER SET utf8
COLLATE utf8_general_ci;