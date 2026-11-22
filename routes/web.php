<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{AuthController,SiteController,StaffController,AdminController};
Route::get('/',[SiteController::class,'home'])->name('home');
Route::get('/tariffs',[SiteController::class,'tariffs'])->name('tariffs');
Route::middleware('guest')->group(function(){Route::get('/login',[AuthController::class,'showLogin'])->name('login');Route::post('/login',[AuthController::class,'login'])->name('login.store');Route::get('/register',[AuthController::class,'showRegister'])->name('register');Route::post('/register',[AuthController::class,'register'])->name('register.store');});
Route::post('/logout',[AuthController::class,'logout'])->middleware('auth')->name('logout');
Route::middleware('auth')->group(function(){Route::get('/account',[SiteController::class,'account'])->name('account');Route::get('/booking/create',[SiteController::class,'createBooking'])->middleware('role:client')->name('booking.create');Route::post('/booking',[SiteController::class,'storeBooking'])->middleware('role:client')->name('booking.store');});
Route::prefix('staff')->name('staff.')->middleware(['auth','role:staff,admin'])->group(function(){Route::get('/',[StaffController::class,'dashboard'])->name('dashboard');Route::patch('/bookings/{booking}',[StaffController::class,'updateBooking'])->name('booking.update');Route::post('/bookings/{booking}/race',[StaffController::class,'createRace'])->name('race.create');Route::patch('/karts/{kart}',[StaffController::class,'kartStatus'])->name('kart.update');Route::patch('/races/{raceSession}',[StaffController::class,'updateRace'])->name('race.update');Route::post('/races/{raceSession}/laps',[StaffController::class,'storeLapResult'])->name('race.lap.store');});
Route::prefix('admin')->name('admin.')->middleware(['auth','role:admin'])->group(function(){Route::get('/',[AdminController::class,'dashboard'])->name('dashboard');Route::post('/tariffs',[AdminController::class,'storeTariff'])->name('tariff.store');Route::patch('/tariffs/{tariff}/toggle',[AdminController::class,'toggleTariff'])->name('tariff.toggle');
    Route::patch('/bookings/{booking}',[AdminController::class,'updateBooking'])->name('booking.update');
    Route::post('/staff',[AdminController::class,'storeStaff'])->name('staff.store');
    Route::patch('/clients/{user}',[AdminController::class,'updateClient'])->name('client.update');
    Route::patch('/staff/{user}',[AdminController::class,'updateStaff'])->name('staff.update');
    Route::post('/karts',[AdminController::class,'storeKart'])->name('kart.store');
    Route::patch('/karts/{kart}',[AdminController::class,'updateKart'])->name('kart.update');
    Route::post('/maintenance',[AdminController::class,'storeMaintenance'])->name('maintenance.store');
    Route::post('/bookings/{booking}/payments',[AdminController::class,'storePayment'])->name('payment.store');
    Route::patch('/payments/{payment}',[AdminController::class,'updatePayment'])->name('payment.update');});
