<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class DocsController extends Controller
{
    /**
     * Display the interactive API documentation page.
     */
    public function index(): Response
    {
        $endpoints = [
            // AUTHENTICATION
            [
                'id' => 'user-info',
                'module' => 'Authentication',
                'title' => 'Get Authenticated User',
                'method' => 'GET',
                'path' => '/api/user',
                'description' => 'Retrieve current authenticated user details and workspace status.',
                'params' => [],
                'requestBody' => null,
                'response' => [
                    'success' => true,
                    'data' => [
                        'id' => 1,
                        'name' => 'Workspace Owner',
                        'email' => 'user@example.com',
                    ],
                ],
            ],

            // NOTES MODULE
            [
                'id' => 'notes-index',
                'module' => 'Notes',
                'title' => 'List Notes',
                'method' => 'GET',
                'path' => '/api/v1/notes',
                'description' => 'Fetch paginated list of notes with optional search keyword, folder, or favorites filter.',
                'params' => [
                    ['name' => 'q', 'type' => 'string', 'required' => false, 'description' => 'Search keyword for title/content'],
                    ['name' => 'folder_id', 'type' => 'integer', 'required' => false, 'description' => 'Filter by folder ID'],
                    ['name' => 'favorites', 'type' => 'boolean', 'required' => false, 'description' => 'Set true to filter favorite notes only'],
                    ['name' => 'per_page', 'type' => 'integer', 'required' => false, 'description' => 'Number of items per page (default: 15)'],
                ],
                'requestBody' => null,
                'response' => [
                    'success' => true,
                    'data' => [
                        'data' => [
                            [
                                'id' => 1,
                                'title' => 'Project Architecture Note',
                                'content' => 'REST API design notes',
                                'folder_id' => null,
                                'is_favorite' => true,
                                'created_at' => '2026-07-21T23:30:00Z',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'id' => 'notes-store',
                'module' => 'Notes',
                'title' => 'Create Note',
                'method' => 'POST',
                'path' => '/api/v1/notes',
                'description' => 'Quickly create a new note in the workspace.',
                'params' => [],
                'requestBody' => [
                    'title' => 'New Idea from Telegram',
                    'content' => 'Automate task reminders via webhooks',
                    'folder_id' => null,
                    'is_favorite' => false,
                ],
                'response' => [
                    'success' => true,
                    'message' => 'Note created successfully',
                    'data' => [
                        'id' => 2,
                        'title' => 'New Idea from Telegram',
                        'content' => 'Automate task reminders via webhooks',
                        'folder_id' => null,
                        'is_favorite' => false,
                        'created_at' => '2026-07-21T23:35:00Z',
                    ],
                ],
            ],
            [
                'id' => 'notes-show',
                'module' => 'Notes',
                'title' => 'Get Note Details',
                'method' => 'GET',
                'path' => '/api/v1/notes/{id}',
                'description' => 'Retrieve details of a specific note including folder and backlinks.',
                'params' => [
                    ['name' => 'id', 'type' => 'integer', 'required' => true, 'description' => 'Note ID in path'],
                ],
                'requestBody' => null,
                'response' => [
                    'success' => true,
                    'data' => [
                        'id' => 2,
                        'title' => 'New Idea from Telegram',
                        'content' => 'Automate task reminders via webhooks',
                        'is_favorite' => false,
                        'created_at' => '2026-07-21T23:35:00Z',
                    ],
                ],
            ],
            [
                'id' => 'notes-update',
                'module' => 'Notes',
                'title' => 'Update Note',
                'method' => 'PATCH',
                'path' => '/api/v1/notes/{id}',
                'description' => 'Update existing note title, content, or folder.',
                'params' => [
                    ['name' => 'id', 'type' => 'integer', 'required' => true, 'description' => 'Note ID in path'],
                ],
                'requestBody' => [
                    'title' => 'Updated Idea Title',
                    'content' => 'Updated content details',
                ],
                'response' => [
                    'success' => true,
                    'message' => 'Note updated successfully',
                    'data' => [
                        'id' => 2,
                        'title' => 'Updated Idea Title',
                        'content' => 'Updated content details',
                    ],
                ],
            ],
            [
                'id' => 'notes-destroy',
                'module' => 'Notes',
                'title' => 'Delete Note',
                'method' => 'DELETE',
                'path' => '/api/v1/notes/{id}',
                'description' => 'Delete a specific note from workspace.',
                'params' => [
                    ['name' => 'id', 'type' => 'integer', 'required' => true, 'description' => 'Note ID in path'],
                ],
                'requestBody' => null,
                'response' => [
                    'success' => true,
                    'message' => 'Note deleted successfully',
                ],
            ],

            // TASKS MODULE
            [
                'id' => 'tasks-index',
                'module' => 'Tasks',
                'title' => 'List Tasks',
                'method' => 'GET',
                'path' => '/api/v1/tasks',
                'description' => 'Fetch workspace tasks filtered by status, priority, or project.',
                'params' => [
                    ['name' => 'status', 'type' => 'string', 'required' => false, 'description' => 'Filter by status (pending / completed)'],
                    ['name' => 'priority', 'type' => 'string', 'required' => false, 'description' => 'Filter by priority (low / medium / high / urgent)'],
                    ['name' => 'project_id', 'type' => 'integer', 'required' => false, 'description' => 'Filter by project ID'],
                ],
                'requestBody' => null,
                'response' => [
                    'success' => true,
                    'data' => [
                        'data' => [
                            [
                                'id' => 10,
                                'title' => 'Review API Documentation',
                                'priority' => 'high',
                                'status' => 'pending',
                                'due_date' => '2026-07-25T17:00:00Z',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'id' => 'tasks-store',
                'module' => 'Tasks',
                'title' => 'Create Task',
                'method' => 'POST',
                'path' => '/api/v1/tasks',
                'description' => 'Create a new task with priority and due date.',
                'params' => [],
                'requestBody' => [
                    'title' => 'Send invoice to client',
                    'description' => 'Project milestone #2 invoice',
                    'priority' => 'high',
                    'due_date' => '2026-07-25T17:00:00Z',
                ],
                'response' => [
                    'success' => true,
                    'message' => 'Task created successfully',
                    'data' => [
                        'id' => 11,
                        'title' => 'Send invoice to client',
                        'priority' => 'high',
                        'created_at' => '2026-07-21T23:40:00Z',
                    ],
                ],
            ],
            [
                'id' => 'tasks-show',
                'module' => 'Tasks',
                'title' => 'Get Task Details',
                'method' => 'GET',
                'path' => '/api/v1/tasks/{id}',
                'description' => 'Retrieve details of a task and its checklist items.',
                'params' => [
                    ['name' => 'id', 'type' => 'integer', 'required' => true, 'description' => 'Task ID in path'],
                ],
                'requestBody' => null,
                'response' => [
                    'success' => true,
                    'data' => [
                        'id' => 11,
                        'title' => 'Send invoice to client',
                        'priority' => 'high',
                        'checklists' => [],
                    ],
                ],
            ],
            [
                'id' => 'tasks-update',
                'module' => 'Tasks',
                'title' => 'Update Task',
                'method' => 'PATCH',
                'path' => '/api/v1/tasks/{id}',
                'description' => 'Update task status, title, priority, or due date.',
                'params' => [
                    ['name' => 'id', 'type' => 'integer', 'required' => true, 'description' => 'Task ID in path'],
                ],
                'requestBody' => [
                    'status' => 'completed',
                ],
                'response' => [
                    'success' => true,
                    'message' => 'Task updated successfully',
                    'data' => [
                        'id' => 11,
                        'status' => 'completed',
                    ],
                ],
            ],
            [
                'id' => 'tasks-destroy',
                'module' => 'Tasks',
                'title' => 'Delete Task',
                'method' => 'DELETE',
                'path' => '/api/v1/tasks/{id}',
                'description' => 'Delete a specific task.',
                'params' => [
                    ['name' => 'id', 'type' => 'integer', 'required' => true, 'description' => 'Task ID in path'],
                ],
                'requestBody' => null,
                'response' => [
                    'success' => true,
                    'message' => 'Task deleted successfully',
                ],
            ],

            // HABITS MODULE
            [
                'id' => 'habits-index',
                'module' => 'Habits',
                'title' => 'List Habits',
                'method' => 'GET',
                'path' => '/api/v1/habits',
                'description' => 'Fetch active habits.',
                'params' => [
                    ['name' => 'archived', 'type' => 'boolean', 'required' => false, 'description' => 'Include archived habits'],
                ],
                'requestBody' => null,
                'response' => [
                    'success' => true,
                    'data' => [
                        [
                            'id' => 1,
                            'name' => 'Morning Jogging',
                            'frequency_type' => 'daily',
                            'is_active' => true,
                        ],
                    ],
                ],
            ],
            [
                'id' => 'habits-store',
                'module' => 'Habits',
                'title' => 'Create Habit',
                'method' => 'POST',
                'path' => '/api/v1/habits',
                'description' => 'Create a new habit tracker.',
                'params' => [],
                'requestBody' => [
                    'name' => 'Read 15 pages of book',
                    'description' => 'Self improvement reading',
                    'frequency_type' => 'daily',
                ],
                'response' => [
                    'success' => true,
                    'message' => 'Habit created successfully',
                    'data' => [
                        'id' => 2,
                        'name' => 'Read 15 pages of book',
                    ],
                ],
            ],
            [
                'id' => 'habits-toggle',
                'module' => 'Habits',
                'title' => 'Toggle Habit Completion',
                'method' => 'POST',
                'path' => '/api/v1/habits/{id}/toggle',
                'description' => 'Check-in or uncheck habit completion for a date.',
                'params' => [
                    ['name' => 'id', 'type' => 'integer', 'required' => true, 'description' => 'Habit ID in path'],
                ],
                'requestBody' => [
                    'date' => '2026-07-21',
                ],
                'response' => [
                    'success' => true,
                    'message' => 'Habit status toggled successfully',
                    'data' => [
                        'habit_id' => 1,
                        'completed' => true,
                        'date' => '2026-07-21',
                    ],
                ],
            ],
            [
                'id' => 'habits-destroy',
                'module' => 'Habits',
                'title' => 'Delete Habit',
                'method' => 'DELETE',
                'path' => '/api/v1/habits/{id}',
                'description' => 'Delete a habit tracker.',
                'params' => [
                    ['name' => 'id', 'type' => 'integer', 'required' => true, 'description' => 'Habit ID in path'],
                ],
                'requestBody' => null,
                'response' => [
                    'success' => true,
                    'message' => 'Habit deleted successfully',
                ],
            ],

            // FINANCE MODULE
            [
                'id' => 'finance-transactions-index',
                'module' => 'Finance',
                'title' => 'List Transactions',
                'method' => 'GET',
                'path' => '/api/v1/finance/transactions',
                'description' => 'Fetch financial transaction history.',
                'params' => [
                    ['name' => 'type', 'type' => 'string', 'required' => false, 'description' => 'income / expense / transfer'],
                    ['name' => 'account_id', 'type' => 'integer', 'required' => false, 'description' => 'Account ID'],
                ],
                'requestBody' => null,
                'response' => [
                    'success' => true,
                    'data' => [
                        'data' => [
                            [
                                'id' => 50,
                                'amount' => 45000,
                                'type' => 'expense',
                                'description' => 'Lunch meal',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'id' => 'finance-transactions-store',
                'module' => 'Finance',
                'title' => 'Create Transaction',
                'method' => 'POST',
                'path' => '/api/v1/finance/transactions',
                'description' => 'Record a new income, expense, or transfer transaction.',
                'params' => [],
                'requestBody' => [
                    'amount' => 45000,
                    'type' => 'expense',
                    'description' => 'Lunch meal',
                    'account_id' => 1,
                    'category_id' => 2,
                    'date' => '2026-07-21',
                ],
                'response' => [
                    'success' => true,
                    'message' => 'Transaction created successfully',
                    'data' => [
                        'id' => 50,
                        'amount' => 45000,
                        'type' => 'expense',
                        'description' => 'Lunch meal',
                        'account_id' => 1,
                        'created_at' => '2026-07-21T23:42:00Z',
                    ],
                ],
            ],
            [
                'id' => 'finance-transactions-destroy',
                'module' => 'Finance',
                'title' => 'Delete Transaction',
                'method' => 'DELETE',
                'path' => '/api/v1/finance/transactions/{id}',
                'description' => 'Delete a financial transaction.',
                'params' => [
                    ['name' => 'id', 'type' => 'integer', 'required' => true, 'description' => 'Transaction ID in path'],
                ],
                'requestBody' => null,
                'response' => [
                    'success' => true,
                    'message' => 'Transaction deleted successfully',
                ],
            ],

            // BOOKMARKS MODULE
            [
                'id' => 'bookmarks-index',
                'module' => 'Bookmarks',
                'title' => 'List Bookmarks',
                'method' => 'GET',
                'path' => '/api/v1/bookmarks',
                'description' => 'Fetch saved bookmarks.',
                'params' => [
                    ['name' => 'q', 'type' => 'string', 'required' => false, 'description' => 'Search title or URL'],
                    ['name' => 'favorite', 'type' => 'boolean', 'required' => false, 'description' => 'Filter favorites'],
                ],
                'requestBody' => null,
                'response' => [
                    'success' => true,
                    'data' => [
                        'data' => [
                            [
                                'id' => 5,
                                'title' => 'Laravel Sanctum Docs',
                                'url' => 'https://laravel.com/docs/sanctum',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'id' => 'bookmarks-store',
                'module' => 'Bookmarks',
                'title' => 'Save Bookmark',
                'method' => 'POST',
                'path' => '/api/v1/bookmarks',
                'description' => 'Save a URL link to your bookmark list.',
                'params' => [],
                'requestBody' => [
                    'title' => 'Laravel Sanctum Docs',
                    'url' => 'https://laravel.com/docs/sanctum',
                    'description' => 'Official API authentication guide',
                ],
                'response' => [
                    'success' => true,
                    'message' => 'Bookmark created successfully',
                    'data' => [
                        'id' => 5,
                        'title' => 'Laravel Sanctum Docs',
                        'url' => 'https://laravel.com/docs/sanctum',
                        'created_at' => '2026-07-21T23:45:00Z',
                    ],
                ],
            ],
            [
                'id' => 'bookmarks-destroy',
                'module' => 'Bookmarks',
                'title' => 'Delete Bookmark',
                'method' => 'DELETE',
                'path' => '/api/v1/bookmarks/{id}',
                'description' => 'Delete a saved bookmark.',
                'params' => [
                    ['name' => 'id', 'type' => 'integer', 'required' => true, 'description' => 'Bookmark ID in path'],
                ],
                'requestBody' => null,
                'response' => [
                    'success' => true,
                    'message' => 'Bookmark deleted successfully',
                ],
            ],

            // CALENDAR MODULE
            [
                'id' => 'calendar-events-index',
                'module' => 'Calendar',
                'title' => 'List Calendar Events',
                'method' => 'GET',
                'path' => '/api/v1/calendar/events',
                'description' => 'Fetch calendar events by date range.',
                'params' => [
                    ['name' => 'start_date', 'type' => 'string', 'required' => false, 'description' => 'Start date (Y-m-d)'],
                    ['name' => 'end_date', 'type' => 'string', 'required' => false, 'description' => 'End date (Y-m-d)'],
                ],
                'requestBody' => null,
                'response' => [
                    'success' => true,
                    'data' => [
                        [
                            'id' => 8,
                            'title' => 'Team Sync Meeting',
                            'start_at' => '2026-07-22T09:00:00Z',
                        ],
                    ],
                ],
            ],
            [
                'id' => 'calendar-events-store',
                'module' => 'Calendar',
                'title' => 'Create Calendar Event',
                'method' => 'POST',
                'path' => '/api/v1/calendar/events',
                'description' => 'Schedule a new calendar event or reminder.',
                'params' => [],
                'requestBody' => [
                    'title' => 'Team Sync Meeting',
                    'start_at' => '2026-07-22T09:00:00Z',
                    'end_at' => '2026-07-22T10:00:00Z',
                    'location' => 'Google Meet',
                ],
                'response' => [
                    'success' => true,
                    'message' => 'Calendar event created successfully',
                    'data' => [
                        'id' => 8,
                        'title' => 'Team Sync Meeting',
                        'start_at' => '2026-07-22T09:00:00Z',
                        'end_at' => '2026-07-22T10:00:00Z',
                    ],
                ],
            ],
            [
                'id' => 'calendar-events-destroy',
                'module' => 'Calendar',
                'title' => 'Delete Calendar Event',
                'method' => 'DELETE',
                'path' => '/api/v1/calendar/events/{id}',
                'description' => 'Delete a calendar event.',
                'params' => [
                    ['name' => 'id', 'type' => 'integer', 'required' => true, 'description' => 'Event ID in path'],
                ],
                'requestBody' => null,
                'response' => [
                    'success' => true,
                    'message' => 'Calendar event deleted successfully',
                ],
            ],
        ];

        return Inertia::render('docs/Index', [
            'endpoints' => $endpoints,
            'baseUrl' => config('app.url', 'http://localhost:8000'),
            'isInApp' => request()->is('app/*'),
        ]);
    }
}
