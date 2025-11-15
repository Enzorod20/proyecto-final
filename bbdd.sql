/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

CREATE DATABASE IF NOT EXISTS `proyecto_final` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `proyecto_final`;

CREATE TABLE IF NOT EXISTS `admin` (
  `ID_admin` int NOT NULL AUTO_INCREMENT,
  `ID_usuario` int DEFAULT NULL,
  PRIMARY KEY (`ID_admin`),
  KEY `ID_usuario` (`ID_usuario`),
  CONSTRAINT `fk_usuario_admin` FOREIGN KEY (`ID_usuario`) REFERENCES `usuario` (`ID_usuario`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


CREATE TABLE IF NOT EXISTS `alumno` (
  `ID_alumno` int NOT NULL AUTO_INCREMENT,
  `ID_usuario` int NOT NULL DEFAULT '0',
  `ID_carrera` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID_alumno`),
  KEY `ID_usuario` (`ID_usuario`),
  KEY `ID_carrera` (`ID_carrera`),
  CONSTRAINT `fk_carrera_alumno` FOREIGN KEY (`ID_carrera`) REFERENCES `carrera` (`ID_carrera`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_usuario_alumno` FOREIGN KEY (`ID_usuario`) REFERENCES `usuario` (`ID_usuario`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


CREATE TABLE IF NOT EXISTS `carrera` (
  `ID_carrera` int NOT NULL AUTO_INCREMENT,
  `Nombre_carrera` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`ID_carrera`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `carrera` (`ID_carrera`, `Nombre_carrera`) VALUES
	(1, 'Psicologia'),
	(2, 'Analisis en sistemas'),
	(3, 'Medicina'),
	(4, 'Profesorado de Educacion Fisica');

CREATE TABLE IF NOT EXISTS `condicion` (
  `ID_condicion` int NOT NULL AUTO_INCREMENT,
  `ID_materia` int NOT NULL DEFAULT '0',
  `Matricula` int NOT NULL DEFAULT '0',
  `Notas` int NOT NULL DEFAULT '0',
  `Condicion` varchar(50) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID_condicion`),
  KEY `ID_materia` (`ID_materia`),
  CONSTRAINT `fk_condicion_materia` FOREIGN KEY (`ID_materia`) REFERENCES `materia` (`ID_materia`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


CREATE TABLE IF NOT EXISTS `docente` (
  `ID_docente` int NOT NULL AUTO_INCREMENT,
  `ID_usuario` int DEFAULT NULL,
  PRIMARY KEY (`ID_docente`),
  KEY `ID_usuario` (`ID_usuario`),
  CONSTRAINT `fk_usuario_docente` FOREIGN KEY (`ID_usuario`) REFERENCES `usuario` (`ID_usuario`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `docente` (`ID_docente`, `ID_usuario`) VALUES
	(1, 4),
	(4, 7),
	(3, 9),
	(5, 11);

CREATE TABLE IF NOT EXISTS `materia` (
  `ID_materia` int NOT NULL AUTO_INCREMENT,
  `ID_docente` int NOT NULL DEFAULT '0',
  `ID_carrera` int NOT NULL DEFAULT '0',
  `Nombre_materia` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`ID_materia`),
  KEY `ID_docente` (`ID_docente`),
  KEY `ID_carrera` (`ID_carrera`),
  CONSTRAINT `fk_materia_carrera` FOREIGN KEY (`ID_carrera`) REFERENCES `carrera` (`ID_carrera`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_materia_docente` FOREIGN KEY (`ID_docente`) REFERENCES `docente` (`ID_docente`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `materia` (`ID_materia`, `ID_docente`, `ID_carrera`, `Nombre_materia`) VALUES
	(7, 1, 2, 'matematica l'),
	(8, 3, 4, 'Pedagogia'),
	(9, 4, 2, 'Programacion I'),
	(10, 3, 4, 'anatomia'),
	(11, 5, 1, 'lengua');

CREATE TABLE IF NOT EXISTS `materia_alumno` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ID_materia` int NOT NULL,
  `ID_carrera` int NOT NULL,
  `ID_alumno` int NOT NULL,
  `nota1` decimal(5,2) DEFAULT NULL,
  `nota2` decimal(5,2) DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `uk_materia_carrera_alumno` (`ID_materia`,`ID_carrera`,`ID_alumno`),
  KEY `ID_carrera` (`ID_carrera`),
  KEY `ID_alumno` (`ID_alumno`),
  CONSTRAINT `materia_alumno_ibfk_1` FOREIGN KEY (`ID_materia`) REFERENCES `materia` (`ID_materia`) ON DELETE CASCADE,
  CONSTRAINT `materia_alumno_ibfk_2` FOREIGN KEY (`ID_carrera`) REFERENCES `carrera` (`ID_carrera`) ON DELETE CASCADE,
  CONSTRAINT `materia_alumno_ibfk_3` FOREIGN KEY (`ID_alumno`) REFERENCES `usuario` (`ID_usuario`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `materia_alumno` (`ID`, `ID_materia`, `ID_carrera`, `ID_alumno`, `nota1`, `nota2`) VALUES
	(10, 7, 2, 3, 7.00, 8.00),
	(12, 7, 2, 12, 6.00, 8.00),
	(13, 9, 2, 12, 9.00, 8.00),
	(15, 7, 2, 8, 3.00, 9.00),
	(16, 9, 2, 3, 8.00, 6.00),
	(17, 9, 2, 8, -9.00, NULL);

CREATE TABLE IF NOT EXISTS `materia_carrera` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ID_materia` int NOT NULL,
  `ID_carrera` int NOT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `uk_materia_carrera` (`ID_materia`,`ID_carrera`),
  KEY `ID_carrera` (`ID_carrera`),
  CONSTRAINT `materia_carrera_ibfk_1` FOREIGN KEY (`ID_materia`) REFERENCES `materia` (`ID_materia`) ON DELETE CASCADE,
  CONSTRAINT `materia_carrera_ibfk_2` FOREIGN KEY (`ID_carrera`) REFERENCES `carrera` (`ID_carrera`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


CREATE TABLE IF NOT EXISTS `materia_docente` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ID_materia` int NOT NULL,
  `ID_carrera` int NOT NULL,
  `ID_docente` int NOT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `uk_materia_carrera_docente` (`ID_materia`,`ID_carrera`,`ID_docente`),
  KEY `ID_carrera` (`ID_carrera`),
  KEY `ID_docente` (`ID_docente`),
  CONSTRAINT `materia_docente_ibfk_1` FOREIGN KEY (`ID_materia`) REFERENCES `materia` (`ID_materia`) ON DELETE CASCADE,
  CONSTRAINT `materia_docente_ibfk_2` FOREIGN KEY (`ID_carrera`) REFERENCES `carrera` (`ID_carrera`) ON DELETE CASCADE,
  CONSTRAINT `materia_docente_ibfk_3` FOREIGN KEY (`ID_docente`) REFERENCES `usuario` (`ID_usuario`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `materia_docente` (`ID`, `ID_materia`, `ID_carrera`, `ID_docente`) VALUES
	(1, 7, 2, 4),
	(2, 9, 2, 4);

CREATE TABLE IF NOT EXISTS `rol` (
  `ID_rol` int NOT NULL AUTO_INCREMENT,
  `Rol_nombre` varchar(50) NOT NULL,
  PRIMARY KEY (`ID_rol`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `rol` (`ID_rol`, `Rol_nombre`) VALUES
	(1, 'admin'),
	(2, 'docente'),
	(3, 'alumno');

CREATE TABLE IF NOT EXISTS `usuario` (
  `ID_usuario` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `apellido` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `ID_rol` int NOT NULL,
  PRIMARY KEY (`ID_usuario`),
  UNIQUE KEY `email` (`email`),
  KEY `ID_rol` (`ID_rol`),
  CONSTRAINT `fk_usuario_rol` FOREIGN KEY (`ID_rol`) REFERENCES `rol` (`ID_rol`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `usuario` (`ID_usuario`, `nombre`, `apellido`, `email`, `password`, `ID_rol`) VALUES
	(3, 'Enzo', 'Rodriguez', 'enzo@gmail.com', '$2y$10$a6r2Ro5832PJgKFwXjJkB.Z6qRhEs3WKNqj3WnO2403BQjPMAkMGe', 3),
	(4, 'javier', 'parra', 'javier@gmail.com', '$2y$10$6LRgiTfY3CNcfoJEYCzqeeR.1ZA8mPMmClh02AEKu9qayFsSOI6S.', 2),
	(6, 'admin', 'admin', 'admin', '$2y$10$DJF3XKyS7zryNRgd3mfZTu14pJ3BB0o3hxtPi9UXp62LKFPYDXZU6', 1),
	(7, 'ivan', 'gonzalez', 'ivan@gmail.com', '$2y$10$SRbzf2CxTYp27gZy1dfliOEJyyJAhcUTPdrAm4QrJbOvh9wRd.9tC', 2),
	(8, 'juan', 'Montechiarini', 'juan@gmail.com', '$2y$10$xH/iP6.r6GSnfKE7bglhquTbI2oA5FCax4pUmY/PovS6nt6HT/vkq', 3),
	(9, 'luciano', 'Urquiza', 'luciano@gmail.com', '$2y$10$ag/8Baub50xScYzSO1UMae/0ditUA2RxmfO.Z7w0ZgGkAcLwpPrPm', 2),
	(11, 'Maria', 'Fernandez', 'maria@gmail.com', '$2y$10$BZWRUeCW30Ej0LHj334Aguy.s7DzdyXkxDelhESmrN7JNZWF2ad.2', 2),
	(12, 'Nicolas', 'Romani', 'nicolas@gmail.com', '$2y$10$SZB6UFf3aCjDGNvNl/EaOut/5iPanYODoEwSKjVbPZZhaSScPa5KK', 3),
	(13, 'Luciana', 'Terrari', 'luciana@gmail.com', '$2y$10$QrHqe.AWV3553lkpMjC9bOTMnXWVDKXMsCiWfPZyN/H./l103PinK', 2),
	(14, 'Aylen', 'Piriz', 'aylen@gmail.com', '$2y$10$A/f7w5igpX39yJlbMONlRuY08bPo66jcch.VpEZ55PxjbHaswGdPG', 3);

CREATE TABLE IF NOT EXISTS `usuario_carrera` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ID_usuario` int NOT NULL,
  `ID_carrera` int NOT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `uk_usuario_carrera` (`ID_usuario`,`ID_carrera`),
  KEY `ID_carrera` (`ID_carrera`),
  CONSTRAINT `usuario_carrera_ibfk_1` FOREIGN KEY (`ID_usuario`) REFERENCES `usuario` (`ID_usuario`) ON DELETE CASCADE,
  CONSTRAINT `usuario_carrera_ibfk_2` FOREIGN KEY (`ID_carrera`) REFERENCES `carrera` (`ID_carrera`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `usuario_carrera` (`ID`, `ID_usuario`, `ID_carrera`) VALUES
	(1, 3, 1),
	(6, 12, 1),
	(3, 12, 2),
	(2, 12, 3),
	(4, 12, 4);

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
