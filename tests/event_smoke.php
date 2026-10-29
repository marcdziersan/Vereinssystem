<?php
/**
 * Alpha.5 contract tests for Models, Services, Middleware and HTTP routes.
 * Database integration is deliberately separate: use bin/migrate-events.php on WAMP.
 */
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';

use Vereinssystem\Auth\{Roles,User};
use Vereinssystem\Core\{Application,Csrf,Request,Response,Session};
use Vereinssystem\Events\{EventRepository,EventValidationException};
use Vereinssystem\Models\{Event,EventTask,TaskAssignment};
use Vereinssystem\Services\{EventService,TaskService};
use Vereinssystem\Http\Middleware\{Stack,Middleware,Authenticated,Permission,VerifyCsrf};
use Vereinssystem\Repositories\UserRepository;

$passed = 0;
function check(bool $ok,string $label): void
{
    global $passed;
    if (!$ok) throw new RuntimeException('FAIL '.$label);
    ++$passed;
}
function invalid(callable $callback): bool
{
    try { $callback(); return false; }
    catch (DomainException|EventValidationException $e) { return true; }
}

/** In-memory implementation honors the same repository contract; no SQL is faked. */
final class SampleEvents implements EventRepository
{
    public array $records = [];
    public array $tasksList = [];
    public array $people = [['id'=>1,'name'=>'Erika Beispiel'],['id'=>2,'name'=>'Max Muster']];
    public function listing(bool $includeInactive=false): array
    {
        $result=[];
        foreach ($this->records as $item) {
            if (!$includeInactive && (int)$item['status']!==1) continue;
            $result[]=$item+['task_count'=>count($this->tasksList),'ortsname'=>'Sportplatz'];
        }
        return $result;
    }
    public function find(int $id): ?array { return $this->records[$id]??null; }
    public function save(Event $event): int
    {
        $id=$event->id ?? count($this->records)+1;
        $this->records[$id]=$event->values()+['status'=>1,'veranstaltungs_id'=>$id];
        return $id;
    }
    public function setStatus(int $id,int $status): bool
    { if(!isset($this->records[$id]))return false;$this->records[$id]['status']=$status;return true; }
    public function tasks(int $eventId): array
    { return array_values(array_filter($this->tasksList,static fn(array $a):bool=>$a['event_id']===$eventId&&$a['status']===1)); }
    public function task(int $eventId,int $taskId): ?array
    { foreach($this->tasks($eventId) as $task)if($task['id']===$taskId)return $task;return null; }
    public function activeMembers(): array {return $this->people;}
    public function activePlaces(): array {return [['id'=>1,'name'=>'Vereinsheim']];}
    public function saveTask(EventTask $task,int $actorId): int
    {
        foreach($task->memberIds() as $id){
            if(!in_array($id,array_column($this->people,'id'),true))throw new DomainException('Mitglied inaktiv.');
            foreach($this->tasksList as $old){
                if($old['status']!==1||$old['id']===$task->id)continue;
                foreach($old['members'] as $member)if($member['id']===$id&&$old['startzeit']<$task->endsAt&&$old['endzeit']>$task->startsAt)
                    throw new DomainException('Doppelbelegung.');
            }
        }
        $id=$task->id??count($this->tasksList)+1;
        $this->tasksList[$id]=[
            'id'=>$id,'event_id'=>$task->eventId,'titel'=>$task->title,
            'beschreibung'=>$task->description,'ort'=>$task->place,
            'startzeit'=>$task->startsAt,'endzeit'=>$task->endsAt,'status'=>1,
            'members'=>array_map(static fn($member)=>['id'=>$member,'name'=>$member===1?'Erika Beispiel':'Max Muster'],$task->memberIds()),
        ];
        return $id;
    }
    public function archiveTask(int $eventId,int $taskId): void
    {
        if(!isset($this->tasksList[$taskId])||$this->tasksList[$taskId]['event_id']!==$eventId)throw new DomainException('Unbekannt.');
        $this->tasksList[$taskId]['status']=2;
    }
}

$repo=new SampleEvents();$events=new EventService($repo);$tasks=new TaskService($repo);
$eventFields=['name'=>'Sommerfest','description'=>'Gemeinsam feiern','start'=>'2027-06-01T10:00',
    'end'=>'2027-06-02T02:00','place'=>'1','contact'=>'1'];
check($events->save($eventFields,null)===1,'Event insert');
check($events->save($eventFields,1)===1,'Event update');
check($events->validate($eventFields) instanceof Event,'Event model created by service');
check(EventService::toTimestamp('2027-03-28T02:30')===null,'DST missing time rejected');
check(EventService::toTimestamp('2027-06-01T10:00')!==null,'Time conversion');
check(invalid(fn()=> $events->save($eventFields+['unused'=>''],123)),'Unknown event');
$bad=$eventFields;$bad['name']='';check(invalid(fn()=> $events->save($bad,null)),'Empty event name');
$bad=$eventFields;$bad['end']='2027-05-01T10:00';check(invalid(fn()=> $events->save($bad,null)),'Reversed event date');
$taskFields=['title'=>'Platzaufbau','description'=>'Tische tragen','place'=>'Vereinsheim',
    'start'=>'2027-06-01T11:00','end'=>'2027-06-01T13:00'];
