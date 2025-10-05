#!/bin/bash

# === CONFIGURACIÓN INICIAL ===
DB_NAME="LegendAR"
DB_USER="usuario"
DB_PASS="miclave123"
PROJECT_DIR="/var/www/html/LegendAR"

# === ACTUALIZACIÓN E INSTALACIÓN DE PAQUETES ===
echo "🔧 Actualizando e instalando Apache, PHP y MySQL..."
sudo apt update && sudo apt upgrade -y
sudo apt install apache2 php libapache2-mod-php php-mysql mysql-server unzip -y

# === INICIO DE SERVICIOS ===
echo "🚀 Iniciando servicios..."
sudo systemctl enable apache2
sudo systemctl enable mysql
sudo systemctl start apache2
sudo systemctl start mysql

# === SEGURIDAD BÁSICA PARA MYSQL ===
echo "🔒 Configurando seguridad básica de MySQL..."
sudo mysql <<EOF
ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'rootpassword';
FLUSH PRIVILEGES;
EOF

# === CREACIÓN DE BASE DE DATOS Y USUARIO ===
echo "🛠️ Creando base de datos y usuario..."
sudo mysql -u root -prootpassword <<EOF
CREATE DATABASE IF NOT EXISTS \`$DB_NAME\`;
CREATE USER IF NOT EXISTS '$DB_USER'@'127.0.0.1' IDENTIFIED BY '$DB_PASS';
GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'127.0.0.1';
FLUSH PRIVILEGES;
EOF

# === CONFIGURACIÓN DEL PROYECTO ===
echo "📂 Configurando directorio del proyecto..."
sudo mkdir -p "$PROJECT_DIR"
sudo chown -R $USER:www-data "$PROJECT_DIR"
sudo chmod -R 755 "$PROJECT_DIR"

# === ARCHIVO DE PRUEBA INDEX.PHP ===
echo "📄 Creando archivo de prueba index.php..."
cat <<PHP > "$PROJECT_DIR/index.php"
<?php
\$host = "127.0.0.1";
\$user = "$DB_USER";
\$password = "$DB_PASS";
\$db = "$DB_NAME";

\$conn = new mysqli(\$host, \$user, \$password, \$db);
if (\$conn->connect_error) {
    die("Conexión fallida: " . \$conn->connect_error);
}
echo "✅ Conexión exitosa a la base de datos!";
?>
PHP

# === REINICIO DEL SERVIDOR APACHE ===
echo "🔄 Reiniciando Apache..."
sudo systemctl restart apache2

# === FINALIZACIÓN ===
echo "🎉 Setup completado. Visita http://localhost/LegendAR en tu navegador."
