# 9. Navigation Structure

Workspace utilizes a collapsible, dual-state Navigation Layout:

1.  **Sidebar Mode:** A permanent left-hand navigation panel containing:
    *   **User Profile & Status Indicator** (Database connected, active jobs, local time).
    *   **Global Command Trigger:** Visual hint showing `Cmd+K`.
    *   **Core Workflows:** Dashboard, Tasks (Kanban/List), Calendar.
    *   **Knowledge & Notes:** Notes (Markdown), Wiki (Knowledge Base), Bookmarks.
    *   **Management:** Projects, Clients, Documents.
    *   **Ops & Security:** Credentials, Activity Log.
    *   **Financials:** Finance, Invoices.
    *   **System:** Settings.
2.  **Focus Mode:** Triggered via `Cmd+F`. Hides the sidebar completely, maximizing screenspace for Markdown writing (Notes/Wiki) or Task kanban.
3.  **Command Palette Overlay:** Instantly triggered via `Cmd+K` from any view. Renders a modal overlay centered on screen, indexing all routes, project names, credentials, notes, and tasks.
