# Guía de desarrollo · Resultados PJ

Sistema de resultados de la evaluación técnica de los procesos de selección de personal de la
**Corte Superior de Justicia de Ucayali** (Poder Judicial del Perú). Esta guía sirve para instalar el
proyecto en otra computadora y continuar el desarrollo con las mismas convenciones.

---

## 1. Estado actual

| Módulo | Estado |
| --- | --- |
| Acceso (login, bloqueo tras 5 intentos) | ✅ Operativo |
| Seguridad: usuarios, roles y permisos por acción | ✅ Operativo |
| Procesos de selección | ✅ Operativo |
| Unidades de organización | ✅ Operativo |
| Puestos (importación desde el Anexo 06-A) | ✅ Operativo |
| Inscripciones (importación con DNI, filtros, Excel para la lectora óptica) | ✅ Operativo |
| Exámenes (carga del .txt de la lectora óptica, verificación por DNI, hoja de respuestas) | ✅ Operativo |
| Resultados (cálculo, orden de mérito, PDF/Excel) | 🚧 Pantalla en blanco, por construir |

Datos cargados en la base de desarrollo original: proceso **002-2026-UE-UCAYALI**, 27 puestos, 18 unidades de
organización y 682 postulantes. **Esos datos no están en el repositorio** (ver sección 4).

---

## 2. Tecnologías

| Capa | Tecnología |
| --- | --- |
| Backend | PHP 8.3 · Laravel 13 |
| Interfaz | Livewire 4 (páginas de varios archivos) · Flux UI 2 (edición gratuita) · Tailwind CSS 4 · Alpine |
| Excel | `maatwebsite/excel` 4 para exportar · `App\Services\Excel\LectorXlsx` propio para importar |
| PDF | `barryvdh/laravel-dompdf` 3 (instalado, aún sin reportes) |
| Íconos | Hugeicons Free (MIT), convertidos a íconos de Flux con `php artisan pj:iconos` |
| Tipografía | Public Sans en el sistema interno · Inter en el acceso |
| Pruebas y calidad | Pest 4 · PHPStan (Larastan) · Pint |
| Base de datos | SQLite en desarrollo (compatible con MySQL/MariaDB) |

---

## 3. Instalación en una computadora nueva

### Requisitos

