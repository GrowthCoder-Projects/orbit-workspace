Act as a Senior Product Manager, Senior Software Architect, Senior UX Designer, and Staff Software Engineer with experience designing modern productivity applications such as Notion, Linear, TickTick, Todoist, Craft, Arc Browser, and Raycast.

Your task is to create a comprehensive Product Requirements Document (PRD) for a personal project.

The PRD should be detailed, realistic, and written as if it will be handed directly to a professional software development team.

==========================================================
PROJECT OVERVIEW
==========================================================

Project Name (Working Title):
Workspace

Workspace is a private web application designed exclusively for myself.

It is NOT:
- a CRM
- an ERP
- a SaaS product
- a team collaboration platform

Workspace is my personal operating system for work.

It will become the application that I open every morning when I start working and the last application I close before finishing work.

The purpose is to centralize everything related to my daily workflow into one modern application.

The application should feel fast, clean, minimal, elegant, enjoyable, and highly organized.

==========================================================
IMPORTANT CONTEXT
==========================================================

This project is completely separated from my Portfolio project.

My Portfolio project already contains:

- Portfolio Website
- Portfolio Management
- Blog CMS
- Contact Management
- Public Pages
- SEO
- Analytics for Portfolio

Do NOT duplicate these features.

Workspace only focuses on my personal daily workflow.

==========================================================
TARGET USER
==========================================================

Single User.

Only me.

No multi-user support.

No organization.

No team management.

No permissions system beyond a single authenticated user.

==========================================================
PRODUCT PHILOSOPHY
==========================================================

Workspace should become my "second brain".

Everything related to my professional life should exist inside Workspace.

The application should reduce context switching.

Instead of opening multiple applications, Workspace should centralize my workflow.

The application should emphasize:

- Simplicity
- Focus
- Speed
- Organization
- Beautiful user experience

Avoid unnecessary complexity.

Avoid enterprise workflows.

Avoid business processes that are not useful for a single user.

Every feature must solve a real daily problem.

The application should feel cohesive rather than a collection of CRUD pages.

==========================================================
GOALS
==========================================================

Workspace should help me:

- manage projects
- manage clients
- manage daily tasks
- organize meetings
- remember deadlines
- write notes
- build a personal knowledge base
- store important documents
- organize bookmarks
- generate invoices
- track income and expenses
- receive reminders
- quickly access everything related to my work

==========================================================
TECH STACK
==========================================================

Backend

- Laravel 13
- PHP 8.4+
- PostgreSQL
- Redis
- Laravel Sanctum
- Laravel Horizon
- Laravel Scheduler
- Laravel Scout (optional)
- Laravel Reverb (future)

Frontend

- Laravel Vue Starter Kit
- Vue 3
- TypeScript
- Inertia.js
- Vite
- Tailwind CSS 4
- shadcn-vue
- Pinia
- VueUse

Storage

- Local Storage
- S3 Compatible Storage

Authentication

- Laravel Sanctum

Queue

- Redis
- Horizon

Notifications

- Database Notifications
- Email
- Telegram

==========================================================
MODULES
==========================================================

Expand every module in detail.

For each module provide:

- Purpose
- Features
- User Flow
- Database Entities
- Relationships
- Validation Rules
- Future Improvements
- UX Recommendations

----------------------------------------------------------

1. Dashboard

Daily overview

Today's tasks

Today's schedule

Upcoming deadlines

Recent activity

Pending invoices

Quick notes

Quick actions

Statistics

Widgets

----------------------------------------------------------

2. Projects

Projects

Milestones

Timeline

Files

Repository links

Production URL

Environment

Status

Progress

Notes

----------------------------------------------------------

3. Clients

Basic client information

Contact information

Notes

Project history

Invoices

No CRM pipeline.

No lead management.

----------------------------------------------------------

4. Tasks

Kanban

List

Priority

Labels

Due dates

Recurring tasks

Checklist

Attachments

Comments

Search

Filtering

----------------------------------------------------------

5. Calendar

Meetings

Deadlines

Project schedule

Personal reminders

Recurring events

----------------------------------------------------------

6. Notes

Markdown

Folders

Tags

Backlinks

Search

Archive

Favorites

Quick Notes

----------------------------------------------------------

7. Knowledge Base

Personal wiki

Technical documentation

Deployment guides

Code snippets

Server documentation

Learning notes

Development notes

----------------------------------------------------------

8. Documents

Folders

Contracts

Invoices

Assets

Preview

Version history

Categorization

----------------------------------------------------------

10. Finance

Income

Expenses

Subscriptions

Monthly reports

Simple reports

No accounting system.

----------------------------------------------------------

11. Invoice

Invoice generation

PDF Export

Payment status

Client relation

Simple invoice management

----------------------------------------------------------

12. Bookmarks

Documentation

Tools

Videos

Articles

Learning resources

Categorization

Search

----------------------------------------------------------

13. Notifications

Reminder Center

In-app Notifications

Telegram Notifications

Email Notifications

Scheduled reminders

----------------------------------------------------------

14. Activity Log

Track important actions

Search

Filtering

Timeline

----------------------------------------------------------

15. Settings

Profile

Appearance

Theme

Preferences

Notification settings

Backup

Integrations

==========================================================
NON FUNCTIONAL REQUIREMENTS
==========================================================

Define detailed requirements for:

Performance

Security

Accessibility

Scalability

Caching

Logging

Backup

Error Handling

Search Performance

Data Encryption

==========================================================
UX PRINCIPLES
==========================================================

The application should feel similar to:

- Notion
- Linear
- TickTick
- Arc Browser
- Raycast

Focus on:

Minimal Design

Dark Mode First

Responsive Layout

Keyboard Shortcuts

Command Palette

Fast Navigation

Power User Experience

Beautiful Typography

Large White Space

Widgets

Contextual Actions

Quick Search

Focus Mode

Avoid unnecessary clicks.

==========================================================
DELIVERABLES
==========================================================

Generate a complete Product Requirements Document containing:

1. Executive Summary

2. Product Vision

3. Product Goals

4. Problem Statement

5. User Persona

6. User Journey

7. Product Principles

8. Information Architecture

9. Navigation Structure

10. Module Specifications

11. Feature Breakdown

12. Functional Requirements

13. Non Functional Requirements

14. Suggested Database Domains

15. Suggested Laravel Architecture

16. Suggested Frontend Architecture

17. Suggested Folder Structure

18. API Design Principles

19. Queue & Notification Architecture

20. Search Architecture

21. UI/UX Recommendations

22. MVP Scope

23. Version 1 Roadmap

24. Future Roadmap (v2, v3)

25. Future Expansion Ideas

==========================================================
IMPORTANT
==========================================================

Do not create an ERP.

Do not create a CRM.

Do not create a SaaS.

Do not create unnecessary enterprise features.

Every module should be intentionally designed for a single developer who wants one beautiful application to manage daily work.

Prioritize quality over quantity.

The application should feel like a premium productivity application instead of an admin panel.
