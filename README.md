# ILicitaciones

ILicitaciones es una plataforma web desarrollada en **Laravel 12** diseñada para centralizar, analizar y monitorear licitaciones del sector público. Su objetivo es facilitar el acceso a la información de contratación pública, proporcionando herramientas analíticas para empresas, investigadores y organismos gubernamentales.

## Características Principales

-   **Dashboard Analítico**: Visualización rápida de estadísticas clave (total de licitaciones, importes, organismos y empresas activas).
-   **Gestión de Licitaciones**: Consulta detallada de procesos de contratación con filtros y búsqueda.
-   **Análisis de Organismos**: Perfiles detallados de entidades públicas con historial de licitaciones.
-   **Perfilado de Empresas**: Seguimiento de adjudicaciones y desempeño de empresas contratistas.
-   **Importación Automatizada**: Comandos de consola para la ingesta masiva de datos desde fuentes externas (XML/APIs).
-   **Optimización de Rendimiento**: Uso de caché y consultas optimizadas para manejar grandes volúmenes de datos.

## Requisitos del Sistema

-   PHP ^8.2
-   Composer
-   Node.js & NPM
-   MySQL o SQLite

## Instalación

1.  **Clonar el repositorio**:
    ```bash
    git clone https://github.com/abrahampo1/ilicitaciones.git
    cd ilicitaciones
    ```

2.  **Instalar dependencias de PHP**:
    ```bash
    composer install
    ```

3.  **Instalar dependencias de Frontend**:
    ```bash
    npm install
    npm run build
    ```

4.  **Configuración de Entorno**:
    ```bash
    cp .env.example .env
    php artisan key:generate
    ```
    Configure los detalles de su base de datos en el archivo `.env`.

5.  **Base de Datos**:
    ```bash
    php artisan migrate
    ```

## Uso

### Servidor de Desarrollo
Para iniciar el servidor local:
```bash
php artisan serve
```

### Importación de Datos
El sistema incluye comandos para poblar la base de datos:

-   **Importar Licitaciones**:
    ```bash
    php artisan app:importar-licitaciones
    ```

-   **Importar Categorías**:
    ```bash
    php artisan app:importar-categorias
    ```