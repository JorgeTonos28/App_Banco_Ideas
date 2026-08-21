<?php

use App\Http\Controllers\Admin\AdminCategoryController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminIdeaController;
use App\Http\Controllers\Admin\AdminTagController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\IdeaController;
use App\Http\Controllers\MyIdeasController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RankingController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Global Search API
Route::get('/api/search', [SearchController::class, 'globalSearch'])->name('api.search');

// Public & Authenticated Core Routes
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/ideas', [IdeaController::class, 'index'])->name('ideas.index');
Route::get('/ranking', [RankingController::class, 'index'])->name('ranking.index');
Route::get('/ideas/{slug}', [IdeaController::class, 'show'])->name('ideas.show');
Route::get('/perfil/{user?}', [ProfileController::class, 'show'])->name('profile.show');

// Authenticated Routes
Route::middleware('auth')->group(function () {
    // Idea Creation & Management
    Route::get('/nueva-idea', [IdeaController::class, 'create'])->name('ideas.create');
    Route::post('/ideas', [IdeaController::class, 'store'])->name('ideas.store');
    Route::get('/ideas/{idea}/editar', [IdeaController::class, 'edit'])->name('ideas.edit');
    Route::put('/ideas/{idea}', [IdeaController::class, 'update'])->name('ideas.update');
    Route::delete('/ideas/{idea}', [IdeaController::class, 'destroy'])->name('ideas.destroy');

    // Voting & Favorites
    Route::post('/ideas/{idea}/votar', [IdeaController::class, 'vote'])->name('ideas.vote')->middleware('throttle:30,1');
    Route::post('/ideas/{idea}/favorito', [IdeaController::class, 'toggleFavorite'])->name('ideas.favorite');

    // Comments & Replies
    Route::post('/ideas/{idea}/comentarios', [CommentController::class, 'store'])->name('comments.store')->middleware('throttle:15,1');
    Route::post('/comentarios/{comment}/like', [CommentController::class, 'toggleLike'])->name('comments.like');
    Route::delete('/comentarios/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');

    // Personal Ideas
    Route::get('/mis-ideas', [MyIdeasController::class, 'index'])->name('my-ideas.index');

    // Profile Settings
    Route::get('/mi-perfil/editar', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/mi-perfil', [ProfileController::class, 'update'])->name('profile.update');

    // Notifications Center
    Route::get('/notificaciones', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notificaciones/{id}/leer', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notificaciones/leer-todas', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');

    // Administration Panel
    Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        // Ideas Administration
        Route::get('/ideas', [AdminIdeaController::class, 'index'])->name('ideas.index');
        Route::get('/ideas/{idea}', [AdminIdeaController::class, 'show'])->name('ideas.show');
        Route::put('/ideas/{idea}', [AdminIdeaController::class, 'update'])->name('ideas.update');
        Route::post('/ideas/{idea}/destacar', [AdminIdeaController::class, 'toggleFeatured'])->name('ideas.feature');
        Route::post('/ideas/acciones-masivas', [AdminIdeaController::class, 'batchAction'])->name('ideas.batch');

        // Categories Administration
        Route::get('/categorias', [AdminCategoryController::class, 'index'])->name('categories.index');
        Route::post('/categorias', [AdminCategoryController::class, 'store'])->name('categories.store');
        Route::put('/categorias/{category}', [AdminCategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categorias/{category}', [AdminCategoryController::class, 'destroy'])->name('categories.destroy');

        // Tags Administration
        Route::get('/etiquetas', [AdminTagController::class, 'index'])->name('tags.index');
        Route::post('/etiquetas', [AdminTagController::class, 'store'])->name('tags.store');
        Route::put('/etiquetas/{tag}', [AdminTagController::class, 'update'])->name('tags.update');
        Route::delete('/etiquetas/{tag}', [AdminTagController::class, 'destroy'])->name('tags.destroy');
        Route::post('/etiquetas/fusionar', [AdminTagController::class, 'merge'])->name('tags.merge');

        // Users Administration
        Route::get('/usuarios', [AdminUserController::class, 'index'])->name('users.index');
        Route::put('/usuarios/{user}/rol', [AdminUserController::class, 'updateRole'])->name('users.role');
        Route::post('/usuarios/{user}/estado', [AdminUserController::class, 'toggleStatus'])->name('users.status');
    });
});
