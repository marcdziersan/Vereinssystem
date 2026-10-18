<?php
/** End-to-end HTTP dispatch tests using a fake repository, no MySQL required. */
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';

use Vereinssystem\Auth\{User,Roles};
use Vereinssystem\Core\{Application,Request,Session};
use Vereinssystem\Repositories\UserRepository;

final class TestUsers implements UserRepository
{
    /** @var array<int,User> */
    public array $accounts;
    public function __construct()
    {
        $this->accounts=[
            1=>new User(1,'administrator','A001','Erika','Mustermann',password_hash('admin-password-strong',PASSWORD_DEFAULT),5,1),
            2=>new User(2,'mitglied','M002','Alex','Beispiel',password_hash('member-password-strong',PASSWORD_DEFAULT),1,1),
            3=>new User(3,'alt','M003','Alt','Mitglied',md5('legacy-password-strong'),3,1),
        ];
    }
    public function byIdentifier(string $identifier): ?User
    { foreach($this->accounts as $u)if($u->username===$identifier||$u->memberNumber===$identifier)return $u;return null; }
    public function byId(int $id): ?User { return $this->accounts[$id]??null; }
    public function accessList(int $limit=100): array { return array_values($this->accounts); }
    public function setRights(int $id,int $level): bool
    { $u=$this->accounts[$id]??null; if(!$u||$u->status!==1)return false;
      $this->accounts[$id]=new User($u->id,$u->username,$u->memberNumber,$u->firstName,$u->lastName,$u->passwordHash,$level,$u->status);return true; }
    public function replaceHash(int $id,string $newHash): void
    { $u=$this->accounts[$id];$this->accounts[$id]=new User($u->id,$u->username,$u->memberNumber,$u->firstName,$u->lastName,$newHash,$u->rights,$u->status); }
    public function recordLogin(int $id,string $ip): void {}
}

$session=new Session();$session->start(); // Start before test output.
$_SESSION=[];
$repo=new TestUsers();
$app=Application::fromProjectRoot(dirname(__DIR__),$repo);
$checks=0;
function expect(bool $condition,string $message): void {
    global $checks;++$checks;
    if(!$condition){fwrite(STDERR,"FAIL: {$message}\n");exit(1);}
    fwrite(STDOUT,"OK: {$message}\n");
}
function req(string $method,string $route,array $post=[]): Request {
    return new Request($method,$route,[],$post,'/index.php','192.0.2.150');
}
expect($app->handle(req('GET','/'))->status===303,'Home redirects to login when signed out');
$login=$app->handle(req('GET','/login'));
expect($login->status===200 && str_contains($login->body,'Benutzername oder Mitgliedsnummer'),'Login form renders');
$csrf=(string)$_SESSION['_vs_csrf'];
expect($app->handle(req('POST','/login',['identifier'=>'administrator','password'=>'admin-password-strong','_csrf'=>'wrong']))->status===419,'Login CSRF rejected');
expect($app->handle(req('POST','/login',['identifier'=>'administrator','password'=>'wrong','_csrf'=>$csrf]))->status===401,'Wrong password rejected');
expect($app->handle(req('POST','/login',['identifier'=>'administrator','password'=>'admin-password-strong','_csrf'=>$csrf]))->status===303,'Valid admin login redirects');
expect($_SESSION['mvc_uid']===1,'Session stores only the user id');
expect($app->handle(req('GET','/account'))->status===200,'Account page works');
expect(str_contains($app->handle(req('GET','/rights'))->body,'Administration'),'Permissions matrix renders');
expect($app->handle(req('GET','/admin/roles'))->status===200,'Admin sees role editor');
$token=(string)$_SESSION['_vs_csrf'];
expect($app->handle(req('POST','/admin/roles',['user_id'=>'2','level'=>'3','_csrf'=>'wrong']))->status===419,'Role update enforces CSRF');
expect($app->handle(req('POST','/admin/roles',['user_id'=>'2','level'=>'3','_csrf'=>$token]))->status===303,'Admin may change another role');
expect($repo->byId(2)->rights===3,'New role saved by repository');
$app->handle(req('POST','/admin/roles',['user_id'=>'1','level'=>'0','_csrf'=>$token]));
expect($repo->byId(1)->rights===5,'Own admin role may not be changed');
$app->handle(req('POST','/logout',['_csrf'=>$token]));
expect(!isset($_SESSION['mvc_uid']),'Logout invalidates authorization');
expect($app->handle(req('POST','/logout',['_csrf'=>'x']))->status===419,'Logout requires CSRF');
$csrf=(string)$_SESSION['_vs_csrf'];
expect($app->handle(req('POST','/login',['identifier'=>'mitglied','password'=>'member-password-strong','_csrf'=>$csrf]))->status===303,'Member login');
expect($app->handle(req('GET','/admin/roles'))->status===403,'Member denied admin view');
$app->handle(req('GET','/account')); // Render navigation and create a fresh CSRF token.
expect($app->handle(req('POST','/admin/roles',['_csrf'=>(string)$_SESSION['_vs_csrf'],'user_id'=>'1','level'=>'0']))->status===403,'Member denied role change');
$token=(string)$_SESSION['_vs_csrf'];$app->handle(req('POST','/logout',['_csrf'=>$token]));
$app->handle(req('GET','/login'));
$csrf=(string)$_SESSION['_vs_csrf'];
expect($app->handle(req('POST','/login',['identifier'=>'alt','password'=>'legacy-password-strong','_csrf'=>$csrf]))->status===303,'Legacy MD5 login');
expect(password_verify('legacy-password-strong',$repo->byId(3)->passwordHash),'MD5 transparently upgraded');
expect(!(new Roles())->allows($repo->byId(3),'rights.assign'),'Permission policy rejects level 3 escalation');
// Rights changes become effective immediately because each request refreshes the user.
$repo->setRights(3,0);
expect($app->handle(req('GET','/account'))->status===303,'Locked session loses access immediately');
expect($app->handle(req('GET','/admin/roles'))->status===303,'Blocked account cannot access roles');
expect(!$repo->byId(3)->active(),'User projection reports blocked account');
expect($app->handle(req('POST','/status'))->status===405,'Router 405 still correct');
expect($app->handle(req('GET','/not-here'))->status===404,'Router 404 still correct');
fwrite(STDOUT,"PASS: {$checks} authentication and rights checks.\n");
