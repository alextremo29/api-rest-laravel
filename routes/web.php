<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
// Cargando clases
use App\Http\Middleware\ApiAuthMiddleware;
Route::get('/', function () {
    return view('welcome');
});

//Rutas de prueba
//
// Route::get('/pruebas/{nombre?}', function ($nombre=null){
//    $texto = '<h2>Texto desde una ruta</h2>';
//    $texto.=" Nombre:" .$nombre;
//    return view('pruebas',array(
//        'texto'=>$texto
//    ));
// });

Route::get('/pruebas','PruebasController@index');
Route::get('/testOrm','PruebasController@testOrm');

//Rutas del API
//  Metodos HTTP Comunes
//  GET: coseguir datos o recursos
//  POST: guardar datos o recursos o hacer logica desde un formulario
//  PUT: actualizar recursos o datos
//  DELETE: Eliminar datos o recursos


    //Rutas pruebas
//    Route::get('/usuario/pruebas','UserController@pruebas');
//    Route::get('/categoria/pruebas','CategoryController@pruebas');
//    Route::get('/post/pruebas','PostController@pruebas');
    
    //Rutas del controlador usuario
    Route::post('/api/register','UserController@register');
    Route::post('/api/login','UserController@login');
    Route::put('/api/user/update','UserController@update');
    //Agregamos un middleware de autenticacion de usuario esto evita repetir codigo en el controlador
    Route::post('/api/user/upload','UserController@upload')->middleware(ApiAuthMiddleware::class);
    //ruta que para optener la imagen del avatar y recibe un parametro
    Route::get('/api/user/avatar/{filename}','UserController@getImage');
    Route::get('/api/user/detail/{id}','UserController@detail');
    
    
    //Rutas de categorias Usamos rutas de tipo resourse (crea todas las rutas 
    //necesarias consultar en la consola php artisan route:list)
    Route::resource('/api/category','CategoryController');

    //Rutas de post Usamos rutas de tipo resourse (crea todas las rutas 
    //necesarias consultar en la consola php artisan route:list)
    Route::resource('/api/post','PostController');