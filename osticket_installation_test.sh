#!/bin/bash
set -e

# Configuración - ¡VALORES PRUEBA!
DB_NAME="osticket_db"
DB_USER="osticket_user"
DB_PASS="123456."
DB_PREFIX="ost_"
WWW_DIR="/var/www/html/osticket"

echo "==== Instalando paquetes necesarios... ===="
sudo apt update
sudo apt install -y apache2 mariadb-server git unzip \
php php-mysql php-gd php-imap php-xml php-mbstring php-intl php-zip php-json

echo "==== Reiniciando servicios... ===="
sudo systemctl enable apache2 mariadb
sudo systemctl restart apache2 mariadb

echo "==== Descargando osTicket vía git... ===="
sudo rm -rf $WWW_DIR
sudo git clone https://github.com/osTicket/osTicket.git $WWW_DIR

echo "==== Creando carpetas necesarias... ===="
sudo mkdir -p $WWW_DIR/attachments $WWW_DIR/logs

echo "==== Copiando archivo de configuración de muestra... ===="
sudo cp $WWW_DIR/include/ost-sampleconfig.php $WWW_DIR/include/ost-config.php

echo "==== Ajustando permisos... ===="
sudo chown -R www-data:www-data $WWW_DIR
sudo chmod -R 755 $WWW_DIR
sudo chmod 0666 $WWW_DIR/include/ost-config.php|
sudo chmod -R 0777 $WWW_DIR/attachments
sudo chmod -R 0777 $WWW_DIR/logs
sudo chmod -R 0777 $WWW_DIR/include

echo "==== Configurando base de datos de osTicket ===="

sudo mysql -u root <<MYSQL_SCRIPT
CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';
FLUSH PRIVILEGES;
MYSQL_SCRIPT

echo "==== Resumen de la configuración de base de datos ===="
echo "Base de datos:     $DB_NAME"
echo "Usuario:           $DB_USER"
echo "Contraseña:        $DB_PASS"
echo "Prefijo de tablas: $DB_PREFIX"
echo "Host MySQL:        localhost"
echo "Directorio osticket: $WWW_DIR"
echo
IP=$(hostname -I | awk '{print $1}')
echo "==== Acceder a http://$IP/osticket/ para terminar la instalación en web ===="
echo "==== Cuando el instalador termine, se recmienda elimina la carpeta setup y cambia permisos de ost-config.php: ===="
echo "sudo rm -rf $WWW_DIR/setup"
echo "sudo chmod 0644 $WWW_DIR/include/ost-config.php"
echo
echo "*** ¡No olvides estos pasos al terminar la instalación desde el navegador! ***"
