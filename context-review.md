# Context Review & Discovery

I have reviewed the documentation in the `/docs` directory. This provides the foundational knowledge needed to proceed with the development of the EDN (Escuela de Negocios Digital) platform.

## Summary of Findings

- **Business Goal:** Automate Amway recruitment and training funnels through sequential "Courses".
- **Key Entities:** 
    - **Entrepreneurs (Users):** Authenticated users who manage courses and access codes.
    - **Prospects:** Non-authenticated users entering via codes.
    - **Courses/Nodes:** The building blocks of the experience (Video, Form, Menu, Info, Action).
- **Core Rules:** One-way progression, branching only via Menus, persistence via cookies/tokens linked to the entrepreneur.
- **Visual Goal:** A Flow Builder using **Vue Flow** to design these experiences visually.

## Strategic Questions (Socratic Gate)

To proceed effectively, I need to clarify the following:

1. **Current Focus:** We are currently in Phase 3/4. The open files (`CourseFlowEditor.vue`, `CoursesAdmin.vue`) suggest we are refining the **Flow Builder**. Should we prioritize completing the visual saving/loading logic (like the `pos_x`/`pos_y` mentioned in history) or start implementing the **Prospect View**?
2. **Schema Alignment:** `3_MODELO_DATOS.md` is the "Definitive" model. Does the current database fully match this? I noticed previous work on `pos_x/pos_y` which isn't explicitly in the `.md` diagram.
3. **Connectors and Logic:** For the Flow Builder, do we have a specific set of "custom node types" already implemented, or should we refine the logic for the `type` field (video, form, meta, html, task)?
4. **Edge Cases:** How should the system respond if a prospect uses an expired code or enters a course they've already completed?

## Next Steps

1. Await feedback on these questions.
2. Finalize the implementation plan for the next feature.
