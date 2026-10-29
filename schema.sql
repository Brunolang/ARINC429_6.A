-- Ejecuta este script seleccionando primero la base de datos correcta:
-- USE ARINC429_6A;

CREATE TABLE IF NOT EXISTS instrumentos (
    ins_id INT NOT NULL AUTO_INCREMENT,
    ins_descripcion VARCHAR(50) NOT NULL,
    PRIMARY KEY (ins_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;