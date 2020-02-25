<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Post;
use App\Helpers\JwtAuth;

class PostController extends Controller
{
    public function __construct()
    {
        $this->middleware('api.auth',['except'=>[
        	'index',
        	'show',
        	'getImage',
        	"getPostsByCategory",
        	"getPostsByUser"
        ]]);
    }
    public function index()
    {
    	$posts = Post::all()->load('category'); // con el metodo load se cargan los datos del metodo category del modelo post, este metodo define la relacion entre tablas 

    	return response()->json([
    		'code' => 200,
    		'status' => 'success',
    		'posts' => $posts
    	],200);
    }
    public function show($id)
    {
    	$post = Post::find($id)->load('category');
    	if (is_object($post)) {
    		$data = array(
                'code' => 200,
                'status' => 'success',
                'post' =>$post
            );
    	} else{
    		$data = array(
                'code' => 404,
                'status' => 'error',
                'message' =>"La entrada no existe"
            );
    	}
    	return response()->json($data,$data["code"]);
    }
    public function store(Request $request)
    {
    	// recoger datos por post
    	$json = $request->input('json',null);
    	$params = json_decode($json);
    	$params_array = json_decode($json,true);

    	if (!empty($params_array)) {
    		//Conseguir el usuario identificado
    		$user= $this->getIdentity($request);

	    	//Validar los datos
    		$validate = \Validator::make($params_array,[
    			'title'=>'required',
    			'content' => 'required',
    			'category_id' => 'required',
    			'image'=>"required"
    		]);
    		if ($validate->fails()) {
    			$data = array(
	                'code' => 400,
	                'status' => 'error',
	                'message' =>"Faltan datos"
	            );
    		}else{
	    		//Guardar el articulo
	    		$post = new Post();
	    		$post->user_id = $user->sub;
	    		$post->category_id = $params->category_id;
	    		$post->title = $params->title;
	    		$post->content = $params->content;
	    		$post->image = $params->image;

	    		$post->save();
    			$data = array(
	                'code' => 200,
	                'status' => 'success',
	                'post' =>$post
	            );
    		}
    	}else{
    		$data = array(
                'code' => 400,
                'status' => 'error',
                'message' =>"No se ha enviado los datos correctamente"
            );
    	}

    	//devolver la respuesta
    	return response()->json($data, $data["code"]);
    }
    public function update($id, Request $request)
    {
    	//Recoger los datos
    	$json = $request->input('json',null);
    	$params = json_decode($json);
    	$params_array = json_decode($json,true);

    	//Datos para devolver
    	$data = array(
    		'code' => 400, 
    		'status' => 'error',
    		'message' => "Datos enviados incorrectamente"
    	);
    	if (!empty($params_array)) {
    		//Validar los datos
	    	$validate = \Validator::make($params_array,[
				'title'=>'required',
				'content' => 'required',
				'category_id' => 'required'
			]);

			if ($validate->fails()) {
				$data['errors'] = $validate->errors();
				return response()->json($data,$data["code"]);
			}
	    	//Eliminar lo que no queremos actualizar
	    	unset($params_array["id"]);
	    	unset($params_array["user_id"]);
	    	unset($params_array["created_at"]);
	    	unset($params_array["user"]);

	    	//Conseguir el usuario identificado
    		$user= $this->getIdentity($request);

	    	//Buscar el registro
	    	$post = Post::where('id',$id)
	    				->where('user_id',$user->sub)
	    				->first();

	    	if (!empty($post) && is_object($post)) {
	    		
	    		//Actualizo el registro 
	    		$post->update($params_array);
	    		//Devolver algo
		    	$data = array(
		    		'code' => 200, 
		    		'status' => 'success',
		    		'post' => $post,
		    		'changes' => $params_array
		    	);
	    	}
	    	/*$where = [
	    		'id'     =>$id,
	    		'user_id'=>$user->sub
	    	];
	    	$post = Post::updateOrCreate($where, $params_array); // la funcion updateOrCreate ademas de actualizar retorna el objeto completo actualizado si no existe el objeto lo crea*/

	    	
    	}
	    	
    	return response()->json($data,$data["code"]);
    }
    public function destroy($id, Request $request)
    {
    	
    	//Conseguir el usuario identificado
    	$user= $this->getIdentity($request);

    	//Conseguir si existe el registro
    	$post = Post::where('id',$id)
    				->where('user_id',$user->sub)
    				->first();

    	if (!empty($post)) {
			//Borrarlo
	    	$post->delete();
	    	//Devolver algo
	    	$data = array(
	    		'code' => 200, 
	    		'status' => 'success',
	    		'post' => $post
	    	);
    		
    	} else{
    		$data = array(
	    		'code' => 404, 
	    		'status' => 'error',
	    		'message' => "El post no existe"
	    	);
    	}

    	return response()->json($data,$data["code"]);
    }
    private function getIdentity($request)
    {
    	//Conseguir el usuario identificado
		$jwtAuth= new JwtAuth();
		$token = $request->header('Authorization',null);
		$user = $jwtAuth->checkToken($token,true);
		return $user;
    }

    public function upload(Request $request)
    {
    	//Recoger la imagen de la peticion
    	$image = $request->file('file0');

    	//Validar la imagen
    	$validate = \Validator::make($request->all(),[
    		'file0' => 'required|image|mimes:jpg,jpeg,png,gif'
    	]);
    	
    	//Guardar la imagen 
    	if (!$image || $validate->fails()) {
    		$data = array(
	    		'code' => 400, 
	    		'status' => 'error',
	    		'message' => "Error al subir la imagen"
	    	);
    	} else{
    		$image_name = time().$image->getClientOriginalName();

    		\Storage::disk('images')->put($image_name,\File::get($image));

    		$data = array(
	    		'code' => 200, 
	    		'status' => 'success',
	    		'image' => $image_name
	    	);
    	}
    	
    	//Devolver datos
    	return response()->json($data,$data["code"]);
    }

    public function getImage($filename)
    {
    	// Comprobar si existe el fichero
    	$isset = \Storage::disk('images')->exists($filename);
    	if ($isset) {
    		// conseguir la imagen
    		$file = \Storage::disk('images')->get($filename);
	    	
	    	// Devolver la imagen
    		return Response($file, 200);
    	} else{
    		$data = array(
	    		'code' => 404, 
	    		'status' => 'error',
	    		'message' => "La imagen no existe"
	    	);
    	}
    	// Mostrar el posible error
    	return response()->json($data,$data["code"]);
    }

    public function getPostsByCategory($id)
    {
    	$posts = Post::where('category_id',$id)->get();
    	return response()->json([
    		"status"=>"success",
    		"posts"=> $posts
    	],200);
    }

    public function getPostsByUser($id)
    {
    	$posts = Post::where("user_id",$id)->get();
    	return response()->json([
    		'status'=>"success",
    		'posts'=>$posts
    	],200);
    }
}
