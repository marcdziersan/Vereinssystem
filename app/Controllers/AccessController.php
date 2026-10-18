<?php
/** Protected account and role administration without member editing workflows. */
declare(strict_types=1);
namespace Vereinssystem\Controllers;

use Vereinssystem\Auth\AuthService;
use Vereinssystem\Auth\Roles;
use Vereinssystem\Auth\User;
use Vereinssystem\Core\Csrf;
use Vereinssystem\Core\Request;
use Vereinssystem\Core\Response;
use Vereinssystem\Core\Session;
use Vereinssystem\Core\View;
use Vereinssystem\Repositories\UserRepository;

final class AccessController extends Controller
{
    public function __construct(View $views,Csrf $csrf,private readonly ?AuthService $auth,
        private readonly ?UserRepository $users,private readonly Roles $roles,private readonly Session $session)
    { parent::__construct($views,$csrf); }

    private function person(): ?User { return $this->auth?->currentUser(); }

    public function account(Request $request): Response
    {
        $user=$this->person();
        if ($user === null) return Response::redirect($request->url('/login'));
        return $this->page($request,'Mein Zugang','auth/account',[
            'user'=>$user,'role'=>Roles::label($user->rights),
        ],$user);
    }

    public function matrix(Request $request): Response
    {
        $user=$this->person();
        if ($user===null) return Response::redirect($request->url('/login'));
        if (!$this->roles->allows($user,'rights.view')) return $this->forbidden($request);
        return $this->page($request,'Rechtesystem','rights/matrix',[
            'permissions'=>Roles::permissions(),'names'=>Roles::NAMES,'roles'=>$this->roles,
        ],$user);
    }

    public function manage(Request $request): Response
    {
        $user=$this->person();
        if ($user===null) return Response::redirect($request->url('/login'));
        if (!$this->roles->allows($user,'rights.assign')) return $this->forbidden($request);
        $flash=(string)$this->session->get('roles_flash','');
        $this->session->forget('roles_flash');
        return $this->page($request,'Rechte verwalten','rights/manage',[
            'members'=>$this->users?->accessList() ?? [],
            'selfId'=>$user->id,'updateUrl'=>$request->url('/admin/roles'),
            'csrfToken'=>$this->csrf->token(),'names'=>Roles::NAMES,'flash'=>$flash,
        ],$user);
    }

    public function change(Request $request): Response
    {
        $user=$this->person();
        if ($user===null) return Response::redirect($request->url('/login'));
        if (!$this->roles->allows($user,'rights.assign')) return $this->forbidden($request);
        if (!$this->csrf->verify($request->inputString('_csrf'))) return $this->badToken($request);
        $id=filter_var($request->inputString('user_id'),FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        $level=filter_var($request->inputString('level'),FILTER_VALIDATE_INT,['options'=>['min_range'=>0,'max_range'=>5]]);
        if ($id===false || $level===false || $id===$user->id || $this->users===null) {
            $this->session->set('roles_flash','Ungültiger Eintrag oder Änderung des eigenen Zugangs.');
        } else {
            $target=$this->users->byId($id);
            if ($target===null || $target->status!==1) {
                $this->session->set('roles_flash','Konto nicht gefunden oder inaktiv.');
            } elseif ($target->rights===$level) {
                $this->session->set('roles_flash','Die Rechtestufe ist bereits gesetzt.');
            } else {
                $changed=$this->users->setRights($id,$level);
                $this->session->set('roles_flash',$changed?'Rechtestufe wurde gespeichert.':'Änderung nicht möglich.');
            }
        }
        return Response::redirect($request->url('/admin/roles'));
    }
}
