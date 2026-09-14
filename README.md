# vuln-app

Aplicación web PHP desarrollada específicamente para el TFM **"Análisis Forense de un Servidor Linux Profesional Simulado para un Ataque Coordinado de Compromiso del Servidor y Robo de Tarjetas de Crédito de los Usuarios"** del Máster en Ciberseguridad Online de IMF Smart Education.

La aplicación simula un sistema de comercio electrónico de muebles con vulnerabilidades introducidas deliberadamente con fines académicos. **No debe desplegarse en entornos de producción ni en redes públicas.**

---

## Índice

- [Requisitos previos](#requisitos-previos)
- [Arquitectura del escenario](#arquitectura-del-escenario)
- [1. Configuración de red en VirtualBox](#1-configuración-de-red-en-virtualbox)
- [2. Instalación de Apache y PHP](#2-instalación-de-apache-y-php)
- [3. Configuración del vhost de Apache](#3-configuración-del-vhost-de-apache)
- [4. Instalación y configuración de MariaDB](#4-instalación-y-configuración-de-mariadb)
- [5. Despliegue de la aplicación](#5-despliegue-de-la-aplicación)
- [6. Instalación y configuración de FTP (vsftpd)](#6-instalación-y-configuración-de-ftp-vsftpd)
- [7. Usuario dedicado al hosting](#7-usuario-dedicado-al-hosting)
- [8. Configuración de PHP y auditd](#8-configuración-de-php-y-auditd)
- [9. Persistencia del journal](#9-persistencia-del-journal)
- [10. Verificación final](#10-verificación-final)
- [Vulnerabilidades introducidas deliberadamente](#vulnerabilidades-introducidas-deliberadamente)
- [Estructura del proyecto](#estructura-del-proyecto)

---

## Requisitos previos

- **Motor de virtualización:** VirtualBox
- **Sistema operativo de la máquina víctima:** Ubuntu Server 24.04 LTS  
  ISO oficial: https://ubuntu.com/download/server
- **Servicios a instalar:** Apache 2.4, PHP 8.3, MariaDB, vsftpd, OpenSSH

---

## Arquitectura del escenario

| Máquina | SO | IP | Rol |
|---|---|---|---|
| Víctima | Ubuntu Server 24.04 LTS | `192.168.56.10` | Hosting de la aplicación |
| Atacante | Kali Linux | `192.168.56.11` | Auditoría y explotación |
| Forense | Ubuntu Desktop | `192.168.56.20` | Análisis post-incidente |

Todas las máquinas usan un **adaptador solo-anfitrión (Host-Only)** en VirtualBox con la red `192.168.56.0/24`, aislado de cualquier red externa. Durante la instalación de paquetes puede habilitarse temporalmente un segundo adaptador en modo NAT.

---

## 1. Configuración de red en VirtualBox
**ATENCION:** Se recomienda primero instalar todos los paquetes mencionados en este fichero usando un adaptador puente o NAT y posteriormente ejecutar este paso, ya que una vez que se conecte el adaptador anfitrión se perderá conexión con el exterior quedando solo la red interna 192.168.56.1/24.

Crea la red Host-Only en VirtualBox (`Archivo → Host Network Manager`):

- **IPv4:** `192.168.56.1`
- **Máscara:** `255.255.255.0`
- **Servidor DHCP:** desactivado

En la VM Ubuntu Server, configura la IP estática con netplan. Identifica primero el nombre de la interfaz:

```bash
ip a
```

Edita el fichero de netplan (ajusta el nombre de interfaz según el resultado anterior):

```bash
sudo nano /etc/netplan/50-cloud-init.yaml
```

```yaml
network:
  version: 2
  ethernets:
    enp0s3:
      dhcp4: no
      addresses: [192.168.56.10/24]
```

```bash
sudo netplan apply
```

---

## 2. Instalación de Apache y PHP

```bash
sudo apt update
sudo apt install apache2 php libapache2-mod-php php-cli php-mysqli \
  php-mbstring php-xml php-curl unzip -y
```

---

## 3. Configuración del vhost de Apache

Crea el directorio de la aplicación y el fichero de configuración del vhost:

```bash
sudo mkdir -p /var/www/vulnapp
sudo cp vulnapp.conf /etc/apache2/sites-available/vulnapp.conf
```

El fichero `vulnapp.conf` incluido en este repositorio configura:
- `DocumentRoot` en `/var/www/vulnapp`
- `Options -Indexes` globalmente (el directory browsing se habilita selectivamente mediante `.htaccess` en `/uploads/` como vulnerabilidad deliberada)
- Logs de acceso y error separados del vhost por defecto

Activa el vhost y deshabilita el de Apache por defecto:

```bash
sudo a2ensite vulnapp.conf
sudo a2dissite 000-default.conf
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### Introducir la vulnerabilidad Directory Browsing

Crea el fichero `.htaccess` en el directorio de subidas:

```bash
sudo nano /var/www/vulnapp/uploads/.htaccess
```

```apache
Options +Indexes
```

Este fichero simula una misconfiguration dejada por un desarrollador, que permite al atacante listar el contenido de `/uploads/` y localizar los ficheros subidos.

---

## 4. Instalación y configuración de MariaDB

```bash
sudo apt install mariadb-server -y
sudo mysql_secure_installation
```

Accede a MariaDB como root y crea la base de datos y el usuario de la aplicación:

```bash
sudo mariadb
```

```sql
CREATE DATABASE vuln_db;
CREATE USER 'enterprise'@'localhost' IDENTIFIED BY 'Vulnerable12345_';
GRANT ALL PRIVILEGES ON vuln_db.* TO 'enterprise'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

Importa el esquema de la base de datos:

```bash
sudo mariadb vuln_db < database.sql
```

> **Nota:** MariaDB escucha únicamente en `localhost` (`127.0.0.1`) por defecto. No modifiques el `bind-address` — el puerto 3306 no debe estar expuesto en la interfaz de red del escenario.

---

## 5. Despliegue de la aplicación

Transfiere el proyecto al servidor (desde tu máquina de desarrollo mediante SCP):

```bash
scp vuln_app.zip imf@192.168.56.10:~/
```

En el servidor, descomprime y despliega:

```bash
cd /var/www/vulnapp
sudo mv ~/vuln_app.zip ./
sudo unzip ./vuln_app.zip
sudo systemctl restart apache2
```

### Ajuste de permisos

Aplica los permisos correctos para que Apache (`www-data`) pueda leer y ejecutar los ficheros, y el usuario `enterprise` pueda escribir vía FTP:

```bash
sudo chown -R enterprise:www-data /var/www/vulnapp
sudo find /var/www/vulnapp -type d -exec chmod 2775 {} \;
sudo find /var/www/vulnapp -type f -exec chmod 664 {} \;
```

> **Importante:** Este bloque debe ejecutarse de nuevo cada vez que se redespliegue la aplicación mediante `unzip`, ya que el proceso restaura los permisos originales del fichero comprimido.

---

## 6. Instalación y configuración de FTP (vsftpd)

```bash
sudo apt install vsftpd -y
sudo cp vsftpd.conf /etc/vsftpd.conf
```

El fichero `vsftpd.conf` incluido en este repositorio configura:
- Escucha solo en IPv4
- Sin acceso anónimo
- Chroot al directorio `/var/www/vulnapp` para usuarios locales
- Log de transferencias en `/var/log/vsftpd.log`
- Whitelist de usuarios mediante `/etc/vsftpd.userlist`

Añade el usuario `enterprise` a la whitelist:

```bash
echo "enterprise" | sudo tee /etc/vsftpd.userlist
sudo systemctl enable --now vsftpd
```

Para que el login FTP funcione con el shell `/usr/sbin/nologin`, registra ese shell como válido en el sistema:

```bash
echo "/usr/sbin/nologin" | sudo tee -a /etc/shells
```

---

## 7. Usuario dedicado al hosting

Crea el usuario `enterprise` con el docroot como directorio home y sin shell interactiva:

```bash
sudo adduser --home /var/www/vulnapp --shell /usr/sbin/nologin enterprise
```

Introduce la contraseña `enterprise123_` cuando se solicite.

Aplica de nuevo los permisos tras crear el usuario:

```bash
sudo chown -R enterprise:www-data /var/www/vulnapp
sudo find /var/www/vulnapp -type d -exec chmod 2775 {} \;
sudo find /var/www/vulnapp -type f -exec chmod 664 {} \;
sudo systemctl restart vsftpd
```

**Credenciales FTP del escenario:**

| Campo | Valor |
|---|---|
| Servidor | `192.168.56.10` |
| Usuario | `enterprise` |
| Contraseña | `enterprise123_` |
| Puerto | `21` |

> ⚠️ Estas credenciales son exclusivamente de laboratorio. No usar en entornos reales.

---

## 8. Configuración de PHP y auditd

### PHP

Edita el fichero `php.ini` (ajusta la versión de PHP si es distinta de 8.3):

```bash
sudo mkdir -p /var/log/php
sudo chown www-data:www-data /var/log/php
sudo nano /etc/php/8.3/apache2/php.ini
```

Localiza y modifica las siguientes directivas:

```ini
log_errors = On
error_log = /var/log/php/vulnapp_error.log
display_errors = Off
```

### auditd

```bash
sudo apt install auditd audispd-plugins -y
sudo nano /etc/audit/rules.d/audit.rules
```

Añade las siguientes reglas:

```
-w /var/www/vulnapp -p wa -k webroot_writes
-w /etc/passwd -p wa -k passwd_changes
-w /etc/sudoers -p wa -k sudoers_changes
-a always,exit -F arch=b64 -S execve -k exec_commands
```

```bash
sudo augenrules --load
sudo systemctl restart auditd
sudo systemctl restart apache2
```

### Logging de eventos de aplicación

Crea el directorio de logs de la aplicación:

```bash
sudo mkdir -p /var/log/vulnapp
sudo chown www-data:www-data /var/log/vulnapp
```

Los eventos de la aplicación (login, registro, compras, pagos, accesos denegados) se registran en `/var/log/vulnapp/app_events.log` en formato JSON Lines mediante `src/includes/logger.php`.

---

## 9. Persistencia del journal

Por defecto `systemd-journald` no conserva los registros entre reinicios. Para habilitarlo:

```bash
sudo mkdir -p /var/log/journal
sudo systemd-tmpfiles --create --prefix /var/log/journal
sudo systemctl restart systemd-journald
```

---

## 10. Verificación final

Comprueba que todos los servicios están activos:

```bash
sudo systemctl status apache2
sudo systemctl status mariadb
sudo systemctl status vsftpd
sudo systemctl status auditd
sudo systemctl status ssh
```

Verifica que los puertos correctos están en escucha:

```bash
sudo ss -tlnp | grep -E ':21|:22|:80'
```

Resultado esperado:

```
tcp  LISTEN  0.0.0.0:21   vsftpd
tcp  LISTEN  0.0.0.0:22   sshd
tcp  LISTEN  0.0.0.0:80   apache2
```

Accede a la aplicación desde cualquier equipo en la misma red Host-Only:

```
http://192.168.56.10
```

---

## Vulnerabilidades introducidas deliberadamente

| Vulnerabilidad | Clasificación OWASP | Ubicación | Descripción |
|---|---|---|---|
| Inclusión de ficheros maliciosos | A04:2021 – Insecure Design (CWE-434) | `index.php` | La extensión del fichero subido se toma del nombre declarado por el cliente sin restringirla a una lista segura, permitiendo subir ficheros polyglot PNG+PHP |
| Directory Browsing | A01:2021 – Broken Access Control (CWE-548) | `/uploads/.htaccess` | El fichero `.htaccess` con `Options +Indexes` permite listar el contenido de `/uploads/` desde el navegador |

> ⚠️ **Aviso legal:** Esta aplicación contiene vulnerabilidades reales introducidas de forma deliberada con fines exclusivamente académicos. Su uso está restringido al entorno de laboratorio descrito en este documento. El autor no se responsabiliza del uso indebido fuera de dicho contexto.

---

## Estructura del proyecto

```
C:.
│   database.sql  ← esquema e inserción de datos iniciales
│   README.md
│
├───resources
│       vsftpd.conf   ← configuración Apache
│       vulnapp.conf  ← configuración vsftpd
│
└───vulnapp
    │   admin.php
    │   escalado.png.php
    │   index.php
    │   login.php
    │   logout.php
    │   payment.php
    │   product.php
    │   purchases.php
    │
    ├───assets
    │   └───img
    │           usuario.png
    │
    ├───src
    │   ├───components
    │   │       footer.php
    │   │       header.php
    │   │
    │   ├───config
    │   │       database.php
    │   │       session.php
    │   │
    │   └───includes
    │           auth_guard.php
    │           helpers.php
    │           logger.php
    │
    └───uploads
        │   .htaccess  ← vulnerabilidad Directory Browsing
        │
        ├───ideas
        │       idea_6aa306ff19aec9.77882349.jpg
        │
        └───products
                armario.jpg
                banera.jpg
                cama-king.jpg
                estanteria.jpg
                lampara.jpg
                lavabo.jpg
                mesa-comedor.jpg
                nevera.jpg
                silla-oficina.jpg
                sofa.jpg
```