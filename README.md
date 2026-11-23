# Gestión Académica

Sistema web para administrar la actividad académica de una institución: carreras, planes de estudio, periodos, cursos, horarios, inscripciones, calificaciones y reportes en PDF y Excel, con tres roles de acceso.

Proyecto de la materia **Programación IV**.

## Stack

| Capa | Tecnología |
|---|---|
| Backend | PHP 8.3 · Laravel 13 |
| Vistas | Blade + Tailwind CSS 3 + Alpine.js |
| Base de datos | PostgreSQL 16 o superior (probado con 18) |
| Assets | Vite |
| Reportes | dompdf (PDF) · PhpSpreadsheet (Excel) |
| Permisos | spatie/laravel-permission |

## Funcionalidades

### Administrador

- Gestión de usuarios con roles, activación/desactivación y eliminación protegida (no puede borrarse a sí mismo, al último administrador ni a usuarios con calificaciones registradas).
- CRUD de carreras, planes de estudio por carrera (modalidad modular o semestral, duración y materias por semestre), periodos académicos (con activación de un único periodo vigente) y cursos (cupo, créditos, semestre, docente y periodo).
- Asignación de horarios con validación de choque para el mismo docente, día y rango de horas.
- Reporte de materias por carrera en PDF y Excel.

### Docente

- Cursos asignados y listado de alumnos inscritos.
- Registro, edición y eliminación de calificaciones con ponderación; la nota final del estudiante se calcula automáticamente.
- Reportes de sus materias, notas por curso, alumnos inscritos y notas por alumno, en PDF y Excel.

### Estudiante

- Catálogo de cursos e inscripción con validación de cupo, horario y periodo activo.
- Retiro de inscripciones, consulta de notas e historial académico.
- Reportes de historial, notas y materias, en PDF y Excel.

### Comunes

- Las cuentas de estudiantes, docentes y administradores las crea exclusivamente el administrador desde `/admin/usuarios`.
- Autenticación, verificación de correo, recuperación de contraseña y perfil.
- Notificaciones internas con estado leído/no leído.

## Requisitos

- PHP **8.3+** con las extensiones `pdo_pgsql`, `mbstring`, `dom`, `zip`, `gd` y `fileinfo`.
- **Composer 2**.
- **Node.js 20+** (probado con 24) y npm.
- **PostgreSQL 16+**.
  El sistema **no funciona con SQLite**: las migraciones usan restricciones `EXCLUDE` y la extensión `btree_gist` para impedir choques de horario.

## Instalación

```bash
# 1. Clonar el repositorio
git clone https://github.com/Alan7294/gestion-academica.git
cd gestion-academica
```

```sql
-- 2. Crear la base de datos (desde psql o pgAdmin)
CREATE DATABASE gestion_academica;
```

```bash
# 3. Configurar el entorno
cp .env.example .env          # PowerShell: Copy-Item .env.example .env
```

Edita `.env` con los datos de tu PostgreSQL:

```env
DB_DATABASE=gestion_academica
DB_USERNAME=postgres
DB_PASSWORD=tu_contraseña
```

```bash
# 4. Instalar dependencias, generar la clave, migrar, sembrar y compilar
composer setup

# 5. (Recomendado) Cargar datos de demostración
composer demo

# 6. Levantar el servidor
php artisan serve
```

La aplicación queda disponible en <http://127.0.0.1:8000>.

> `composer setup` ejecuta `composer install`, copia `.env` si no existe, genera `APP_KEY`, corre las migraciones con los seeders base y compila los assets con Vite.

### Si prefieres hacerlo paso a paso

```bash
composer install
php artisan key:generate
php artisan migrate --seed
composer demo                    # opcional
npm install
npm run build
php artisan serve
```

## Credenciales

Con `composer demo` se crean usuarios de prueba con cursos, inscripciones y calificaciones:

| Rol | Correo | Contraseña |
|---|---|---|
| Administrador | `admin@academica.test` | `Admin1234!` |
| Docente | `docente1@academica.test` … `docente3@academica.test` | `Demo1234!` |
| Estudiante | `estudiante1@academica.test` … `estudiante6@academica.test` | `Demo1234!` |

Sin el `DemoSeeder`, solo existe el administrador (`admin@academica.test` / `Admin1234!`).

## Comandos útiles

| Comando | Descripción |
|---|---|
| `php artisan serve` | Levanta el servidor en el puerto 8000. |
| `npm run dev` | Compila assets en modo desarrollo (Vite). |
| `npm run build` | Compila assets para producción. |
| `php artisan migrate:fresh --seed` | Reinicia la base con los datos base. |
| `composer demo` | Carga los datos de demostración. |
| `php artisan test` | Ejecuta la suite de pruebas. |
| `./vendor/bin/pint` | Aplica el estilo de código de Laravel. |

## Pruebas

Las pruebas usan una base **independiente** para no tocar los datos de desarrollo:

```sql
CREATE DATABASE gestion_academica_test;
```

```bash
php artisan test
```

`phpunit.xml` fija el nombre `gestion_academica_test`, mientras que el usuario y la contraseña se toman del `.env` de cada máquina. La suite actual tiene **122 pruebas y 348 aserciones**.

## Estructura relevante

```
app/
├── Enums/             Roles, días de la semana y estados de inscripción
├── Http/Controllers/  Controladores por rol (Admin, Docente, Estudiante)
├── Models/            Eloquent: User, Carrera, Periodo, Curso, Horario, Inscripcion, Calificacion
├── Notifications/     Avisos internos del sistema
├── Policies/          Autorización de cursos e inscripciones
├── Repositories/      Contrato e implementación Eloquent para cursos
├── Reports/           Contrato Reporte + estrategias PDF/Excel y FormatoFactory
├── Services/          Reglas de negocio (inscripción, calificaciones, horarios, usuarios)
└── View/              Componentes de Blade

database/
├── migrations/        Esquema con restricciones de integridad en PostgreSQL
└── seeders/           Roles, administrador, carreras, planes de estudio, periodos y DemoSeeder

resources/views/       Vistas Blade (layout con barra lateral y componentes)
routes/web.php         Rutas agrupadas por rol
```

### Patrones aplicados

- **Service Layer** para las reglas de negocio de inscripciones, calificaciones, horarios, usuarios y planes de estudio.
- **Repository** para el acceso a datos de cursos.
- **Policy** para la autorización por recurso.
- **Strategy + Factory** en los reportes: `Reporte` define el contrato, `FormatoPdf` y `FormatoExcel` son las estrategias y `FormatoFactory` elige según el parámetro `formato`.
- **Notifications** para los avisos internos.

## Problemas comunes

| Síntoma | Solución |
|---|---|
| `could not find driver` | Habilita `extension=pdo_pgsql` en `php.ini` y reinicia. |
| `permission denied to create extension "btree_gist"` | Crea la extensión como superusuario: `CREATE EXTENSION IF NOT EXISTS btree_gist;` dentro de la base. |
| `Vite manifest not found` | Ejecuta `npm install` y `npm run build`. |
| `SQLSTATE[08006]` al migrar | Revisa `DB_HOST`, `DB_PORT`, `DB_USERNAME` y `DB_PASSWORD` en `.env`. |
| Los cambios de estilos no se ven | Vuelve a compilar con `npm run build` (o usa `npm run dev`). |
