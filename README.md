# MiMarket — Proyecto Definitivo

MiMarket es una aplicación web MVC en PHP + MySQL para catálogo, carrito, pedidos, inventario, usuarios, reportería analítica, facturación PDF y exportaciones. La interfaz es responsiva para escritorio, tablet y móvil.

## Cambios definitivos V7
- Cascadas/desplegables con buscador **dentro del mismo control**. Al escribir, solo aparecen coincidencias; el valor seleccionado continúa enviándose mediante el `<select>` original para conservar compatibilidad.
- Paginación visual de módulos: 10 registros por página conservando filtros.
- PDFs tabulares: máximo 10 filas de detalle por hoja. Si un reporte o factura contiene más de 10 filas/productos, continúa automáticamente en Página 2, Página 3, etc., repitiendo encabezado de tabla.
- Carrito automático, perfil, recuperación por correo, filtros, exportaciones, unidades de venta y seguridad de las versiones anteriores se conservan.

## Base de datos
Para una instalación nueva existe **un único archivo definitivo**:

`database/BASE_DATOS_FINAL.sql`

> ADVERTENCIA: el script final elimina y vuelve a crear la base `mimarket`. Úsalo para instalación limpia. Si tu base actual contiene información que deseas conservar, realiza respaldo antes.

## Requisitos
- PHP 8.x con PDO MySQL, OpenSSL y Fileinfo.
- MySQL 8.x.
- Navegador moderno.

## Configuración local
Configura `.env` con tus propios valores. Ejemplo:

```env
APP_URL=http://localhost:8000
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=mimarket
DB_USER=root
DB_PASS=
DB_SSL_CA=
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=tu_correo@gmail.com
SMTP_PASS=tu_clave_de_aplicacion
SMTP_FROM=tu_correo@gmail.com
SMTP_FROM_NAME=MiMarket
SMTP_SECURE=tls
```

No publiques `.env` ni contraseñas. Para Gmail usa una contraseña de aplicación, no la contraseña normal de la cuenta.

## Ejecución
1. Ejecuta `database/BASE_DATOS_FINAL.sql` en MySQL Workbench para una instalación limpia.
2. Configura `.env`.
3. Desde la raíz del proyecto ejecuta:

```powershell
php -S localhost:8000 -t public
```

4. Abre `http://localhost:8000`.

## Organización MVC
- `app/Controllers`: flujo de solicitudes, validación y acciones.
- `app/Models`: acceso a datos mediante PDO/prepared statements.
- `app/Views`: interfaz del catálogo, cuenta y administración.
- `app/Core.php`: seguridad, paginación, SMTP y generación PDF.
- `public`: punto de entrada, estilos e imágenes.
- `database`: script SQL definitivo.

## Seguridad
El proyecto usa consultas preparadas, tokens CSRF, sesiones con autorización por rol, hash de contraseñas, tokens temporales para recuperación, escape HTML, cabeceras de seguridad, validación de entradas y `Cache-Control: no-store` en áreas autenticadas. Ninguna aplicación debe considerarse invulnerable; antes de uso real se recomienda revisión de seguridad y pruebas de carga en un entorno controlado.

## PDF y paginación
`pdfTableDocument()` controla los documentos tabulares. V7 limita el detalle a 10 filas por página y genera automáticamente páginas adicionales. `invoicePdf()` reutiliza este motor, por lo que una factura con 11 productos tendrá dos páginas.

## Cascadas buscables
Los `<select class="searchable-select">` se mejoran desde `app/Views/layouts/main.php`. El buscador aparece al abrir la misma cascada; no se muestra un campo independiente encima. Esto mantiene los formularios existentes y mejora la experiencia móvil.
