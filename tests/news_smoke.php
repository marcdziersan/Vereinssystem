<?php
/** Alpha.6: in-memory bulletin policy and middleware tests, no MySQL necessary. */
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
use Vereinssystem\Auth\{Roles,User};
use Vereinssystem\Core\{Application,Request,Session};
use Vereinssystem\Models\NewsPost;
use Vereinssystem\News\{NewsRepository,NewsValidationException};
use Vereinssystem\Repositories\UserRepository;
use Vereinssystem\Services\NewsService;

$checks=0;
function verify(bool $ok,string $label):void {global $checks; if(!$ok)throw new RuntimeException('FAIL: '.$label);$checks++;}
function rejected(callable $cb):bool {try{$cb();return false;}catch(DomainException|NewsValidationException $e){return true;}}

final class FakeBulletins implements NewsRepository
{
    /** @var array<int,NewsPost> */ public array $posts=[];
    public function listing(int $rights,string $search='',int $page=1,int $perPage=15):array {
        $rows=[];foreach($this->posts as $p){if($p->status!==1||$p->minRight>$rights)continue;
            if($search!==''&&!str_contains(strtolower($p->title.' '.$p->content),strtolower($search)))continue;
            $rows[]=['news_id'=>$p->id,'title'=>$p->title,'content'=>$p->content,'created'=>$p->createdAt,
                'author_first'=>'Erika','author_last'=>'Beispiel','minright'=>$p->minRight,'status'=>$p->status];}
        usort($rows,static fn(array $a,array $b):int=>$b['created']<=>$a['created']);
        return array_slice($rows,($page-1)*$perPage,$perPage);
    }
    public function countVisible(int $rights,string $search=''):int {return count($this->listing($rights,$search,1,1000));}
    public function find(int $id):?NewsPost {return $this->posts[$id]??null;}
    public function authorName(int $id):string{return 'Erika Beispiel';}
    public function save(NewsPost $post):int {
        $id=$post->id??count($this->posts)+1;
        $this->posts[$id]=new NewsPost($id,$post->title,$post->content,$post->minRight,$post->authorId,$post->createdAt,$post->changedAt,$post->status);
        return $id;
    }
    public function archive(int $id,int $editorId,bool $isAdmin):bool {
        $p=$this->find($id);if(!$p||$p->status!==1||(!$isAdmin&&$p->authorId!==$editorId))return false;
        $this->posts[$id]=new NewsPost($p->id,$p->title,$p->content,$p->minRight,$p->authorId,$p->createdAt,time(),2);return true;
    }
    public function archived(int $limit=100):array {return array_values(array_filter($this->posts,static fn(NewsPost $p):bool=>$p->status===2));}
    public function restore(int $id):bool {
        $p=$this->find($id);if(!$p||$p->status!==2)return false;
        $this->posts[$id]=new NewsPost($p->id,$p->title,$p->content,$p->minRight,$p->authorId,$p->createdAt,time(),1);return true;
    }
}
final class FakeBulletinUsers implements UserRepository
{
    public function byId(int $id):?User {
        // The same fixture password needs hashing only once; repeated bcrypt rounds slow down HTTP checks.
        static $hash=null; $hash??=password_hash('a_secret_12345678',PASSWORD_DEFAULT);
        return match($id){1,2,3,4=>new User($id,'user'.$id,'M-'.$id,'Erika','Beispiel',$hash,match($id){1=>5,2=>4,3=>4,4=>1},1),default=>null};
    }
    public function byIdentifier(string $identifier):?User{return $this->byId(1);}
    public function accessList(int $limit=100):array{return [$this->byId(1)];}
    public function setRights(int $id,int $level):bool{return false;}
    public function replaceHash(int $id,string $newHash):void {}
    public function recordLogin(int $id,string $ip):void {}
}

$repo=new FakeBulletins();$users=new FakeBulletinUsers();$svc=new NewsService($repo);
$admin=$users->byId(1);$board=$users->byId(2);$other=$users->byId(3);$member=$users->byId(4);
$fields=['title'=>'Trainingsinfo','content'=>'Training ab 18:00 Uhr','minright'=>'1'];
$id=$svc->save($fields,$board,null);
verify($id===1,'Create post');
verify($repo->find(1)?->authorId===2,'Author stored');
verify($svc->canRead($member,$repo->find(1)),'Public post is visible');
verify($svc->canEdit($board,$repo->find(1)),'Author can edit');
verify(!$svc->canEdit($other,$repo->find(1)),'Different board member cannot edit');
verify($svc->canEdit($admin,$repo->find(1)),'Admin may edit');
verify(rejected(fn()=>$svc->save(['title'=>'','content'=>'Hi','minright'=>'1'],$board,null)),'Empty title refused');
verify(rejected(fn()=>$svc->save(['title'=>'Test','content'=>'','minright'=>'1'],$board,null)),'Empty text refused');
verify(rejected(fn()=>$svc->save(['title'=>'Test','content'=>'Hi','minright'=>'6'],$board,null)),'Invalid visibility refused');
verify(rejected(fn()=>$svc->save(['title'=>'Test','content'=>'Hi','minright'=>'5'],$board,null)),'Cannot publish above own level');
verify(rejected(fn()=>$svc->save($fields,$member,null)),'Member cannot create');
verify(rejected(fn()=>$svc->save($fields,$other,1)),'Cannot edit others post');
$svc->save(['title'=>'Aktualisiert','content'=>'<script>alert(1)</script>','minright'=>'4'],$board,1);
verify(!$svc->canRead($member,$repo->find(1)),'Private post hidden from member');
verify(count($repo->listing(1))===0,'Index filter does not leak restricted post');
verify($svc->canRead($board,$repo->find(1)),'Board can read level four');
$svc->archive(1,$board);
verify($repo->find(1)->status===2,'Soft archive persists');
verify(!$svc->canRead($board,$repo->find(1)),'Archived post not readable');
verify(rejected(fn()=>$svc->archive(1,$board)),'Double archive prevented');
verify(rejected(fn()=>$svc->restore(1,$board)),'Only admin can restore');
$svc->restore(1,$admin);
verify($repo->find(1)->status===1,'Admin restore');
verify(Roles::permissions()['news.archive']===4,'News has dedicated permission');
verify(Roles::permissions()['news.restore']===5,'Restore reserved for administrator');

