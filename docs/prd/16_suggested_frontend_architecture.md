# 16. Suggested Frontend Architecture

The frontend is built as a Single Page Application (SPA) utilizing **Inertia.js v3** and **Vue 3**. It leverages the standard Laravel Vue Starter Kit.

### Frontend Component Stack
*   **UI Primitives:** Custom primitives configured via `shadcn-vue`, wrapping Tailwind CSS 4 styled elements.
*   **State Management (Pinia):**
    *   `useAppStore`: Handles layout configurations, Sidebar collapse status, and current themes.
    *   `useSearchStore`: Manages fuzzy indexing lists loaded locally for the Command Palette.
    *   `useCredentialStore`: Stores the master key session check to cache decryption variables on local tabs.
*   **Utilities (VueUse):**
    *   `useActiveElement`: Monitors text area inputs for active states.
    *   `useDebounceFn`: Handles notes auto-save timing.
    *   `useKeyModifier` & `onKeyStroke`: Tracks keyboard navigation shortcuts globally.
