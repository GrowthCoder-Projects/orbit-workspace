# 20. Search Architecture

*   **Engine Option:** Implement **PostgreSQL Full-Text Search (FTS)** for simple hosting dependencies, or **Laravel Scout with Meilisearch** for instant fuzzy matches.
*   **Fuzzy Modal Integration:** Clicking `Cmd+K` launches the Vue search interface. The client pulls cached database identifiers (e.g. project names, note titles) on mount, updating indexes dynamically using a debounced 150ms remote API endpoint check as query lengths increase.