- **PHP 8.3** con las extensiones `zip`, `gd`, `xml`, `mbstring`, `fileinfo` y `pdo_sqlite`.
  En Windows lo más simple es [Laravel Herd](https://herd.laravel.com), que ya las trae.
- **Composer 2**
- **Node 22** y **npm**
- **Git** con acceso por SSH al repositorio `git@github.com:soujamt/pjresultados.git`

### Pasos

```bash
git clone git@github.com:soujamt/pjresultados.git
cd pjresultados

composer install
cp .env.example .env
php artisan key:generate

# Base de datos SQLite (en Windows PowerShell: New-Item database/database.sqlite)
touch database/database.sqlite
php artisan migrate --seed

npm install
npm run build
```

`migrate --seed` crea el rol **Super Administrador**, la cuenta inicial y el proceso 002-2026-UE-UCAYALI.

- Usuario: `admin@pj.gob.pe`
- Contraseña: `admin1234`

Para no dejar esa contraseña, define antes de sembrar `ADMIN_USUARIO` y `ADMIN_CLAVE` en el `.env`, o cámbiala
desde **Seguridad › Usuarios** después de entrar.

### Levantar el proyecto

- **Con Herd:** si la carpeta está dentro de una ruta enlazada por Herd, el sitio queda en
  `http://pjresultados.test`.
- **Sin Herd:** `php artisan serve` y entra a `http://localhost:8000`.
- **Desarrollo de interfaz:** `npm run dev` (o `composer run dev`) recarga los estilos al guardar.

> Si detienes `npm run dev` y la página se ve sin estilos, borra el archivo `public/hot` y ejecuta
> `npm run build`: ese archivo le dice a Laravel que pida los estilos al servidor de Vite.

---

## 4. Cargar los datos de postulantes

Los listados con DNI son **datos personales** y no se suben al repositorio. Copia el Excel por un medio
seguro (no por correo ni chat) y cárgalo con el comando o desde la pantalla:

```bash
# Carga postulantes, crea los puestos que falten y les registra su unidad de organización
php artisan pj:importar-inscripciones "C:\ruta\DATA GENERAL - POSTULANTES PESP PJ.xlsx"

# Solo puestos (acepta también el Anexo 06-A sin DNI)
php artisan pj:importar-puestos "C:\ruta\Anexo 06-A.xlsx"
```

Ambos comandos usan el proceso habilitado más reciente; para otro, agrega `--proceso=002-2026-UE-UCAYALI`.
Desde la interfaz: **Inscripciones › Importar desde Excel**.

Formato que se reconoce (la cabecera puede estar en cualquier fila entre las primeras 40 y empezar en
cualquier columna): `Nº`, `DNI` o `DOCUMENTO NACIONAL DE IDENTIDAD (DNI)`, `APELLIDOS Y NOMBRES`,
`CÓDIGO DE PUESTO`, `PUESTO` y `UNIDAD DE ORGANIZACIÓN`. Las columnas `PABELLÓN`, `PISO` y `AULA` se ignoran
a propósito. Si hay una sola observación no se guarda nada y se informa la fila exacta.

### Exámenes de la lectora óptica

```bash
php artisan pj:importar-examenes "C:\ruta\23-09-2026_11-41-28.txt"
```

Desde la interfaz: **Exámenes › Importar desde la lectora**. Al elegir el archivo se muestra una vista previa
sin guardar nada (hojas, quiénes quedarán sin examen, nombres distintos, puntajes y observaciones) y solo se
escribe al confirmar. El .txt viene en Windows-1252, separado por punto
y coma, con la cabecera `NRO DE DNI;APELLIDOS Y NOMBRES;Nota 30;Aciertos;Errores;Blancos;Dobles;RESPUESTAS;`
y una columna por pregunta después de `RESPUESTAS`. El puntaje es el número de aciertos. Se rechaza todo el
archivo si un DNI no está inscrito o se repite, si la nota no es igual a los aciertos o si una hoja suma un
total de preguntas distinto al de las demás. Un nombre distinto al del padrón no detiene la carga: queda
marcado para revisarlo con el filtro «Nombre distinto». Volver a subir un archivo actualiza por DNI, así que
los lotes se pueden cargar uno tras otro; **Vaciar exámenes** borra las hojas de prueba antes de las reales.

Para ensayar sin hojas reales hay un generador de archivos ficticios con el mismo formato (30 preguntas, nota
igual a los aciertos). No se ejecuta en producción y por defecto escribe en `storage/app/private/lectora/`, que
no se sube al repositorio porque lleva los DNI del padrón:

```bash
php artisan pj:generar-examenes-ficticios --faltantes=3            # 3 inscritos al azar no se presentan
php artisan pj:generar-examenes-ficticios --faltantes=3 --semilla=7 # siempre el mismo archivo
```

---

## 5. Estructura del proyecto

```
app/
├─ Console/Commands/      pj:importar-puestos, pj:importar-inscripciones, pj:importar-examenes,
│                         pj:generar-examenes-ficticios, pj:iconos
├─ Enums/                 Permiso (recurso.accion), EstadoRegistro, TipoImportacion
├─ Exports/               Exportaciones de maatwebsite/excel (PostulantesLectoraExport)
├─ Http/Controllers/      Solo descargas y cierre de sesión (controladores __invoke)
├─ Livewire/Forms/        Form Objects: validación y estado de cada formulario
├─ Models/ (+ Concerns/)  Eloquent; trait TieneEstado para habilitar/deshabilitar
└─ Services/
   ├─ Auth/               Autenticación y permisos (AccesoService, cacheado por rol)
   ├─ Evaluacion/         Lector del .txt de la lectora, importador y consultas de exámenes
   ├─ Excel/              LectorXlsx (lee .xlsx por streaming, sin librerías)
   ├─ Seguridad/          Usuarios y roles
   └─ Seleccion/          Procesos, unidades, puestos, inscripciones e importadores
resources/views/
├─ layouts/               app (sistema interno) y auth (pantalla de acceso)
├─ pages/<modulo>/<pagina>/   Páginas Livewire: <pagina>.php + <pagina>.blade.php
├─ components/            panel, pagina.encabezado, tabla.marco, tabla.vacia, tabla.accion, menu.seccion, marca.*
└─ flux/icon/             Íconos Hugeicons generados (no editar a mano)
tests/Feature/            Pruebas por módulo; tests/Support arma Excel de prueba (Anexo06A, ConstructorXlsx)
```

---

## 6. Convenciones del código

**Base de datos**
- Tablas `tbl_<entidad>` en singular; cada columna con el sufijo de su tabla: `id_pue`, `codigo_pue`,
  `estado_pue`. La clave foránea se llama igual que la primaria referida (`tbl_puesto.id_uni`).
- Estado lógico `estado_<sufijo>` con el enum `EstadoRegistro` y `SoftDeletes` para borrar.
- **Nunca editar una migración ya subida**: crear una nueva con `php artisan make:migration`.

**Capas**
- La página Livewire o el controlador solo **autoriza, valida, llama a un servicio y muestra el resultado**.
  La lógica vive en `app/Services`.
- Los servicios lanzan `RuntimeException` con un mensaje para el usuario; la pantalla lo muestra con
  `Flux::toast(..., variant: 'danger')`. El detalle técnico va a `report()`.
- Los importadores validan todo antes de escribir y guardan en `DB::transaction(..., 3)`, por lotes de 500.
  Cada archivo cargado queda registrado en `tbl_importacion` (usuario, sha256, filas, errores).

**Permisos**
- Cada acción es un caso de `App\Enums\Permiso` con valor `recurso.accion`; se publica como Gate.
  En Livewire: `$this->authorize(Permiso::X->value)`; en Blade: `@can(App\Enums\Permiso::X->value)`.
- El rol con `es_super_rol` tiene acceso a todo, incluidos los permisos que se agreguen después.
- Al crear un módulo: agrega sus casos al enum, su etiqueta y el nombre del recurso en `nombreDelRecurso()`.

**Idioma**
- Identificadores y comentarios en español **sin tildes**; las tildes solo en textos visibles.

**Excel**
- Una columna que cruza otro sistema (como el DNI para la lectora) se escribe **siempre como texto** con
  `WithCustomValueBinder`: PhpSpreadsheet guarda «87654321» como número y «01234567» como texto.

---

## 7. Diseño de la interfaz

- **El acceso no se toca.** Todos los estilos del sistema interno cuelgan de `<body class="sistema">`
  (`resources/css/app.css`). El login usa `layouts/auth.blade.php` y conserva Inter.
- **Color:** guinda institucional `pj-50…pj-950`; `pj-700` es el rojo exacto del logo (#a82214). Se usa solo
  en la acción principal y en la sección activa del menú.
- **Superficies:** fondo gris claro y superficies blancas con la utilidad `sombra-borde` (anillo de 1 px más
  sombras suaves) en lugar de bordes.
- **Componentes a reutilizar en pantallas nuevas:**

```blade
<x-pagina.encabezado titulo="Título" bajada="Una línea de contexto.">
    <x-slot:acciones>…botones…</x-slot:acciones>
</x-pagina.encabezado>

<x-panel>…filtros…</x-panel>

<x-tabla.marco>
    <flux:table>…</flux:table>
</x-tabla.marco>
```

- **Cifras:** usa `tabular-nums` en DNI, códigos y totales para que se alineen en columna.
- **Fuentes:** se descargan al compilar con el plugin de fuentes de `vite.config.js` y las sirve el propio
  servidor, sin depender de un CDN (la red de la Corte puede bloquearlos).

### Íconos (Hugeicons Free)

Busca el ícono en <https://hugeicons.com> (estilo *Stroke Rounded*, que es el gratuito) y tráelo por su nombre
en kebab-case:

```bash
php artisan pj:iconos user-add-01 printer
```

Luego úsalo como cualquier ícono de Flux: `icon="user-add-01"` o `<flux:icon.printer />`.

- `--actualizar` vuelve a descargar todos los ya generados.
- Si el nombre coincide con un Heroicon, el comando se niega, porque lo reemplazaría en todo el sistema.
  Para hacerlo a propósito se usa un alias y `--forzar`, como ya se hizo con los íconos internos de Flux:
  `php artisan pj:iconos eye=view eye-slash=view-off-slash check=tick-02 --forzar`.
- Las flechas internas de Flux (listas desplegables, paginación, cerrar modal) siguen siendo Heroicons.

---

## 8. Calidad antes de cada commit

```bash
php artisan test --compact          # todas las pruebas
vendor/bin/pint --dirty             # formato del código PHP
vendor/bin/phpstan analyse          # análisis estático
npm run build                       # compila estilos y fuentes
```

El CI de GitHub (`.github/workflows/tests.yml`) corre lo mismo en cada push a `main`.

### Commits

Mensajes en español con [Conventional Commits](https://www.conventionalcommits.org/es/):
`feat(modulo):`, `fix(modulo):`, `refactor:`, `add:`, `build:`, `test:`, `docs:`, `chore:`.
Un commit por tema; nunca subir `.env`, la base SQLite ni Excel con datos de postulantes.

---

## 9. Asistentes de IA (Claude Code)

Los archivos de agentes no se versionan (`CLAUDE.md`, `AGENTS.md`, `.claude/`, `boost.json`, `.mcp.json`).
En una computadora nueva:

```bash
# Guías y herramientas de Laravel Boost (pregunta qué agentes y skills instalar)
php artisan boost:install

# Skills de pulido de interfaz usadas en el rediseño
npx skills add https://github.com/jakubkrehel/skills -s better-ui -s better-typography -s better-layout -a claude-code --copy -y
```

> **Windows + Composer de Herd:** `composer.bat` pasa por `cmd`, que se come el `^` de las versiones
> (`composer require paquete:^4.0` instala una versión fija equivocada). Si necesitas instalar un paquete,
> llama directo al PHP de Herd:
> `& "$env:USERPROFILE\.config\herd\bin\php83\php.exe" "$env:USERPROFILE\.config\herd\bin\composer.phar" require 'vendor/paquete:^4.0'`

---

## 10. Próximos pasos sugeridos

1. **Resultados:** orden de mérito por puesto a partir de `tbl_examen.puntaje_exa` y la condición de cada
   postulante (los inscritos sin hoja no se presentaron). Falta definir con el Comité el puntaje mínimo, el
   criterio de desempate y el número de vacantes por puesto (aún no existe ese campo).
2. **Reportes:** PDF de resultados por puesto y por unidad con Dompdf (`config/dompdf.php` ya usa DejaVu Sans
   con subconjunto de fuentes) y Excel con `maatwebsite/excel` en `app/Exports`.
3. **Pendiente de decidir:** si las flechas internas de Flux también pasan a Hugeicons.
