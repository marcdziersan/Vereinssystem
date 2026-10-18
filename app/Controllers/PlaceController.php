<?php
/** Places HTTP controller: access checks and CSRF protection precede mutations. */
declare(strict_types=1);
namespace Vereinssystem\Controllers;
use Vereinssystem\Auth\{AuthService,Roles,User};
use Vereinssystem\Core\{Csrf,Request,Response,Session,View};
use Vereinssystem\Places\{PlaceRepository,PlaceService,PlaceValidationException};

final class PlaceController extends Controller
{
    public function __construct(View $views,Csrf $csrf,private readonly ?AuthService $auth,
        private readonly ?PlaceRepository $places,private readonly Roles $roles,private readonly Session $session)
    {parent::__construct($views,$csrf);}

    private function current(): ?User {return $this->auth?->currentUser();}
    private function allowed(User $actor,string $action): bool {return $this->roles->allows($actor,$action);}
    private static function id(string $s): int
    {$v=filter_var($s,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);return is_int($v)?$v:0;}
    private function repository(): PlaceRepository
    {if($this->places===null)throw new \Vereinssystem\Core\DatabaseUnavailable('Places not configured');return $this->places;}
    private function message(): string
    {$msg=(string)$this->session->get('places_flash','');$this->session->forget('places_flash');return $msg;}

    public function index(Request $r): Response
    {
        $user=$this->current();if(!$user)return Response::redirect($r->url('/login'));
        if(!$this->allowed($user,'places.list'))return $this->forbidden($r);
        return $this->page($r,'Orte und Anschriften','places/index',[
            'items'=>$this->repository()->all($user->rights===5 && $r->queryString('inactive')==='1'),
            'url'=>$r->url('/places'),'editUrl'=>$r->url('/places/edit'),
            'createUrl'=>$r->url('/places/create'),'canCreate'=>$this->allowed($user,'places.create'),
            'canEdit'=>$this->allowed($user,'places.edit'),'canStatus'=>$this->allowed($user,'places.status'),
            'csrfToken'=>$this->csrf->token(),'statusUrl'=>$r->url('/places/status'),
            'showInactive'=>$user->rights===5&&$r->queryString('inactive')==='1','flash'=>$this->message(),
        ],$user);
    }
    public function create(Request $r): Response
    {
        $user=$this->current();if(!$user)return Response::redirect($r->url('/login'));
        if(!$this->allowed($user,'places.create'))return $this->forbidden($r);
        return $this->form($r,$user,null,[],[]);
    }
    public function edit(Request $r): Response
    {
        $user=$this->current();if(!$user)return Response::redirect($r->url('/login'));
        if(!$this->allowed($user,'places.edit'))return $this->forbidden($r);
        $place=$this->repository()->find(self::id($r->queryString('id')));
        if(!$place)return $this->forbidden($r);
        return $this->form($r,$user,$place,$place,[]);
    }
    private function form(Request $r,User $user,?array $place,array $values,array $errors,int $status=200): Response
    {
        return $this->page($r,$place?'Ort bearbeiten':'Ort anlegen','places/form',[
            'place'=>$place,'values'=>$values,'errors'=>$errors,
            'saveUrl'=>$r->url($place?'/places/update':'/places/store'),
            'cancelUrl'=>$r->url('/places'),'csrfToken'=>$this->csrf->token(),
        ],$user,$status);
    }
    private static function fields(Request $r): array
    {
        $fields=[];foreach(['name','strasse','hausnummer','plz','ort','telefon','typ'] as $key)$fields[$key]=$r->inputString($key);
        return $fields;
    }
    public function save(Request $r): Response
    {
        $user=$this->current();if(!$user)return Response::redirect($r->url('/login'));
        $edit=$r->path==='/places/update';
        if(!$this->allowed($user,$edit?'places.edit':'places.create'))return $this->forbidden($r);
        if(!$this->csrf->verify($r->inputString('_csrf')))return $this->badToken($r);
        $id=$edit?self::id($r->inputString('id')):null;
        $place=$id?$this->repository()->find($id):null;
        if($edit&&(!$place||$place['status']!=1))return $this->forbidden($r);
        $values=self::fields($r);
        try{
            (new PlaceService($this->repository()))->save($values,$id,$user->id);
            $this->session->set('places_flash','Anschrift wurde gespeichert.');
            return Response::redirect($r->url('/places'));
        }catch(PlaceValidationException $e){return $this->form($r,$user,$place,$values,$e->errors,422);}
        catch(\PDOException $e){
            error_log('[MVC/place] '.$e->getMessage());
            return $this->form($r,$user,$place,$values,['name'=>'Ort konnte nicht gespeichert werden (möglicherweise bereits vorhanden).'],422);
        }
    }
    public function status(Request $r): Response
    {
        $user=$this->current();if(!$user)return Response::redirect($r->url('/login'));
        if(!$this->allowed($user,'places.status'))return $this->forbidden($r);
        if(!$this->csrf->verify($r->inputString('_csrf')))return $this->badToken($r);
        $id=self::id($r->inputString('id'));$state=$r->inputString('status');
        if($id>0&&in_array($state,['1','2'],true))$this->repository()->setStatus($id,(int)$state);
        return Response::redirect($r->url('/places'));
    }
}
