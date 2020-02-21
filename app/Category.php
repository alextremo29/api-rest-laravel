<?php
//Eloquent Model Generator Herramienta utilizada para mapear una base de datos https://github.com/krlove/eloquent-model-generator
namespace App;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'categories';
    
    //relacion de uno a muchos
    public function posts(){
        return $this->hasMany('App\Post');
    }
}
