<?php
/** Alpha 10.2 domain and route integration checks; never needs a live MySQL server. */
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
use Vereinssystem\Settings\{SettingsRepository,SettingsService};
use Vereinssystem\Auth\User;
use Vereinssystem\Core\{Application,Request,Session};
use Vereinssystem\Repositories\UserRepository;
$session=new Session();$session->start();$_SESSION=[];

function check102(bool $ok,string $message):void
{
    if(!$ok)throw new RuntimeException('FAIL: '.$message);
    echo 'OK: '.$message.PHP_EOL;
}
function denies102(callable $run,string $label):void
{
    try{$run();}catch(DomainException){check102(true,$label);return;}
    throw new RuntimeException('FAIL: '.$label);
}
final class SettingsStub102 implements SettingsRepository
{
    public array $data=[];
    public int $revision=0;
    public array $audit=[];
    public function load():array{return ['revision'=>$this->revision,'values'=>$this->data];}
    public function save(array $values,int $expectedRevision,int $actorId):int
    {
        if($expectedRevision!==$this->revision)throw new DomainException('Konflikt');
        $old=array_replace(SettingsService::DEFAULTS,$this->data);
        $delta=[];
        foreach($values as $key=>$value)if($old[$key]!==$value)$delta[$key]=['before'=>$old[$key],'after'=>$value];
        if(!$delta)return $this->revision;
        $this->revision++;
        $this->data=$values;
        $this->audit[]=['id'=>count($this->audit)+1,'actor_id'=>$actorId,'revision'=>$this->revision,
            'changes'=>$delta,'created_at'=>'2026-10-29 21:00:00'];
        return $this->revision;
    }
    public function history(int $limit=30,int $offset=0):array{return array_slice(array_reverse($this->audit),$offset,$limit);}
}
final class UsersStub102 implements UserRepository
{
    private array $users;
    public function __construct(){
        $this->users=[1=>new User(1,'admin','ADM1','Ada','Admin',password_hash('safe-password-123',PASSWORD_DEFAULT),5,1),
            2=>new User(2,'member','MEM2','Max','Mitglied',password_hash('safe-password-123',PASSWORD_DEFAULT),1,1)];
    }
    public function byIdentifier(string $identifier):?User{return null;}
    public function byId(int $id):?User{return $this->users[$id]??null;}
    public function accessList(int $limit=100):array{return array_values($this->users);}
    public function setRights(int $id,int $level):bool{return false;}
    public function replaceHash(int $id,string $newHash):void{}
    public function recordLogin(int $id,string $ip):void{}
}
$repository=new SettingsStub102();
$service=new SettingsService($repository);
$defaults=SettingsService::DEFAULTS;
check102($service->snapshot()['revision']===0,'Unconfigured defaults and revision 0');
$invalid=$defaults;$invalid['club_name']='A';
denies102(fn()=>SettingsService::validate($invalid),'Short club name rejected');
$invalid=$defaults;$invalid['club_website']='javascript:alert(1)';
denies102(fn()=>SettingsService::validate($invalid),'Unsafe website scheme rejected');
$invalid=$defaults;$invalid['mail_from_email']="hello@example.org\r\nBcc: victim@example.org";
denies102(fn()=>SettingsService::validate($invalid),'Mail header injection rejected');
$invalid=$defaults;$invalid['mail_transport']='smtp';
denies102(fn()=>SettingsService::validate($invalid),'SMTP requires host and sender');
$invalid=$defaults;$invalid['notify_events']='yes';
denies102(fn()=>SettingsService::validate($invalid),'Notification flag whitelist enforced');
$invalid=$defaults;$invalid['smtp_port']='0';
denies102(fn()=>SettingsService::validate($invalid),'Invalid SMTP port rejected');
$values=$defaults;$values['club_name']='Ein Verein & Freunde';
$values['mail_transport']='smtp';$values['smtp_host']='smtp.example.org';$values['mail_from_email']='mail@example.org';
$values['notify_events']='1';
check102($service->save($values,0,1)===1,'Save revision 1');
check102($service->snapshot()['values']['club_name']==='Ein Verein & Freunde','Saved fields read back');
check102(count($service->history())===1 && !isset($service->snapshot()['values']['smtp_password']),'No password stored');
check102($service->save($values,1,1)===1 && count($service->history())===1,'No-op does not generate audit');
denies102(fn()=> $service->save($values,0,1),'Stale revision rejected');
$next=$values;$next['club_name']='<script>alert(1)</script> & Team';
check102($service->save($next,1,1)===2,'Second revision saved');
check102(count($service->history())===2,'Audit records both edits');

$app=Application::fromProjectRoot(dirname(__DIR__),new UsersStub102(),null,null,null,null,null,null,null,null,$repository);
$req=static fn(string $method,string $path,array $post=[]):Request=>new Request($method,$path,[],$post,'/index.php','192.0.2.111');
check102($app->handle($req('GET','/admin/settings'))->status===303,'Guest redirected to login');
$_SESSION['mvc_uid']=2;
check102($app->handle($req('GET','/admin/settings'))->status===403,'Member settings view forbidden');
check102($app->handle($req('GET','/admin/settings/logs'))->status===403,'Member audit forbidden');
check102($app->handle($req('POST','/admin/settings/save'))->status===403,'Member settings write forbidden');
$_SESSION['mvc_uid']=1;
$view=$app->handle($req('GET','/admin/settings'));
check102($view->status===200 && str_contains($view->body,'Systemeinstellungen'),'Administrator can open configuration');
check102(!str_contains($view->body,'<script>alert(1)</script>'),'HTML-escaping of configuration fields');
$csrf=(string)$_SESSION['_vs_csrf'];
check102($app->handle($req('POST','/admin/settings/save',['_csrf'=>'bad']))->status===419,'CSRF blocks update');
$posted=$next;$posted['club_name']='Neuer Verein';$posted['revision']='2';$posted['_csrf']=$csrf;
check102($app->handle($req('POST','/admin/settings/save',$posted))->status===303,'Valid admin POST redirects');
check102($repository->revision===3 && $repository->load()['values']['club_name']==='Neuer Verein','POST reaches repository');
$posted['revision']='2';
check102($app->handle($req('POST','/admin/settings/save',$posted))->status===303 && $repository->revision===3,'Revision conflict prevents overwrite');
$log=$app->handle($req('GET','/admin/settings/logs'));
check102($log->status===200 && str_contains($log->body,'Änderungsprotokoll'),'Administrator may read audit log');
check102($app->handle($req('GET','/admin/settings/save'))->status===405,'Save only accepts POST');
check102($app->handle($req('POST','/admin/settings/logs'))->status===405,'Audit only accepts GET');
echo "PASS: Alpha 10.2 settings, validation, audit and HTTP checks.\n";
