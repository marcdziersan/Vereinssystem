<?php
/** Native PDO persistence for the original news table. Never compose user SQL. */
declare(strict_types=1);
namespace Vereinssystem\News;
use PDO;
use Vereinssystem\Core\Database;
use Vereinssystem\Models\NewsPost;

final class PdoNewsRepository implements NewsRepository
{
    public function __construct(private readonly Database $database) {}

    public function listing(int $rights,string $search='',int $page=1,int $perPage=15): array
    {
        $page=max(1,$page);$limit=max(1,min(50,$perPage));$skip=($page-1)*$limit;
        // Archive visibility is handled by separate editor lookup, never mixed in public listing.
        $sql='SELECT n.news_id,n.title,n.content,n.minright,n.author,n.created,n.lastchange,n.status,
                    COALESCE(m.vorname,\'\') AS author_first, COALESCE(m.nachname,\'\') AS author_last
              FROM news n LEFT JOIN mitglieder m ON m.mitglieder_id=n.author
              WHERE n.status=1 AND n.minright<=:rights';
        if($search!=='')$sql.=' AND (n.title LIKE :search_title OR n.content LIKE :search_content)';
        $sql.=' ORDER BY n.created DESC,n.news_id DESC LIMIT :lim OFFSET :skip';
        $stmt=$this->database->connection()->prepare($sql);
        $stmt->bindValue(':rights',$rights,PDO::PARAM_INT);
        if($search!==''){
            $needle='%'.str_replace(['\\','%','_'],['\\\\','\\%','\\_'],$search).'%';
            // MariaDB/MySQL LIKE escaping with explicit ESCAPE clause for literal wildcard search.
            $stmt->bindValue(':search_title',$needle,PDO::PARAM_STR);
            $stmt->bindValue(':search_content',$needle,PDO::PARAM_STR);
        }
        $stmt->bindValue(':lim',$limit,PDO::PARAM_INT);$stmt->bindValue(':skip',$skip,PDO::PARAM_INT);
        $stmt->execute();return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countVisible(int $rights,string $search=''): int
    {
        $sql='SELECT COUNT(*) FROM news WHERE status=1 AND minright<=?';
        $args=[$rights];
        if($search!==''){
            $sql.=' AND (title LIKE ? OR content LIKE ?)';
            $needle='%'.str_replace(['\\','%','_'],['\\\\','\\%','\\_'],$search).'%';
            $args[]=$needle;$args[]=$needle;
        }
        $stmt=$this->database->connection()->prepare($sql);$stmt->execute($args);
        return (int)$stmt->fetchColumn();
    }

    public function find(int $id): ?NewsPost
    {
        $stmt=$this->database->connection()->prepare('SELECT * FROM news WHERE news_id=?');
        $stmt->execute([$id]);$record=$stmt->fetch(PDO::FETCH_ASSOC);
        return $record?NewsPost::fromRow($record):null;
    }

    public function authorName(int $id): string
    {
        $stmt=$this->database->connection()->prepare('SELECT CONCAT(TRIM(vorname),\' \',TRIM(nachname)) FROM mitglieder WHERE mitglieder_id=?');
        $stmt->execute([$id]);return trim((string)($stmt->fetchColumn()?:'Vereinsverwaltung'));
    }

    public function save(NewsPost $post): int
    {
        $pdo=$this->database->connection();
        if($post->id===null){
            $stmt=$pdo->prepare('INSERT INTO news (title,content,minright,author,created,lastchange,status) VALUES (?,?,?,?,?,?,1)');
            $stmt->execute([$post->title,$post->content,$post->minRight,$post->authorId,$post->createdAt,$post->changedAt]);
            return (int)$pdo->lastInsertId();
        }
        // Administrator can edit other authors, so actor authorization is handled
        // by the service. Do not require matching author here.
        $stmt=$pdo->prepare('UPDATE news SET title=?,content=?,minright=?,lastchange=? WHERE news_id=? AND status=1');
        $stmt->execute([$post->title,$post->content,$post->minRight,$post->changedAt,$post->id]);
        if($stmt->rowCount()===0){
            $check=$this->find($post->id);
            if(!$check || $check->status!==1)throw new \DomainException('Nachricht wurde inzwischen archiviert.');
        }
        return $post->id;
    }

    public function archive(int $id,int $editorId,bool $isAdmin): bool
    {
        $sql='UPDATE news SET status=2,lastchange=? WHERE news_id=? AND status=1';
        if(!$isAdmin)$sql.=' AND author=?';
        $args=[time(),$id];if(!$isAdmin)$args[]=$editorId;
        $s=$this->database->connection()->prepare($sql);$s->execute($args);
        return $s->rowCount()===1;
    }
    public function archived(int $limit=100): array
    {
        $stmt=$this->database->connection()->prepare('SELECT * FROM news WHERE status=2 ORDER BY lastchange DESC, news_id DESC LIMIT ?');
        $stmt->bindValue(1,max(1,min(200,$limit)),PDO::PARAM_INT);$stmt->execute();
        return array_map(static fn(array $row):NewsPost=>NewsPost::fromRow($row),$stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function restore(int $id): bool
    {
        $s=$this->database->connection()->prepare('UPDATE news SET status=1,lastchange=? WHERE news_id=? AND status=2');
        $s->execute([time(),$id]);return $s->rowCount()===1;
    }
}
