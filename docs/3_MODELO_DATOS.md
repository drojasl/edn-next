# Modelo de Datos (Definitivo)

## 1. Diagrama ER (Sugerido)

```mermaid
erDiagram
    users ||--o{ user_style_settings : has
    users ||--o{ user_social_links : has
    users ||--o{ courses : owns
    courses ||--o{ course_nodes : contains
    course_nodes ||--o{ course_node_options : has
    courses ||--|| courses : next_course
    users ||--o{ access_codes : manages
    access_codes ||--|| courses : starts_at
    prospects ||--o{ prospect_access_logs : has
    prospects ||--o{ prospect_course_progress : tracks
    prospects ||--o{ prospect_node_progress : tracks

    users {
        bigint id PK
        string name
        string last_name
        string email UK
        string password
        string codigo_amway "Non-unique"
        boolean is_active
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    user_style_settings {
        bigint id PK
        bigint user_id FK
        string primary_color
        string secondary_color
        string accent_color
        string logo_url
        string theme_mode "light/dark"
    }

    courses {
        bigint id PK
        bigint user_id FK
        string title
        string slug UK
        text description
        boolean is_active
        bigint next_course_id FK "nullable"
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    course_nodes {
        bigint id PK
        bigint course_id FK
        string type "video, form, meta, html, task"
        string title
        json content "Flexible data"
        string video_url "nullable"
        int position
        boolean is_start
        boolean is_end
    }

    course_node_options {
        bigint id PK
        bigint course_node_id FK
        string label
        bigint next_node_id FK "nullable"
    }

    access_codes {
        bigint id PK
        bigint user_id FK
        bigint course_id FK
        string code UK
        timestamp expires_at
        boolean is_active
    }

    prospects {
        bigint id PK
        string email "nullable"
        string phone "nullable"
        string city "nullable"
        string country "nullable"
    }

    prospect_access_logs {
        bigint id PK
        bigint prospect_id FK
        bigint access_code_id FK
        timestamp activated_at
        timestamp expired_at
    }

    prospect_course_progress {
        bigint id PK
        bigint prospect_id FK
        bigint course_id FK
        timestamp started_at
        timestamp completed_at
    }

    prospect_node_progress {
        bigint id PK
        bigint prospect_id FK
        bigint course_node_id FK
        timestamp viewed_at
        json data "Captured answers/data"
    }
```

## 2. Definición de Tablas y Campos

### 2.1 Users & Config
*   **users**: Tabla principal para empresarios. Soporta `soft_deletes`.
*   **user_style_settings**: Personalización visual por usuario.
*   **user_social_links**: Redes sociales y contacto del empresario.

### 2.2 Cursos y Nodos
*   **courses**: Secuencias educativas. `next_course_id` permite encadenamiento.
*   **course_nodes**: Pasos del curso. `type` define el comportamiento (video, form, etc). `is_start` e `is_end` marcan los límites.
*   **course_node_options**: Define la navegación entre nodos (ramificaciones).

### 2.3 Accesos y Prospectos
*   **access_codes**: Códigos generados por usuarios para dar entrada a cursos.
*   **prospects**: Usuarios finales (sin auth tradicional). Datos mínimos iniciales.

### 2.4 Progreso y Logs
*   **prospect_access_logs**: Historial de activaciones de códigos.
*   **prospect_course_progress**: Seguimiento de cursos iniciados/completados.
*   **prospect_node_progress**: Registro granular de nodos vistos y datos capturados (en campo `data`).

## 3. Notas de Implementación
*   **Soft Deletes**: Implementar en todas las tablas principales (`users`, `courses`, `nodes`).
*   **JSON Content**: Usar campo `content` en `course_nodes` para flexibilidad de tipos de nodo.
*   **Navegación**: La lógica de "siguiente nodo" reside en `course_node_options`. Si un nodo no tiene opciones, es un nodo terminal o lineal simple.
