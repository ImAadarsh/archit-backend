<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post("/register",'App\Http\Controllers\AuthenticationController@register');
Route::post("/login",'App\Http\Controllers\AuthenticationController@login');
Route::post("/forgot",'App\Http\Controllers\AuthenticationController@forgot');
Route::post("/updateprofile",'App\Http\Controllers\AuthenticationController@updateprofile');


// Admin API's
Route::post("/insertBusiness",'App\Http\Controllers\Admin@insertBusiness');
Route::post("/insertLocation",'App\Http\Controllers\Admin@insertLocation');


// User App API's

Route::post("/createInvoice",'App\Http\Controllers\Admin@createInvoice');
Route::get("/invoice/delete/{id}",'App\Http\Controllers\Admin@removeInvoice');
Route::post("/editInvoice",'App\Http\Controllers\Admin@editInvoice');


Route::post("/addProduct",'App\Http\Controllers\Admin@addProduct');
Route::post("/editProduct",'App\Http\Controllers\Admin@editProduct');
Route::get("/item/delete/{item_id}",'App\Http\Controllers\Admin@removeItem');


Route::post("/addAddress",'App\Http\Controllers\Admin@addAddress');

Route::post("/addExpense",'App\Http\Controllers\Admin@addExpense');
Route::get("/getAllExpenses",'App\Http\Controllers\Admin@getAllExpenses');
Route::get("/getExpenseById",'App\Http\Controllers\Admin@getExpenseById');
Route::get("/deleteExpense",'App\Http\Controllers\Admin@deleteExpense');

Route::get("/getItemsByInvoiceId",'App\Http\Controllers\Admin@getItemsByInvoiceId');
Route::get("/getAddressByInvoiceId",'App\Http\Controllers\Admin@getAddressByInvoiceId');
Route::get("/getAllInvoices",'App\Http\Controllers\Admin@getAllInvoices');
Route::get("/getDetailedInvoice/{invoiceId}",'App\Http\Controllers\Admin@getDetailedInvoice');
Route::get("/getExistedUser",'App\Http\Controllers\Admin@getExistedUser');
Route::get("/dashboardReport",'App\Http\Controllers\Admin@dashboardReport');





// AFter Report Module
Route::get("/getSaleReport",'App\Http\Controllers\Admin@getSaleReport');
Route::get("/getExpenseReport",'App\Http\Controllers\Admin@getExpenseReport');
Route::get("/getPurchaseSaleInvoice",'App\Http\Controllers\Admin@getPurchaseSaleInvoice');
Route::get("/getInvoiceListReport",'App\Http\Controllers\Admin@getInvoiceListReport');


Route::get("/getSaleReport",'App\Http\Controllers\Admin@getSaleReport');
