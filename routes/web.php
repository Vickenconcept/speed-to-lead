<?php

use App\Http\Controllers\BusinessProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PublicConversationController;
use App\Http\Controllers\PublicIntakeController;
use App\Http\Controllers\WebsiteHookController;
use App\Http\Controllers\WebsiteSourceController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('onboarding', [OnboardingController::class, 'show'])->name('onboarding.show');
    Route::post('onboarding/website', [OnboardingController::class, 'website'])->name('onboarding.website');
    Route::post('onboarding/google', [OnboardingController::class, 'google'])->name('onboarding.google');
    Route::post('onboarding/manual', [OnboardingController::class, 'manual'])->name('onboarding.manual');
    Route::get('onboarding/review', [OnboardingController::class, 'review'])->name('onboarding.review');
    Route::put('onboarding/review', [OnboardingController::class, 'update'])->name('onboarding.update');
    Route::post('onboarding/confirm', [OnboardingController::class, 'confirm'])->name('onboarding.confirm');

    Route::middleware('business.ready')->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');
        Route::get('leads/{lead}', [LeadController::class, 'show'])->name('leads.show');
        Route::get('business', [BusinessProfileController::class, 'edit'])->name('business.edit');
        Route::put('business', [BusinessProfileController::class, 'update'])->name('business.update');
        Route::post('business/website', [BusinessProfileController::class, 'website'])->name('business.website');
        Route::post('business/google', [BusinessProfileController::class, 'google'])->name('business.google');
        Route::get('sources/website', [WebsiteSourceController::class, 'show'])->name('sources.website');
        Route::post('sources/website/sample', [WebsiteSourceController::class, 'sample'])->name('sources.sample');
    });
});

Route::get('i/{token}', [PublicIntakeController::class, 'show'])->name('intake.show');
Route::post('i/{token}', [PublicIntakeController::class, 'store'])->middleware('throttle:20,1')->name('intake.store');
Route::get('c/{token}', [PublicConversationController::class, 'show'])->name('conversation.show');
Route::post('c/{token}', [PublicConversationController::class, 'store'])->middleware('throttle:30,1')->name('conversation.store');
Route::post('hooks/website/{token}', WebsiteHookController::class)->middleware('throttle:60,1')->name('hooks.website');

require __DIR__.'/settings.php';
