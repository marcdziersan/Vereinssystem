<?php
/** Bulletin business policy: visibility, ownership, input validation and state. */
declare(strict_types=1);
namespace Vereinssystem\Services;

use Vereinssystem\Auth\User;
use Vereinssystem\Models\NewsPost;
use Vereinssystem\News\{NewsRepository,NewsValidationException};

final class NewsService
{
    public function __construct(private readonly NewsRepository $news) {}

    public function canRead(User $viewer,NewsPost $post): bool
    { return $viewer->active() && $post->status===1 && $viewer->rights >= $post->minRight; }

    public function canEdit(User $editor,NewsPost $post): bool
    { return $editor->active() && $editor->rights>=4 && ($editor->rights===5 || $post->authorId===$editor->id); }

    /** @param array<string,string> $fields */
    public function validate(array $fields,User $actor,?NewsPost $existing=null): NewsPost
    {
        $title=trim($fields['title']??'');
        $content=trim(str_replace(["\r\n","\r"],"\n",$fields['content']??''));
        $right=filter_var($fields['minright']??'1',FILTER_VALIDATE_INT);
        $errors=[];
        if($title==='' || self::size($title)>180)$errors['title']='Titel mit 1 bis 180 Zeichen erforderlich.';
        if($content==='' || self::size($content)>20000)$errors['content']='Text mit 1 bis 20.000 Zeichen erforderlich.';
        if($right===false || $right<1 || $right>5)$errors['minright']='Freigabestufe muss zwischen 1 und 5 liegen.';
        // Never allow a user to publish material above their own clearance.
        if($right!==false && $right>$actor->rights)$errors['minright']='Freigabe darf die eigene Rechtestufe nicht übersteigen.';
        if($errors)throw new NewsValidationException($errors);
        $now=time();
        return new NewsPost($existing?->id,$title,$content,(int)$right,
            $existing?->authorId ?? $actor->id,$existing?->createdAt??$now,$now,$existing?->status??1);
    }

    /** @param array<string,string> $fields */
    public function save(array $fields,User $actor,?int $id): int
    {
        if(!$actor->active() || $actor->rights<4)throw new \DomainException('Keine Bearbeitungsrechte.');
        $previous=$id===null?null:$this->news->find($id);
        if($id!==null && (!$previous || $previous->status!==1 || !$this->canEdit($actor,$previous)))
            throw new \DomainException('Nachricht nicht bearbeitbar.');
        return $this->news->save($this->validate($fields,$actor,$previous));
    }

    public function archive(int $id,User $actor): void
    {
        $item=$this->news->find($id);
        if(!$item || $item->status!==1 || !$this->canEdit($actor,$item))throw new \DomainException('Nachricht nicht archivierbar.');
        if(!$this->news->archive($id,$actor->id,$actor->rights===5))throw new \DomainException('Nachricht bereits archiviert.');
    }

    public function restore(int $id,User $actor): void
    {
        if(!$actor->active() || $actor->rights!==5)throw new \DomainException('Nur Administration kann archivierte Meldungen wiederherstellen.');
        $post=$this->news->find($id);
        if(!$post || $post->status!==2 || !$this->news->restore($id))throw new \DomainException('Nachricht nicht wiederherstellbar.');
    }

    private static function size(string $value): int
    { return function_exists('mb_strlen')?mb_strlen($value,'UTF-8'):strlen($value); }
}
