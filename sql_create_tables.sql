CREATE DATABASE IF NOT EXISTS web_procesador CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE web_procesador;


-- Tabla usuarios
CREATE TABLE usuarios (
id INT AUTO_INCREMENT PRIMARY KEY,
niu VARCHAR(7) NOT NULL UNIQUE,
nombre VARCHAR(100) NOT NULL,
password VARCHAR(255) NOT NULL,
rol ENUM('admin','cliente') NOT NULL DEFAULT 'cliente',
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Tabla archivos (se almacenará el archivo .doc en BLOB)
CREATE TABLE archivos (
id INT AUTO_INCREMENT PRIMARY KEY,
usuario_niu VARCHAR(7) NOT NULL,
filename VARCHAR(255) NOT NULL,
mime VARCHAR(100) DEFAULT NULL,
doc_blob LONGBLOB NOT NULL,
uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
procesado_path VARCHAR(255) DEFAULT NULL,
status ENUM('subido','procesando','procesado','error') DEFAULT 'subido',
error_msg TEXT DEFAULT NULL,
FOREIGN KEY (usuario_niu) REFERENCES usuarios(niu) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;