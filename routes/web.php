<?php

use App\Http\Controllers\Admin\AdminCategoryController;
use App\Http\Controllers\Admin\AdminCategoryDimensionController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminIdeaController;
use App\Http\Controllers\Admin\AdminIdeaPublicationController;
use App\Http\Controllers\Admin\AdminRegionalController;
use App\Http\Controllers\Admin\AdminTagController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ForcePasswordChangeController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\IdeaController;
use App\Http\Controllers\IdeaHierarchyController;
use App\Http\Controllers\IdeaPublicationController;
use App\Http\Controllers\IdeaRelationController;
use App\Http\Controllers\MyIdeasController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RankingController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\TwoFactorController;
use Illuminate\Support\Facades\Route;

// ==========================================
// Authentication & 2FA Public Routes
// ==========================================
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// 2FA Challenge during Login
Route::get('/login/2fa', [TwoFactorController::class, 'showChallenge'])->name('2fa.challenge');
Route::post('/login/2fa', [TwoFactorController::class, 'verifyChallenge'])->name('2fa.verify')->middleware('throttle:6,1');
Route::post('/login/2fa/reenviar', [TwoFactorController::class, 'resendEmailCode'])->name('2fa.resend')->middleware('throttle:3,1');

// Onboarding Invitation Public Activation Flow
Route::get('/onboarding/activar/{token}', [OnboardingController::class, 'show'])->name('onboarding.accept');
Route::post('/onboarding/activar/{token}', [OnboardingController::class, 'activate'])->name('onboarding.activate');

// ==========================================
// Authenticated Platform Routes
// ==========================================
// Public application entry point: redirect guests to login and
// authenticated users to their ideas dashboard.
Route::get('/', [HomeController::class, 'root'])->name('home');