check($tasks->save(1,null,$taskFields,['1','2','1'],1)===1,'Multi-member task saved');
check(count($repo->tasks(1)[0]['members'])===2,'Duplicate selection normalized');
check($tasks->validate(1,null,$taskFields,[1]) instanceof EventTask,'Task domain model');
check((new TaskAssignment(1))->memberId===1,'Assignment domain model');
$bad=$taskFields;$bad['place']='';check(invalid(fn()=> $tasks->save(1,null,$bad,[1],1)),'Task location required');
$bad=$taskFields;$bad['end']='2027-06-01T10:00';check(invalid(fn()=> $tasks->save(1,null,$bad,[1],1)),'Task time bounds');
check(invalid(fn()=> $tasks->save(1,null,$taskFields,[['bad']],1)),'Nested member IDs rejected');
check(invalid(fn()=> $tasks->save(1,null,$taskFields,[999],1)),'Unknown member rejected');
check(invalid(fn()=> $tasks->save(1,null,$taskFields,[1],1)),'Member overlap rejected');
$taskFields['start']='2027-06-01T13:00';$taskFields['end']='2027-06-01T15:00';
check($tasks->save(1,null,$taskFields,[1],1)===2,'Adjacent times allowed');
check($tasks->save(1,2,$taskFields,[2],1)===2,'Existing task edits assignments');
check($repo->task(1,2)['members'][0]['id']===2,'Assignment replacement');
$tasks->archive(1,2);check($repo->task(1,2)===null,'Archive hides task');
check($repo->tasks(1)[0]['id']===1,'Remaining task keeps its ID');
check(count($repo->tasks(1))===1,'Other task still active');
check(Request::normalizePath('/events/tasks/create')==='/events/tasks/create','Route parser');
check((new Request('POST','/events/tasks/save',[],['members'=>['1','2']]))->inputList('members')===['1','2'],'Multi-select request');
check(Roles::permissions()['events.tasks.manage']===4,'Dedicated task permission');

final class SampleUsers implements UserRepository
{
    public function byId(int $id): ?User
    {
        if(!in_array($id,[1,2],true))return null;
        static $hash=null;$hash??=password_hash('testpassword123456',PASSWORD_DEFAULT);
        return new User($id,'test'.$id,'T-'.$id,'Test','Konto',$hash,$id===1?5:1,1);
    }
    public function byIdentifier(string $identifier): ?User {return $this->byId(1);}
    public function accessList(int $limit=100): array {return [$this->byId(1),$this->byId(2)];}
    public function setRights(int $id,int $level): bool {return false;}
    public function replaceHash(int $id,string $newHash): void {}
    public function recordLogin(int $id,string $ip): void {}
}
$session=new Session();$session->start();$_SESSION=[];
$app=Application::fromProjectRoot(dirname(__DIR__),new SampleUsers(),null,null,null,$repo);
$get=static fn(string $path,array $query=[]):Request=>new Request('GET',$path,$query);
$post=static fn(string $path,array $data=[]):Request=>new Request('POST',$path,[],$data);
check($app->handle($get('/events'))->status===303,'Anonymous redirect');
$_SESSION['mvc_uid']=2;
check($app->handle($get('/events'))->status===200,'Member event listing');
check($app->handle($get('/events/view',['id'=>'1']))->status===200,'Member task details');
check($app->handle($get('/events/create'))->status===403,'Member cannot create events');
check($app->handle($get('/events/tasks/create',['event_id'=>'1']))->status===403,'Member cannot create tasks');
check($app->handle($post('/events/tasks/save'))->status===403,'Permission checked before CSRF');
$_SESSION['mvc_uid']=1;
check($app->handle($get('/events/create'))->status===200,'Admin event form');
check($app->handle($get('/events/tasks/create',['event_id'=>'1']))->status===200,'Admin task form');
check($app->handle($post('/events/tasks/save'))->status===419,'CSRF middleware');
$token=(string)$_SESSION['_vs_csrf'];
$bad=$taskFields;$bad['title']='';
check($app->handle($post('/events/tasks/save',['_csrf'=>$token,'event_id'=>'1']+$bad))->status===422,'Task form validation');
$taskFields['title']='Getränkeausgabe';$taskFields['start']='2027-06-01T16:00';$taskFields['end']='2027-06-01T19:00';
check($app->handle($post('/events/tasks/save',['_csrf'=>$token,'event_id'=>'1','members'=>['1','2']]+$taskFields))->status===303,'MVC task and members saved');
check($app->handle($get('/events/view',['id'=>'1']))->status===200,'Task visible after save');
check($app->handle($post('/events/tasks/archive',['_csrf'=>$token,'event_id'=>'1','task_id'=>'3']))->status===303,'Archive MVC route');
check($app->handle($post('/events/status',['_csrf'=>'bad','id'=>'1','status'=>'2']))->status===419,'Status CSRF rejected');
check(is_file(dirname(__DIR__).'/.htaccess'),'WAMP root rewrite');
$schema=file_get_contents(dirname(__DIR__).'/database/migrations/003_events_tasks.sql');
check(preg_match_all('/^CREATE TABLE IF NOT EXISTS/m',$schema)===3,'Three event schema tables');
check(!str_contains(file_get_contents(dirname(__DIR__).'/resources/views/events/show.php'),'Standdienst'),'No standing duties');
check(!str_contains(file_get_contents(dirname(__DIR__).'/resources/views/events/show.php'),'Kuchen'),'No cake duty');
echo "PASS $passed alpha.5 event/task assertions\n";
