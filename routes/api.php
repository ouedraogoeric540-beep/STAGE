<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AgentController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\EvenementController;
use App\Http\Controllers\ScanQRController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RechercheController;
use Illuminate\Support\Facades\Route;

// Test API
Route::get('/', fn() => response()->json(['message' => 'SecurePass API v2.0 ✅']));

// ─── Routes Publiques ──────────────────────────────────────────
Route::post('/login',    [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'registerParticipant']);
Route::get('/evenements',      [EvenementController::class, 'index']);
Route::get('/evenements/{id}', [EvenementController::class, 'show']);

Route::post('/tickets/reserver',                [TicketController::class, 'reserver']);
Route::post('/tickets/{id}/confirmer-paiement', [TicketController::class, 'confirmerPaiement']);
Route::get('/tickets/{id}/pdf', [TicketController::class, 'telechargerPdf']);


// ─── Routes Protégées (auth + compte non bloqué) ───────────────
Route::middleware(['auth:sanctum', 'compte.bloque'])->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);

    Route::get('/tickets/mes-tickets', [TicketController::class, 'mesTickets']);

    // ─── Profil ────────────────────────────────────────────────
    Route::put('/user/profile',  [ProfileController::class, 'updateProfile']);
    Route::put('/user/password', [ProfileController::class, 'updatePassword']);

    // ─── Recherche globale ─────────────────────────────────────
    Route::get('/recherche', [RechercheController::class, 'search']);

    // ─── Organisateur ──────────────────────────────────────────
    Route::middleware('role:organisateur')->group(function () {
        Route::get('/mes-evenements',          [EvenementController::class, 'mesEvenements']);
        Route::get('/mes-evenements/stats-sexe', [EvenementController::class, 'statsSexe']);
        Route::get('/mes-evenements/historique-revenus', [EvenementController::class, 'historiqueRevenus']);
        Route::post('/evenements',             [EvenementController::class, 'store']);
        Route::put('/evenements/{id}',         [EvenementController::class, 'update']);
        Route::delete('/evenements/{id}',      [EvenementController::class, 'destroy']);
        Route::get('/evenements/{id}/export-participants', [EvenementController::class, 'exportParticipants']);
        
        Route::get('/scans/organisateur-logs', [ScanQRController::class, 'organisateurLogs']);

        Route::get('/agents',                  [AgentController::class, 'index']);
        Route::post('/agents',                 [AgentController::class, 'store']);
        Route::post('/agents/affecter',        [AgentController::class, 'affecter']);
        Route::patch('/agents/{id}/toggle',    [AgentController::class, 'toggle']);
        Route::delete('/agents/{id}',          [AgentController::class, 'destroy']);
    });

    // ─── Agent ─────────────────────────────────────────────────
    Route::middleware('role:agent')->group(function () {
        Route::post('/scan',               [ScanQRController::class, 'scanner']);
        Route::get('/scans/historique',    [ScanQRController::class, 'historique']);
        Route::get('/agent/evenements',    [ScanQRController::class, 'evenementsAgent']);
    });

    // ─── Admin ─────────────────────────────────────────────────
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/statistiques',          [AdminController::class, 'statistiques']);
        Route::get('/users',                 [AdminController::class, 'users']);
        Route::post('/users',                [AdminController::class, 'createUser']);
        Route::patch('/users/{id}/toggle',   [AdminController::class, 'toggleUser']);
        Route::put('/users/{id}',            [AdminController::class, 'updateUser']);
        Route::delete('/users/{id}',         [AdminController::class, 'deleteUser']);
        Route::get('/logs',                  [AdminController::class, 'logs']);
        Route::get('/evenements',            [AdminController::class, 'evenements']);
        Route::post('/evenements',           [AdminController::class, 'createEvenement']);
        Route::put('/evenements/{id}',       [AdminController::class, 'updateEvenement']);
        Route::delete('/evenements/{id}',    [AdminController::class, 'deleteEvenement']);
    });
});