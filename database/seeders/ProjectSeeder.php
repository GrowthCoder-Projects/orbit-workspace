<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\Task;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $acme = Client::where('company', 'Acme Corp')->first();
        $stark = Client::where('company', 'Stark Industries')->first();
        $wayne = Client::where('company', 'Wayne Enterprises')->first();

        // Project 1: Workspace OS — Active flagship project
        $workspace = Project::create([
            'client_id' => $wayne?->id,
            'name' => 'Workspace OS',
            'slug' => 'workspace-os',
            'description' => 'Personal operating system for work. A unified web app centralizing project tracking, task management, client records, secure credentials, and personal knowledge base.',
            'color' => '#5C59D9',
            'repository_url' => 'https://github.com/growthcoder/workspace',
            'production_url' => 'https://workspace.growthcoder.local',
            'staging_url' => 'https://staging.workspace.growthcoder.local',
            'server_ip' => '192.168.1.100',
            'status' => 'active',
        ]);

        // Milestones for Workspace OS
        ProjectMilestone::create([
            'project_id' => $workspace->id,
            'title' => 'Phase 1 — Foundation (Auth, Tasks, Settings)',
            'description' => 'Authentication system, Kanban task management, and settings module.',
            'due_date' => now()->subDays(5)->format('Y-m-d'),
            'completed_at' => now()->subDays(5),
            'status' => 'completed',
            'sort_order' => 1,
        ]);

        ProjectMilestone::create([
            'project_id' => $workspace->id,
            'title' => 'Phase 2 — Projects & Clients Module',
            'description' => 'Core Projects CRUD, Milestones tracker, and Client management module.',
            'due_date' => now()->addDays(7)->format('Y-m-d'),
            'completed_at' => null,
            'status' => 'pending',
            'sort_order' => 2,
        ]);

        ProjectMilestone::create([
            'project_id' => $workspace->id,
            'title' => 'Phase 3 — Notes & Knowledge Base',
            'description' => 'WYSIWYG markdown editor with backlinks and folder hierarchy.',
            'due_date' => now()->addDays(21)->format('Y-m-d'),
            'completed_at' => null,
            'status' => 'pending',
            'sort_order' => 3,
        ]);

        ProjectMilestone::create([
            'project_id' => $workspace->id,
            'title' => 'Phase 4 — Finance & Invoice',
            'description' => 'Income/expense ledger, PDF invoice generator, and client billing integration.',
            'due_date' => now()->addDays(45)->format('Y-m-d'),
            'completed_at' => null,
            'status' => 'pending',
            'sort_order' => 4,
        ]);

        // Create some tasks linked to this project
        Task::create([
            'project_id' => $workspace->id,
            'title' => 'Build Projects Index page with card grid',
            'description' => 'Implement the card grid UI for the Projects module with initials avatar and status badges.',
            'status' => 'in_progress',
            'priority' => 'urgent',
            'due_date' => now()->addDays(2)->format('Y-m-d'),
        ]);

        Task::create([
            'project_id' => $workspace->id,
            'title' => 'Build Milestones timeline component',
            'description' => 'Visual vertical timeline showing project milestones with toggle complete.',
            'status' => 'todo',
            'priority' => 'high',
            'due_date' => now()->addDays(3)->format('Y-m-d'),
        ]);

        Task::create([
            'project_id' => $workspace->id,
            'title' => 'Write feature tests for Projects CRUD',
            'description' => 'Pest tests covering create, read, update, delete, and milestone management.',
            'status' => 'done',
            'priority' => 'high',
            'due_date' => now()->subDay()->format('Y-m-d'),
        ]);

        // Project 2: GrowthCoder Platform — Pipeline
        $growthcoder = Project::create([
            'client_id' => $acme?->id,
            'name' => 'GrowthCoder Platform',
            'slug' => 'growthcoder-platform',
            'description' => 'Main public-facing educational platform for the GrowthCoder brand. Laravel + Nuxt.js stack with subscription billing and course management.',
            'color' => '#2BB673',
            'repository_url' => 'https://github.com/growthcoder/platform',
            'production_url' => 'https://growthcoder.id',
            'staging_url' => 'https://staging.growthcoder.id',
            'server_ip' => '103.45.67.89',
            'status' => 'active',
        ]);

        ProjectMilestone::create([
            'project_id' => $growthcoder->id,
            'title' => 'Course Player Redesign',
            'description' => 'Redesign video player with progress tracking and transcript viewer.',
            'due_date' => now()->addDays(14)->format('Y-m-d'),
            'completed_at' => null,
            'status' => 'pending',
            'sort_order' => 1,
        ]);

        ProjectMilestone::create([
            'project_id' => $growthcoder->id,
            'title' => 'Payment Gateway Integration',
            'description' => 'Midtrans + Stripe integration for subscription billing.',
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'completed_at' => null,
            'status' => 'pending',
            'sort_order' => 2,
        ]);

        // Project 3: Client API Service — Archived
        Project::create([
            'client_id' => $stark?->id,
            'name' => 'Client Portal API',
            'slug' => 'client-portal-api',
            'description' => 'Legacy REST API for an external client portal. Maintained for backward compatibility. No new features planned.',
            'color' => '#9B59B6',
            'repository_url' => 'https://github.com/growthcoder/client-portal-api',
            'production_url' => 'https://api.clientportal.com',
            'staging_url' => null,
            'server_ip' => '45.120.33.12',
            'status' => 'archived',
        ]);
    }
}
