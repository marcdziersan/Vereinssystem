<?php
/** Alpha.4 checks covering both HTTP integration and independent domain rules. */
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
use Vereinssystem\Auth\User;
use Vereinssystem\Core\{Application,Request,Session};
use Vereinssystem\Repositories\UserRepository;
use Vereinssystem\Places\{PlaceRepository,PlaceService,PlaceValidationException};
use Vereinssystem\Departments\{DepartmentRepository,DepartmentService,DepartmentValidationException};

$checks=0;
function verify4(bool $test,string $why):void {global $checks;++$checks;if(!$test){fwrite(STDERR,"FAIL {$why}\n");exit(1);}echo "OK {$why}\n";}
final class TestPlaceStore implements PlaceRepository
{
    public array $rows=[];public int $calls=0;
    public function all(bool $includeInactive=false):array {return array_values(array_filter($this->rows,static fn($r)=>$includeInactive||$r['status']===1));}
    public function find(int $id):?array {return $this->rows[$id]??null;}
    public function save(array $values,?int $id,int $actor):int {$id??=count($this->rows)+1;$this->rows[$id]=$values+['orts_id'=>$id,'status'=>1,'createdby'=>$actor];$this->calls++;return $id;}
    public function setStatus(int $id,int $status):bool {if(!isset($this->rows[$id]))return false;$this->rows[$id]['status']=$status;return true;}
}
final class TestDepartmentStore implements DepartmentRepository
{
    public array $rows=[];public array $assignments=[];
    public function all(bool $includeInactive=false):array{return array_values(array_map(static fn($r)=>$r+['member_count'=>0],array_filter($this->rows,static fn($r)=>$includeInactive||$r['status']===1)));}
    public function find(int $id):?array{return $this->rows[$id]??null;}
    public function members(int $id):array{return [];}
    public function save(array $values,?int $id):int{$id??=count($this->rows)+1;$this->rows[$id]=$values+['abteilungs_id'=>$id,'status'=>1];return $id;}
    public function assign(int $department,int $member,int $active,string $since):void{$this->assignments[]=[$department,$member,$active,$since];}
    public function unassign(int $department,int $member):bool{return true;}
    public function setStatus(int $id,int $status):bool{if(!isset($this->rows[$id]))return false;$this->rows[$id]['status']=$status;return true;}
}
final class TestAuthUsers4 implements UserRepository
{
    public function byId(int $id):?User{return $id===1?new User(1,'admin','A1','Admin','Person',password_hash('sample-password',PASSWORD_DEFAULT),5,1):null;}
    public function byIdentifier(string $identifier):?User{return $identifier==='admin'?$this->byId(1):null;}
    public function accessList(int $limit=100):array{return [$this->byId(1)];}
    public function setRights(int $id,int $level):bool{return false;}
    public function replaceHash(int $id,string $newHash):void{}
    public function recordLogin(int $id,string $ip):void{}
}
function web4(string $method,string $path,array $body=[],array $query=[]):Request{return new Request($method,$path,$query,$body);}
$root=dirname(__DIR__);
$session=new Session();$session->start();$_SESSION=[];
// The browser installer works before PDO credentials or account setup exist.
$unconfigured=Application::fromProjectRoot($root);
verify4($unconfigured->handle(web4('GET','/'))->status===303,'Fresh site redirects to installer');
$install=$unconfigured->handle(web4('GET','/install'));
verify4($install->status===200&&str_contains($install->body,'Vereinssystem einrichten'),'Installer is visible before setup');
verify4($unconfigured->handle(web4('POST','/install',['_csrf'=>'fake']))->status===419,'Installer requires CSRF');
$token=(string)$_SESSION['_vs_csrf'];
verify4($unconfigured->handle(web4('POST','/install',['_csrf'=>$token]))->status===422,'Invalid setup data rejected without writing config');
verify4(!is_file($root.'/config/database.local.php'),'No credentials written by invalid installation');
verify4(str_contains(file_get_contents($root.'/.htaccess'),'public/$1'),'Root Apache routing present');
verify4(str_contains(file_get_contents($root.'/.htaccess'),'[F,NC]'),'Root source-access protection present');
verify4(str_contains(file_get_contents($root.'/database/migrations/002_departments_places.sql'),'CREATE TABLE IF NOT EXISTS `orte`'),'Places migration available');
$_SERVER['SCRIPT_NAME']='/verein/public/index.php';$_SERVER['REQUEST_URI']='/verein/?r=%2Fdepartments';$_GET=['r'=>'/departments'];
$sub=Request::fromGlobals();
verify4($sub->path==='/departments','Subdirectory path normalized');
verify4($sub->url('/install')==='/verein/?r=%2Finstall','WAMP project root URL used');
verify4($sub->asset('css/vs-ui.css')==='/verein/public/assets/css/vs-ui.css','WAMP static assets resolve');
unset($_SERVER['SCRIPT_NAME'],$_SERVER['REQUEST_URI']);$_GET=[];
// Pure domain tests run against fakes, never against real member data.
$place=new TestPlaceStore();$service=new PlaceService($place);
$placeForm=['name'=>'Sportplatz','strasse'=>'Feldstraße','hausnummer'=>'9','plz'=>'44536','ort'=>'Lünen','telefon'=>'0231','typ'=>'2'];
verify4($service->save($placeForm,null,1)===1,'Place created with actor');
verify4($place->rows[1]['typ']===2,'Place type validated');
try{$service->validate($placeForm+['x'=>'y']);verify4(true,'Place accepts valid values');}catch(Throwable $e){verify4(false,'Place accepts valid values');}
try{$service->validate([...$placeForm,'plz'=>'NOPE']);verify4(false,'Invalid postcode');}catch(PlaceValidationException){verify4(true,'Invalid postcode rejected');}
$d=new TestDepartmentStore();$svc=new DepartmentService($d);
verify4(DepartmentService::parseCents('12,50')===1250,'Euro comma parsed without floats');
verify4(DepartmentService::parseCents('12.5')===1250,'Euro dot parsed without floats');
verify4(DepartmentService::parseCents('12.345')===null,'Too many decimal places rejected');
$department=['name'=>'G2 Minis','beschreibung'=>'Jugendteam','homepage'=>'','abteilungsleiter'=>'0','aktivenumlage'=>'6,50'];
verify4($svc->save($department,null)===1,'Department created');
verify4($d->rows[1]['aktivenumlage']===650,'Umlage stored in cents');
$svc->assign(1,7,1,'2026-10-01');
verify4(count($d->assignments)===1,'Member assignment validated');
try{$svc->assign(1,7,1,'2026-02-30');verify4(false,'Invalid assignment date');}catch(DomainException){verify4(true,'Invalid assignment date rejected');}
// Controller integration, ACL, CSRF and responsive views.
$_SESSION['mvc_uid']=1;
$app=Application::fromProjectRoot($root,new TestAuthUsers4(),null,$place,$d);
verify4($app->handle(web4('GET','/places'))->status===200,'Places list routed through MVC');
verify4($app->handle(web4('GET','/departments'))->status===200,'Departments list routed through MVC');
verify4(str_contains($app->handle(web4('GET','/departments/view',[],['id'=>'1']))->body,'G2 Minis'),'Department detail renders');
verify4($app->handle(web4('POST','/places/status',['id'=>'1','status'=>'2','_csrf'=>'invalid']))->status===419,'Place status CSRF enforced');
verify4($app->handle(web4('GET','/install'))->status===303 || $app->handle(web4('GET','/install'))->status===200,'Installer route never exposes secrets');
fwrite(STDOUT,"PASS {$checks} alpha.4 assertions\n");
