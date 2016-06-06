<?php


class Model {
  /**
   * database var
   * @var PDO
   */
  private $db;

  /**
   * Pass the $db object from app object
   * @param $db
   */

  function __construct($db) {
    $this->db = $db;
  }

  /**
   * Get single article
   */
  public function getArticle($id) {
    $sql  = "SELECT id, title, url, blurb, body FROM pages ";
    $sql .= " WHERE id = :id";
    $query = $this->db->prepare($sql);
    $params = array(':id' => $id);
    $query->execute($params);
    return $query->fetchAll();
  }

  /**
   * Get list of current articles
   */
  public function getAllArticles() {
    $sql = "SELECT id, title FROM pages";
    $query = $this->db->prepare($sql);
    $query->execute();
    return $query->fetchAll();
  }

  /**
   * Add new article
   */
  public function addNewArticle($title, $url, $blurb, $body) {
    $sql  = "INSERT INTO pages (title, url, blurb, body) ";
    $sql .= "VALUES (:title, :url, :blurb, :body)";
    $query = $this->db->prepare($sql);
    $params = array(':title' => $title, ':url' => $url, ':blurb' => $blurb, ':body' => $body);
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
  public function updateArticle($id, $title, $url, $blurb, $body) {
    $sql  = "UPDATE pages SET ";
    $sql .= "title = :title, ";
    $sql .= "url = :url, ";
    $sql .= "blurb = :blurb, ";
    $sql .= "body = :body ";
    $sql .= "WHERE id = :id ";
    $query = $this->db->prepare($sql);
    $params = array(
      ':id' => $id, ':title' => $title,
      ':url' => $url, ':blurb' => $blurb,
      ':body' => $body);
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
