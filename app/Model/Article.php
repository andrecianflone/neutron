<?php

namespace Neutron\Model;

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
    $sql  = "SELECT id, title, url, blurb, body, parse_math FROM pages ";
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
    $sql  = "SELECT id, title, url, blurb, body, published, parse_math FROM pages ";
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
  public function getAllArticles($only_published=false, $slice=null, $order=null) {
    $sql = "SELECT id, blurb, title, body, url, parse_math FROM pages";
    if ($only_published) {$sql .= " WHERE url like 'setup_dl_machine'";}
    if ($order) {$sql .= " ORDER BY " . $order;}
    //if ($only_published) {$sql .= " WHERE published = TRUE";}
    $query = $this->db->prepare($sql);
    $query->execute();
    $all_articles = $query->fetchAll();
    // Parse article body from Markdown to HTML and slice
    foreach ($all_articles as &$art) {
      $art->body = $this->parse->text($art->body);
      if ($slice) {$art->body = $this->truncateHtml($art->body, $slice);}
    }
    return $all_articles;
  }

  /**
   * Add new article
   */
  public function addNewArticle($title, $url, $blurb, $body, $published, $parse_math) {
    $sql  = "INSERT INTO pages (title, url, blurb, body, published, parse_math, dt_created) ";
    $sql .= "VALUES (:title, :url, :blurb, :body, :published, :parse_math, NOW())";
    $query = $this->db->prepare($sql);
    $params = array(
      ':title'     => $title,
      ':url'       => $url,
      ':blurb'     => $blurb,
      ':body'      => $body,
      ':published' => $published,
      ':parse_math'=> $parse_math);
    try {
      $query->execute($params);
    }
    catch(PDOException $e) {
      return $e->getMessage();
    }
    catch(Exception $e) {
      return $e->getMessage();
    }
    return true;
  }

  /**
   * Update article
   */
  public function updateArticle($id, $title, $url, $blurb, $body, $published, $parse_math) {
    $sql  = "UPDATE pages SET ";
    $sql .= "title = :title, ";
    $sql .= "url = :url, ";
    $sql .= "blurb = :blurb, ";
    $sql .= "body = :body, ";
    $sql .= "published = :published, ";
    $sql .= "parse_math = :parse_math ";
    $sql .= "WHERE id = :id ";
    $query = $this->db->prepare($sql);
    $params = array(
      ':id' => $id, ':title' => $title,
      ':url' => $url, ':blurb' => $blurb,
      ':body' => $body, ':published' => $published,
      ':parse_math' => $parse_math
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

  /**
   * truncateHtml can truncate a string up to a number of characters while preserving whole words and HTML tags
   * from: http://alanwhipple.com/2011/05/25/php-truncate-string-preserving-html-tags-words/
   *
   * @param string $text String to truncate.
   * @param integer $length Length of returned string, including ellipsis.
   * @param string $ending Ending to be appended to the trimmed string.
   * @param boolean $exact If false, $text will not be cut mid-word
   * @param boolean $considerHtml If true, HTML tags would be handled correctly
   *
   * @return string Trimmed string.
   */
  private function truncateHtml($text, $length = 100, $ending = '...', $exact = false, $considerHtml = true) {
    if ($considerHtml) {
      // if the plain text is shorter than the maximum length, return the whole text
      if (strlen(preg_replace('/<.*?>/', '', $text)) <= $length) {
        return $text;
      }
      // splits all html-tags to scanable lines
      preg_match_all('/(<.+?>)?([^<>]*)/s', $text, $lines, PREG_SET_ORDER);
      $total_length = strlen($ending);
      $open_tags = array();
      $truncate = '';
      foreach ($lines as $line_matchings) {
        // if there is any html-tag in this line, handle it and add it (uncounted) to the output
        if (!empty($line_matchings[1])) {
          // if it's an "empty element" with or without xhtml-conform closing slash
          if (preg_match('/^<(\s*.+?\/\s*|\s*(img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param)(\s.+?)?)>$/is', $line_matchings[1])) {
            // do nothing
          // if tag is a closing tag
          } else if (preg_match('/^<\s*\/([^\s]+?)\s*>$/s', $line_matchings[1], $tag_matchings)) {
            // delete tag from $open_tags list
            $pos = array_search($tag_matchings[1], $open_tags);
            if ($pos !== false) {
            unset($open_tags[$pos]);
            }
          // if tag is an opening tag
          } else if (preg_match('/^<\s*([^\s>!]+).*?>$/s', $line_matchings[1], $tag_matchings)) {
            // add tag to the beginning of $open_tags list
            array_unshift($open_tags, strtolower($tag_matchings[1]));
          }
          // add html-tag to $truncate'd text
          $truncate .= $line_matchings[1];
        }
        // calculate the length of the plain text part of the line; handle entities as one character
        $content_length = strlen(preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|[0-9a-f]{1,6};/i', ' ', $line_matchings[2]));
        if ($total_length+$content_length> $length) {
          // the number of characters which are left
          $left = $length - $total_length;
          $entities_length = 0;
          // search for html entities
          if (preg_match_all('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|[0-9a-f]{1,6};/i', $line_matchings[2], $entities, PREG_OFFSET_CAPTURE)) {
            // calculate the real length of all entities in the legal range
            foreach ($entities[0] as $entity) {
              if ($entity[1]+1-$entities_length <= $left) {
                $left--;
                $entities_length += strlen($entity[0]);
              } else {
                // no more characters left
                break;
              }
            }
          }
          $truncate .= substr($line_matchings[2], 0, $left+$entities_length);
          // maximum lenght is reached, so get off the loop
          break;
        } else {
          $truncate .= $line_matchings[2];
          $total_length += $content_length;
        }
        // if the maximum length is reached, get off the loop
        if($total_length>= $length) {
          break;
        }
      }
    } else {
      if (strlen($text) <= $length) {
        return $text;
      } else {
        $truncate = substr($text, 0, $length - strlen($ending));
      }
    }
    // if the words shouldn't be cut in the middle...
    if (!$exact) {
      // ...search the last occurance of a space...
      $spacepos = strrpos($truncate, ' ');
      if (isset($spacepos)) {
        // ...and cut the text in this position
        $truncate = substr($truncate, 0, $spacepos);
      }
    }
    // add the defined ending to the text
    $truncate .= $ending;
    if($considerHtml) {
      // close all unclosed html-tags
      foreach ($open_tags as $tag) {
        $truncate .= '</' . $tag . '>';
      }
    }
    return $truncate;
  }

}
?>
