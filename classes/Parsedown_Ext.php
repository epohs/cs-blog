<?php


class Parsedown_Ext extends Parsedown {
  
  
  // Keep the original text() method unchanged
  public function text($text) {
    return parent::text($text);
  }
  
  // Create a new method for truncation
  public function text_with_word_limit($text, $word_limit) {
    $html = $this->text($text); // Call the original text() method
    return $this->truncate_by_words_and_close_tags($html, $word_limit);
  }
  
  private function truncate_by_words_and_close_tags($html, $word_limit) {
    $truncated = '';
    $stack = [];
    $word_count = 0;
    
    $dom = new DOMDocument();
    @$dom->loadHTML(
      mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'),
      LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
    );
    
    foreach ($dom->childNodes as $node) :
      $truncated .= $this->truncate_node_by_words($node, $word_limit, $word_count, $stack);
      if ($word_count >= $word_limit) :
        break;
      endif;
    endforeach;
    
    // Close remaining tags
    while (!empty($stack)) :
      $truncated .= "</" . array_pop($stack) . ">";
    endwhile;
    
    return $truncated;
  }
  
  private function truncate_node_by_words($node, $word_limit, &$word_count, &$stack) {
    $output = '';
    
    if ($node->nodeType == XML_TEXT_NODE) :
      $words = preg_split('/\s+/', $node->nodeValue, -1, PREG_SPLIT_NO_EMPTY);
      $num_words = count($words);
      if ($word_count + $num_words > $word_limit) :
        $words_to_include = array_slice($words, 0, $word_limit - $word_count);
        $output .= implode(' ', $words_to_include) . ' ';
        $word_count = $word_limit;
      else :
        $output .= htmlspecialchars($node->nodeValue) . ' ';
        $word_count += $num_words;
      endif;
      
    elseif ($node->nodeType == XML_ELEMENT_NODE) :
      $stack[] = $node->nodeName;
      $output .= "<" . $node->nodeName;
      
      // Add attributes if present
      if ($node->hasAttributes()) :
        foreach ($node->attributes as $attr) :
          $output .= " " . $attr->nodeName . '="' . htmlspecialchars($attr->nodeValue) . '"';
        endforeach;
      endif;
      
      $output .= ">";
      
      foreach ($node->childNodes as $child_node) :
        $output .= $this->truncate_node_by_words($child_node, $word_limit, $word_count, $stack);
        if ($word_count >= $word_limit) :
          break;
        endif;
      endforeach;
      
      if ($word_count < $word_limit) :
        $output .= "</" . array_pop($stack) . ">";
      endif;
        
    endif;
    
    return $output;
  }
  
  
} // ::Parsedown_Ext
    