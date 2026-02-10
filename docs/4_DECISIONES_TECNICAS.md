# Decisiones Técnicas y Arquitectura

## 1. Stack Tecnológico (Definitivo)

### Backend
*   **Framework:** Laravel (PHP).
*   **API:** RESTful API.
*   **Auth:** Sistema de autenticación solo para Empresarios (Users). Prospectos sin login tradicional (Cookies/Token).
*   **Base de Datos:** PostgreSQL (Recomendado por soporte JSONB superior).

### Frontend
*   **Framework:** Vue.js con Typescript.
*   **Estilos:** Tailwind CSS.
*   **Cliente HTTP:** Axios.
*   **Visual Builder:** **Vue Flow** (Librería recomendada para diagramas de nodos en Vue).

## 2. Arquitectura y Estándares

### 2.1 Estructura del Código
*   **Idioma:** Código y variables en **Inglés**.
*   **Lógica:** Encapsulada en **Services** (Service Pattern) para mantener controladores limpios.
*   **Modelos:** Uso de `SoftDeletes` en todas las entidades principales.
*   **Calidad:** ESLint, Prettier y Husky para pre-commit hooks.

### 2.2 Diseño y UX
*   **Mobile First:** Diseño responsivo priorizando móviles.
*   **Multilenguaje (i18n):** Interfaz preparada para Español e Inglés desde el inicio.
*   **Accesibilidad:** "La complejidad es invisible para el usuario".

### 2.3 Principios Inquebrantables
1.  **El sistema guía, no presiona:** UX fluida y amigable.
2.  **Identidad Clara:** El prospecto siempre sabe quién es su empresario (User context).
3.  **Valor primero:** El contenido educa antes de vender.
4.  **Acción:** Los nodos no son solo informativos; deben impulsar pequeñas tareas/hábitos.

## 3. Estrategia de Desarrollo
*   **Fase 1:** Setup de repositorios (Laravel API + Vue Client) y configuración de base de datos.
*   **Fase 2:** CRUD de Usuarios y Autenticación.
*   **Fase 3:** Builder de Cursos (Nodos y Opciones).
*   **Fase 4:** Vista pública del Prospecto (Landing y ejecución de curso).
