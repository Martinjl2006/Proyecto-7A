
CREATE DATABASE LegendAR;

USE LegendAR;

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

-- PROVINCIAS
INSERT INTO Provincias (Coordenada_long, Coordenada_lat, Nombre) VALUES
(-65, -26, 'Tucumán'),
(-58, -34, 'Buenos Aires'),
(-68, -32, 'Mendoza'),
(-64, -31, 'Córdoba'),
(-60, -27, 'Santa Fe');

-- CIUDADES (ID Provincia coincidente)
INSERT INTO Ciudad (Nombre, Coordenada_long, Coordenada_lat, id_provincia) VALUES
('San Miguel de Tucumán', -65, -26, 1),
('La Plata', -58, -34, 2),
('Godoy Cruz', -68, -32, 3),
('Villa Carlos Paz', -64, -31, 4),
('Rosario', -60, -27, 5);

-- USUARIOS (ID Provincia y Ciudad existente)
INSERT INTO Usuarios (Nombre, mail, apellido, Username, contraseña, id_provincia, id_ciudad) VALUES
('Lucía', 'lucia@gmail.com', 'Pérez', 'luperez', 'clave123', 1, 1),
('Martín', 'martin@gmail.com', 'Gómez', 'mgomez', 'segura456', 2, 2),
('Sofía', 'sofia@hotmail.com', 'López', 'soflope', 'myp4ss789', 3, 3),
('Carlos', 'carlos@yahoo.com', 'Ramírez', 'caram', 'passw0rd', 4, 4),
('Valentina', 'valen@gmail.com', 'Díaz', 'valediaz', 'qwerty321', 5, 5);

-- MITOLEYENDA (ID Ciudad, Provincia y Usuario que ya existen)
INSERT INTO MitoLeyenda (Titulo, Descripcion, Fecha, id_ciudad, id_provincia, id_usuario) VALUES
('La Luz Mala', 'Un espíritu errante que aparece como una luz en los campos tucumanos.', '2024-05-10', 1, 1, 1),
('El Lobizón', 'Un mito urbano sobre el séptimo hijo varón transformado en bestia.', '2024-04-22', 2, 2, 2),
('El Futre', 'Un elegante fantasma que aparece en bodegas mendocinas.', '2024-03-15', 3, 3, 3),
('La Pelada de Carlos Paz', 'Una mujer sin cabello que asusta a los conductores de noche.', '2024-06-01', 4, 4, 4),
('La Llorona de Rosario', 'Un alma en pena que llora por sus hijos cerca del río Paraná.', '2024-01-30', 5, 5, 5);




