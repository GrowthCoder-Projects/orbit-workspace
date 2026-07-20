<?php

namespace App\Providers;

use App\Listeners\LogAuthActivity;
use App\Models\Bookmark;
use App\Models\CalendarEvent;
use App\Models\Client;
use App\Models\Document;
use App\Models\FinanceTransaction;
use App\Models\Invoice;
use App\Models\KbArticle;
use App\Models\Note;
use App\Models\Project;
use App\Models\Task;
use App\Observers\BookmarkObserver;
use App\Observers\CalendarEventObserver;
use App\Observers\ClientObserver;
use App\Observers\DocumentObserver;
use App\Observers\FinanceTransactionObserver;
use App\Observers\InvoiceObserver;
use App\Observers\KbArticleObserver;
use App\Observers\NoteObserver;
use App\Observers\ProjectObserver;
use App\Observers\TaskObserver;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->registerObservers();
        $this->registerEventListeners();
    }

    /**
     * Register all model observers.
     */
    protected function registerObservers(): void
    {
        Project::observe(ProjectObserver::class);
        Task::observe(TaskObserver::class);
        Note::observe(NoteObserver::class);
        Client::observe(ClientObserver::class);
        Invoice::observe(InvoiceObserver::class);
        FinanceTransaction::observe(FinanceTransactionObserver::class);
        Bookmark::observe(BookmarkObserver::class);
        Document::observe(DocumentObserver::class);
        KbArticle::observe(KbArticleObserver::class);
        CalendarEvent::observe(CalendarEventObserver::class);
    }

    /**
     * Register event listeners and subscribers.
     */
    protected function registerEventListeners(): void
    {
        Event::subscribe(LogAuthActivity::class);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
