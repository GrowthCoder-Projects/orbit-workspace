<?php
$content = file_get_contents(__DIR__ . '/../node_modules/@lucide/vue/dist/lucide-vue.d.ts');
$icons = ['Trello', 'ListTodo', 'Kanban', 'Columns', 'List', 'LayoutGrid', 'LayoutList', 'CheckSquare'];
foreach ($icons as $icon) {
    $exists = str_contains($content, "declare const {$icon}:");
    echo "$icon: " . ($exists ? "YES" : "NO") . "\n";
}
