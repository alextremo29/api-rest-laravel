<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\User;
class UserController extends Controller
{
    public function pruebas(Request $request){
        return "Accion de pruebas de UserController";
    }
    
    public function register(Request $request){
        //recoger los datos del usuario por post
        $json = $request->input('json', null);
        $params = json_decode($json); //objeto de tipo json
        $params_array = json_decode($json,true); //array
        
        if (!empty($params)&&!empty($params_array)) {
            //limpiar datos
            $params_array = array_map('trim', $params_array);
            
            // validar datos 
            $validate = \Validator::make($params_array,[
                'name'=>'required|alpha',
                'surname'=>'required|alpha',
                'email'=>'required|email|unique:users',//unique valida si esxite el usuario
                'password'=>'required',
            ]);
            if ($validate->fails()) {
                $data = array(
                    'status'=>'error',
                    'code'=>404,
                    'message' =>'El usuario no se ha creado',
                    'errors' => $validate->errors()
                );
            } else{
                // cifrar contraseña
                // $pwd = password_hash($params->password,PASSWORD_BCRYPT,['cost'=>4]); //genera una contraseña diferente
                $pwd = hash('sha256', $params->password);

                //crear usuario
                $user = new User();
                $user->name = $params_array["name"];
                $user->surname = $params_array["surname"];
                $user->email = $params_array["email"];
                $user->password = $pwd;
                $user->role = 'ROLE_USER';
                
                //Guardar Usuario
                $user->save();
                $data = array(
                    'status'=>'success',
                    'code'=>200,
                    'message' =>'El usuario se ha creado'
                );
            }

            
        } else{
            $data = array(
                'status'=>'error',
                'code'=>400,
                'message' =>'Los datos enviados no son correctos',
            );
        }
        
        
        
        return response()->json($data,$data['code']);
    }
    
    public function login(Request $request){
        $jwtAuth = new \JwtAuth();
        //recibir datos por POST
        $json = $request->input('json', null);
        $params = json_decode($json);
        $params_array = json_decode($json,true);
        
        //validar estos datos
        $validate = \Validator::make($params_array,[
                'email'=>'required|email',//unique valida si exite el usuario
                'password'=>'required',
            ]);
        if ($validate->fails()) {
            $singup = array(
                'status'=>'error',
                'code'=>404,
                'message' =>'El usuario no se ha logeado',
                'errors' => $validate->errors()
            );
        } else{
            //crifrar contraseña
            $pwd = hash('sha256', $params->password);
            
            //devilver token o datos
            $singup = $jwtAuth->signup($params->email, $pwd);
            if (!empty($params->gettoken)) {
                $singup = $jwtAuth->signup($params->email, $pwd,true);
            }
        }
        return response()->json($singup,200);
    }
    public function update(Request $request) {
        
        // comprobar si el usuario esta identificado
        $token = $request->header('Authorization') ;
        $jwtAuth = new \JwtAuth();
        $checkToken = $jwtAuth->checkToken($token);
        
        //recoger los datos por post
        $json = $request->input('json', null);
        $params_array = json_decode($json,true);
        
        if ($checkToken && !empty($params_array)) {
            //sacar usuario identificado 
            $user= $jwtAuth->checkToken($token,true);
            //validar los datos
            $validate  = \Validator::make($params_array,[
               'name'=>'required|alpha',
                'surname'=>'required|alpha',
                'email'=>'required|email|unique:users,'.$user->sub//unique valida si exite el usuario
            ]);
            
            //quitar los campos que no se actualizan
            unset($params_array["id"]);
            unset($params_array["role"]);
            unset($params_array["password"]);
            unset($params_array["created_at"]);
            unset($params_array["remember_token"]);
            
            //Actualizar el usuario en la bd
            $user_update = User::where('id',$user->sub)->update($params_array); 
            //devolver respuesta
            $data = array(
                'code' => 200,
                'status' => 'success',
                'message'=>$user,
                'changes' => $params_array
            );
        } else{
            //
            $data = array(
                'code' => 400,
                'status' => 'error',
                'message'=>'El usuario no esta identificado'
            );
        }
        return response()->json($data, $data["code"]);
    }
    //metodo para actualizar la imagen del avatar
    public function upload(Request $request){
        //recoger los datos de la peticion 
        
        //Validaion de imagen
        $validate = \Validator::make($request->all(),[
            'file0' =>'required|image|mimes:jpg,jpeg,png,gif'
        ]);
        //usamos este nombre ya que desde el front lo enviaremos de esta forma
        $image = $request->file('file0');
        
        //Guardar la imagen
        if (!$image || $validate->fails()) {
            $data =  array(
                'code' => 400,
                'status' => 'error',
                'message'=>'Error al subir imagen'
            );
        } else{
            $image_name = time().$image->getClientOriginalName();
            
            //Tener en cuenta crear el disco (carpeta) donde se alamcena la imagen, 
            //esta se crea en la ruta storage->app.
            //Tambien se debe agregar el disco en el archivo config->filesystem 
            //(usar de referencia el public).
            
            \Storage::disk('users')->put($image_name,\File::get($image));
            $data = array(
                'code' => 200,
                'status' => 'success',
                'image'=>$image_name
            );
        }
        return response()->json($data, $data["code"]);
    }
    public function getImage($filename){
        $isset = \Storage::disk('users')->exists($filename);
        if ($isset) {
            $file = \Storage::disk('users')->get($filename);
            return new Response($file,200);
        } else{
            $data = array(
                'code' => 404,
                'status' => 'error',
                'message'=>'La imagen no existe'
            );
            return response()->json($data, $data["code"]);
        }
        
    }
    public function detail($id){
        $user = User::find($id);
        
        if (is_object($user)) {
            $data= array(
                'code'=>200,
                'status' =>'success',
                'user' => $user
            );
        } else{
            $data= array(
                'code'=>404,
                'status' =>'error',
                'messaje' => 'El usuario no existe'
            );
        }
        return response()->json($data,$data["code"]);
    }
}
