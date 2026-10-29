# CRUD de instrumentos — ARINC 429

Aplicación web en PHP para crear, consultar, actualizar y eliminar instrumentos en MySQL. Está basada en la tabla de la captura:

- `ins_id`: entero, clave primaria autoincremental.
- `ins_descripcion`: texto obligatorio de hasta 50 caracteres.

## Requisitos

- PHP 8 o posterior, con las extensiones `pdo` y `pdo_mysql`.
- MySQL en ejecución y la base de datos con la tabla `instrumentos`.

La captura muestra el nombre de la base como `ARINC429_6A` (sin punto). La configuración usa ese nombre por defecto. Si tu base realmente se llama `ARINC429_6.A`, define `DB_NAME` con el nombre exacto.

## Configuración y ejecución

1. Revisa los valores de conexión en `config.php`. Por defecto conecta a `127.0.0.1:3306` con el usuario `root` y contraseña vacía.
2. Si todavía no existe la tabla, selecciona tu base en MySQL y ejecuta el contenido de `schema.sql`.
3. Desde esta carpeta, inicia el servidor local de PHP con `php -S localhost:8000`.
4. Abre `http://localhost:8000` en el navegador.

También puedes configurar la conexión con las variables de entorno `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` y `DB_PASSWORD`.

## Operaciones incluidas

- **Crear:** añade una descripción validada como obligatoria y de máximo 50 caracteres.
- **Leer:** lista todos los instrumentos, ordenados por ID descendente.
- **Actualizar:** permite editar una descripción existente.
- **Eliminar:** borra el registro tras una confirmación.

Las consultas usan PDO y parámetros preparados; las acciones de formulario están protegidas con token CSRF.