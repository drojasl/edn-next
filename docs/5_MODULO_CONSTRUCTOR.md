
## 13. Creación de Cursos (Experiencia del Empresario)

### 13.1 Objetivo del Módulo
El módulo de creación NO es un formulario, sino un **Flow Builder Visual**.
** Objetivos:**
*   Diseñar experiencias educativas completas.
*   Visualizar el flujo del prospecto.
*   Entender puntos de decisión y abandono.
*   Crear recorridos sin conocimientos técnicos.

### 13.2 Principio Fundamental
> "Un empresario debe poder crear y entender un curso sin leer documentación técnica."
*   **Basado en:** Representación visual, metáforas claras (nodos, mapa) e interacción directa.

### 13.3 Enfoque Visual (Flow Builder)
**Componentes:**
1.  **Lienzo (Canvas):** Área infinita con zoom/pan donde se diseña UN curso.
2.  **Nodos Visuales:** Tarjetas que representan pasos (`Video`, `Form`, `Menu`, `Info`, `Action`).
    *   Muestran tipo e ícono.
    *   Título editable.
    *   Indican si es Inicio/Fin.
3.  **Conexiones:** Líneas que unen botones de un nodo con el siguiente nodo (representan decisiones/flujo).

### 13.4 Interacción Drag & Drop
*   Arrastrar nodos desde paleta.
*   Reordenar libremente.
*   Conectar arrastrando desde conectores ("sockets").
*   Validaciones:
    *   No guardar sin nodo inicial.
    *   No conectar a nodos de *otros* cursos.
    *   Ciclos permitidos (pero validados).

### 13.5 Separación de Cursos
*   La conexión entre cursos (Curso A -> Curso B) **NO** se dibuja en el canvas.
*   Se define por configuración (`next_course_id`).
*   Esto mantiene el canvas limpio y enfocado en la experiencia *actual*.

### 13.6 Nodo Menú (Ramificación)
*   Visualmente es un nodo que lista opciones.
*   Cada opción lleva a una URL/Inicio de otro curso.
*   Se usa para: Menú principal, Centro de exploración, Reingreso.

### 13.7 Cursos por Defecto
Al crear cuenta, se generan plantillas editables:
*   Introducción
*   Seguimiento
*   La empresa
*   La oportunidad
*   Onboarding

### 13.8 URLs
*   `/cursos/{course-slug}` (Slug único por empresario).
*   Contexto resuelto por Cookies/Código activo.

### 13.9 Métricas en el Canvas (Overlay)
*   Mostrar cantidad de prospectos que pasaron por cada nodo.
*   % de Abandono visual (ej. nodo en rojo si muchos se van ahí).
