<?php

class Parsedown_Ext extends Parsedown {
  
  
  
  private static $instance = null;
  
  
  
  // Keep the original text() method from the
  // Parsedown library unchanged.
  public function text($text) {
    
    return parent::text($text);
    
  } // text()
  
  
  
  
  
  
  // Create a new public method for truncation
  public function text_limit_words($text, $wordLimit){
  
    $html = $this->text($text); // Call our thin wrapper for Parsedown text()
    
    return $this->truncateByWordsAndCloseTags($html, $wordLimit);
    
  } // text_limit_words()
  
  
  
  
  
  
  private function truncateByWordsAndCloseTags($html, $wordLimit) {
    
    $truncated = '';
    $stack = [];
    $wordCount = 0;
  
    $dom = new DOMDocument();
    
    @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
  
    foreach ($dom->childNodes as $node):
      
      $truncated .= $this->truncateNodeByWords($node, $wordLimit, $wordCount, $stack);
      
      if ($wordCount >= $wordLimit):
        
        break;
        
      endif;
      
    endforeach;
  
    // Close remaining tags
    while (!empty($stack)):

      $truncated .= "</" . array_pop($stack) . ">";
      
    endwhile;
  
    return $truncated;
    
  } // truncateByWordsAndCloseTags()
  
  
  
  
  
  
  
  
  private function truncateNodeByWords($node, $wordLimit, &$wordCount, &$stack) {
    
    $output = '';
    
    if ($node->nodeType == XML_TEXT_NODE):
      
      $words = preg_split('/\s+/', $node->nodeValue, -1, PREG_SPLIT_NO_EMPTY);
      $numWords = count($words);
      
      if ($wordCount + $numWords > $wordLimit):
        
        $wordsToInclude = array_slice($words, 0, $wordLimit - $wordCount);
        $output .= implode(' ', $wordsToInclude) . ' ';
        $wordCount = $wordLimit;
      
      else:
        
        $output .= htmlspecialchars($node->nodeValue) . ' ';
        $wordCount += $numWords;
      
      endif;
      
    elseif ($node->nodeType == XML_ELEMENT_NODE):
      
      $stack[] = $node->nodeName;
      $output .= "<" . $node->nodeName;
    
      // Add attributes if present
      if ($node->hasAttributes()):
        
        foreach ($node->attributes as $attr):
          
          $output .= " " . $attr->nodeName . '="' . htmlspecialchars($attr->nodeValue) . '"';
        
        endforeach;
          
      endif;
    
      $output .= ">";
      
      foreach ($node->childNodes as $childNode):

        $output .= $this->truncateNodeByWords($childNode, $wordLimit, $wordCount, $stack);

        if ($wordCount >= $wordLimit):
          
          break;
          
        endif;
        
      endforeach;
    
      if ($wordCount < $wordLimit):
        
        $output .= "</" . array_pop($stack) . ">";
        
      endif;
      
    endif;
    
    return $output;
    
  } // truncateNodeByWords()

  
  


    




  public static function get_instance() {
  
    if (self::$instance === null):
      
      self::$instance = new Parsedown_Ext();

    endif;
  
    return self::$instance;
  
  } // get_instance()  
  
  
  
  
} // ::Parsedown_Ext
