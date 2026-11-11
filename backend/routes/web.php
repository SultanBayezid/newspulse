<?php

use App\Http\Controllers\FeedController;
use Illuminate\Support\Facades\Route;

Route::get('/feed', [FeedController::class, 'rss'])->name('feed');
Route::get('/sitemap.xml', [FeedController::class, 'sitemap'])->name('sitemap');
