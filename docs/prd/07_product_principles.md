# 7. Product Principles

1.  **Simplicity Over Scale:** If a feature is only useful for teams (e.g., mention notifications, audit trails of who made a change, permission roles), discard it.
2.  **Keyboard Efficiency First:** Every major action—creating a task, searching credentials, jumping to notes—must be executable via keyboard shortcuts or the command palette.
3.  **Speed is a Feature:** The interface must feel instantaneous. Use Inertia partial reloads, Redis caching, and front-end optimistic updates to eliminate load delays.
4.  **Cohesion, Not CRUD:** Sibling modules must talk to each other. Notes can reference Projects; Credentials can link to Project servers; Invoices must link to Clients.
5.  **Premium Craftsmanship:** Interface styling must be refined. Use Tailwind CSS 4 variables, custom HSL palettes, subtle blur backdrops, and Outfit/Inter fonts.