$session=new Session();$session->start();$_SESSION=[];
$app=Application::fromProjectRoot(dirname(__DIR__),$users,null,null,null,null,$repo);
$get=static fn(string $route,array $query=[]):Request=>new Request('GET',$route,$query);
$post=static fn(string $route,array $fields=[]):Request=>new Request('POST',$route,[],$fields);
verify($app->handle($get('/news'))->status===303,'Anonymous redirected');
$_SESSION['mvc_uid']=4;
verify($app->handle($get('/news'))->status===200,'Member listing');
verify($app->handle($get('/news/view',['id'=>'1']))->status===403,'Restricted post not readable by member');
verify($app->handle($get('/news/create'))->status===403,'Member cannot create');
verify($app->handle($get('/news/archived'))->status===403,'Member cannot see archive');
verify($app->handle($post('/news/archive',['id'=>'1']))->status===403,'Rights before CSRF');
$_SESSION['mvc_uid']=2;
verify($app->handle($get('/news/create'))->status===200,'Board create form');
verify($app->handle($post('/news/store',$fields))->status===419,'CSRF checked by middleware');
$token=(string)$_SESSION['_vs_csrf'];
verify($app->handle($post('/news/store',['_csrf'=>$token,'title'=>'Neu','content'=>'Hallo','minright'=>'4']))->status===303,'Create HTTP post');
verify(count($repo->posts)===2,'Post persisted');
verify($app->handle($get('/news/view',['id'=>'1']))->status===200,'Board reads post');
$rendered=$app->handle($get('/news/view',['id'=>'1']))->body;
verify(str_contains($rendered,'&lt;script&gt;') && !str_contains($rendered,'<script>alert(1)</script>'),'Untrusted legacy HTML escaped');
verify($app->handle($post('/news/update',['_csrf'=>$token,'id'=>'1','title'=>'','content'=>'Text','minright'=>'4']))->status===422,'Validation error rendered');
$_SESSION['mvc_uid']=3;
verify($app->handle($get('/news/edit',['id'=>'1']))->status===403,'Another board user cannot edit');
verify($app->handle($post('/news/archive',['_csrf'=>$token,'id'=>'1']))->status===303,'Denied archive handled without leak');
verify($repo->find(1)->status===1,'Another author could not archive');
$_SESSION['mvc_uid']=1;
verify($app->handle($get('/news/edit',['id'=>'1']))->status===200,'Admin can edit');
verify($app->handle($get('/news/archived'))->status===200,'Admin archive view works');
verify($app->handle($post('/news/archive',['_csrf'=>$token,'id'=>'1']))->status===303,'Admin archives via post');
verify($repo->find(1)->status===2,'Admin archive saved');
verify($app->handle($get('/news/archived'))->status===200,'Archived items listed for admin');
verify($app->handle($post('/news/restore',['_csrf'=>$token,'id'=>'1']))->status===303,'Admin restores via POST');
verify($repo->find(1)->status===1,'Restore state persisted');
verify($app->handle($post('/news/archive',['_csrf'=>$token,'id'=>'1']))->status===303,'Rearchive for remaining checks');
verify($app->handle($get('/news/view',['id'=>'1']))->status===403,'Archived detail hidden for admin');
verify(is_file(dirname(__DIR__).'/database/migrations/004_news.sql'),'Migration exists');
verify(preg_match_all('/CREATE TABLE IF NOT EXISTS/',file_get_contents(dirname(__DIR__).'/database/migrations/004_news.sql'))===1,'One news table');
verify(str_contains(file_get_contents(dirname(__DIR__).'/app/Install/InstallationService.php'),'004_news.sql'),'Installer includes news schema');
verify(!is_file(dirname(__DIR__).'/news.php'),'No legacy news endpoint');
echo "PASS $checks alpha.6 bulletin assertions\n";