Route::middleware('auth')->group(function () {

    // Mandatory Password Change for Temporary Passwords
    Route::get('/cambiar-password-obligatorio', [ForcePasswordChangeController::class, 'show'])->name('password.force-change');
    Route::post('/cambiar-password-obligatorio', [ForcePasswordChangeController::class, 'update'])->name('password.force-update');

    // Global Search API
    Route::get('/api/search', [SearchController::class, 'globalSearch'])->name('api.search');

    // Core Platform Routes
    // Root landing module: Mis Ideas
    Route::get('/mis-ideas', [MyIdeasController::class, 'index'])->name('my-ideas.index');

    // Community / Innovation Feed
    Route::get('/comunidad', [HomeController::class, 'index'])->name('community');

    // Explore Ideas & Ranking
    Route::get('/ideas', [IdeaController::class, 'index'])->name('ideas.index');
    Route::get('/ranking', [RankingController::class, 'index'])->name('ranking.index');
    Route::get('/ideas/{slug}', [IdeaController::class, 'show'])->name('ideas.show');
    Route::get('/perfil/{user?}', [ProfileController::class, 'show'])->name('profile.show');

    // Idea Creation & Management
    Route::get('/nueva-idea', [IdeaController::class, 'create'])->name('ideas.create');
    Route::post('/ideas', [IdeaController::class, 'store'])->name('ideas.store');
    Route::get('/ideas/{idea}/editar', [IdeaController::class, 'edit'])->name('ideas.edit');
    Route::put('/ideas/{idea}', [IdeaController::class, 'update'])->name('ideas.update');
    Route::delete('/ideas/{idea}', [IdeaController::class, 'destroy'])->name('ideas.destroy');
    Route::put('/ideas/{idea}/jerarquia', [IdeaHierarchyController::class, 'update'])
        ->name('ideas.hierarchy.update')
        ->middleware('throttle:30,1');
    Route::post('/ideas/{idea}/relaciones', [IdeaRelationController::class, 'store'])
        ->name('ideas.relations.store')
        ->middleware('throttle:30,1');
    Route::put('/relaciones/{ideaRelation}', [IdeaRelationController::class, 'update'])
        ->name('ideas.relations.update')
        ->middleware('throttle:30,1');
    Route::delete('/relaciones/{ideaRelation}', [IdeaRelationController::class, 'destroy'])
        ->name('ideas.relations.destroy')
        ->middleware('throttle:30,1');
    Route::post('/ideas/{idea}/publicacion/solicitar', [IdeaPublicationController::class, 'store'])
        ->name('ideas.publication.request')
        ->middleware('throttle:5,1');
    Route::delete('/ideas/{idea}/publicacion/solicitud', [IdeaPublicationController::class, 'destroy'])
        ->name('ideas.publication.cancel')
        ->middleware('throttle:5,1');

    // Voting & Favorites
    Route::post('/ideas/{idea}/votar', [IdeaController::class, 'vote'])->name('ideas.vote')->middleware('throttle:30,1');
    Route::post('/ideas/{idea}/favorito', [IdeaController::class, 'toggleFavorite'])->name('ideas.favorite');

    // Comments & Replies
    Route::post('/ideas/{idea}/comentarios', [CommentController::class, 'store'])->name('comments.store')->middleware('throttle:15,1');
    Route::post('/comentarios/{comment}/like', [CommentController::class, 'toggleLike'])->name('comments.like');
    Route::delete('/comentarios/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');

    // Profile Settings & 2FA Management
    Route::get('/mi-perfil/editar', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/mi-perfil', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/mi-perfil/seguridad', [TwoFactorController::class, 'showSecurity'])->name('profile.security');
    Route::put('/mi-perfil/seguridad/password', [TwoFactorController::class, 'updatePassword'])->name('profile.security.password');
    Route::post('/mi-perfil/seguridad/totp', [TwoFactorController::class, 'enableTotp'])->name('profile.security.totp');
    Route::post('/mi-perfil/seguridad/email/solicitar', [TwoFactorController::class, 'enableEmail'])->name('profile.security.email.request');
    Route::post('/mi-perfil/seguridad/email/confirmar', [TwoFactorController::class, 'confirmEmail'])->name('profile.security.email.confirm');
    Route::post('/mi-perfil/seguridad/desactivar', [TwoFactorController::class, 'disable'])->name('profile.security.disable');

    // Notifications Center
    Route::get('/notificaciones', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notificaciones/{id}/leer', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notificaciones/leer-todas', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');

    // ==========================================
    // Administration Panel (Admin Only)
    // ==========================================
    Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        // Ideas Administration
        Route::get('/ideas', [AdminIdeaController::class, 'index'])->name('ideas.index');
        Route::get('/ideas/{idea}', [AdminIdeaController::class, 'show'])->name('ideas.show');
        Route::put('/ideas/{idea}', [AdminIdeaController::class, 'update'])->name('ideas.update');
        Route::put('/ideas/{idea}/publicacion', [AdminIdeaPublicationController::class, 'update'])
            ->name('ideas.publication.update')
            ->middleware('throttle:30,1');
        Route::post('/ideas/{idea}/destacar', [AdminIdeaController::class, 'toggleFeatured'])->name('ideas.feature');
        Route::post('/ideas/acciones-masivas', [AdminIdeaController::class, 'batchAction'])->name('ideas.batch');

        // Regionals Administration
        Route::get('/regionales', [AdminRegionalController::class, 'index'])->name('regionals.index');
        Route::post('/regionales', [AdminRegionalController::class, 'store'])->name('regionals.store');
        Route::put('/regionales/{regional}', [AdminRegionalController::class, 'update'])->name('regionals.update');
        Route::post('/regionales/{regional}/estado', [AdminRegionalController::class, 'toggleStatus'])->name('regionals.status');
        Route::delete('/regionales/{regional}', [AdminRegionalController::class, 'destroy'])->name('regionals.destroy');

        // Categories Administration
        Route::get('/categorias', [AdminCategoryController::class, 'index'])->name('categories.index');
        Route::post('/dimensiones-categoria', [AdminCategoryDimensionController::class, 'store'])->name('category-dimensions.store');
        Route::put('/dimensiones-categoria/{categoryDimension}', [AdminCategoryDimensionController::class, 'update'])->name('category-dimensions.update');
        Route::delete('/dimensiones-categoria/{categoryDimension}', [AdminCategoryDimensionController::class, 'destroy'])->name('category-dimensions.destroy');
        Route::post('/categorias', [AdminCategoryController::class, 'store'])->name('categories.store');
        Route::put('/categorias/{category}', [AdminCategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categorias/{category}', [AdminCategoryController::class, 'destroy'])->name('categories.destroy');

        // Tags Administration
        Route::get('/etiquetas', [AdminTagController::class, 'index'])->name('tags.index');
        Route::post('/etiquetas', [AdminTagController::class, 'store'])->name('tags.store');
        Route::put('/etiquetas/{tag}', [AdminTagController::class, 'update'])->name('tags.update');
        Route::delete('/etiquetas/{tag}', [AdminTagController::class, 'destroy'])->name('tags.destroy');
        Route::post('/etiquetas/fusionar', [AdminTagController::class, 'merge'])->name('tags.merge');

        // Users & Invitations Administration
        Route::get('/usuarios', [AdminUserController::class, 'index'])->name('users.index');
        Route::post('/usuarios', [AdminUserController::class, 'store'])->name('users.store');
        Route::put('/usuarios/{user}', [AdminUserController::class, 'update'])->name('users.update');
        Route::delete('/usuarios/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
        Route::put('/usuarios/{user}/rol', [AdminUserController::class, 'updateRole'])->name('users.role');
        Route::post('/usuarios/{user}/estado', [AdminUserController::class, 'toggleStatus'])->name('users.status');
        Route::post('/usuarios/invitaciones/{invitation}/reenviar', [AdminUserController::class, 'resendInvitation'])->name('users.invitations.resend');
        Route::delete('/usuarios/invitaciones/{invitation}', [AdminUserController::class, 'cancelInvitation'])->name('users.invitations.cancel');
    });
});
