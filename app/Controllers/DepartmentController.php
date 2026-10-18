<?php
/** MVC department CRUD and membership assignment; all mutations require POST + CSRF. */
declare(strict_types=1);
namespace Vereinssystem\Controllers;
use Vereinssystem\Auth\{AuthService,Roles,User};
use Vereinssystem\Core\{Csrf,Request,Response,Session,View};
use Vereinssystem\Departments\{DepartmentRepository,DepartmentService,DepartmentValidationException};

final class DepartmentController extends Controller
{
    public function __construct(View $views,Csrf $csrf,private readonly ?AuthService $auth,
        private readonly ?DepartmentRepository $departments,private readonly Roles $roles,private readonly Session $session)
    {parent::__construct($views,$csrf);}
    private function current(): ?User {return $this->auth?->currentUser();}
    private function allowed(User $u,string $permission): bool {return $this->roles->allows($u,$permission);}
    private function repository(): DepartmentRepository
    {if(!$this->departments)throw new \Vereinssystem\Core\DatabaseUnavailable('Department database missing');return $this->departments;}
    private static function id(string $s): int
    {$v=filter_var($s,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);return is_int($v)?$v:0;}
    private function flash(): string
    {$text=(string)$this->session->get('departments_flash','');$this->session->forget('departments_flash');return $text;}
    public function index(Request $r): Response
    {
        $user=$this->current();if(!$user)return Response::redirect($r->url('/login'));
        if(!$this->allowed($user,'departments.list'))return $this->forbidden($r);
        return $this->page($r,'Abteilungen','departments/index',[
            'items'=>$this->repository()->all($user->rights===5&&$r->queryString('inactive')==='1'),
            'flash'=>$this->flash(),'showUrl'=>$r->url('/departments/view'),
            'createUrl'=>$r->url('/departments/create'),'canCreate'=>$this->allowed($user,'departments.create'),
            'showInactive'=>$user->rights===5&&$r->queryString('inactive')==='1',
            'listUrl'=>$r->url('/departments'),'isAdmin'=>$user->rights===5,
        ],$user);
    }
    public function show(Request $r): Response
    {
        $user=$this->current();if(!$user)return Response::redirect($r->url('/login'));
        if(!$this->allowed($user,'departments.view'))return $this->forbidden($r);
        $id=self::id($r->queryString('id'));
        $dept=$this->repository()->find($id);
        if(!$dept||($dept['status']!=1&&$user->rights!==5))return $this->forbidden($r);
        return $this->page($r,'Abteilung: '.$dept['name'],'departments/show',[
            'dept'=>$dept,'members'=>$this->repository()->members($id),
            'canEdit'=>$this->allowed($user,'departments.edit'),'canAssign'=>$this->allowed($user,'departments.assign'),
            'canStatus'=>$this->allowed($user,'departments.status'),'csrfToken'=>$this->csrf->token(),
            'editUrl'=>$r->url('/departments/edit').'&id='.$id,
            'assignUrl'=>$r->url('/departments/assign'),'removeUrl'=>$r->url('/departments/remove'),
            'statusUrl'=>$r->url('/departments/status'),'backUrl'=>$r->url('/departments'),
            'flash'=>$this->flash(),
        ],$user);
    }
    public function create(Request $r): Response
    {
        $user=$this->current();if(!$user)return Response::redirect($r->url('/login'));
        if(!$this->allowed($user,'departments.create'))return $this->forbidden($r);
        return $this->form($r,$user,null,[],[]);
    }
    public function edit(Request $r): Response
    {
        $user=$this->current();if(!$user)return Response::redirect($r->url('/login'));
        if(!$this->allowed($user,'departments.edit'))return $this->forbidden($r);
        $dept=$this->repository()->find(self::id($r->queryString('id')));
        if(!$dept||$dept['status']!=1)return $this->forbidden($r);
        return $this->form($r,$user,$dept,$dept,[]);
    }
    private function form(Request $r,User $user,?array $dept,array $values,array $errors,int $status=200): Response
    {
        return $this->page($r,$dept?'Abteilung bearbeiten':'Neue Abteilung','departments/form',[
            'dept'=>$dept,'values'=>$values,'errors'=>$errors,'csrfToken'=>$this->csrf->token(),
            'saveUrl'=>$r->url($dept?'/departments/update':'/departments/store'),
            'backUrl'=>$r->url('/departments'),
        ],$user,$status);
    }
    private static function fields(Request $r): array
    {
        $a=[];foreach(['name','beschreibung','homepage','abteilungsleiter','aktivenumlage'] as $key)$a[$key]=$r->inputString($key);
        return $a;
    }
    public function save(Request $r): Response
    {
        $user=$this->current();if(!$user)return Response::redirect($r->url('/login'));
        $edit=$r->path==='/departments/update';
        if(!$this->allowed($user,$edit?'departments.edit':'departments.create'))return $this->forbidden($r);
        if(!$this->csrf->verify($r->inputString('_csrf')))return $this->badToken($r);
        $id=$edit?self::id($r->inputString('id')):null;
        $dept=$id?$this->repository()->find($id):null;
        if($edit&&(!$dept||$dept['status']!=1))return $this->forbidden($r);
        $values=self::fields($r);
        try{
            $result=(new DepartmentService($this->repository()))->save($values,$id);
            $this->session->set('departments_flash','Abteilung wurde gespeichert.');
            return Response::redirect($r->url('/departments/view').'&id='.$result);
        }catch(DepartmentValidationException $e){return $this->form($r,$user,$dept,$values,$e->errors,422);}
    }
    public function assign(Request $r): Response
    {
        $user=$this->current();if(!$user)return Response::redirect($r->url('/login'));
        if(!$this->allowed($user,'departments.assign'))return $this->forbidden($r);
        if(!$this->csrf->verify($r->inputString('_csrf')))return $this->badToken($r);
        $dept=self::id($r->inputString('department_id'));
        $member=self::id($r->inputString('member_id'));
        try {
            $active=$r->inputString('active');
            if(!in_array($active,['0','1'],true))throw new \DomainException('Ungültige Mitgliedschaftsart.');
            (new DepartmentService($this->repository()))->assign($dept,$member,(int)$active,$r->inputString('date'));
            $this->session->set('departments_flash','Zugehörigkeit gespeichert.');
        }catch(\DomainException $e){$this->session->set('departments_flash','Zuordnung nicht möglich: '.$e->getMessage());}
        return Response::redirect($r->url('/departments/view').'&id='.$dept);
    }
    public function remove(Request $r): Response
    {
        $user=$this->current();if(!$user)return Response::redirect($r->url('/login'));
        if(!$this->allowed($user,'departments.assign'))return $this->forbidden($r);
        if(!$this->csrf->verify($r->inputString('_csrf')))return $this->badToken($r);
        $dept=self::id($r->inputString('department_id'));$member=self::id($r->inputString('member_id'));
        if($dept&&$member)$this->repository()->unassign($dept,$member);
        return Response::redirect($r->url('/departments/view').'&id='.$dept);
    }
    public function status(Request $r): Response
    {
        $user=$this->current();if(!$user)return Response::redirect($r->url('/login'));
        if(!$this->allowed($user,'departments.status'))return $this->forbidden($r);
        if(!$this->csrf->verify($r->inputString('_csrf')))return $this->badToken($r);
        $id=self::id($r->inputString('id'));$status=$r->inputString('status');
        if($id&&in_array($status,['1','2'],true))$this->repository()->setStatus($id,(int)$status);
        return Response::redirect($r->url('/departments'));
    }
}
