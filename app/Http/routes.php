<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::group(['middleware' => 'web'], function () {


    Route::get('/','GuestController@index');
    Route::auth();
    Route::get('auth/verify/{token}', 'Auth\AuthController@verify');
    Route::get('auth/send-verification', 'Auth\AuthController@sendVerification');

    Route::get('/home', 'HomeController@index');
    Route::get('settings/profile','SettingController@profile');
    Route::get('settings/profile/edit', 'SettingController@editProfile');
    Route::post('settings/profile/edit', 'SettingController@updateProfile');
    Route::get('settings/password', 'SettingController@editPassword');
    Route::post('settings/password', 'SettingController@updatePassword');

    Route::get('books/{book}/borrow',[
        'middleware'=>['auth','role:member'],
        'as'=>'books.borrow',//penamaan route
        'uses'=>'BooksController@borrow']);//Ke method borrow di Bookscontroller

    Route::put('books/{book}/return', [
        'middleware'=>['auth', 'role:member'],
        'as'=>'books.return',//Penamaan route
        'uses'=>'BooksController@returnBack']);//ke method returnback di BooksController



    //Group untuk role admin
    Route::group(['prefix'=>'admin', 'middleware'=>['auth','role:admin']], function ()
    {
        Route::resource('authors', 'AuthorsController');
        Route::resource('books', 'BooksController');//All isi controller
        Route::resource('members','MembersController',[
                'only'=>['index','show','destroy']]);//hanya index, show, dan destroy yang punya role admin
        Route::get('statistics', [
            'as'=>'admin.statistics.index',
            'uses'=>'StatisticsController@index']);

        Route::get('export/books', [
            'as'=>'admin.export.books',
            'uses'=>'BooksController@export']);
        Route::post('export/books', [
            'as'=>'admin.export.books.post',
            'uses'=>'BooksController@exportPost']);
        //Route untuk template
        Route::get('template/books', [
            'as'=>'admin.template.books',
            'uses'=>'BooksController@generateExcelTemplate']);
        //Route untuk import
        Route::post('import/books', [
            'as'=>'admin.import.books',
            'uses'=>'BooksController@importExcel']);

    });
    

});

