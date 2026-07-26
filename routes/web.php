<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\BookmarkCategoryController;
use App\Http\Controllers\BookmarkController;
use App\Http\Controllers\CalendarEventController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DailyWelcomeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocsController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DocumentFolderController;
use App\Http\Controllers\FinanceAccountController;
use App\Http\Controllers\FinanceAssetController;
use App\Http\Controllers\FinanceBillController;
use App\Http\Controllers\FinanceBudgetController;
use App\Http\Controllers\FinanceCategoryController;
use App\Http\Controllers\FinanceDashboardController;
use App\Http\Controllers\FinanceGoalController;
use App\Http\Controllers\FinanceInvestmentController;
use App\Http\Controllers\FinanceLiabilityController;
use App\Http\Controllers\FinanceReportController;
use App\Http\Controllers\FinanceSavingController;
use App\Http\Controllers\FinanceTransactionController;
use App\Http\Controllers\FolderController;
use App\Http\Controllers\HabitController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\InvoicePdfController;
use App\Http\Controllers\KbArticleController;
use App\Http\Controllers\KbCategoryController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectMilestoneController;
use App\Http\Controllers\TaskChecklistController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskTimeLogController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/app/dashboard')->name('home');
Route::get('docs', [DocsController::class, 'index'])->name('docs.index');

// Custom Media / Image routing
Route::middleware(['auth', 'single_user'])->group(function () {
    Route::get('media/image/{path}', [MediaController::class, 'show'])->where('path', '.*')->name('media.image');
    Route::get('media/{path}', [MediaController::class, 'show'])->where('path', '.*')->name('media.show');
});

