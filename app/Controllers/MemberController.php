<?php
/**
 * HTTP adapter for MVC member workflows: separate read, create, update and
 * soft-deactivation actions. Every route performs server-side access checks.
 */
declare(strict_types=1);

namespace Vereinssystem\Controllers;

use Vereinssystem\Auth\{AuthService, Roles, User};
use Vereinssystem\Core\{Csrf, Request, Response, Session, View};
use Vereinssystem\Members\{MemberRepository, MemberService, MemberValidationException};

final class MemberController extends Controller
{
    public function __construct(
        View $views, Csrf $csrf,
        private readonly ?AuthService $auth,
        private readonly ?MemberRepository $members,
        private readonly Roles $roles,
        private readonly Session $session,
    ) { parent::__construct($views, $csrf); }

    private function person(): ?User { return $this->auth?->currentUser(); }

    private function staff(User $user, string $capability): bool
    {
        return $this->roles->allows($user, $capability);
    }

    private static function id(string $raw): int
    {
        $id = filter_var($raw, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
        return is_int($id) ? $id : 0;
    }

    /** Form fields are explicitly listed, never copied wholesale from $_POST. */
    private static function fields(Request $r): array
    {
        $keys = ['number','first_name','last_name','birthday','joined_at','gender',
            'email','mobile','membership','billing','address_id','parent1','parent2','notes','username','password'];
        $data = [];
        foreach ($keys as $key) $data[$key] = $r->inputString($key);
        return $data;
    }

    private function flash(): string
    {
        $message = (string)$this->session->get('member_flash', '');
        $this->session->forget('member_flash');
        return $message;
    }

    public function index(Request $r): Response
    {
        $user = $this->person();
        if (!$user) return Response::redirect($r->url('/login'));
        if (!$this->staff($user,'members.list')) return $this->forbidden($r);
        $q = trim($r->queryString('q'));
        if (strlen($q)>100) $q=substr($q,0,100);
        $page = max(1,min(100000,(int)$r->queryString('page','1')));
        $filter=$r->queryString('filter')==='inactive' && $user->rights===5 ? 'inactive':'active';
        $result = $this->members?->search($q,$page,20,$filter)
            ?? ['items'=>[],'total'=>0,'page'=>1,'pages'=>1];
        return $this->page($r,'Mitglieder','members/index',[
            'result'=>$result,'q'=>$q,'filter'=>$filter,'flash'=>$this->flash(),
            'listUrl'=>$r->url('/members'),'viewUrl'=>$r->url('/members/view'),
            'newUrl'=>$r->url('/members/create'),'mayAdd'=>$this->staff($user,'members.create'),
            'isAdmin'=>$user->rights===5,
        ],$user);
    }

    public function show(Request $r): Response
    {
        $user=$this->person();
        if (!$user) return Response::redirect($r->url('/login'));
        $id=self::id($r->queryString('id'));
        if ($id === 0 || ($id!==$user->id && !$this->staff($user,'members.view'))) return $this->forbidden($r);
        $member=$this->members?->byId($id);
        if (!$member || ($member->status!==1 && $user->rights!==5)) return $this->missing($r);
        $isStaff=$this->staff($user,'members.view');
        return $this->page($r,'Mitgliedsprofil','members/show',[
            'member'=>$member,'canSeeNotes'=>$isStaff,
            'canEdit'=>$isStaff && $member->status===1 && $this->staff($user,'members.edit')
                && ($member->rights!==5 || $user->rights===5),
            'canStatus'=>$user->rights===5 && $id!==$user->id && $member->rights!==5,
            'editUrl'=>$r->url('/members/edit').'&id='.$id,
            'statusUrl'=>$r->url('/members/status'),'csrfToken'=>$this->csrf->token(),
            'backUrl'=>$isStaff?$r->url('/members'):$r->url('/dashboard'),
            'flash'=>$this->flash(),
        ],$user);
    }

    public function create(Request $r): Response
    {
        $user=$this->person();
        if (!$user) return Response::redirect($r->url('/login'));
        if (!$this->staff($user,'members.create')) return $this->forbidden($r);
        return $this->form($r,$user,null,[],[]);
    }

    public function edit(Request $r): Response
    {
        $user=$this->person();
        if (!$user) return Response::redirect($r->url('/login'));
        if (!$this->staff($user,'members.edit')) return $this->forbidden($r);
        $member=$this->members?->byId(self::id($r->queryString('id')));
        if (!$member || $member->status!==1) return $this->missing($r);
        if ($member->rights===5 && $user->rights!==5) return $this->forbidden($r);
        return $this->form($r,$user,$member,$member->editable(),[]);
    }

    private function form(Request $r,User $user,?\Vereinssystem\Members\Member $member,array $values,array $errors,int $status=200): Response
    {
        $defaults=['birthday'=>'','joined_at'=>date('Y-m-d'),'gender'=>'0',
            'membership'=>'0','billing'=>'0','address_id'=>'0','parent1'=>'0','parent2'=>'0'];
        $values=$values+$defaults;
        return $this->page($r,$member?'Mitglied bearbeiten':'Mitglied anlegen','members/form',[
            'member'=>$member,'values'=>$values,'errors'=>$errors,
            'csrfToken'=>$this->csrf->token(),
            'saveUrl'=>$r->url($member?'/members/update':'/members/store'),
            'cancelUrl'=>$member?$r->url('/members/view').'&id='.$member->id:$r->url('/members'),
        ],$user,$status);
    }

    public function store(Request $r): Response
    {
        $user=$this->person();
        if (!$user) return Response::redirect($r->url('/login'));
        if (!$this->staff($user,'members.create')) return $this->forbidden($r);
        if (!$this->csrf->verify($r->inputString('_csrf'))) return $this->badToken($r);
        $values=self::fields($r);
        try {
            $id=(new MemberService($this->requiredRepository()))->add($values);
            $this->session->set('member_flash','Mitglied wurde angelegt.');
            return Response::redirect($r->url('/members/view').'&id='.$id);
        } catch(MemberValidationException $e) {
            unset($values['password']);
            return $this->form($r,$user,null,$values,$e->errors,422);
        }
    }

    public function update(Request $r): Response
    {
        $user=$this->person();
        if (!$user) return Response::redirect($r->url('/login'));
        if (!$this->staff($user,'members.edit')) return $this->forbidden($r);
        if (!$this->csrf->verify($r->inputString('_csrf'))) return $this->badToken($r);
        $id=self::id($r->inputString('id'));
        $member=$this->members?->byId($id);
        if (!$member || $member->status!==1) return $this->missing($r);
        if ($member->rights===5 && $user->rights!==5) return $this->forbidden($r);
        $values=self::fields($r);
        try {
            if (!(new MemberService($this->requiredRepository()))->edit($id,$values)) return $this->missing($r);
            $this->session->set('member_flash','Änderungen wurden gespeichert.');
            return Response::redirect($r->url('/members/view').'&id='.$id);
        } catch(MemberValidationException $e) {
            unset($values['password']);
            return $this->form($r,$user,$member,$values,$e->errors,422);
        }
    }

    public function status(Request $r): Response
    {
        $user=$this->person();
        if (!$user) return Response::redirect($r->url('/login'));
        if (!$this->staff($user,'members.status')) return $this->forbidden($r);
        if (!$this->csrf->verify($r->inputString('_csrf'))) return $this->badToken($r);
        $id=self::id($r->inputString('id'));
        $status=$r->inputString('status')==='1' ? 1 : ($r->inputString('status')==='2' ? 2 : 0);
        $ok=(new MemberService($this->requiredRepository()))->status($id,$status,$user);
        $this->session->set('member_flash',$ok?'Mitgliedsstatus wurde geändert.':'Statusänderung nicht zulässig.');
        return Response::redirect($r->url('/members/view').'&id='.$id);
    }

    private function requiredRepository(): MemberRepository
    {
        if (!$this->members) throw new \Vereinssystem\Core\DatabaseUnavailable('Member database not configured');
        return $this->members;
    }

    private function missing(Request $r): Response
    {
        return $this->page($r,'Mitglied nicht gefunden','members/missing',[
            'backUrl'=>$r->url('/members'),
        ],$this->person(),404);
    }
}
