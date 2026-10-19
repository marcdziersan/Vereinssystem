<?php
/**
 * Presentation layer for events and freely configurable tasks.
 * Access and CSRF gates are installed at route registration (middleware).
 * This controller only maps HTTP fields to services and renders views.
 */
declare(strict_types=1);
namespace Vereinssystem\Controllers;

use Vereinssystem\Auth\{AuthService,Roles,User};
use Vereinssystem\Core\{Csrf,DatabaseUnavailable,Request,Response,Session,View};
use Vereinssystem\Events\{EventRepository,EventValidationException};
use Vereinssystem\Services\{EventService,TaskService};

final class EventController extends Controller
{
    public function __construct(View $views, Csrf $csrf, private readonly ?AuthService $auth,
        private readonly ?EventRepository $events, private readonly Roles $roles, private readonly Session $session)
    { parent::__construct($views, $csrf); }

    private function user(): User
    { return $this->auth?->currentUser() ?? throw new \LogicException('Authentifizierungs-Middleware fehlt.'); }

    private function repository(): EventRepository
    { return $this->events ?? throw new DatabaseUnavailable('Veranstaltungsdatenbank nicht konfiguriert.'); }

    private static function id(string $value): int
    {
        $id = filter_var($value, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
        return is_int($id) ? $id : 0;
    }

    private function flash(): string
    {
        $message = (string)$this->session->get('events_flash', '');
        $this->session->forget('events_flash');
        return $message;
    }

    public function index(Request $request): Response
    {
        $user = $this->user();
        $showInactive = $user->rights === 5 && $request->queryString('inactive') === '1';
        return $this->page($request, 'Veranstaltungen', 'events/index', [
            'events'=>$this->repository()->listing($showInactive),
            'viewUrl'=>$request->url('/events/view'), 'createUrl'=>$request->url('/events/create'),
            'listUrl'=>$request->url('/events'), 'canCreate'=>$this->roles->allows($user, 'events.create'),
            'isAdmin'=>$user->rights === 5, 'inactive'=>$showInactive, 'flash'=>$this->flash(),
        ], $user);
    }

    public function show(Request $request): Response
    {
        $user = $this->user();
        $eventId = self::id($request->queryString('id'));
        $event = $this->repository()->find($eventId);
        if (!$event || ((int)$event['status'] !== 1 && $user->rights !== 5)) return $this->forbidden($request);
        $manager = $this->roles->allows($user, 'events.tasks.manage');
        return $this->page($request, 'Veranstaltung: '.$event['veranstaltungsname'], 'events/show', [
            'event'=>$event, 'tasks'=>$this->repository()->tasks($eventId),
            'manager'=>$manager, 'canEdit'=>$this->roles->allows($user, 'events.edit') && (int)$event['status'] === 1,
            'canStatus'=>$this->roles->allows($user, 'events.status'),
            'editUrl'=>$request->url('/events/edit').'&id='.$eventId,
            'backUrl'=>$request->url('/events'),
            'taskCreateUrl'=>$request->url('/events/tasks/create').'&event_id='.$eventId,
            'taskEditUrl'=>$request->url('/events/tasks/edit'),
            'taskArchiveUrl'=>$request->url('/events/tasks/archive'),
            'statusUrl'=>$request->url('/events/status'),
            'csrfToken'=>$this->csrf->token(), 'flash'=>$this->flash(),
        ], $user);
    }

    public function create(Request $request): Response
    { return $this->eventForm($request, $this->user(), null, [], []); }

    public function edit(Request $request): Response
    {
        $user = $this->user();
        $event = $this->repository()->find(self::id($request->queryString('id')));
        if (!$event || (int)$event['status'] !== 1) return $this->forbidden($request);
        $values = [
            'name'=>(string)$event['veranstaltungsname'], 'description'=>(string)$event['beschreibung'],
            'start'=>date('Y-m-d\TH:i', (int)$event['startzeit']), 'end'=>date('Y-m-d\TH:i', (int)$event['endzeit']),
            'place'=>(string)$event['ort'], 'contact'=>(string)$event['ansprechpartner'],
        ];
        return $this->eventForm($request, $user, $event, $values, []);
    }

    /** @param array<string,string> $values @param array<string,string> $errors */
    private function eventForm(Request $request, User $user, ?array $event, array $values, array $errors, int $status=200): Response
    {
        return $this->page($request, $event?'Veranstaltung bearbeiten':'Neue Veranstaltung', 'events/form', [
            'event'=>$event, 'values'=>$values, 'errors'=>$errors,
            'members'=>$this->repository()->activeMembers(), 'places'=>$this->repository()->activePlaces(),
            'csrfToken'=>$this->csrf->token(),
            'saveUrl'=>$request->url($event?'/events/update':'/events/store'), 'backUrl'=>$request->url('/events'),
        ], $user, $status);
    }

    /** @return array<string,string> */
    private static function eventFields(Request $request): array
    {
        $fields = [];
        foreach (['name','description','start','end','place','contact'] as $key) {
            $fields[$key] = $request->inputString($key);
        }
        return $fields;
    }

    public function save(Request $request): Response
    {
        $user = $this->user();
        $editing = $request->path === '/events/update';
        $id = $editing ? self::id($request->inputString('id')) : null;
        $event = $id ? $this->repository()->find($id) : null;
        if ($editing && (!$event || (int)$event['status'] !== 1)) return $this->forbidden($request);
        $values = self::eventFields($request);
        try {
            $saved = (new EventService($this->repository()))->save($values, $id);
            $this->session->set('events_flash', 'Veranstaltung gespeichert.');
            return Response::redirect($request->url('/events/view').'&id='.$saved);
        } catch (EventValidationException $e) {
            return $this->eventForm($request, $user, $event, $values, $e->errors, 422);
        } catch (\DomainException $e) {
            return $this->eventForm($request, $user, $event, $values, ['name'=>$e->getMessage()], 422);
        } catch (\PDOException $e) {
            error_log('[Vereinssystem/events] '.$e->getMessage());
            return $this->eventForm($request, $user, $event, $values, ['name'=>'Veranstaltung konnte nicht gespeichert werden.'], 422);
        }
    }

    public function status(Request $request): Response
    {
        $eventId = self::id($request->inputString('id'));
        $status = $request->inputString('status');
        if ($eventId > 0 && in_array($status, ['1','2'], true)) {
            $this->repository()->setStatus($eventId, (int)$status);
        }
        return Response::redirect($request->url('/events'));
    }

    public function createTask(Request $request): Response
    {
        $eventId = self::id($request->queryString('event_id'));
        $event = $this->repository()->find($eventId);
        if (!$event || (int)$event['status'] !== 1) return $this->forbidden($request);
        return $this->taskForm($request, $event, null, [], [], []);
    }

    public function editTask(Request $request): Response
    {
        $eventId = self::id($request->queryString('event_id'));
        $taskId = self::id($request->queryString('task_id'));
        $event = $this->repository()->find($eventId);
        $task = $this->repository()->task($eventId, $taskId);
        if (!$event || (int)$event['status'] !== 1 || !$task) return $this->forbidden($request);
        $values = [
            'title'=>(string)$task['titel'], 'description'=>(string)$task['beschreibung'],
            'place'=>(string)$task['ort'], 'start'=>date('Y-m-d\TH:i', (int)$task['startzeit']),
            'end'=>date('Y-m-d\TH:i', (int)$task['endzeit']),
        ];
        $members = array_map(static fn(array $m): int => (int)$m['id'], $task['members']);
        return $this->taskForm($request, $event, $task, $values, $members, []);
    }

    /** @param array<string,string> $values @param array<mixed> $selected @param array<string,string> $errors */
    private function taskForm(Request $request, array $event, ?array $task, array $values, array $selected,
        array $errors, int $status=200): Response
    {
        return $this->page($request, $task?'Aufgabe bearbeiten':'Aufgabe erstellen', 'events/task-form', [
            'event'=>$event, 'task'=>$task, 'values'=>$values, 'selected'=>$selected,
            'errors'=>$errors, 'members'=>$this->repository()->activeMembers(),
            'csrfToken'=>$this->csrf->token(), 'saveUrl'=>$request->url('/events/tasks/save'),
            'backUrl'=>$request->url('/events/view').'&id='.(int)$event['veranstaltungs_id'],
        ], $this->user(), $status);
    }

    public function saveTask(Request $request): Response
    {
        $eventId = self::id($request->inputString('event_id'));
        $taskId = self::id($request->inputString('task_id')) ?: null;
        $event = $this->repository()->find($eventId);
        if (!$event || (int)$event['status'] !== 1) return $this->forbidden($request);
        $values = [];
        foreach (['title','description','place','start','end'] as $key) $values[$key] = $request->inputString($key);
        $selected = $request->inputList('members');
        try {
            (new TaskService($this->repository()))->save($eventId, $taskId, $values, $selected, $this->user()->id);
            $this->session->set('events_flash', 'Aufgabe und Mitgliederzuordnung gespeichert.');
            return Response::redirect($request->url('/events/view').'&id='.$eventId);
        } catch (EventValidationException $e) {
            return $this->taskForm($request, $event, $taskId?$this->repository()->task($eventId,$taskId):null,
                $values, $selected, $e->errors, 422);
        } catch (\DomainException $e) {
            return $this->taskForm($request, $event, $taskId?$this->repository()->task($eventId,$taskId):null,
                $values, $selected, ['members'=>$e->getMessage()], 422);
        } catch (\PDOException $e) {
            error_log('[Vereinssystem/tasks] '.$e->getMessage());
            return $this->taskForm($request, $event, $taskId?$this->repository()->task($eventId,$taskId):null,
                $values, $selected, ['members'=>'Aufgabe konnte nicht gespeichert werden.'], 422);
        }
    }

    public function archiveTask(Request $request): Response
    {
        $eventId = self::id($request->inputString('event_id'));
        $taskId = self::id($request->inputString('task_id'));
        try {
            (new TaskService($this->repository()))->archive($eventId, $taskId);
            $this->session->set('events_flash', 'Aufgabe archiviert.');
        } catch (\DomainException $e) {
            $this->session->set('events_flash', 'Aktion nicht möglich: '.$e->getMessage());
        }
        return Response::redirect($request->url('/events/view').'&id='.$eventId);
    }
}