Route::middleware(['auth', 'verified', 'single_user'])->prefix('app')->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('docs', [DocsController::class, 'index'])->name('app.docs.index');

    // Projects Module
    Route::middleware(['module.enabled:projects'])->group(function () {
        Route::resource('projects', ProjectController::class);
    });

    // Clients Module
    Route::middleware(['module.enabled:clients'])->group(function () {
        Route::resource('clients', ClientController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::post('projects/{project}/milestones', [ProjectMilestoneController::class, 'store'])->name('projects.milestones.store');
        Route::patch('projects/milestones/{milestone}', [ProjectMilestoneController::class, 'update'])->name('projects.milestones.update');
        Route::delete('projects/milestones/{milestone}', [ProjectMilestoneController::class, 'destroy'])->name('projects.milestones.destroy');
    });

    // Tasks Module
    Route::middleware(['module.enabled:tasks'])->group(function () {
        Route::get('tasks', [TaskController::class, 'index'])->name('tasks.index');
        Route::post('tasks', [TaskController::class, 'store'])->name('tasks.store');
        Route::patch('tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
        Route::delete('tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');

        Route::post('tasks/{task}/checklists', [TaskChecklistController::class, 'store'])->name('tasks.checklists.store');
        Route::patch('tasks/checklists/{checklist}', [TaskChecklistController::class, 'update'])->name('tasks.checklists.update');
        Route::delete('tasks/checklists/{checklist}', [TaskChecklistController::class, 'destroy'])->name('tasks.checklists.destroy');

        Route::post('tasks/{task}/time-logs', [TaskTimeLogController::class, 'store'])->name('tasks.time-logs.store');
        Route::delete('time-logs/{timeLog}', [TaskTimeLogController::class, 'destroy'])->name('tasks.time-logs.destroy');
    });

    // Notes Module
    Route::middleware(['module.enabled:notes'])->group(function () {
        Route::get('notes', [NoteController::class, 'index'])->name('notes.index');
        Route::post('notes', [NoteController::class, 'store'])->name('notes.store');
        Route::patch('notes/{note}', [NoteController::class, 'update'])->name('notes.update');
        Route::delete('notes/{note}', [NoteController::class, 'destroy'])->name('notes.destroy');
        Route::post('notes/upload-image', [NoteController::class, 'uploadImage'])->name('notes.images.upload');
        Route::get('notes/images/{filename}', [NoteController::class, 'showImage'])->name('notes.images.show');

        Route::post('folders', [FolderController::class, 'store'])->name('folders.store');
        Route::patch('folders/{folder}', [FolderController::class, 'update'])->name('folders.update');
        Route::delete('folders/{folder}', [FolderController::class, 'destroy'])->name('folders.destroy');
    });

    // Knowledge Base Module
    Route::middleware(['module.enabled:kb'])->group(function () {
        Route::get('kb', [KbArticleController::class, 'index'])->name('kb.index');
        Route::post('kb/articles', [KbArticleController::class, 'store'])->name('kb.articles.store');
        Route::patch('kb/articles/{article}', [KbArticleController::class, 'update'])->name('kb.articles.update');
        Route::delete('kb/articles/{article}', [KbArticleController::class, 'destroy'])->name('kb.articles.destroy');
        Route::post('kb/categories', [KbCategoryController::class, 'store'])->name('kb.categories.store');
        Route::patch('kb/categories/{category}', [KbCategoryController::class, 'update'])->name('kb.categories.update');
        Route::delete('kb/categories/{category}', [KbCategoryController::class, 'destroy'])->name('kb.categories.destroy');
    });

    // Bookmarks Module
    Route::middleware(['module.enabled:bookmarks'])->group(function () {
        Route::resource('bookmarks', BookmarkController::class)->except(['create', 'edit', 'show']);
        Route::patch('bookmarks/{bookmark}/favorite', [BookmarkController::class, 'toggleFavorite'])->name('bookmarks.favorite');
        Route::resource('bookmark-categories', BookmarkCategoryController::class)->only(['store', 'update', 'destroy']);
    });

    // Calendar Module
    Route::middleware(['module.enabled:calendar'])->group(function () {
        Route::get('calendar', [CalendarEventController::class, 'index'])->name('calendar.index');
        Route::post('calendar/events', [CalendarEventController::class, 'store'])->name('calendar.events.store');
        Route::patch('calendar/events/{event}', [CalendarEventController::class, 'update'])->name('calendar.events.update');
        Route::delete('calendar/events/{event}', [CalendarEventController::class, 'destroy'])->name('calendar.events.destroy');
    });

    // Finance Module
    Route::middleware(['module.enabled:finance'])->group(function () {
        Route::get('finance', [FinanceDashboardController::class, 'index'])->name('finance.index');
        Route::resource('finance/accounts', FinanceAccountController::class)->except(['create', 'edit', 'show']);
        Route::resource('finance/transactions', FinanceTransactionController::class)->except(['create', 'edit', 'show']);
        Route::resource('finance/categories', FinanceCategoryController::class)->except(['create', 'edit', 'show']);
        Route::resource('finance/budgets', FinanceBudgetController::class)->except(['create', 'edit', 'show']);
        Route::resource('finance/savings', FinanceSavingController::class)->except(['create', 'edit', 'show']);
        Route::resource('finance/goals', FinanceGoalController::class)->except(['create', 'edit', 'show']);
        Route::resource('finance/investments', FinanceInvestmentController::class)->except(['create', 'edit', 'show']);
        Route::resource('finance/assets', FinanceAssetController::class)->except(['create', 'edit', 'show']);
        Route::resource('finance/liabilities', FinanceLiabilityController::class)->except(['create', 'edit', 'show']);
        Route::resource('finance/bills', FinanceBillController::class)->except(['create', 'edit', 'show']);
        Route::post('finance/bills/{bill}/pay', [FinanceBillController::class, 'pay'])->name('bills.pay');
        Route::get('finance/bills/{bill}/history', [FinanceBillController::class, 'history'])->name('bills.history');
        Route::get('finance/reports', [FinanceReportController::class, 'index'])->name('finance.reports.index');
    });

    // Invoices Module
    Route::middleware(['module.enabled:invoices'])->group(function () {
        Route::resource('invoices', InvoiceController::class);
        Route::post('invoices/{invoice}/pay', [InvoiceController::class, 'pay'])->name('invoices.pay');
        Route::get('invoices/{invoice}/pdf', [InvoicePdfController::class, 'download'])->name('invoices.pdf');
    });

    // Documents Module
    Route::middleware(['module.enabled:documents'])->group(function () {
        Route::post('document-folders', [DocumentFolderController::class, 'store'])->name('document-folders.store');
        Route::patch('document-folders/{folder}', [DocumentFolderController::class, 'update'])->name('document-folders.update');
        Route::delete('document-folders/{folder}', [DocumentFolderController::class, 'destroy'])->name('document-folders.destroy');

        Route::get('documents', [DocumentController::class, 'index'])->name('documents.index');
        Route::post('documents', [DocumentController::class, 'store'])->name('documents.store');
        Route::patch('documents/{document}', [DocumentController::class, 'update'])->name('documents.update');
        Route::delete('documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');
        Route::post('documents/{document}/versions', [DocumentController::class, 'storeVersion'])->name('documents.versions.store');
        Route::get('documents/download/{version}', [DocumentController::class, 'download'])->name('documents.download');
        Route::get('documents/preview/{version}', [DocumentController::class, 'preview'])->name('documents.preview');
    });

    // Activity & Notifications (Always accessible)
    Route::get('activity', [ActivityController::class, 'index'])->name('activity.index');
    Route::post('notifications/mark-all-read', [ActivityController::class, 'markAllRead'])->name('notifications.mark-all-read');
    Route::get('notifications/poll', [ActivityController::class, 'notifications'])->name('notifications.poll');

    // Daily Welcome Modal Summary & Quotes
    Route::get('daily-welcome', [DailyWelcomeController::class, 'index'])->name('daily-welcome.index');
    Route::get('daily-welcome/quote', [DailyWelcomeController::class, 'randomQuote'])->name('daily-welcome.quote');

    // Habits Module
    Route::middleware(['module.enabled:habits'])->group(function () {
        Route::resource('habits', HabitController::class)->except(['create', 'edit', 'show']);
        Route::post('habits/{habit}/toggle', [HabitController::class, 'toggle'])->name('habits.toggle');
        Route::post('habits/{habit}/archive', [HabitController::class, 'toggleArchive'])->name('habits.archive');
    });
});

require __DIR__.'/settings.php';
