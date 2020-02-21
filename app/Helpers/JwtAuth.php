<?php
namespace App\Helpers;

use Firebase\JWT\JWT;
use Illuminate\Support\Facades\DB;
use App\User;

class JwtAuth{
    public $key; 
    
    public function __construct() {
        $this->key='alex.159875321';//clave para decodigficacion del token
    }

    public function signup($email, $password, $getTokken=null){
        //Comprovar si son correctas
        
        $user = User::where([
            'email'=>$email,
            'password'=>$password
        ])->first();
        $signup = false;
        if (is_object($user)) {
            $signup = true;
        }
        //Generar token con los datos del usuario
        if ($signup) {
            $token = array(
                'sub' => $user->id,//id del usuario
                'email' => $user->email,
                'name'=>$user->name,
                'surname'=>$user->surname,
                'iat'=>time(),//momento de creacion 
                'exp'=>time()+(7*24*60*60) //tiempo de duracion
            );
            
            $jwt = JWT::encode($token, $this->key,'HS256');
            $decoded = JWT::decode($jwt, $this->key,['HS256']);
            
            //devolver los datos de codificados o el token 
            if (is_null($getTokken)) {
                $data= $jwt;
            } else{
                $data=  $decoded;
            }
        }else{
            $data = array(
                'status'=>'error',
                'message'=>'Login incorreto'
            );
        }
        
        
        return $data;
    }
    public function checkToken ($jwt,$getIdentity = false){
        $auth = false;
        
        try{
            $jwt= str_replace('"', '', $jwt);
            $decoded = JWT::decode($jwt, $this->key, ["HS256"]);
        } catch (\UnexpectedValueException $e){
            $auth = false;
        } catch (\DomainException $e){
            $auth= false;
        }
        if (!empty($decoded) && is_object($decoded) && isset($decoded->sub)) {
            $auth = true;
        } else{
            $auth = false;
        }
        if ($getIdentity) {
            return $decoded;
        }
        return $auth;
    }
    
    
    
}
?>

