<?php
/** Public installation pages use existing CSRF/session primitives. */
declare(strict_types=1);
namespace Vereinssystem\Controllers;
use Vereinssystem\Core\{Csrf,Request,Response,View};
use Vereinssystem\Install\InstallationService;

final class InstallController extends Controller
{
    public function __construct(View $views,Csrf $csrf,private readonly InstallationService $service){parent::__construct($views,$csrf);}
    public function form(Request $request): Response
    {
        if($this->service->installed())return Response::redirect($request->url('/login'));
        return $this->display($request,'',[],200);
    }
    public function submit(Request $request): Response
    {
        if($this->service->installed())return Response::redirect($request->url('/login'));
        if(!$this->csrf->verify($request->inputString('_csrf')))return $this->badToken($request);
        $keys=['host','port','database','username','db_password','first','last','login','email','birthday','admin_password','confirm_password'];
        $data=[];foreach($keys as $key)$data[$key]=$request->inputString($key);
        try{
            $this->service->install($data);
            return Response::redirect($request->url('/login'));
        }catch(\DomainException $e){
            return $this->display($request,$e->getMessage(),$data,422);
        }
    }
    private function display(Request $request,string $error,array $data,int $code): Response
    {
        unset($data['db_password'],$data['admin_password'],$data['confirm_password']);
        return $this->page($request,'Installation','install/form',[
            'error'=>$error,'values'=>$data,'csrfToken'=>$this->csrf->token(),
            'installUrl'=>$request->url('/install'),
        ],null,$code);
    }
}
