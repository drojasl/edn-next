# Reglas y Flujos del Proyecto

## 1. Conceptos Clave
### 4.1 Curso (Course)
Un curso es una experiencia educativa completa y coherente (ej: Introducción, Seguimiento, La empresa, Onboarding, Primeros pasos, Hábitos).
**Propiedades:**
*   Tiene un nombre.
*   Pertenece a un empresario.
*   Contiene nodos.
*   Tiene inicio y fin.
*   Puede encadenarse a **un solo** curso siguiente (`next_course_id`).

### 4.2 Nodo (Course Node)
Unidad mínima de experiencia dentro de un curso.
**Tipos:**
*   `video`: Enlace a YouTube.
*   `form`: Captura de datos.
*   `menu`: Selección de cursos (permite navegación libre, cursos paralelos, reingreso).
*   `info`: Texto informativo.
*   `action`: Call to Action (CTA).

**Reglas de Diseño:**
*   Mínimos botones visibles.
*   Solo un CTA principal.
*   Navegación secundaria o retroceso oculta.

### 4.3 Código de acceso
Representa la combinación **Empresario + Curso inicial**. NO representa un usuario, registro o contrato. Solo provee el contexto de entrada.

## 2. Actores del Sistema
### 3.1 Empresario (User)
Usuario autenticado del sistema.
**Puede representar:** Empresario Amway, Cotitular o (futuro) Empresario SaaS.
**Datos:** Nombre, Email, Código Amway (NO único), Datos de contacto, Estilos visuales.

### 3.2 Prospecto (Prospect)
Usuario **no autenticado**.
**Características:**
*   No tiene login.
*   Se identifica por contexto y cookies.
*   Puede existir varias veces y tener múltiples progresos.
*   Puede cambiar de empresario según reglas.

## 3. Reglas de Negocio Críticas
### 5.1 Prioridad de Empresarios
*   **Proceso activo/reciente:** Prima el primer empresario.
*   **Proceso antiguo:** Un nuevo código reemplaza al anterior.
*   **Sin código nuevo:** Se reactiva el último válido.
*   **Mismo código:** Continúa el proceso.

### 5.2 Persistencia (Cookies/LocalStorage)
*   El código activo se guarda (duración configurable, ej: 15 días).
*   Permite encadenar cursos y retomar sin fricción.

### 5.3 Acceso a Cursos
Un prospecto puede ver otros cursos sin un código específico para ellos, siempre que pertenezcan al **mismo empresario** del código activo.

### 7. Regla de Encadenamiento
*   Un curso solo puede ir a un **curso siguiente** (`next_course_id`).
*   Los escenarios múltiples se resuelven con un **Nodo Menú**.
*   **PROHIBIDO:** `course_transitions` o múltiples destinos directos por curso.

## 4. Flujos Principales
### 6.1 Ingreso
*   Landing `/curso`.
*   Ingreso manual de código.
*   Link directo: `/curso?access=CODIGO`.

### 6.2 Curso
*   Nodo inicial.
*   Avance secuencial.
*   Contacto del empresario **siempre visible**.
*   Progreso guardado automáticamente.

### 6.3 Encadenamiento
Al finalizar un curso:
*   Si existe `next_course_id` -> Sugerir continuar.
*   Si no -> Mostrar Menú o Cierre.
