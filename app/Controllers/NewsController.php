<?php
/** MVC bulletin controller: HTTP mapping only. Visibility lives in NewsService. */
declare(strict_types=1);
namespace Vereinssystem\Controllers;

use Vereinssystem\Auth\{AuthService,Roles,User};
use Vereinssystem\Core\{Csrf,DatabaseUnavailable,Request,Response,Session,View};
use Vereinssystem\News\{NewsRepository,NewsValidationException};
use Vereinssystem\Services\NewsService;
use Vereinssystem\Models\NewsPost;

final class NewsController extends Controller
{
    public function __construct(View $views,Csrf $csrf,private readonly ?AuthService $auth,
        private readonly ?NewsRepository $repo,private readonly Roles $roles,private readonly Session $session)
    { parent::__construct($views,$csrf); }

    private function user(): User
    { return $this->auth?->currentUser()??throw new \LogicException('Authentication middleware missing.'); }
    private function repository(): NewsRepository
    { return $this->repo??throw new DatabaseUnavailable('Nachrichtendatenbank nicht verfügbar.'); }
    private function service(): NewsService { return new NewsService($this->repository()); }
    private static function positive(string $input): int
    { $n=filter_var($input,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);return is_int($n)?$n:0; }
    private function flash(): string
    { $message=(string)$this->session->get('news_flash','');$this->session->forget('news_flash');return $message; }

    public function index(Request $request): Response
    {
        $viewer=$this->user();$search=trim($request->queryString('q'));
        if((function_exists('mb_strlen')?mb_strlen($search):strlen($search))>100)$search=substr($search,0,100);
        $page=min(10000,max(1,self::positive($request->queryString('page','1'))));
        $total=$this->repository()->countVisible($viewer->rights,$search);
        $pages=max(1,(int)ceil($total/15));$page=min($page,$pages);
        return $this->page($request,'Vereinsnachrichten','news/index',[
            'posts'=>$this->repository()->listing($viewer->rights,$search,$page),
            'search'=>$search,'pageNumber'=>$page,'pageCount'=>$pages,'total'=>$total,
            'indexUrl'=>$request->url('/news'),'showUrl'=>$request->url('/news/view'),
            'createUrl'=>$request->url('/news/create'),
            'canCreate'=>$this->roles->allows($viewer,'news.create'),
            'archiveUrl'=>$request->url('/news/archived'),
            'isAdmin'=>$viewer->rights===5,'flash'=>$this->flash(),
        ],$viewer);
    }

    public function show(Request $request): Response
    {
        $user=$this->user();$id=self::positive($request->queryString('id'));
        $post=$this->repository()->find($id);
        if(!$post || !$this->service()->canRead($user,$post))return $this->forbidden($request);
        return $this->page($request,$post->title,'news/show',[
            'post'=>$post,'author'=>$this->repository()->authorName($post->authorId),
            'canEdit'=>$this->service()->canEdit($user,$post),
            'showUrl'=>$request->url('/news/view'),'indexUrl'=>$request->url('/news'),
            'editUrl'=>$request->url('/news/edit').'&id='.$post->id,
            'archiveUrl'=>$request->url('/news/archive'),'csrfToken'=>$this->csrf->token(),
        ],$user);
    }

    public function archived(Request $request): Response
    {
        $user=$this->user();
        return $this->page($request,'Nachrichtenarchiv','news/archived',[
            'posts'=>$this->repository()->archived(),
            'csrfToken'=>$this->csrf->token(),
            'restoreUrl'=>$request->url('/news/restore'),
            'indexUrl'=>$request->url('/news'),
            'flash'=>$this->flash(),
        ],$user);
    }

    public function restore(Request $request): Response
    {
        try {
            $this->service()->restore(self::positive($request->inputString('id')),$this->user());
            $this->session->set('news_flash','Nachricht wiederhergestellt.');
        } catch(\DomainException $e) {$this->session->set('news_flash',$e->getMessage());}
        return Response::redirect($request->url('/news/archived'));
    }

    public function create(Request $request): Response
    { return $this->form($request,null,[],[]); }
    public function edit(Request $request): Response
    {
        $post=$this->repository()->find(self::positive($request->queryString('id')));
        if(!$post || !$this->service()->canEdit($this->user(),$post) || $post->status!==1)return $this->forbidden($request);
        return $this->form($request,$post,['title'=>$post->title,'content'=>$post->content,'minright'=>(string)$post->minRight],[]);
    }

    /** @param array<string,string> $values @param array<string,string> $errors */
    private function form(Request $request,?NewsPost $post,array $values,array $errors,int $status=200): Response
    {
        return $this->page($request,$post?'Nachricht bearbeiten':'Neue Nachricht','news/form',[
            'post'=>$post,'values'=>$values,'errors'=>$errors,'csrfToken'=>$this->csrf->token(),
            'userRights'=>$this->user()->rights,'indexUrl'=>$request->url('/news'),
            'saveUrl'=>$request->url($post?'/news/update':'/news/store'),
        ],$this->user(),$status);
    }

    public function save(Request $request): Response
    {
        $editing=$request->path==='/news/update';
        $id=$editing?self::positive($request->inputString('id')):null;
        $post=$id?$this->repository()->find($id):null;
        if($editing && (!$id || !$post || !$this->service()->canEdit($this->user(),$post) || $post->status!==1))
            return $this->forbidden($request);
        $values=[];foreach(['title','content','minright'] as $field)$values[$field]=$request->inputString($field);
        try {
            $saved=$this->service()->save($values,$this->user(),$id);
            $this->session->set('news_flash','Nachricht gespeichert.');
            return Response::redirect($request->url('/news/view').'&id='.$saved);
        } catch(NewsValidationException $e) {
            return $this->form($request,$post,$values,$e->errors,422);
        } catch(\DomainException $e){
            return $this->form($request,$post,$values,['title'=>$e->getMessage()],422);
        } catch(\PDOException $e){
            error_log('[MVC/news] '.$e->getMessage());
            return $this->form($request,$post,$values,['title'=>'Nachricht konnte nicht gespeichert werden.'],503);
        }
    }

    public function archive(Request $request): Response
    {
        try {
            $this->service()->archive(self::positive($request->inputString('id')),$this->user());
            $this->session->set('news_flash','Nachricht archiviert.');
        } catch(\DomainException $e){$this->session->set('news_flash',$e->getMessage());}
        return Response::redirect($request->url('/news'));
    }
}
