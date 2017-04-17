<?php

namespace Neutron\Model;

class Article {
  /**
   * database var
   * @var PDO
   * @var Parsedown
   * @var string
   */
  private $db;
  private $parse;
  private $getArticleQuery;
  private $disqus_enabled;
  private $disqus_forum_name;

  /**
   * Pass the $db object from app object
   * @param $db
   */

  function __construct($db, $parse, $disqus_config) {
    $this->db = $db;
    $this->parse = $parse;
    // Common query to get article
    $this->getArticleQuery = "SELECT id, author, title, dt_display, tags, url, blurb, category, tags, dt_display, body, published, parse_math FROM pages ";
    $this->disqus_enabled = $disqus_config['enable'];
    $this->disqus_forum_name = $disqus_config['forum_name'];
  }

  /**
   * Return Disqus comments if enabled
   */
  public function getComments($article) {
    if ($this->disqus_enabled == "true" and $this->disqus_forum_name != "") {
      $canonical = "http://www.proximacent.com/article/" . $article->url;
      $id = $article->id;
      $forum_name = $this->disqus_forum_name;
      $comments = <<<EOS
<div id="disqus_thread"></div>
<script>

var disqus_config = function () {
  this.page.url = '$canonical';
  this.page.identifier = $id;
};

(function() { // DON'T EDIT BELOW THIS LINE
  var d = document, s = d.createElement('script');
  s.src = 'https://$forum_name.disqus.com/embed.js';
  s.setAttribute('data-timestamp', +new Date());
  (d.head || d.body).appendChild(s);
})();
</script>
<noscript>Please enable JavaScript to view the <a href="https://disqus.com/?ref_noscript">comments powered by Disqus.</a></noscript>
EOS;
      return $comments;
    } else {
      return "";
    }
  }

  /**
   * Get list of available categories
   */
  public function getAllCategories() {
    $sql = "SELECT category FROM categories";
    $query = $this->db->prepare($sql);
    $query->execute();
    return $query->fetchAll();
  }

  /**
   * Return list of article id and title in a category
   * If category is null, return titles with no category
   * If no category parameter, returns all titles
   */
  public function getArticleTitles($category = 'all', $order=null) {
    $sql  = "SELECT id, title from pages ";
    $sqlWhere = "";

    // If all, no 'WHERE' statement
    if ($category != 'all') {
      $sqlWhere = "WHERE category = :category ";
    }
    if ($category == 'null') {
      $sqlWhere = "WHERE category is :category";
    }
    $sql .= $sqlWhere;
    if ($order) {$sql .= " ORDER BY " . $order;}
    $query = $this->db->prepare($sql);

    // Set query parameters
    if ($category != 'all') {
      if ($category == 'null') {
        $query->bindValue(':category', NULL, \PDO::PARAM_INT);
      } else {
        $query->bindValue(':category', $category, \PDO::PARAM_STR);
      }
    }
    //print_r($query);
    $query->execute();
    return $query->fetchAll();
  }

  /**
   * Get single article by id
   */
  public function getArticleByUrl($url) {
    $sql = $this->getArticleQuery;
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
    $sql = $this->getArticleQuery;
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
    $sql = $this->getArticleQuery;
    //if ($only_published) {$sql .= " WHERE url like 'setup_dl_machine'";}
    if ($only_published) {$sql .= " WHERE published = TRUE";}
    if ($order) {$sql .= " ORDER BY " . $order;}
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
  public function addNewArticle($title, $url, $category, $dt_display, $tags,
      $blurb, $body, $published, $parse_math) {
    // Prepare query
    $sql  = "INSERT INTO pages (title, url, category, dt_display, tags, blurb, body, published, parse_math, dt_created) ";
    $sql .= "VALUES (:title, :url, :category, :dt_display, :tags, :blurb, :body, :published, :parse_math, NOW())";
    $query = $this->db->prepare($sql);

    // Set query parameters
    $query->bindValue(':title', $title, \PDO::PARAM_STR);
    $query->bindValue(':dt_display', $dt_display, \PDO::PARAM_STR);
    $query->bindValue(':tags', $tags, \PDO::PARAM_STR);
    $query->bindValue(':url', $url, \PDO::PARAM_STR);
    if ($category == 'null') {
      $query->bindValue(':category', null, \PDO::PARAM_INT);
    } else {
      $query->bindValue(':category', $category, \PDO::PARAM_STR);
    }
    $query->bindValue(':blurb', $blurb, \PDO::PARAM_STR);
    $query->bindValue(':body', $body, \PDO::PARAM_STR);
    $query->bindValue(':published', $published, \PDO::PARAM_INT);
    $query->bindValue(':parse_math', $parse_math, \PDO::PARAM_INT);

    // Execute the query
    try {
    $query->execute();
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
  public function updateArticle($id, $title, $category, $dt_display, $tags,
      $url, $blurb, $body,$published, $parse_math) {
    $sql  = "UPDATE pages SET ";
    $sql .= "title = :title, ";
    $sql .= "category = :category, ";
    $sql .= "dt_display = :dt_display, ";
    $sql .= "tags = :tags, ";
    $sql .= "url = :url, ";
    $sql .= "blurb = :blurb, ";
    $sql .= "body = :body, ";
    $sql .= "published = :published, ";
    $sql .= "parse_math = :parse_math ";
    $sql .= "WHERE id = :id ";
    $query = $this->db->prepare($sql);

    // Set query parameters
    $query->bindValue(':id', $id, \PDO::PARAM_INT);
    $query->bindValue(':title', $title, \PDO::PARAM_STR);
    $query->bindValue(':dt_display', $dt_display, \PDO::PARAM_STR);
    $query->bindValue(':tags', $tags, \PDO::PARAM_STR);
    $query->bindValue(':url', $url, \PDO::PARAM_STR);
    if ($category == 'null') {
      $query->bindValue(':category', null, \PDO::PARAM_INT);
    } else {
      $query->bindValue(':category', $category, \PDO::PARAM_STR);
    }
    $query->bindValue(':blurb', $blurb, \PDO::PARAM_STR);
    $query->bindValue(':body', $body, \PDO::PARAM_STR);
    $query->bindValue(':published', $published, \PDO::PARAM_INT);
    $query->bindValue(':parse_math', $parse_math, \PDO::PARAM_INT);

    // Execute the query
    try {
      $query->execute();
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
