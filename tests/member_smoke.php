<?php
/** Self-contained MVC member integration checks with in-memory repositories. */
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';

use Vereinssystem\Auth\{User};
use Vereinssystem\Core\{Application,Request,Session};
use Vereinssystem\Members\{Member,MemberRepository,MemberService,MemberValidationException};
use Vereinssystem\Repositories\UserRepository;

final class FakeMembers implements MemberRepository
{
    /** @var array<int,Member> */
    public array $items=[];
    public array $writes=[];
    public function __construct()
    {
        $this->items[1]=self::member(1,'Erika','Mustermann',5);
        $this->items[2]=self::member(2,'Alex','Beispiel',1);
        $this->items[3]=self::member(3,'Klaus','Beispiel',4);
    }
    private static function member(int $id,string $first,string $last,int $rights): Member
    {
        return new Member($id,'VS-'.str_pad((string)$id,4,'0',STR_PAD_LEFT),$first,$last,
            '1990-01-01','2020-01-01','mail@example.test','',0,0,0,0,0,0,'PRIVATE NOTE',strtolower($first),$rights,1);
    }
    public function byId(int $id): ?Member {return $this->items[$id]??null;}
    public function search(string $term,int $page=1,int $perPage=20,string $status='active'): array
    {
        $rows=array_values(array_filter($this->items,static fn(Member $m):bool=>$m->status===($status==='inactive'?2:1)
            && ($term===''||stripos($m->fullName(),$term)!==false||stripos($m->number,$term)!==false)));
        return ['items'=>array_slice($rows,($page-1)*$perPage,$perPage),'total'=>count($rows),'page'=>$page,'pages'=>max(1,(int)ceil(count($rows)/$perPage))];
    }
    public function statistics():array { return ['total'=>count(array_filter($this->items,static fn($x)=>$x->status===1)), 'ordinary'=>2,'supporting'=>1,'resting'=>0]; }
    public function create(array $data,string $passwordHash):int
    {
        $id=max(array_keys($this->items))+1;
        $this->writes[]=['create',$data,$passwordHash];
        $this->items[$id]=self::member($id,(string)$data['first_name'],(string)$data['last_name'],1);
        return $id;
    }
    public function update(int $id,array $data):bool
    {
        $this->writes[]=['update',$id,$data];$old=$this->items[$id]??null;
        if(!$old)return false;
        $this->items[$id]=new Member($id,(string)$data['number'],(string)$data['first_name'],(string)$data['last_name'],
            (string)$data['birthday'],(string)$data['joined_at'],(string)$data['email'],(string)$data['mobile'],
            (int)$data['gender'],(int)$data['membership'],(int)$data['billing'],(int)$data['address_id'],
            (int)$data['parent1'],(int)$data['parent2'],(string)$data['notes'],$old->username,$old->rights,$old->status);
        return true;
    }
    public function setStatus(int $id,int $status):bool
    {
        $old=$this->items[$id]??null;if(!$old)return false;
        $this->writes[]=['status',$id,$status];
        $this->items[$id]=new Member($old->id,$old->number,$old->firstName,$old->lastName,$old->birthday,
            $old->joinedAt,$old->email,$old->mobile,$old->gender,$old->membership,$old->billing,
            $old->addressId,$old->parent1,$old->parent2,$old->notes,$old->username,$old->rights,$status);
        return true;
    }
}
final class FakeAccounts implements UserRepository
{
    public function __construct(public FakeMembers $members) {}
    public function byId(int $id): ?User
    {
        $m=$this->members->byId($id);
        return $m?new User($m->id,$m->username,$m->number,$m->firstName,$m->lastName,
            password_hash('password-password-123',PASSWORD_DEFAULT),$m->rights,$m->status):null;
    }
    public function byIdentifier(string $identifier):?User
    {foreach($this->members->items as $m)if($m->username===$identifier)return $this->byId($m->id);return null;}
    public function accessList(int $limit=100):array {return array_filter(array_map($this->byId(...),array_keys($this->members->items)));}
    public function setRights(int $id,int $level):bool{return false;}
    public function replaceHash(int $id,string $newHash):void{}
    public function recordLogin(int $id,string $ip):void{}
}
$session=new Session();$session->start();$_SESSION=[];
$members=new FakeMembers();$accounts=new FakeAccounts($members);
$app=Application::fromProjectRoot(dirname(__DIR__),$accounts,$members);
$checks=0;
function assertion(bool $result,string $name):void {global $checks;$checks++;if(!$result){fwrite(STDERR,"FAIL: {$name}\n");exit(1);}echo "OK: {$name}\n";}
function request2(string $method,string $route,array $data=[],array $query=[]):Request{return new Request($method,$route,$query,$data,'/index.php','192.0.2.25');}
function input():array{return ['number'=>'M-100','first_name'=>'Lisa','last_name'=>'Neu','birthday'=>'2001-02-03','joined_at'=>'2025-03-04',
'gender'=>'2','email'=>'lisa@example.test','mobile'=>'123','membership'=>'0','billing'=>'1',
'address_id'=>'0','parent1'=>'0','parent2'=>'0','notes'=>'Testnotiz','username'=>'lisa','password'=>'this-is-a-long-password'];}
assertion($app->handle(request2('GET','/dashboard'))->status===303,'Signed-out dashboard redirects');
assertion($app->handle(request2('GET','/members'))->status===303,'Signed-out directory redirects');
$_SESSION['mvc_uid']=2;
$view=$app->handle(request2('GET','/dashboard'));
assertion($view->status===200 && str_contains($view->body,'Willkommen'),'Member dashboard available');
assertion(!str_contains($view->body,'Aktive Mitglieder'),'Dashboard stats hidden for members');
assertion($app->handle(request2('GET','/members'))->status===403,'Directory denied for member');
assertion($app->handle(request2('GET','/members/create'))->status===403,'Create page denied for member');
assertion($app->handle(request2('GET','/members/view',[],['id'=>'1']))->status===403,'Other profile denied');
$own=$app->handle(request2('GET','/members/view',[],['id'=>'2']));
assertion($own->status===200 && !str_contains($own->body,'PRIVATE NOTE'),'Member sees own restricted profile');
assertion($app->handle(request2('POST','/members/store',input()))->status===403,'Member cannot forge create');
$_SESSION['mvc_uid']=3;
assertion($app->handle(request2('GET','/members'))->status===200,'Board sees directory');
assertion($app->handle(request2('GET','/members/view',[],['id'=>'2']))->status===200,'Board sees detail');
assertion($app->handle(request2('GET','/members/create'))->status===403,'Board cannot create');
assertion($app->handle(request2('GET','/members/edit',[],['id'=>'2']))->status===200,'Board can edit');
assertion($app->handle(request2('GET','/members/edit',[],['id'=>'1']))->status===403,'Board cannot edit admin profile');
assertion($app->handle(request2('POST','/members/status',['id'=>'2','status'=>'2']))->status===403,'Board cannot deactivate');
$_SESSION['mvc_uid']=1;
$adminDash=$app->handle(request2('GET','/dashboard'));
assertion(str_contains($adminDash->body,'Aktive Mitglieder'),'Admin dashboard stats visible');
assertion($app->handle(request2('GET','/members/create'))->status===200,'Admin can create');
$token=(string)$_SESSION['_vs_csrf'];
assertion($app->handle(request2('POST','/members/store',input()+['_csrf'=>'invalid']))->status===419,'Create enforces CSRF');
$invalid=input();$invalid['birthday']='2026-02-30';
assertion($app->handle(request2('POST','/members/store',$invalid+['_csrf'=>$token]))->status===422,'Invalid calendar date blocked');
assertion($app->handle(request2('POST','/members/store',input()+['_csrf'=>$token]))->status===303,'Member created with redirect');
assertion(count($members->items)===4,'Repository stores new member');
assertion(password_verify(input()['password'],$members->writes[0][2]),'Initial password hashed');
assertion(!isset($members->writes[0][1]['rights']),'No privileges from create form');
$change=input();$change['first_name']='Lisa-Ä';$change['username']='evil';
assertion($app->handle(request2('POST','/members/update',$change+['id'=>'4','_csrf'=>$token]))->status===303,'Edit works');
assertion($members->byId(4)->firstName==='Lisa-Ä' && $members->byId(4)->username==='lisa','Edit cannot change login');
assertion(!isset($members->writes[1][2]['rights']) && !isset($members->writes[1][2]['username']),'Edit whitelist excludes account fields');
assertion($app->handle(request2('POST','/members/status',['id'=>'1','status'=>'2','_csrf'=>$token]))->status===303,'Self-disable refused and redirected');
assertion($members->byId(1)->status===1,'Own admin protected');
assertion($app->handle(request2('POST','/members/status',['id'=>'2','status'=>'2','_csrf'=>$token]))->status===303,'Disable member');
assertion($members->byId(2)->status===2,'Soft status saved');
assertion($app->handle(request2('GET','/members/view',[],['id'=>'2']))->status===200,'Admin can view inactive member');
assertion($app->handle(request2('POST','/members/status',['id'=>'2','status'=>'1','_csrf'=>$token]))->status===303,'Restore member');
assertion($members->byId(2)->status===1,'Member restored');
$service=new MemberService($members);
try{$data=input();$data['email']='not-an-email';$service->validate($data,true);assertion(false,'Bad email');}catch(MemberValidationException){assertion(true,'Invalid email rejected');}
try{$data=input();$data['membership']='5';$service->validate($data,true);assertion(false,'Bad role');}catch(MemberValidationException){assertion(true,'Unsupported membership rejected');}
assertion($app->handle(request2('POST','/members/update',input()+['id'=>'4','_csrf'=>'invalid']))->status===419,'Edit rejects CSRF');
assertion($app->handle(request2('POST','/members/status',['id'=>'2','status'=>'2','_csrf'=>'invalid']))->status===419,'Status rejects CSRF');
assertion($app->handle(request2('GET','/nonsense'))->status===404,'Router 404 remains');
echo "PASS {$checks} dashboard/member assertions\n";
