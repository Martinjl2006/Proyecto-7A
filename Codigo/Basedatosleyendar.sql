
CREATE DATABASE LegendAR;

DROP TABLE IF EXISTS MitoLeyenda;
DROP TABLE IF EXISTS Usuarios;
DROP TABLE IF EXISTS Ciudad;
DROP TABLE IF EXISTS Provincias;


CREATE TABLE Provincias (
    id_provincia INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
    Coordenada_long INT NOT NULL,
    Coordenada_lat INT NOT NULL,
    Nombre VARCHAR(100) NOT NULL
);

CREATE TABLE Ciudad (
    id_ciudad INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
    Nombre TEXT NOT NULL,
    Coordenada_long INT NOT NULL,
    Coordenada_lat INT NOT NULL,
    id_provincia INT NOT NULL,
    FOREIGN KEY (id_provincia) REFERENCES Provincias(id_provincia)
);

CREATE TABLE Usuarios (
    id_Usuario INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
    Nombre TEXT NOT NULL,
    mail VARCHAR(255) NOT NULL,
    apellido TEXT NOT NULL,
    Username VARCHAR(100) NOT NULL,
    contraseña VARCHAR(100) NOT NULL,
    id_provincia INT NOT NULL,
    id_ciudad INT NOT NULL,
    FOREIGN KEY (id_provincia) REFERENCES Provincias(id_provincia),
    FOREIGN KEY (id_ciudad) REFERENCES Ciudad(id_ciudad)
);

CREATE TABLE MitoLeyenda (
    id_mitooleyenda INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
    Titulo VARCHAR(255) NOT NULL,
    Descripcion TEXT NOT NULL,
    Fecha DATE NOT NULL,
    id_ciudad INT NOT NULL,
    id_provincia INT NOT NULL,
    id_usuario INT NOT NULL,
    FOREIGN KEY (id_ciudad) REFERENCES Ciudad(id_ciudad),
    FOREIGN KEY (id_provincia) REFERENCES Provincias(id_provincia),
    FOREIGN KEY (id_usuario) REFERENCES Usuarios(id_Usuario)
);

-- Insertar las 23 provincias y CABA
INSERT INTO Provincias (Nombre) VALUES
('Buenos Aires'),
('Catamarca'),
('Chaco'),
('Chubut'),
('Córdoba'),
('Corrientes'),
('Entre Ríos'),
('Formosa'),
('Jujuy'),
('La Pampa'),
('La Rioja'),
('Mendoza'),
('Misiones'),
('Neuquén'),
('Río Negro'),
('Salta'),
('San Juan'),
('San Luis'),
('Santa Cruz'),
('Santa Fe'),
('Santiago del Estero'),
('Tierra del Fuego'),
('Tucumán'),
('Ciudad Autónoma de Buenos Aires');

-- Insertar capitales de provincias (una ciudad por provincia)
INSERT INTO Ciudad (Nombre, id_provincia) VALUES
('La Plata', 1),                -- Buenos Aires
('San Fernando del Valle de Catamarca', 2),
('Resistencia', 3),
('Rawson', 4),
('Córdoba', 5),
('Corrientes', 6),
('Paraná', 7),
('Formosa', 8),
('San Salvador de Jujuy', 9),
('Santa Rosa', 10),
('La Rioja', 11),
('Mendoza', 12),
('Posadas', 13),
('Neuquén', 14),
('Viedma', 15),
('Salta', 16),
('San Juan', 17),
('San Luis', 18),
('Río Gallegos', 19),
('Santa Fe', 20),
('Santiago del Estero', 21),
('Ushuaia', 22),
('San Miguel de Tucumán', 23),
('Ciudad Autónoma de Buenos Aires', 24); -- CABA



