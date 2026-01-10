# 🛠 Instalación y Despliegue (Entorno Local)

## Requisitos Previos

Antes de ejecutar el proyecto, asegúrese de que su máquina local tenga instalados **PHP** y **Composer**. Además, debe instalar **Node.js** y **NPM** (o **Bun**) para compilar los activos frontend de la aplicación.

-   **PHP** >= 8.1
-   **Composer**
-   **MySQL / MariaDB** (o XAMPP activo)
-   **Node.js** (para compilación de assets con Vite)

### Instalación Automática (Windows)

Si no tiene PHP y Composer instalados en su máquina local, puede ejecutar el siguiente comando en **PowerShell como Administrador** para instalar PHP, Composer y el instalador de Laravel automáticamente:

```powershell
Set-ExecutionPolicy Bypass -Scope Process -Force; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072; iex ((New-Object System.Net.WebClient).DownloadString('https://php.new/install/windows/8.4'))
```
> *Nota: Después de ejecutar el comando anterior, debe reiniciar su terminal para que los cambios surtan efecto.*

## Configuración del Proyecto

Una vez clonado el repositorio, ejecute los siguientes comandos para preparar las dependencias y el archivo de entorno:

```bash
# 1. Instalar dependencias de PHP y Node
composer install
npm install

# 2. Configurar variables de entorno (Base de datos)
cp .env.example .env
php artisan key:generate

# 3. (Opcional) Si ya tienes tu BD configurada en el .env
php artisan migrate
```


## 🗄️ Poblado de Datos (Seeders)

Dado que el sistema es de **acceso cerrado** (sin registro público), es necesario ejecutar los *seeders* para crear al primer usuario Administrador y la estructura base de los ciclos escolares.

Ejecute el siguiente comando para migrar la estructura de la base de datos e insertar los datos iniciales:

```bash
php artisan migrate --seed
```

### Datos de Prueba por Rol (Testing)

Si deseas probar el sistema con datos de prueba para cada uno de los roles (Admin, Maestro, Padre), puedes ejecutar el siguiente comando:

```bash
php artisan db:seed --class=RoleTestingSeeder
```

#### Credenciales de Prueba
Una vez ejecutado, tendrás acceso a las siguientes cuentas (contraseña común: `password`):

- **Admin:** `admin.test@escuela.edu.mx`
- **Maestro:** `teacher.test@escuela.edu.mx`
- **Padre:** `parent.test@escuela.edu.mx` (asociado al alumno `ALUMNO DE PRUEBAS`)

---

## Ejecutando la aplicación

Para iniciar tanto el servidor backend como la compilación de estilos (frontend) en un solo paso, use el siguiente comando:

```bash
composer run dev
```

Una vez que haya iniciado el servidor de desarrollo, su aplicación será accesible en su navegador web en:
👉 **http://localhost:8000**

