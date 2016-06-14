<?php

namespace Neutrino\Model;

class Article {
  /**
   * database var
   * @var PDO
   * @var Parsedown
   */
  private $db;
  private $parse;

  /**
   * Pass the $db object from app object
   * @param $db
   */

  function __construct($db, $parse) {
    $this->db = $db;
    $this->parse = $parse;
  }

  /**
   * Get single article by id
   */
  public function getArticleByUrl($url) {
    $sql  = "SELECT id, title, url, blurb, body FROM pages ";
    $sql .= " WHERE url = :url";
    $query = $this->db->prepare($sql);
    $params = array(':url' => $url);
    $query->execute($params);
    return $query->fetchAll();
  }

  /**
   * Get single article by id
   */
  public function getArticleById($id) {
    $sql  = "SELECT id, title, url, blurb, body, published FROM pages ";
    $sql .= " WHERE id = :id";
    $query = $this->db->prepare($sql);
    $params = array(':id' => $id);
    $query->execute($params);
    return $query->fetchAll();
  }

  /**
   * Get list of current articles
   * Optionally slice each article body, keep beginning
   */
  public function getAllArticles($only_published = false, $slice = null) {
    $sql = "SELECT id, blurb, title, body, url FROM pages";
    if ($only_published) {$sql .= " WHERE published = TRUE";}
    $query = $this->db->prepare($sql);
    $query->execute();
    $all_articles = $query->fetchAll();
    // Parse article body from Markdown to HTML
    foreach ($all_articles as &$art) {
      if ($slice) {$art->body = substr($art->body, 0, $slice);}
      $art->body = $this->parse->text($art->body);
    }
    return $all_articles;
  }

  /**
   * Add new article
   */
  public function addNewArticle($title, $url, $blurb, $body, $published) {
    $sql  = "INSERT INTO pages (title, url, blurb, body, dt_created) ";
    $sql .= "VALUES (:title, :url, :blurb, :body, :published, NOW())";
    $query = $this->db->prepare($sql);
    $params = array(
      ':title' => $title,
      ':url' => $url,
      ':blurb' => $blurb,
      ':body' => $body,
      ':published' => $published);
    try {
      $query->execute($params);
    }
    catch(PDOException $e) {
      return $e->getMessage();
    }
    return true;
  }

  /**
   * Add new article
   */
  public function updateArticle($id, $title, $url, $blurb, $body, $published) {
    $sql  = "UPDATE pages SET ";
    $sql .= "title = :title, ";
    $sql .= "url = :url, ";
    $sql .= "blurb = :blurb, ";
    $sql .= "body = :body, ";
    $sql .= "published = :published ";
    $sql .= "WHERE id = :id ";
    $query = $this->db->prepare($sql);
    $params = array(
      ':id' => $id, ':title' => $title,
      ':url' => $url, ':blurb' => $blurb,
      ':body' => $body, ':published' => $published
    );
    try {
      $query->execute($params);
    }
    catch(PDOException $e) {
      return $e->getMessage() . $sql;
    }
    return true;
  }

  /**
   * Delete article
   */
  public function deleteArticle($id) {
    $sql  = "DELETE FROM pages WHERE id = :id ";
    $query = $this->db->prepare($sql);
    $params = array(':id' => $id);
    try {
      $query->execute($params);
    }
    catch(PDOException $e) {
      return $e->getMessage();
    }
    return true;
  }
}
